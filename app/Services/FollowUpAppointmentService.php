<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FollowUpAppointmentService
{
    /**
     * @var list<string>
     */
    public const SCHEDULABLE_PARENT_STATUSES = ['completed'];

    public function __construct(
        private readonly DoctorAvailabilityService $availability,
        private readonly PatientAppointmentNotifier $notifier,
    ) {}

    public function create(Doctor $doctor, Appointment $parent, string $date, string $time): Appointment
    {
        if ((int) $parent->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if (! in_array((string) $parent->status, self::SCHEDULABLE_PARENT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.follow_up.parent_not_eligible'),
            ]);
        }

        if ($parent->user_id === null) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.follow_up.patient_missing'),
            ]);
        }

        $durationMinutes = max(15, (int) $parent->duration);
        $slots = $this->availability->availableSlots($doctor, $date, $durationMinutes);

        if (! in_array($time, $slots, true)) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.follow_up.slot_unavailable'),
            ]);
        }

        $existingPending = Appointment::query()
            ->where('parent_id', $parent->id)
            ->where('status', 'pending_follow_up')
            ->exists();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.follow_up.already_pending'),
            ]);
        }

        $timezone = config('app.timezone');
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $timezone);
        $end = (clone $start)->addMinutes($durationMinutes);
        $price = $this->sessionPrice($doctor, $durationMinutes);

        $followUp = DB::transaction(function () use ($parent, $doctor, $start, $end, $durationMinutes, $price): Appointment {
            $appointment = Appointment::create([
                'appointment_number' => PatientPaymentCompletionService::generateAppointmentNumber(),
                'doctor_id' => (int) $doctor->id,
                'user_id' => (int) $parent->user_id,
                'parent_id' => $parent->id,
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
            ]);

            $this->syncCommunicationsFromParent($appointment, $parent);

            return $appointment;
        });

        $this->notifier->notifyFollowUpScheduled($followUp, $doctor, $start);

        return $followUp;
    }

    public function pendingFollowUpFor(Appointment $parent): ?Appointment
    {
        return Appointment::query()
            ->where('parent_id', $parent->id)
            ->where('status', 'pending_follow_up')
            ->latest('id')
            ->first();
    }

    public function confirm(Appointment $appointment, User $user): Appointment
    {
        if ((int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $appointment->isPendingFollowUp()) {
            abort(404);
        }

        if ($appointment->patient_confirmed_at !== null) {
            return $appointment;
        }

        $appointment->update(['patient_confirmed_at' => now()]);

        return $appointment->fresh();
    }

    public function parentCanScheduleFollowUp(Appointment $parent): bool
    {
        return in_array((string) $parent->status, self::SCHEDULABLE_PARENT_STATUSES, true)
            && $parent->parent_id === null;
    }

    private function sessionPrice(Doctor $doctor, int $durationMinutes): float
    {
        $durationRow = $doctor->durations()->where('durations.duration', $durationMinutes)->first();

        return round((float) ($durationRow?->pivot?->price ?? 0), 2);
    }

    private function syncCommunicationsFromParent(Appointment $followUp, Appointment $parent): void
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
                'appointment_id' => $followUp->id,
                'communication' => $channel,
            ]);
        }
    }
}
