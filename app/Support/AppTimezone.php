<?php

namespace App\Support;

final class AppTimezone
{
    /**
     * Timezone used for appointments, working hours, and slot availability.
     * Saudi Arabia is the default when config is still UTC.
     */
    public static function name(): string
    {
        $timezone = (string) config('app.timezone', 'UTC');

        if ($timezone === '' || $timezone === 'UTC') {
            return 'Asia/Riyadh';
        }

        return $timezone;
    }
}
