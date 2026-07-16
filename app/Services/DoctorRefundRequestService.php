<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DoctorRefundRequestService
{
    public const REASON_KEY = 'other';

    public function __construct(
        private readonly AppointmentWalletService $wallet,
        private readonly AppointmentRefundRequestNotifier $refundRequestNotifier,
    ) {}

    public function canRequestRefund(Appointment $appointment): bool
    {
        return (string) $appointment->status === 'in_process'
            && (float) $appointment->total > 0
            && ! $appointment->hasOpenRefundRequest()
            && ! $appointment->isPatientRefunded()
            && ! $this->wallet->hasRefunded($appointment);
    }

    public function assertCanRequestRefund(Doctor $doctor, Appointment $appointment): void
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if ((string) $appointment->status !== 'in_process') {
            throw ValidationException::withMessages([
                'appointment' => __('doctor.refund.not_eligible'),
            ]);
        }

        if ((float) $appointment->total <= 0) {
            throw ValidationException::withMessages([
                'appointment' => __('doctor.refund.not_paid'),
            ]);
        }

        if ($appointment->isPatientRefunded() || $this->wallet->hasRefunded($appointment)) {
            throw ValidationException::withMessages([
                'appointment' => __('doctor.refund.already_refunded'),
            ]);
        }

        if ($appointment->hasOpenRefundRequest()) {
            throw ValidationException::withMessages([
                'appointment' => __('doctor.refund.already_requested'),
            ]);
        }
    }

    public function requestRefund(
        Doctor $doctor,
        Appointment $appointment,
        string $reasonNote,
    ): AppointmentRefundRequest {
        $this->assertCanRequestRefund($doctor, $appointment);

        $reasonNote = trim($reasonNote);

        if (blank($reasonNote)) {
            throw ValidationException::withMessages([
                'refundReasonNote' => __('doctor.refund.reason_required'),
            ]);
        }

        if (mb_strlen($reasonNote) > 2000) {
            throw ValidationException::withMessages([
                'refundReasonNote' => __('doctor.refund.reason_too_long'),
            ]);
        }

        $processing = app(AppointmentRefundProcessingService::class);
        $requestedAmount = $processing->maximumRefundableAmount(
            $appointment,
            AppointmentRefundRequest::REFUND_DESTINATION_WALLET,
        );

        if ($requestedAmount < 0.01) {
            throw ValidationException::withMessages([
                'appointment' => __('doctor.refund.not_paid'),
            ]);
        }

        $request = DB::transaction(function () use ($appointment, $doctor, $reasonNote, $requestedAmount): AppointmentRefundRequest {
            $appointment->loadMissing('user');

            return AppointmentRefundRequest::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->user_id,
                'doctor_id' => $doctor->id,
                'requested_by' => 'doctor',
                'reason_key' => self::REASON_KEY,
                'reason_note' => $reasonNote,
                'status' => 'pending_review',
                'refund_destination' => AppointmentRefundRequest::REFUND_DESTINATION_WALLET,
                'requested_amount' => $requestedAmount,
            ]);
        });

        try {
            $this->refundRequestNotifier->notifySubmitted($request);
        } catch (\Throwable $e) {
            report($e);
        }

        return $request;
    }
}
