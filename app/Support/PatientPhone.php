<?php

namespace App\Support;

final class PatientPhone
{
    /**
     * Normalize handset input so sign-in/register store and query the same digits.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return trim($digits) !== '' ? trim($digits) : trim($phone);
    }

    /**
     * Build E.164-style storage digits: country dial (e.g. 966) + national number without leading zeros.
     */
    public static function combineInternational(string $dialDigits, string $nationalInput): string
    {
        $dial = preg_replace('/\D/', '', $dialDigits) ?? '';
        $national = preg_replace('/\D/', '', $nationalInput) ?? '';
        $national = ltrim($national, '0');

        return $dial.$national;
    }
}
