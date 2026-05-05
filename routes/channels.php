<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('appointment.{appointmentId}', function ($user, int $appointmentId) {
    $appointment = Appointment::query()->find($appointmentId);

    if ($appointment === null || $user === null) {
        return false;
    }

    if ($user instanceof Doctor) {
        return (int) $appointment->doctor_id === (int) $user->id;
    }

    if ($user instanceof User) {
        return (int) $appointment->user_id === (int) $user->id;
    }

    return false;
});
