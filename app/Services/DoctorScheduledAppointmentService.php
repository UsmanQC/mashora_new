<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DoctorScheduledAppointmentService
{
    public function __construct(
        private readonly DoctorAvailabilityService $availability,
        private readonly FollowUpAppointmentService $followUpService,
        private readonly PatientAppointmentNotifier $notifier,
    ) {}

    public static function paymentGraceMinutes(): int
    {
        return max(1, (int) config('appointments.doctor_scheduled_payment_grace_minutes', 60));
    }

    public function parentCanSchedulePaidAppointment(Appointment $parent): bool
    {
        if (! $parent->is_follow_up || (string) $parent->status !== 'completed') {
            return false;
        }

        if ($parent->user_id === null) {
            return false;
        }

        if ($this->existingPaidAppointmentFor($parent) !== null) {
            return false;
        }

        return $this->followUpService->maxSelectableDate($parent)->greaterThanOrEqualTo(
            $this->followUpService->windowStartFor($parent)
        );
    }

    public function existingPaidAppointmentFor(Appointment $parent): ?Appointment
    {
        return Appointment::query()
            ->where('parent_id', $parent->id)
            ->where('is_follow_up', false)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();
    }

    public function pendingPaidAppointmentFor(Appointment $parent): ?Appointment
    {
        $appointment = $this->existingPaidAppointmentFor($parent);

        return $appointment?->requiresPatientPayment() ? $appointment : null;
    }

    public function createPaid(Doctor $doctor, Appointment $parent, string $date, string $time): Appointment
    {
        if ((int) $parent->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if (! $this->parentCanSchedulePaidAppointment($parent)) {
            throw ValidationException::withMessages([
                'paidSelectedTime' => __('doctor.scheduled_appointment.not_eligible'),
            ]);
        }

        $this->followUpService->assertDateWithinWindow($parent, $date);

        $durationMinutes = max(15, (int) $parent->duration);
        $slots = $this->availability->availableSlots($doctor, $date, $durationMinutes);

        if (! in_array($time, $slots, true)) {
            throw ValidationException::withMessages([
                'paidSelectedTime' => __('doctor.scheduled_appointment.slot_unavailable'),
            ]);
        }

        $price = $this->resolveSessionPrice($doctor, $durationMinutes);

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'paidSelectedTime' => __('doctor.scheduled_appointment.price_missing'),
            ]);
        }

        $timezone = config('app.timezone');
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $timezone);
        $end = (clone $start)->addMinutes($durationMinutes);
        $expiresAt = now()->addMinutes(self::paymentGraceMinutes());

        $appointment = DB::transaction(function () use ($parent, $doctor, $start, $end, $durationMinutes, $price, $expiresAt): Appointment {
            $appointment = Appointment::create([
                'appointment_number' => PatientPaymentCompletionService::generateAppointmentNumber(),
                'doctor_id' => (int) $doctor->id,
                'user_id' => (int) $parent->user_id,
                'parent_id' => $parent->id,
                'is_follow_up' => false,
                'scheduled_at' => $start->format('Y-m-d H:i:s'),
                'appointment_date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration' => $durationMinutes,
                'extend_at' => $end->format('Y-m-d H:i:s'),
                'appointment_for' => $parent->appointment_for ?? 'self',
                'patient_name' => $parent->patient_name,
                'patient_email' => $parent->patient_email,
                'patient_phone' => $parent->patient_phone,
                'patient_notes' => $parent->patient_notes,
                'amount' => $price,
                'discount' => 0.0,
                'tax' => 0.0,
                'total' => $price,
                'wallet_amount' => 0,
                'appointment_type' => 'regular',
                'status' => 'pending_follow_up',
                'payment_expires_at' => $expiresAt,
            ]);

            $this->syncCommunicationsFromParent($appointment, $parent);

            return $appointment;
        });

        $this->notifier->notifyDoctorScheduledAppointmentPaymentRequired(
            $appointment->fresh(),
            $doctor,
            $start,
            $expiresAt,
        );

        return $appointment;
    }

    public function expireDuePayments(): int
    {
        $expired = Appointment::query()
            ->where('status', 'pending_follow_up')
            ->where('is_follow_up', false)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->whereNull('patient_confirmed_at')
            ->where('total', '>', 0)
            ->with('doctor')
            ->get();

        $count = 0;

        foreach ($expired as $appointment) {
            $appointment->forceFill([
                'status' => 'cancelled',
                'cancel_status' => 'patient_payment_missed',
            ])->save();

            $doctor = $appointment->doctor;

            if ($doctor instanceof Doctor) {
                $this->notifier->notifyDoctorScheduledPaymentMissed($appointment->fresh(), $doctor);
            }

            $count++;
        }

        return $count;
    }

    private function resolveSessionPrice(Doctor $doctor, int $durationMinutes): float
    {
        $durationRow = $doctor->durations()->where('durations.duration', $durationMinutes)->first();

        return round((float) ($durationRow?->pivot->price ?? 0), 2);
    }

    private function syncCommunicationsFromParent(Appointment $appointment, Appointment $parent): void
    {
        $channels = DB::table('appointment_communication')
            ->where('appointment_id', $parent->id)
            ->pluck('communication')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($channels === []) {
            $channels = ['chat'];
        }

        $allowed = ['chat', 'voice_call', 'video_call'];

        foreach ($channels as $channel) {
            if (! in_array($channel, $allowed, true)) {
                continue;
            }

            DB::table('appointment_communication')->insertOrIgnore([
                'appointment_id' => $appointment->id,
                'communication' => $channel,
            ]);
        }
    }
}
