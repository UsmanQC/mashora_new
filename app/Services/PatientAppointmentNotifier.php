<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PatientAppointmentNotifier
{
    public function __construct(
        private readonly FcmPushService $push,
    ) {}

    public function notifyRescheduled(Appointment $appointment, Doctor $doctor, Carbon $newStart): void
    {
        $user = $this->patientUser($appointment);
        if ($user === null) {
            return;
        }

        $locale = app()->getLocale();
        $title = __('patient.notifications.rescheduled_title');
        $message = __('patient.notifications.rescheduled_message', [
            'doctor' => $doctor->displayName(),
            'date' => $newStart->locale($locale)->translatedFormat('d M Y'),
            'time' => $newStart->locale($locale)->translatedFormat('g:i a'),
        ]);

        Notification::query()->create([
            'type' => 'appointment_rescheduled',
            'title' => $title,
            'message' => $message,
            'userable_type' => User::class,
            'userable_id' => $user->id,
            'senderable_type' => Doctor::class,
            'senderable_id' => $doctor->id,
            'action' => route('patient.appointments', ['tab' => 'rescheduled']),
        ]);

        $this->push->sendToUser($user, $title, $message, [
            'type' => 'appointment_rescheduled',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    public function notifyCancelled(Appointment $appointment, Doctor $doctor): void
    {
        $user = $this->patientUser($appointment);
        if ($user === null) {
            return;
        }

        $startsAt = $this->appointmentStartsAt($appointment);
        $locale = app()->getLocale();
        $title = __('patient.notifications.cancelled_title');
        $message = __('patient.notifications.cancelled_message', [
            'doctor' => $doctor->displayName(),
            'date' => $startsAt?->locale($locale)->translatedFormat('d M Y') ?? '--',
            'time' => $startsAt?->locale($locale)->translatedFormat('g:i a') ?? '--',
        ]);

        Notification::query()->create([
            'type' => 'appointment_cancelled',
            'title' => $title,
            'message' => $message,
            'userable_type' => User::class,
            'userable_id' => $user->id,
            'senderable_type' => Doctor::class,
            'senderable_id' => $doctor->id,
            'action' => route('patient.appointments', ['tab' => 'cancelled']),
        ]);

        $this->push->sendToUser($user, $title, $message, [
            'type' => 'appointment_cancelled',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    public function notifyStartReminder(Appointment $appointment): void
    {
        $user = $this->patientUser($appointment);
        if ($user === null) {
            return;
        }

        $startsAt = $this->appointmentStartsAt($appointment);
        if ($startsAt === null) {
            return;
        }

        $locale = app()->getLocale();
        $doctorName = $appointment->doctor?->displayName() ?? __('patient.appointments.title');
        $title = __('patient.notifications.start_reminder_title');
        $message = __('patient.notifications.start_reminder_message', [
            'doctor' => $doctorName,
            'time' => $startsAt->locale($locale)->translatedFormat('g:i a'),
            'date' => $startsAt->locale($locale)->translatedFormat('d M Y'),
        ]);

        Notification::query()->create([
            'type' => 'appointment_start_reminder',
            'title' => $title,
            'message' => $message,
            'userable_type' => User::class,
            'userable_id' => $user->id,
            'senderable_type' => Doctor::class,
            'senderable_id' => $appointment->doctor_id,
            'action' => route('patient.appointments'),
        ]);

        $this->push->sendToUser($user, $title, $message, [
            'type' => 'appointment_start_reminder',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    private function patientUser(Appointment $appointment): ?User
    {
        if ($appointment->user_id === null) {
            return null;
        }

        return User::query()->find($appointment->user_id);
    }

    public function appointmentStartsAt(Appointment $appointment): ?CarbonInterface
    {
        if ($appointment->scheduled_at !== null) {
            return Carbon::parse($appointment->scheduled_at)->timezone(config('app.timezone'));
        }

        if ($appointment->appointment_date === null || ! filled($appointment->start_time)) {
            return null;
        }

        try {
            $date = $appointment->appointment_date instanceof Carbon
                ? $appointment->appointment_date->format('Y-m-d')
                : (string) $appointment->appointment_date;

            return Carbon::parse($date.' '.$appointment->start_time, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
