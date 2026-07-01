<?php

namespace App\Livewire\Concerns;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentCompletionService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

trait CompletesDoctorAppointment
{
    public bool $showCompleteAppointmentModal = false;

    public bool $showDiagnosisRequiredModal = false;

    public bool $showPrescriptionRequiredModal = false;

    public ?int $appointmentPendingCompleteId = null;

    public function requestCompleteAppointment(?int $appointmentId = null): void
    {
        $doctor = $this->doctorForAppointmentCompletion();
        if ($doctor === null) {
            abort(403);
        }

        $resolvedId = $appointmentId ?? $this->defaultAppointmentIdForCompletion();
        if ($resolvedId === null) {
            return;
        }

        $appointment = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereKey($resolvedId)
            ->first();

        if ($appointment === null || $appointment->status !== 'in_process') {
            return;
        }

        $this->appointmentPendingCompleteId = $resolvedId;
        $this->showCompleteAppointmentModal = true;
    }

    public function dismissCompleteAppointmentModal(): void
    {
        $this->showCompleteAppointmentModal = false;
        $this->appointmentPendingCompleteId = null;
    }

    public function updatedShowCompleteAppointmentModal(bool $value): void
    {
        if (! $value) {
            $this->appointmentPendingCompleteId = null;
        }
    }

    public function confirmCompleteAppointment(): void
    {
        $id = $this->appointmentPendingCompleteId;
        $this->dismissCompleteAppointmentModal();

        if ($id === null) {
            return;
        }

        $doctor = $this->doctorForAppointmentCompletion();
        if ($doctor === null) {
            abort(403);
        }

        $appointment = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereKey($id)
            ->first();

        if ($appointment === null) {
            return;
        }

        $result = app(AppointmentCompletionService::class)->attemptCompletion($appointment);

        match ($result) {
            AppointmentCompletionService::MISSING_DIAGNOSIS => $this->showDiagnosisRequiredModal = true,
            AppointmentCompletionService::MISSING_PRESCRIPTION => $this->showPrescriptionRequiredModal = true,
            AppointmentCompletionService::COMPLETED => $this->redirectAfterAppointmentCompletion($appointment->fresh()),
            default => null,
        };
    }

    public function dismissDiagnosisRequiredModal(): void
    {
        $this->showDiagnosisRequiredModal = false;
    }

    public function dismissPrescriptionRequiredModal(): void
    {
        $this->showPrescriptionRequiredModal = false;
    }

    protected function redirectAfterAppointmentCompletion(Appointment $appointment): void
    {
        Flux::toast(variant: 'success', text: __('doctor.complete_flow.success'));

        $this->redirect(route('doctor.appointments.follow-up', $appointment), navigate: true);
    }

    protected function doctorForAppointmentCompletion(): ?Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        return $doctor instanceof Doctor ? $doctor : null;
    }

    protected function defaultAppointmentIdForCompletion(): ?int
    {
        if (! property_exists($this, 'appointment')) {
            return null;
        }

        $appointment = $this->appointment;

        if (! $appointment instanceof Appointment) {
            return null;
        }

        return (int) $appointment->id;
    }
}
