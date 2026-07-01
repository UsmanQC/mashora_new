<?php

namespace App\Services;

use App\Events\AppointmentCallEnded;
use App\Http\Controllers\Patient\PatientAppointmentRealtimeController;
use App\Models\Appointment;

final class AppointmentCompletionService
{
    public const COMPLETED = 'completed';

    public const MISSING_DIAGNOSIS = 'missing_diagnosis';

    public const MISSING_PRESCRIPTION = 'missing_prescription';

    public const NOT_IN_PROCESS = 'not_in_process';

    /**
     * @return self::COMPLETED|self::MISSING_DIAGNOSIS|self::MISSING_PRESCRIPTION|self::NOT_IN_PROCESS
     */
    public function attemptCompletion(Appointment $appointment): string
    {
        if ((string) $appointment->status !== 'in_process') {
            return self::NOT_IN_PROCESS;
        }

        $appointment->loadMissing(['diagnosis', 'medications']);

        if ($appointment->diagnosis === null) {
            return self::MISSING_DIAGNOSIS;
        }

        if (! $appointment->prescription_not_needed && $appointment->medications->isEmpty()) {
            return self::MISSING_PRESCRIPTION;
        }

        $appointment->forceFill([
            'status' => 'completed',
            'actual_end_at' => now()->format('Y-m-d H:i:s'),
        ])->save();

        $this->clearPendingCallState($appointment->fresh());

        return self::COMPLETED;
    }

    private function clearPendingCallState(Appointment $appointment): void
    {
        if ($appointment->user_id === null) {
            return;
        }

        PatientAppointmentRealtimeController::clearPendingIncomingCall(
            (int) $appointment->user_id,
            (int) $appointment->id,
        );

        broadcast(new AppointmentCallEnded(
            (int) $appointment->id,
            (int) $appointment->user_id,
        ));
    }
}
