<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AppointmentRescheduleService
{
    /**
     * @var list<string>
     */
    public const RESCHEDULABLE_STATUSES = ['new', 'in_process', 'rescheduled'];

    public function __construct(
        private readonly DoctorAvailabilityService $availability,
        private readonly PatientAppointmentNotifier $notifier,
    ) {}

    public function reschedule(Doctor $doctor, Appointment $appointment, string $date, string $time): Appointment
    {
        if ((int) $appointment->doctor_id !== (int) $doctor->id) {
            abort(403);
        }

        if (! in_array((string) $appointment->status, self::RESCHEDULABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.reschedule.not_eligible'),
            ]);
        }

        if ($appointment->user_id === null) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.reschedule.patient_missing'),
            ]);
        }

        $durationMinutes = max(15, (int) $appointment->duration);
        $slots = $this->availability->availableSlots(
            $doctor,
            $date,
            $durationMinutes,
            $appointment->id,
        );

        if (! in_array($time, $slots, true)) {
            throw ValidationException::withMessages([
                'selectedTime' => __('doctor.reschedule.slot_unavailable'),
            ]);
        }

        $timezone = AppTimezone::name();
        $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time, $timezone);
        $end = (clone $start)->addMinutes($durationMinutes);

        $updated = DB::transaction(function () use ($appointment, $start, $end, $durationMinutes): Appointment {
            $appointment->update([
                'scheduled_at' => $start->format('Y-m-d H:i:s'),
                'appointment_date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration' => $durationMinutes,
                'extend_at' => $end->format('Y-m-d H:i:s'),
                'status' => 'rescheduled',
            ]);

            return $appointment->fresh();
        });

        $this->notifier->notifyRescheduled($updated, $doctor, $start);

        return $updated;
    }
}
