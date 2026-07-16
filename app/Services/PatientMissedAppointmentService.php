<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

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
    ) {}

    public function canResolve(Appointment $appointment): bool
    {
        return $appointment->isDoctorMissed()
            && ! $appointment->is_follow_up
            && ! $appointment->hasOpenRefundRequest()
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

        if ($appointment->hasOpenRefundRequest()) {
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
        string $refundDestination = AppointmentRefundRequest::REFUND_DESTINATION_WALLET,
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

        $refundDestination = in_array($refundDestination, [
            AppointmentRefundRequest::REFUND_DESTINATION_WALLET,
            AppointmentRefundRequest::REFUND_DESTINATION_PAYMENT_ACCOUNT,
        ], true)
            ? $refundDestination
            : AppointmentRefundRequest::REFUND_DESTINATION_WALLET;

        if (
            $refundDestination === AppointmentRefundRequest::REFUND_DESTINATION_PAYMENT_ACCOUNT
            && ! $appointment->hasPaymentAccountRefundSource()
        ) {
            throw ValidationException::withMessages([
                'refundDestination' => __('patient.missed.refund_account_missing'),
            ]);
        }

        $processing = app(AppointmentRefundProcessingService::class);
        $requestedAmount = $processing->maximumRefundableAmount($appointment, $refundDestination);

        if ($requestedAmount < 0.01) {
            throw ValidationException::withMessages([
                'appointment' => $refundDestination === AppointmentRefundRequest::REFUND_DESTINATION_PAYMENT_ACCOUNT
                    ? __('patient.missed.refund_account_amount_unavailable')
                    : __('patient.missed.not_eligible'),
            ]);
        }

        $request = DB::transaction(function () use ($appointment, $user, $reasonKey, $reasonNote, $refundDestination, $requestedAmount): AppointmentRefundRequest {
            $appointment->loadMissing('doctor');

            $payload = [
                'appointment_id' => $appointment->id,
                'patient_id' => $user->id,
                'doctor_id' => $appointment->doctor_id,
                'requested_by' => 'patient',
                'reason_key' => $reasonKey,
                'reason_note' => $reasonNote,
                'status' => 'pending_review',
                'requested_amount' => $requestedAmount,
            ];

            if (Schema::hasColumn('appointment_refund_requests', 'refund_destination')) {
                $payload['refund_destination'] = $refundDestination;
            }

            return AppointmentRefundRequest::query()->create($payload);
        });

        $this->queueSubmittedNotification((int) $request->id);

        return $request;
    }

    private function queueSubmittedNotification(int $requestId): void
    {
        $callback = function () use ($requestId): void {
            try {
                $fresh = AppointmentRefundRequest::query()->find($requestId);

                if ($fresh instanceof AppointmentRefundRequest) {
                    app(AppointmentRefundRequestNotifier::class)->notifySubmitted($fresh);
                }
            } catch (Throwable $e) {
                report($e);
            }
        };

        if (app()->runningUnitTests()) {
            $callback();

            return;
        }

        dispatch($callback)->afterResponse();
    }

    public function refund(User $user, Appointment $appointment): void
    {
        if ($appointment->hasOpenRefundRequest()) {
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
