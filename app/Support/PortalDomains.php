<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PortalDomains
{
    public static function patient(): ?string
    {
        $domain = config('domains.patient');

        return filled($domain) ? strtolower(trim((string) $domain)) : null;
    }

    public static function doctor(): ?string
    {
        $domain = config('domains.doctor');

        return filled($domain) ? strtolower(trim((string) $domain)) : null;
    }

    public static function patientEnabled(): bool
    {
        return self::patient() !== null;
    }

    public static function doctorEnabled(): bool
    {
        return self::doctor() !== null;
    }

    public static function isPatientHost(?Request $request = null): bool
    {
        $domain = self::patient();

        if ($domain === null) {
            return false;
        }

        return strtolower((string) ($request ?? request())->getHost()) === $domain;
    }

    public static function isDoctorHost(?Request $request = null): bool
    {
        $domain = self::doctor();

        if ($domain === null) {
            return false;
        }

        return strtolower((string) ($request ?? request())->getHost()) === $domain;
    }

    public static function isPatientPortalRequest(?Request $request = null): bool
    {
        $request ??= request();

        return self::isPatientHost($request)
            || $request->is('patient', 'patient/*');
    }

    public static function isDoctorPortalRequest(?Request $request = null): bool
    {
        $request ??= request();

        return self::isDoctorHost($request)
            || $request->is('doctor', 'doctor/*');
    }
}
