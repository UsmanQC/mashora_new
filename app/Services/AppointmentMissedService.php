<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class AppointmentMissedService
{
    /**
     * @var list<string>
     */
    public const DOCTOR_MISSED_STATUSES = ['new', 'rescheduled'];

    public function processDueMissedAppointments(): int
    {
        $graceMinutes = max(0, (int) config('appointments.doctor_missed_grace_minutes', 10));
        $now = now()->timezone(config('app.timezone'));

        $candidates = Appointment::query()
            ->whereIn('status', self::DOCTOR_MISSED_STATUSES)
            ->whereDate('appointment_date', '<=', $now->toDateString())
            ->with(['doctor', 'user'])
            ->get();

        $processed = 0;

        foreach ($candidates as $appointment) {
            if (! $this->shouldMarkDoctorMissed($appointment, $graceMinutes, $now)) {
                continue;
            }

            $this->markDoctorMissed($appointment);
            $processed++;
        }

        return $processed;
    }

    public function shouldMarkDoctorMissed(Appointment $appointment, ?int $graceMinutes = null, ?CarbonInterface $now = null): bool
    {
        if (! in_array((string) $appointment->status, self::DOCTOR_MISSED_STATUSES, true)) {
            return false;
        }

        if ($appointment->actual_start_at !== null) {
            return false;
        }

        $sessionStartsAt = $appointment->sessionStartsAt();

        if ($sessionStartsAt === null) {
            return false;
        }

        $graceMinutes ??= max(0, (int) config('appointments.doctor_missed_grace_minutes', 10));
        $now ??= now()->timezone(config('app.timezone'));

        return $sessionStartsAt->copy()->addMinutes($graceMinutes)->lessThanOrEqualTo($now);
    }

    public function markDoctorMissed(Appointment $appointment): void
    {
        if (! in_array((string) $appointment->status, self::DOCTOR_MISSED_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($appointment): void {
            $appointment->forceFill([
                'status' => 'not_attended',
                'cancel_status' => 'doctor_missed',
            ])->save();
        });

        $appointment->refresh()->loadMissing('doctor');

        $doctor = $appointment->doctor;

        if ($doctor instanceof Doctor) {
            app(PatientAppointmentNotifier::class)->notifyMissed($appointment, $doctor);
        }
    }
}
