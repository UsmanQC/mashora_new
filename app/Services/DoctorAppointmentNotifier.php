<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;

final class DoctorAppointmentNotifier
{
    public function __construct(
        private readonly FcmPushService $push,
        private readonly PatientAppointmentNotifier $patientAppointmentNotifier,
    ) {}

    public function notifyNewBooking(Appointment $appointment): void
    {
        $appointment->loadMissing('doctor', 'user');

        $doctor = $appointment->doctor;
        if (! $doctor instanceof Doctor) {
            return;
        }

        $patientName = filled($appointment->patient_name)
            ? (string) $appointment->patient_name
            : ($appointment->user?->name ?? __('patient.appointments.title'));

        $startsAt = $this->patientAppointmentNotifier->appointmentStartsAt($appointment);
        $locale = app()->getLocale();

        $title = __('doctor.notifications.appointment_booked_title');
        $message = __('doctor.notifications.appointment_booked_body', [
            'patient' => $patientName,
            'date' => $startsAt?->locale($locale)->translatedFormat('d M Y') ?? '--',
            'time' => $startsAt?->locale($locale)->translatedFormat('g:i a') ?? '--',
        ]);

        Notification::query()->create([
            'type' => 'appointment_booked',
            'title' => $title,
            'message' => $message,
            'userable_type' => Doctor::class,
            'userable_id' => $doctor->id,
            'senderable_type' => User::class,
            'senderable_id' => $appointment->user_id,
            'action' => route('doctor.appointments', ['status' => 'new']),
        ]);

        $this->push->sendToNotifiable($doctor, $title, $message, [
            'type' => 'appointment_booked',
            'appointment_id' => (string) $appointment->id,
        ]);
    }

    public function notifySessionStartApproved(Appointment $appointment, Doctor $doctor): void
    {
        $appointment->loadMissing('user');

        $patientName = filled($appointment->patient_name)
            ? (string) $appointment->patient_name
            : ($appointment->user?->name ?? __('patient.appointments.title'));

        $title = __('doctor.notifications.session_start_approved_title');
        $message = __('doctor.notifications.session_start_approved_body', [
            'patient' => $patientName,
        ]);

        Notification::query()->create([
            'type' => 'session_start_approved',
            'title' => $title,
            'message' => $message,
            'userable_type' => Doctor::class,
            'userable_id' => $doctor->id,
            'senderable_type' => User::class,
            'senderable_id' => $appointment->user_id,
            'action' => route('doctor.appointments.conversation', $appointment),
        ]);

        $this->push->sendToNotifiable($doctor, $title, $message, [
            'type' => 'session_start_approved',
            'appointment_id' => (string) $appointment->id,
        ]);
    }
}
