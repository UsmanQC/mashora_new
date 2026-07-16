<?php

namespace App\Support;

use App\Models\Doctor;
use Illuminate\Http\Request;

final class PendingPatientBooking
{
    public const SESSION_KEY = 'pending_patient_booking';

    /**
     * @param  array{doctor_id: int, date: string, time: string, duration: int}|null  $data
     */
    public static function store(int $doctorId, string $date, string $time, int $duration): void
    {
        session([self::SESSION_KEY => [
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
        ]]);
    }

    public static function urlFor(int $doctorId, string $date, string $time, int $duration): string
    {
        return route('patient.book-appointments', ['doctor' => $doctorId], false)
            .'?'.http_build_query([
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
            ]);
    }

    /**
     * @param  array{date: string, time: string}|null  $slot
     */
    public static function remember(int $doctorId, ?array $slot, int $duration): ?string
    {
        if ($doctorId < 1 || $duration < 1 || $slot === null) {
            return null;
        }

        $date = $slot['date'] ?? '';
        $time = $slot['time'] ?? '';

        if ($date === '' || $time === '') {
            return null;
        }

        self::store($doctorId, $date, $time, $duration);

        return self::urlFor($doctorId, $date, $time, $duration);
    }

    public static function captureFromRequest(Request $request): bool
    {
        $isBookingPath = $request->is('patient/book-appointments/*')
            || $request->is('book-appointments/*');

        if (! $isBookingPath) {
            return false;
        }

        $doctorId = self::resolveDoctorIdFromRequest($request);
        $date = $request->query('date');
        $time = $request->query('time');
        $duration = (int) $request->query('duration', 0);

        if ($doctorId < 1 || ! is_string($date) || $date === '' || ! is_string($time) || $time === '' || $duration < 1) {
            return false;
        }

        self::store($doctorId, $date, $time, $duration);

        return true;
    }

    public static function captureFromUrl(string $url): bool
    {
        if (! self::isBookingUrl($url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($path) || ! preg_match('#/(?:patient/)?book-appointments/(\d+)(?:/|$)#', $path, $matches)) {
            return false;
        }

        parse_str(is_string($query) ? $query : '', $params);

        $date = $params['date'] ?? '';
        $time = $params['time'] ?? '';
        $duration = (int) ($params['duration'] ?? 0);

        if (! is_string($date) || $date === '' || ! is_string($time) || $time === '' || $duration < 1) {
            return false;
        }

        self::store((int) $matches[1], $date, $time, $duration);

        return true;
    }

    public static function isBookingUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && (bool) preg_match('#/(?:patient/)?book-appointments/#', $path);
    }

    /**
     * @return array{doctor_id: int, date: string, time: string, duration: int}|null
     */
    public static function get(): ?array
    {
        $data = session(self::SESSION_KEY);

        if (! is_array($data)) {
            return null;
        }

        $doctorId = (int) ($data['doctor_id'] ?? 0);
        $date = $data['date'] ?? '';
        $time = $data['time'] ?? '';
        $duration = (int) ($data['duration'] ?? 0);

        if ($doctorId < 1 || ! is_string($date) || $date === '' || ! is_string($time) || $time === '' || $duration < 1) {
            return null;
        }

        return [
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
        ];
    }

    public static function url(): ?string
    {
        $data = self::get();

        if ($data === null) {
            return null;
        }

        return self::urlFor(
            $data['doctor_id'],
            $data['date'],
            $data['time'],
            $data['duration'],
        );
    }

    public static function homeOrBookingUrl(): string
    {
        return self::url() ?? route('patient.home');
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private static function resolveDoctorIdFromRequest(Request $request): int
    {
        $doctor = $request->route('doctor');

        if ($doctor instanceof Doctor) {
            return (int) $doctor->getKey();
        }

        if (is_numeric($doctor)) {
            return (int) $doctor;
        }

        if (preg_match('#patient/book-appointments/(\d+)#', $request->path(), $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
