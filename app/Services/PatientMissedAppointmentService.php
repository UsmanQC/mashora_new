<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientMissedAppointmentService
{
    /**
     * @var list<string>
     */
    public const REFUND_REASON_KEYS = [
        'duplicate_payment',
        'appointment_cancelled',
        'service_not_provided',
        'technical_issue',
        'doctor_unable_to_attend',
        'other',
    ];

    public function __construct(
        private readonly AppointmentWalletService $wallet,
        private readonly DoctorAvailabilityService $availability,
        private readonly PatientAppointmentNotifier $notifier,
        private readonly AppointmentRefundRequestNotifier $refundRequestNotifier,
    ) {}

    public function canResolve(Appointment $appointment): bool
    {
        return $appointment->isDoctorMissed()
            && ! $appointment->is_follow_up
            && ! $appointment->refundRequests()->exists()
            && ! $appointment->isPatientRefunded()
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

        if ($appointment->isPatientRefunded() || $this->wallet->hasRefunded($appointment)) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.missed.already_refunded'),
            ]);
        }

        if ($appointment->refundRequests()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => __('patient.missed.already_requested'),
            ]);
        }
    }

    public function requestRefund(
        User $user,
        Appointment $appointment,
        string $reasonKey,
        ?string $reasonNote = null,
    ): AppointmentRefundRequest {
        if ((int) $appointment->user_id !== (int) $user->id) {
            abort(403);
        }

        $this->assertCanResolve($user, $appointment);

        $reasonKey = trim($reasonKey);

        if (! in_array($reasonKey, self::REFUND_REASON_KEYS, true)) {
            throw ValidationException::withMessages([
                'refundReason' => __('patient.missed.invalid_reason'),
            ]);
        }

        $reasonNote = $reasonNote !== null ? trim($reasonNote) : null;

        if ($reasonKey === 'other' && blank($reasonNote)) {
            throw ValidationException::withMessages([
                'refundReasonNote' => __('patient.missed.reason_note_required'),
            ]);
        }

        $request = DB::transaction(function () use ($appointment, $user, $reasonKey, $reasonNote): AppointmentRefundRequest {
            $appointment->loadMissing('doctor');

            return AppointmentRefundRequest::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $user->id,
                'doctor_id' => $appointment->doctor_id,
                'reason_key' => $reasonKey,
                'reason_note' => $reasonNote,
                'status' => 'pending_review',
                'requested_amount' => (float) $appointment->total,
            ]);
        });

        $this->refundRequestNotifier->notifySubmitted($request);

        return $request;
    }

    public function refund(User $user, Appointment $appointment): void
    {
        if ($appointment->refundRequests()->exists()) {
            return;
        }

        $this->requestRefund($user, $appointment, 'service_not_provided');
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
