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
     * Mobile "More" screen sections (matches luxury mobile layout).
     *
     * @return array<int, array{heading: string, items: array<int, array{label: string, sub: string, icon: string, route: string, available: bool, badge_key: string|null}>}>
     */
    public static function mobileMoreSections(): array
    {
        return [
            [
                'heading' => __('doctor.menu.group_practice'),
                'items' => [
                    self::mobileItem('doctor.menu.working_hours', 'doctor.menu.working_hours_sub', 'clock', 'doctor.settings.working-hours'),
                    self::mobileItem('doctor.menu.duration', 'doctor.menu.duration_sub', 'currency-dollar', 'doctor.settings.duration'),
                    self::mobileItem('doctor.menu.profile', 'doctor.menu.profile_sub', 'user-circle', 'doctor.settings.profile'),
                ],
            ],
            [
                'heading' => __('doctor.mobile.more_work'),
                'items' => [
                    self::mobileItem('doctor.mobile.more_patients', 'doctor.mobile.more_patients_sub', 'users', 'doctor.appointments', 'patients_count'),
                    self::mobileItem('doctor.mobile.more_prescriptions', 'doctor.mobile.more_prescriptions_sub', 'clipboard-document-list', 'doctor.appointments', 'prescriptions_count', ['status' => 'completed']),
                    self::mobileItem('doctor.menu.ratings', 'doctor.menu.ratings_sub', 'chart-bar', 'doctor.ratings'),
                    self::mobileItem('doctor.menu.notifications', 'doctor.menu.notifications_sub', 'bell', 'doctor.settings.notifications', 'notifications_count'),
                ],
            ],
            [
                'heading' => __('doctor.menu.group_finance'),
                'items' => [
                    self::mobileItem('doctor.menu.wallet', 'doctor.menu.wallet_sub', 'banknotes', 'doctor.settings.wallet'),
                    self::mobileItem('doctor.menu.invoices', 'doctor.menu.invoices_sub', 'document-text', 'doctor.settings.invoices'),
                    self::mobileItem('doctor.menu.bank_account', 'doctor.menu.bank_account_sub', 'credit-card', 'doctor.settings.bank-account'),
                ],
            ],
            [
                'heading' => __('doctor.menu.group_help'),
                'items' => [
                    self::mobileItem('doctor.menu.support', 'doctor.menu.support_sub', 'lifebuoy', 'doctor.settings.support'),
                    self::mobileItem('doctor.menu.privacy', 'doctor.menu.privacy_sub', 'shield-check', 'doctor.settings.privacy-policy'),
                ],
            ],
        ];
    }

    /**
     * @return array{label: string, sub: string, icon: string, route: string, available: bool, badge_key: string|null, query: array<string, string>}
     */
    private static function mobileItem(
        string $labelKey,
        string $subKey,
        string $icon,
        string $route,
        ?string $badgeKey = null,
        array $query = [],
    ): array {
        return [
            'label' => __($labelKey),
            'sub' => __($subKey),
            'icon' => $icon,
            'route' => $route,
            'available' => Route::has($route),
            'badge_key' => $badgeKey,
            'query' => $query,
        ];
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
