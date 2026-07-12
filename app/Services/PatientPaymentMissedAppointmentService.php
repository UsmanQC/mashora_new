<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientPaymentMissedAppointmentService
{
    public function __construct(
        private readonly DoctorAvailabilityService $availability,
        private readonly PatientAppointmentNotifier $notifier,
    ) {}

    public static function rescheduleWindowHours(): int
    {
        return max(1, (int) config('appointments.payment_missed_reschedule_window_hours', 24));
    }

    public function isWithinRescheduleWindow(Appointment $appointment): bool
    {
        if ($appointment->created_at === null) {
            return false;
        }

        return $appointment->created_at->greaterThan(
            now()->subHours(self::rescheduleWindowHours()),
        );
    }

    public function canReschedule(Appointment $appointment): bool
    {
        return $appointment->isPatientPaymentMissed()
            && ! $appointment->is_follow_up
            && $appointment->parent_id !== null
            && $this->isWithinRescheduleWindow($appointment);
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function constrainEligible(Builder $query): Builder
    {
        return $query
            ->where('status', 'cancelled')
            ->where('cancel_status', 'patient_payment_missed')
            ->where('created_at', '>', now()->subHours(self::rescheduleWindowHours()));
    }

    public function assertCanReschedule(User $user, Appointment $appointment): void
    {
        if ((int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $this->canReschedule($appointment)) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.scheduled_appointment.not_eligible'),
            ]);
        }
    }

    public function reschedule(User $user, Appointment $appointment, string $date, string $time): Appointment
    {
        $this->assertCanReschedule($user, $appointment);

        $appointment->loadMissing('doctor');
        $doctor = $appointment->doctor;

        if (! $doctor instanceof Doctor) {
            throw ValidationException::withMessages([
                'selectedTime' => __('patient.missed.doctor_unavailable'),
            ]);
        }

        $durationMinutes = max(15, (int) $appointment->duration);
        $slots = $this->availability->availableSlots(
            $doctor,
            $date,
            $durationMinutes,
            $appointment->id,
        );

        if (! in_array($time, $slots, true)) {
            throw ValidationException::withMessages([
                'selectedTime' => __('patient.missed.slot_unavailable'),
            ]);
        }

        $timezone = AppTimezone::name();
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $timezone);
        $end = (clone $start)->addMinutes($durationMinutes);
        $expiresAt = now()->addMinutes(DoctorScheduledAppointmentService::paymentGraceMinutes());

        $updated = DB::transaction(function () use ($appointment, $start, $end, $durationMinutes, $expiresAt): Appointment {
            $appointment->update([
                'scheduled_at' => $start->format('Y-m-d H:i:s'),
                'appointment_date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration' => $durationMinutes,
                'extend_at' => $end->format('Y-m-d H:i:s'),
                'status' => 'pending_follow_up',
                'cancel_status' => null,
                'patient_confirmed_at' => null,
                'payment_expires_at' => $expiresAt,
            ]);

            return $appointment->fresh();
        });

        $this->notifier->notifyDoctorScheduledAppointmentPaymentRequired(
            $updated,
            $doctor,
            $start,
            $expiresAt,
        );

        return $updated;
    }
}
