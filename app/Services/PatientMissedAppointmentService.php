<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientMissedAppointmentService
{
    public function __construct(
        private readonly AppointmentWalletService $wallet,
        private readonly DoctorAvailabilityService $availability,
        private readonly PatientAppointmentNotifier $notifier,
    ) {}

    public function canResolve(Appointment $appointment): bool
    {
        return $appointment->isDoctorMissed()
            && ! $appointment->is_follow_up
            && ! $this->wallet->hasRefunded($appointment);
    }

    public function assertCanResolve(User $user, Appointment $appointment): void
    {
        if ((int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($appointment->is_follow_up) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.missed.follow_up_not_eligible'),
            ]);
        }

        if (! $appointment->isDoctorMissed()) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.missed.not_eligible'),
            ]);
        }

        if ($this->wallet->hasRefunded($appointment)) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.missed.already_refunded'),
            ]);
        }
    }

    public function refund(User $user, Appointment $appointment): void
    {
        if ((int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($this->wallet->hasRefunded($appointment)) {
            return;
        }

        $this->assertCanResolve($user, $appointment);

        DB::transaction(function () use ($appointment): void {
            $this->wallet->refundToPatient($appointment->fresh());
        });
    }

    public function reschedule(User $user, Appointment $appointment, string $date, string $time): Appointment
    {
        $this->assertCanResolve($user, $appointment);

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

        $updated = DB::transaction(function () use ($appointment, $start, $end, $durationMinutes): Appointment {
            $appointment->update([
                'scheduled_at' => $start->format('Y-m-d H:i:s'),
                'appointment_date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration' => $durationMinutes,
                'extend_at' => $end->format('Y-m-d H:i:s'),
                'status' => 'rescheduled',
                'cancel_status' => null,
            ]);

            return $appointment->fresh();
        });

        $this->notifier->notifyRescheduled($updated, $doctor, $start);

        return $updated;
    }
}
