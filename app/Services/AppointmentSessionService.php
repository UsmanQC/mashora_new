<?php

namespace App\Services;

use App\Events\AppointmentSessionStarted;
use App\Models\Appointment;
use App\Models\Doctor;

final class AppointmentSessionService
{
    /**
     * @var list<string>
     */
    public const STARTABLE_STATUSES = ['new', 'rescheduled'];

    public function canDoctorStart(Appointment $appointment): bool
    {
        if (! in_array((string) $appointment->status, self::STARTABLE_STATUSES, true)) {
            return false;
        }

        return $appointment->isSessionStartDue();
    }

    public function canPatientJoin(Appointment $appointment): bool
    {
        if ($appointment->is_follow_up) {
            return false;
        }

        return (string) $appointment->status === 'in_process';
    }

    public function start(Doctor $doctor, Appointment $appointment): bool
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if (! $this->canDoctorStart($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => 'in_process',
            'actual_start_at' => now(),
            'extend_at' => now()->addMinutes(max(1, (int) $appointment->duration)),
        ]);

        $appointment->refresh();

        $this->broadcastStarted($appointment);

        return true;
    }

    public function ensureStartedForDoctor(Doctor $doctor, Appointment $appointment): Appointment
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if ((string) $appointment->status === 'in_process') {
            return $appointment;
        }

        if ($this->start($doctor, $appointment)) {
            return $appointment->fresh();
        }

        return $appointment;
    }

    public function broadcastStarted(Appointment $appointment): void
    {
        broadcast(new AppointmentSessionStarted(
            (int) $appointment->id,
            (string) $appointment->status,
            $appointment->actual_start_at?->toIso8601String(),
            $appointment->extend_at?->toIso8601String(),
        ));
    }
}
