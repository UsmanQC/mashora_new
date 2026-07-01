<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

final class PatientMenu
{
    /**
     * @return array<int, array{label: string, icon: string, route: string, href: string, available: bool}>
     */
    public static function primaryItems(): array
    {
        return [
            self::primaryItem('patient.nav.home', 'home', 'patient.home', authRequired: false),
        ];
    }

    /**
     * @return array<int, array{heading: string, items: array<int, array{label: string, icon: string, route: string, href: string, available: bool}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'heading' => __('patient.sidebar.group_main'),
                'items' => [
                    self::item('patient.nav.appointments', '', 'calendar-days', 'patient.appointments'),
                    self::item('patient.nav.important_numbers', '', 'phone', 'patient.important-numbers', authRequired: false),
                ],
            ],
            [
                'heading' => __('patient.sidebar.group_account'),
                'items' => [
                    self::item('patient.menu.notifications', 'patient.menu.notifications_sub', 'bell', 'patient.notifications'),
                    self::item('patient.menu.wallet', 'patient.menu.wallet_sub', 'banknotes', 'patient.wallet'),
                    self::item('patient.menu.account_settings', 'patient.menu.account_settings_sub', 'user-circle', 'profile.edit'),
                ],
            ],
            [
                'heading' => __('patient.sidebar.group_health'),
                'items' => [
                    self::item('patient.menu.medications', 'patient.menu.medications_sub', 'clipboard-document', 'patient.medications'),
                    self::item('patient.menu.favorites', 'patient.menu.favorites_sub', 'heart', 'patient.favorites'),
                ],
            ],
            [
                'heading' => __('patient.sidebar.group_help'),
                'items' => [
                    self::item('patient.menu.support', 'patient.menu.support_sub', 'lifebuoy', 'patient.support'),
                    self::item('patient.menu.privacy', 'patient.menu.privacy_sub', 'shield-check', 'patient.privacy'),
                ],
            ],
        ];
    }

    public static function href(string $route, bool $authRequired = true): string
    {
        if ($authRequired && ! Auth::check()) {
            return route('patient.phone');
        }

        return route($route);
    }

    public static function isRouteActive(string $route): bool
    {
        if ($route === 'patient.home') {
            return request()->routeIs('patient.home');
        }

        if ($route === 'patient.appointments') {
            return request()->routeIs([
                'patient.appointments',
                'patient.schedule.filter',
                'patient.schedule.specialists',
                'patient.book-appointments',
                'patient.checkout',
                'patient.checkout.demo',
                'patient.payment.success',
                'patient.payment.failed',
            ]);
        }

        if ($route === 'patient.important-numbers') {
            return request()->routeIs('patient.important-numbers');
        }

        if ($route === 'patient.support') {
            return request()->routeIs('patient.support', 'patient.support.*');
        }

        if ($route === 'profile.edit') {
            return request()->routeIs('profile.edit');
        }

        return request()->routeIs($route);
    }

    /**
     * @return array{label: string, icon: string, route: string, href: string, available: bool}
     */
    private static function primaryItem(
        string $labelKey,
        string $icon,
        string $route,
        bool $authRequired = true,
    ): array {
        return [
            'label' => __($labelKey),
            'icon' => $icon,
            'route' => $route,
            'href' => self::href($route, $authRequired),
            'available' => Route::has($route),
        ];
    }

    /**
     * @return array{label: string, icon: string, route: string, href: string, available: bool}
     */
    private static function item(
        string $labelKey,
        string $subKey,
        string $icon,
        string $route,
        bool $authRequired = true,
    ): array {
        return [
            'label' => __($labelKey),
            'sub' => filled($subKey) ? __($subKey) : null,
            'icon' => $icon,
            'route' => $route,
            'href' => self::href($route, $authRequired),
            'available' => Route::has($route),
        ];
    }
}
