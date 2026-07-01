<?php

namespace App\Support;

final class PatientPortalBackNavigation
{
    /**
     * @return array{url: string, label: string}|null
     */
    public static function resolve(): ?array
    {
        return match (true) {
            request()->routeIs('patient.schedule.filter') => [
                'url' => auth()->check() ? route('patient.home') : route('home'),
                'label' => __('patient_auth.back'),
            ],
            request()->routeIs('patient.schedule.specialists') => [
                'url' => route('patient.schedule.filter'),
                'label' => __('patient_auth.back'),
            ],
            default => null,
        };
    }
}
