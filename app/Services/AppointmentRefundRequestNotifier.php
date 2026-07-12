<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;

final class AppointmentRefundRequestNotifier
{
    public function __construct(
        private readonly FcmPushService $push,
    ) {}

    public function notifySubmitted(AppointmentRefundRequest $request): void
    {
        $request->loadMissing(['appointment.user', 'doctor', 'patient']);
        $appointment = $request->appointment;

        if (! $appointment instanceof Appointment) {
            return;
        }

        $this->notifyDoctor($request, $appointment, [
            'type' => 'refund_request_submitted',
            'title' => __('doctor.notifications.refund_request_submitted_title'),
            'message' => __('doctor.notifications.refund_request_submitted_body', [
                'patient' => $this->patientName($appointment),
                'amount' => number_format((float) $request->requested_amount, 2),
            ]),
        ]);

        $this->notifyPatient($request, $appointment, [
            'type' => 'refund_request_submitted',
            'title' => __('patient.notifications.refund_request_submitted_title'),
            'message' => __('patient.notifications.refund_request_submitted_body', [
                'amount' => number_format((float) $request->requested_amount, 2),
            ]),
            'action' => route('patient.wallet'),
        ]);
    }

    public function notifyApproved(AppointmentRefundRequest $request): void
    {
        $request->loadMissing(['appointment.user', 'doctor', 'patient']);
        $appointment = $request->appointment;

        if (! $appointment instanceof Appointment) {
            return;
        }

        $this->notifyPatient($request, $appointment, [
            'type' => 'refund_request_approved',
            'title' => __('patient.notifications.refund_request_approved_title'),
            'message' => __('patient.notifications.refund_request_approved_body', [
                'amount' => number_format((float) $request->requested_amount, 2),
            ]),
            'action' => route('patient.wallet'),
        ]);

        $this->notifyDoctor($request, $appointment, [
            'type' => 'refund_request_approved',
            'title' => __('doctor.notifications.refund_request_approved_title'),
            'message' => __('doctor.notifications.refund_request_approved_body', [
                'patient' => $this->patientName($appointment),
                'amount' => number_format((float) $request->requested_amount, 2),
            ]),
        ]);
    }

    public function notifyRejected(AppointmentRefundRequest $request): void
    {
        $request->loadMissing(['appointment.user', 'doctor', 'patient']);
        $appointment = $request->appointment;

        if (! $appointment instanceof Appointment) {
            return;
        }

        $this->notifyPatient($request, $appointment, [
            'type' => 'refund_request_rejected',
            'title' => __('patient.notifications.refund_request_rejected_title'),
            'message' => __('patient.notifications.refund_request_rejected_body', [
                'amount' => number_format((float) $request->requested_amount, 2),
            ]),
            'action' => route('patient.wallet'),
        ]);

        $this->notifyDoctor($request, $appointment, [
            'type' => 'refund_request_rejected',
            'title' => __('doctor.notifications.refund_request_rejected_title'),
            'message' => __('doctor.notifications.refund_request_rejected_body', [
                'patient' => $this->patientName($appointment),
            ]),
        ]);
    }

    public function notifyProcessed(AppointmentRefundRequest $request): void
    {
        $request->loadMissing(['appointment.user', 'doctor', 'patient']);
        $appointment = $request->appointment;

        if (! $appointment instanceof Appointment) {
            return;
        }

        $amount = number_format((float) ($request->processed_amount ?? $request->requested_amount), 2);

        $this->notifyPatient($request, $appointment, [
            'type' => 'refund_request_processed',
            'title' => __('patient.notifications.refund_request_processed_title'),
            'message' => __('patient.notifications.refund_request_processed_body', [
                'amount' => $amount,
            ]),
            'action' => route('patient.wallet'),
        ]);

        $this->notifyDoctor($request, $appointment, [
            'type' => 'refund_request_processed',
            'title' => __('doctor.notifications.refund_request_processed_title'),
            'message' => __('doctor.notifications.refund_request_processed_body', [
                'patient' => $this->patientName($appointment),
                'amount' => $amount,
            ]),
        ]);
    }

    /**
     * @param  array{type: string, title: string, message: string, action?: string}  $payload
     */
    private function notifyPatient(AppointmentRefundRequest $request, Appointment $appointment, array $payload): void
    {
        $user = $appointment->user instanceof User
            ? $appointment->user
            : ($request->patient instanceof User ? $request->patient : null);

        if ($user === null) {
            return;
        }

        $doctor = $appointment->doctor instanceof Doctor
            ? $appointment->doctor
            : ($request->doctor instanceof Doctor ? $request->doctor : null);

        Notification::query()->create([
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'userable_type' => User::class,
            'userable_id' => $user->id,
            'senderable_type' => $doctor instanceof Doctor ? Doctor::class : null,
            'senderable_id' => $doctor?->id,
            'action' => $payload['action'] ?? route('patient.wallet'),
        ]);

        $this->push->sendToUser($user, $payload['title'], $payload['message'], [
            'type' => $payload['type'],
            'appointment_id' => (string) $appointment->id,
            'refund_request_id' => (string) $request->id,
        ]);
    }

    /**
     * @param  array{type: string, title: string, message: string}  $payload
     */
    private function notifyDoctor(AppointmentRefundRequest $request, Appointment $appointment, array $payload): void
    {
        $doctor = $appointment->doctor instanceof Doctor
            ? $appointment->doctor
            : ($request->doctor instanceof Doctor ? $request->doctor : null);

        if ($doctor === null) {
            return;
        }

        Notification::query()->create([
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'userable_type' => Doctor::class,
            'userable_id' => $doctor->id,
            'senderable_type' => User::class,
            'senderable_id' => $appointment->user_id,
            'action' => route('doctor.appointments', ['status' => 'cancelled']),
        ]);

        $this->push->sendToNotifiable($doctor, $payload['title'], $payload['message'], [
            'type' => $payload['type'],
            'appointment_id' => (string) $appointment->id,
            'refund_request_id' => (string) $request->id,
        ]);
    }

    private function patientName(Appointment $appointment): string
    {
        if (filled($appointment->patient_name)) {
            return (string) $appointment->patient_name;
        }

        return $appointment->user?->name ?? __('patient.appointments.title');
    }
}
