<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

final class DoctorMenu
{
    /**
     * @return array<int, array{heading: string, items: array<int, array{label: string, sub: string, icon: string, route: string, available: bool}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'heading' => __('doctor.menu.group_practice'),
                'items' => [
                    self::item('doctor.menu.appointments', 'doctor.menu.appointments_sub', 'calendar-days', 'doctor.appointments'),
                    self::item('doctor.menu.ratings', 'doctor.menu.ratings_sub', 'star', 'doctor.ratings'),
                    self::item('doctor.menu.working_hours', 'doctor.menu.working_hours_sub', 'clock', 'doctor.settings.working-hours'),
                    self::item('doctor.menu.duration', 'doctor.menu.duration_sub', 'currency-dollar', 'doctor.settings.duration'),
                ],
            ],
            [
                'heading' => __('doctor.menu.group_finance'),
                'items' => [
                    self::item('doctor.menu.wallet', 'doctor.menu.wallet_sub', 'banknotes', 'doctor.settings.wallet'),
                    self::item('doctor.menu.invoices', 'doctor.menu.invoices_sub', 'document-text', 'doctor.settings.invoices'),
                    self::item('doctor.menu.bank_account', 'doctor.menu.bank_account_sub', 'credit-card', 'doctor.settings.bank-account'),
                ],
            ],
            [
                'heading' => __('doctor.menu.group_account'),
                'items' => [
                    self::item('doctor.menu.profile', 'doctor.menu.profile_sub', 'user-circle', 'doctor.settings.profile'),
                    self::item('doctor.menu.notifications', 'doctor.menu.notifications_sub', 'bell', 'doctor.settings.notifications'),
                ],
            ],
            [
                'heading' => __('doctor.menu.group_help'),
                'items' => [
                    self::item('doctor.menu.support', 'doctor.menu.support_sub', 'lifebuoy', 'doctor.settings.support'),
                    self::item('doctor.menu.privacy', 'doctor.menu.privacy_sub', 'shield-check', 'doctor.settings.privacy-policy'),
                ],
            ],
        ];
    }

    public static function isRouteActive(string $route): bool
    {
        if (request()->routeIs($route)) {
            return true;
        }

        $childRoutePrefixes = [
            'doctor.appointments',
            'doctor.settings.invoices',
            'doctor.settings.support',
        ];

        if (in_array($route, $childRoutePrefixes, true)) {
            return request()->routeIs($route.'.*');
        }

        return false;
    }

    /**
     * @return array{label: string, sub: string, icon: string, route: string, available: bool}
     */
    private static function item(string $labelKey, string $subKey, string $icon, string $route): array
    {
        return [
            'label' => __($labelKey),
            'sub' => __($subKey),
            'icon' => $icon,
            'route' => $route,
            'available' => Route::has($route),
        ];
    }
}
