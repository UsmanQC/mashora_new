<?php

namespace App\Support;

final class PatientAuthBackNavigation
{
    /**
     * @return array{url: string, label: string}|null
     */
    public static function resolve(): ?array
    {
        $phoneQuery = request()->query('phone');
        $phoneForBack = is_string($phoneQuery) && $phoneQuery !== '' ? $phoneQuery : null;

        return match (true) {
            request()->routeIs('patient.auth.verify-phone', 'patient.auth.sign-up') => [
                'url' => $phoneForBack !== null
                    ? route('patient.phone', ['phone' => $phoneForBack])
                    : route('patient.phone'),
                'label' => __('patient_auth.back'),
            ],
            request()->routeIs('patient.profile.basic') => [
                'url' => route('home'),
                'label' => __('patient_auth.back'),
            ],
            request()->routeIs('patient.register.done') => null,
            default => [
                'url' => route('home'),
                'label' => __('patient_auth.back'),
            ],
        };
    }
}
