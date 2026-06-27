<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates one monthly platform invoice per doctor for completed, uninvoiced sessions.
 */
final class DoctorMonthlyInvoiceService
{
    /**
     * @return array{created: int, skipped: int, doctors: int}
     */
    public function generateForIssueDate(?CarbonInterface $issueDate = null): array
    {
        $issueDate = Carbon::parse($issueDate ?? now(config('app.timezone')))->startOfDay();

        // TEMP — daily test mode: bill completed sessions from the previous calendar day.
        // Run: php artisan invoices:generate-monthly
        // Or:  php artisan invoices:generate-monthly --date=2026-06-22  (bills 2026-06-21)
        $periodStart = $issueDate->copy()->subDay()->startOfDay();
        $periodEnd = $issueDate->copy()->subDay()->endOfDay();

        // Monthly production logic (restore when finished testing):
        // $periodStart = $issueDate->copy()->subMonth()->startOfMonth();
        // $periodEnd = $issueDate->copy()->subMonth()->endOfMonth();

        $doctorIds = $this->billableDoctorIds($periodStart, $periodEnd);

        $created = 0;
        $skipped = 0;

        foreach ($doctorIds as $doctorId) {
            $result = $this->generateForDoctor(
                (int) $doctorId,
                $periodStart,
                $periodEnd,
                $issueDate,
            );

            if ($result === null) {
                $skipped++;

                continue;
            }

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'doctors' => $doctorIds->count(),
        ];
    }

    /**
     * @return Collection<int, int|string>
     */
    private function billableDoctorIds(CarbonInterface $periodStart, CarbonInterface $periodEnd): Collection
    {
        return Appointment::query()
            ->where('status', 'completed')
            ->whereNull('invoice_id')
            ->whereDate('appointment_date', '>=', $periodStart->toDateString())
            ->whereDate('appointment_date', '<=', $periodEnd->toDateString())
            ->distinct()
            ->orderBy('doctor_id')
            ->pluck('doctor_id');
    }

    private function generateForDoctor(
        int $doctorId,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        CarbonInterface $issueDate,
    ): ?Invoice {
        if (! Doctor::query()->whereKey($doctorId)->exists()) {
            return null;
        }

        $existing = Invoice::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('from_date', $periodStart->toDateString())
            ->whereDate('to_date', $periodEnd->toDateString())
            ->first();

        if ($existing !== null) {
            return null;
        }

        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereNull('invoice_id')
            ->whereDate('appointment_date', '>=', $periodStart->toDateString())
            ->whereDate('appointment_date', '<=', $periodEnd->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        if ($appointments->isEmpty()) {
            return null;
        }

        $totals = $this->summarizeAppointments($appointments);

        if ($totals['total_amount'] <= 0 && $totals['doctor_share'] <= 0) {
            return null;
        }

        return DB::transaction(function () use (
            $doctorId,
            $periodStart,
            $periodEnd,
            $issueDate,
            $appointments,
            $totals,
        ): Invoice {
            $invoice = Invoice::query()->create([
                'reference' => $this->referenceFor($doctorId, $periodStart),
                'doctor_id' => $doctorId,
                'issue_date' => $issueDate->toDateString(),
                'from_date' => $periodStart->toDateString(),
                'to_date' => $periodEnd->toDateString(),
                'total_amount' => $totals['total_amount'],
                'doctor_share' => $totals['doctor_share'],
                'mashora_share' => $totals['mashora_share'],
                'payment_status' => 'unpaid',
                'paid_at' => null,
            ]);

            Appointment::query()
                ->whereIn('id', $appointments->pluck('id'))
                ->update(['invoice_id' => $invoice->id]);

            return $invoice->fresh(['doctor']);
        });
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return array{total_amount: float, doctor_share: float, mashora_share: float}
     */
    private function summarizeAppointments(Collection $appointments): array
    {
        $totalAmount = 0.0;
        $doctorShare = 0.0;
        $mashoraShare = 0.0;

        foreach ($appointments as $appointment) {
            $totalAmount += (float) $appointment->total;
            $doctorShare += (float) $appointment->doctor_share;
            $mashoraShare += (float) $appointment->mashora_share;
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'doctor_share' => round($doctorShare, 2),
            'mashora_share' => round($mashoraShare, 2),
        ];
    }

    private function referenceFor(int $doctorId, CarbonInterface $periodStart): string
    {
        // TEMP — daily reference while testing (restore Y/m when switching back to monthly).
        return sprintf(
            'MSH-%d-%s',
            $doctorId,
            $periodStart->format('Y/m/d'),
        );

        // Monthly production reference:
        // return sprintf('MSH-%d-%s', $doctorId, $periodStart->format('Y/m'));
    }
}
