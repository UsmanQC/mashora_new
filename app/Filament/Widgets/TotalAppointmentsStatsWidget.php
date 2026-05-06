<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AdminDashboardAppointmentMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Uses Filament {@see StatsOverviewWidget} so styling ships with the panel CSS (custom Blade views can appear unchanged without a viteTheme).
 *
 * @see AdminDashboardAppointmentMetrics
 */
final class TotalAppointmentsStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -96;

    protected static bool $isLazy = false;

    /** @var int | array<string, ?int> | null */
    protected int|array|null $columns = [
        'default' => 1,
        '@sm' => 2,
        '@lg' => 5,
    ];

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];

    protected function getHeading(): ?string
    {
        return __('Total appointments');
    }

    protected function getStats(): array
    {
        $m = AdminDashboardAppointmentMetrics::data();

        return [
            Stat::make(__('Today appointments'), number_format($m['appointments_today']))
                ->color('primary'),
            Stat::make(__("Yesterday's appointments"), number_format($m['appointments_yesterday']))
                ->color('gray'),
            Stat::make(__('Previous month appointments'), number_format($m['appointments_previous_month']))
                ->color('warning'),
            Stat::make(__('Current year appointments'), number_format($m['appointments_current_year_ytd']))
                ->color('success'),
            Stat::make(__('Last year appointments'), number_format($m['appointments_last_year']))
                ->color('danger'),
        ];
    }
}
