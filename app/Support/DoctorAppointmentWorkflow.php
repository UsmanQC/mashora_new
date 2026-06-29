<?php

namespace App\Support;

use App\Models\Appointment;
use App\Services\FollowUpAppointmentService;

final class DoctorAppointmentWorkflow
{
    public function __construct(
        private FollowUpAppointmentService $followUpService,
    ) {}

    /**
     * @return list<array{key: string, label: string, route: ?string, complete: bool, current: bool}>
     */
    public function steps(Appointment $appointment, string $activeKey): array
    {
        $appointment->loadMissing(['diagnosis', 'medications']);

        $historyReviewed = (bool) session()->get($this->historySessionKey($appointment), false);
        $hasDiagnosis = $appointment->diagnosis !== null;
        $prescriptionDone = (bool) $appointment->prescription_not_needed
            || $appointment->medications->isNotEmpty();
        $isComplete = $appointment->status === 'completed';
        $canFollowUp = $appointment->parent_id === null && $isComplete;
        $followUpDone = false;

        if ($canFollowUp) {
            $followUpDone = $this->followUpService->existingFollowUpFor($appointment) !== null
                || $this->followUpService->pendingFollowUpFor($appointment) !== null
                || $this->followUpService->parentDeclinedFollowUp($appointment);
        }

        $steps = [
            [
                'key' => 'medical_history',
                'label' => __('doctor.workflow.step_medical_history'),
                'route' => route('doctor.appointments.medical-history', $appointment),
                'complete' => $historyReviewed || $hasDiagnosis,
            ],
            [
                'key' => 'diagnosis',
                'label' => __('doctor.workflow.step_diagnosis'),
                'route' => route('doctor.appointments.diagnosis', $appointment),
                'complete' => $hasDiagnosis,
            ],
            [
                'key' => 'prescription',
                'label' => __('doctor.workflow.step_prescription'),
                'route' => route('doctor.appointments.prescription', $appointment),
                'complete' => $prescriptionDone,
            ],
            [
                'key' => 'complete',
                'label' => __('doctor.workflow.step_complete'),
                'route' => null,
                'complete' => $isComplete,
            ],
        ];

        if ($canFollowUp) {
            $steps[] = [
                'key' => 'follow_up',
                'label' => __('doctor.workflow.step_follow_up'),
                'route' => route('doctor.appointments.follow-up', $appointment),
                'complete' => $followUpDone,
            ];
        }

        return array_map(function (array $step) use ($activeKey): array {
            $step['current'] = $step['key'] === $activeKey;

            return $step;
        }, $steps);
    }

    public function nextStepRoute(Appointment $appointment): ?string
    {
        $appointment->loadMissing(['diagnosis', 'medications']);

        if (! session()->get($this->historySessionKey($appointment), false) && $appointment->diagnosis === null) {
            return route('doctor.appointments.medical-history', $appointment);
        }

        if ($appointment->diagnosis === null) {
            return route('doctor.appointments.diagnosis', $appointment);
        }

        if (! $appointment->prescription_not_needed && $appointment->medications->isEmpty()) {
            return route('doctor.appointments.prescription', $appointment);
        }

        if ($appointment->status === 'completed'
            && $appointment->parent_id === null
            && ! $this->followUpService->parentDeclinedFollowUp($appointment)
            && $this->followUpService->parentCanScheduleFollowUp($appointment)
            && $this->followUpService->existingFollowUpFor($appointment) === null
            && $this->followUpService->pendingFollowUpFor($appointment) === null
        ) {
            return route('doctor.appointments.follow-up', $appointment);
        }

        return null;
    }

    public function markHistoryReviewed(Appointment $appointment): void
    {
        session()->put($this->historySessionKey($appointment), true);
    }

    private function historySessionKey(Appointment $appointment): string
    {
        return "doctor.workflow.history.{$appointment->id}";
    }
}
