<?php

namespace App\Services;

use App\Events\AppointmentSessionStartApproved;
use App\Events\AppointmentSessionStarted;
use App\Events\PatientAppointmentSessionStarted;
use App\Events\PatientSessionStartRequested;
use App\Models\Appointment;
use App\Models\Doctor;

final class AppointmentSessionService
{
    public function __construct(
        private readonly PatientAppointmentNotifier $patientNotifier,
        private readonly DoctorAppointmentNotifier $doctorNotifier,
    ) {}

    /**
     * @var list<string>
     */
    public const STARTABLE_STATUSES = ['new', 'rescheduled'];

    public function canDoctorStart(Appointment $appointment): bool
    {
        if (! in_array((string) $appointment->status, self::STARTABLE_STATUSES, true)) {
            return false;
        }

        if ($appointment->isSessionStartRequestPending()) {
            return false;
        }

        if ($appointment->isSessionStartApproved()) {
            return true;
        }

        if ((bool) config('appointments.relaxed_session_limits', false)) {
            return true;
        }

        return false;
    }

    public function canDoctorStartWithoutPatientApproval(Appointment $appointment): bool
    {
        return (bool) config('appointments.relaxed_session_limits', false);
    }

    public function canPatientJoin(Appointment $appointment): bool
    {
        if ($appointment->is_follow_up && ! (bool) config('appointments.follow_up_allows_calls', false)) {
            return false;
        }

        return (string) $appointment->status === 'in_process';
    }

    public function canDoctorOfferSessionStart(Appointment $appointment): bool
    {
        if (! in_array((string) $appointment->status, self::STARTABLE_STATUSES, true)) {
            return false;
        }

        if ((bool) config('appointments.relaxed_session_limits', false)) {
            return true;
        }

        return $appointment->isSessionStartDue();
    }

    public function requestStart(Doctor $doctor, Appointment $appointment): bool
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if (! in_array((string) $appointment->status, self::STARTABLE_STATUSES, true)) {
            return false;
        }

        if ($appointment->isSessionStartRequestPending() || $appointment->isSessionStartApproved()) {
            return false;
        }

        if (! $appointment->isSessionStartDue() && ! (bool) config('appointments.relaxed_session_limits', false)) {
            return false;
        }

        $appointment->update(['session_start_requested_at' => now()]);

        $this->patientNotifier->notifySessionStartRequest($appointment, $doctor);
        $this->broadcastSessionStartRequested($appointment);

        return true;
    }

    public function broadcastSessionStartRequested(Appointment $appointment): void
    {
        if ($appointment->user_id === null) {
            return;
        }

        broadcast(new PatientSessionStartRequested(
            (int) $appointment->user_id,
            (int) $appointment->id,
        ));
    }

    public function approveStart(Appointment $appointment): bool
    {
        if (! $appointment->isSessionStartRequestPending()) {
            return false;
        }

        $appointment->update(['session_start_approved_at' => now()]);

        broadcast(new AppointmentSessionStartApproved((int) $appointment->id));

        $appointment->loadMissing('doctor');
        $doctor = $appointment->doctor;

        if ($doctor instanceof Doctor) {
            $this->doctorNotifier->notifySessionStartApproved($appointment, $doctor);
        }

        return true;
    }

    public function clearStartRequest(Appointment $appointment): bool
    {
        if (! $appointment->hasSessionStartRequest()) {
            return false;
        }

        $appointment->update([
            'session_start_requested_at' => null,
            'session_start_approved_at' => null,
        ]);

        return true;
    }

    public function start(Doctor $doctor, Appointment $appointment): bool
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if ($appointment->isSessionStartRequestPending()) {
            return false;
        }

        if (! $appointment->isSessionStartApproved() && ! $this->canDoctorStartWithoutPatientApproval($appointment)) {
            return $this->requestStart($doctor, $appointment);
        }

        if (! $this->canDoctorStart($appointment)) {
            return false;
        }

        $appointment->update([
            'status' => 'in_process',
            'actual_start_at' => now(),
            'extend_at' => now()->addMinutes($appointment->effectiveSessionDurationMinutes()),
        ]);

        $appointment->refresh();

        $this->broadcastStarted($appointment);
        $this->patientNotifier->notifySessionStarted($appointment, $doctor);
        $this->broadcastPatientSessionStarted($appointment);

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

    public function broadcastPatientSessionStarted(Appointment $appointment): void
    {
        if ($appointment->user_id === null) {
            return;
        }

        broadcast(new PatientAppointmentSessionStarted(
            (int) $appointment->user_id,
            (int) $appointment->id,
            (string) $appointment->status,
            $appointment->actual_start_at?->toIso8601String(),
            $appointment->extend_at?->toIso8601String(),
        ));
    }
}
