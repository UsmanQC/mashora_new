<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

final class AppointmentChatNotifier
{
    public function __construct(
        private readonly FcmPushService $push,
    ) {}

    public function notifyRecipient(ChMessage $message): void
    {
        $message->loadMissing('appointment.doctor', 'appointment.user');

        $appointment = $message->appointment;

        if (! $appointment instanceof Appointment) {
            return;
        }

        $chatAllowed = match ((string) $message->send_by) {
            'doctor' => $appointment->isDoctorChatOpen(),
            'patient' => $appointment->isChatOpen(),
            default => $appointment->isChatOpen(),
        };

        if (! $chatAllowed) {
            return;
        }

        match ((string) $message->send_by) {
            'patient' => $this->notifyDoctor($appointment, $message),
            'doctor' => $this->notifyPatient($appointment, $message),
            default => null,
        };
    }

    private function notifyDoctor(Appointment $appointment, ChMessage $message): void
    {
        $doctor = $appointment->doctor;

        if (! $doctor instanceof Doctor) {
            return;
        }

        $patientName = filled($appointment->patient_name)
            ? (string) $appointment->patient_name
            : ($appointment->user?->name ?? __('patient.appointments.title'));

        $preview = $this->messagePreview($message);
        $title = __('doctor.notifications.chat_message_title', ['patient' => $patientName]);
        $body = __('doctor.notifications.chat_message_body', ['preview' => $preview]);

        Notification::query()->create([
            'type' => 'appointment_chat_message',
            'title' => $title,
            'message' => $body,
            'userable_type' => Doctor::class,
            'userable_id' => $doctor->id,
            'senderable_type' => User::class,
            'senderable_id' => $appointment->user_id,
            'action' => route('doctor.appointments.conversation', $appointment),
        ]);

        $this->push->sendToNotifiable($doctor, $title, $body, [
            'type' => 'appointment_chat_message',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    private function notifyPatient(Appointment $appointment, ChMessage $message): void
    {
        $user = $appointment->user;

        if (! $user instanceof User) {
            return;
        }

        $doctor = $appointment->doctor;

        if (! $doctor instanceof Doctor) {
            return;
        }

        $preview = $this->messagePreview($message);
        $title = __('patient.notifications.chat_message_title', ['doctor' => $doctor->displayName()]);
        $body = __('patient.notifications.chat_message_body', ['preview' => $preview]);

        Notification::query()->create([
            'type' => 'appointment_chat_message',
            'title' => $title,
            'message' => $body,
            'userable_type' => User::class,
            'userable_id' => $user->id,
            'senderable_type' => Doctor::class,
            'senderable_id' => $doctor->id,
            'action' => route('patient.appointments.conversation', $appointment),
        ]);

        $this->push->sendToUser($user, $title, $body, [
            'type' => 'appointment_chat_message',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    private function messagePreview(ChMessage $message): string
    {
        $body = trim(strip_tags((string) $message->body));

        if ($body === '') {
            return __('patient.notifications.chat_message_empty');
        }

        return Str::limit($body, 120);
    }
}
