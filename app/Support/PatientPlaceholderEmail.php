<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Stable internal email derived from digits-only phone — required by Fortify + unique email rule.
 */
final class PatientPlaceholderEmail
{
    public static function make(string $normalizedDigits): string
    {
        $domain = trim((string) config('patient.placeholder_email_domain', 'patient.invalid'));

        $suffix = substr(hash('sha256', Str::slug($normalizedDigits).config('app.key')), 0, 16);

        return sprintf('p.%s@%s', $suffix, $domain);
    }
}
