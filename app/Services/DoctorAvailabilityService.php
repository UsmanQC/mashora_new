<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\AppTimezone;
use Carbon\Carbon;

class DoctorAvailabilityService
{
    /**
     * Appointment statuses that occupy a slot and should block re-booking.
     *
     * @var list<string>
     */
    public const BLOCKING_STATUSES = ['new', 'in_process', 'rescheduled', 'pending_follow_up'];

    private const SLOT_MINUTES = 15;

    /**
     * Build the bookable start times (H:i) for a doctor on a given date, excluding
     * past times (for today) and slots that clash with existing appointments.
     *
     * @return list<string>
     */
    public function availableSlots(
        Doctor $doctor,
        string $date,
        int $durationMinutes,
        ?int $excludeAppointmentId = null,
    ): array {
        $timezone = AppTimezone::name();
        $duration = max(self::SLOT_MINUTES, $durationMinutes);

        $baseSlots = $this->baseSlotsForDate($doctor, $date, $timezone, $duration);
        if ($baseSlots === []) {
            return [];
        }

        try {
            $selectedDate = Carbon::parse($date, $timezone)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        $now = now()->timezone($timezone);
        $isToday = $selectedDate->toDateString() === $now->toDateString();

        $taken = $this->takenRanges($doctor->id, $date, $excludeAppointmentId);

        return collect($baseSlots)
            ->filter(function (string $slot) use ($selectedDate, $duration, $now, $isToday, $taken, $timezone): bool {
                try {
                    $start = Carbon::createFromFormat('Y-m-d H:i', $selectedDate->format('Y-m-d').' '.$slot, $timezone);
                } catch (\Throwable) {
                    return false;
                }

                if ($isToday && ! $start->greaterThan($now)) {
                    return false;
                }

                $end = (clone $start)->addMinutes($duration);

                foreach ($taken as [$takenStart, $takenEnd]) {
                    if ($start->lessThan($takenEnd) && $end->greaterThan($takenStart)) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * First calendar date (Y-m-d) within the lookahead window that has at least one bookable slot.
     */
    public function firstDateWithSlots(
        Doctor $doctor,
        int $durationMinutes,
        ?string $preferredDate = null,
        int $lookaheadDays = 60,
    ): ?string {
        $timezone = AppTimezone::name();
        $start = $preferredDate !== null
            ? Carbon::parse($preferredDate, $timezone)->startOfDay()
            : now($timezone)->startOfDay();

        if ($start->lessThan(now($timezone)->startOfDay())) {
            $start = now($timezone)->startOfDay();
        }

        for ($offset = 0; $offset < $lookaheadDays; $offset++) {
            $candidate = $start->copy()->addDays($offset)->format('Y-m-d');

            if ($this->availableSlots($doctor, $candidate, $durationMinutes) !== []) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Raw working-hour slots (H:i) for a date, ignoring conflicts.
     *
     * @return list<string>
     */
    public function baseSlotsForDate(
        Doctor $doctor,
        string $date,
        ?string $timezone = null,
        int $durationMinutes = self::SLOT_MINUTES,
    ): array {
        $timezone ??= AppTimezone::name();
        $duration = max(self::SLOT_MINUTES, $durationMinutes);

        try {
            $selectedDate = Carbon::parse($date, $timezone);
        } catch (\Throwable) {
            return [];
        }

        $weekday = strtolower($selectedDate->englishDayOfWeek);

        $workingDays = $doctor->workingDays()
            ->where('is_working', true)
            ->where(function ($query) use ($selectedDate, $weekday): void {
                $query->whereDate('override_date', $selectedDate->toDateString())
                    ->orWhere(function ($inner) use ($weekday): void {
                        $inner->whereNull('override_date')
                            ->where('day_of_week', $weekday);
                    });
            })
            ->with('workingHours')
            ->get();

        if ($workingDays->isEmpty()) {
            return [];
        }

        return $workingDays
            ->flatMap(function ($workingDay) use ($timezone, $duration) {
                return $workingDay->workingHours->flatMap(function ($hour) use ($timezone, $duration) {
                    if (! filled($hour->start_time) || ! filled($hour->end_time)) {
                        return [];
                    }

                    $start = $this->parseClockTime((string) $hour->start_time, $timezone);
                    $end = $this->parseClockTime((string) $hour->end_time, $timezone);

                    if ($start === null || $end === null || $start->greaterThanOrEqualTo($end)) {
                        return [];
                    }

                    $times = [];
                    $cursor = $start->copy();

                    while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($end)) {
                        $times[] = $cursor->format('H:i');
                        $cursor = $cursor->addMinutes(self::SLOT_MINUTES);
                    }

                    return $times;
                });
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function parseClockTime(string $time, string $timezone): ?Carbon
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $time, $timezone);

                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($time, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Existing appointment time ranges (as Carbon pairs) for the doctor on a date.
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function takenRanges(int $doctorId, string $date, ?int $excludeAppointmentId): array
    {
        $timezone = AppTimezone::name();

        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->when($excludeAppointmentId !== null, fn ($query) => $query->where('id', '!=', $excludeAppointmentId))
            ->get(['start_time', 'end_time'])
            ->map(function (Appointment $appointment) use ($date, $timezone): ?array {
                if (! filled($appointment->start_time) || ! filled($appointment->end_time)) {
                    return null;
                }

                try {
                    $start = Carbon::parse($date.' '.$appointment->start_time, $timezone);
                    $end = Carbon::parse($date.' '.$appointment->end_time, $timezone);
                } catch (\Throwable) {
                    return null;
                }

                return [$start, $end];
            })
            ->filter()
            ->values()
            ->all();
    }
}
