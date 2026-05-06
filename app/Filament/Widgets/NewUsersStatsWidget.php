<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Support\AdminDashboardAppointmentMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Service provider (doctors) + new-user metrics in one stats row ({@see AdminDashboardAppointmentMetrics}).
 */
final class NewUsersStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -93;

    /** @var int | array<string, ?int> | null */
    protected int|array|null $columns = [
        'default' => 1,
        '@sm' => 2,
        '@lg' => 4,
    ];

    protected int|string|array $columnSpan = [
        'default' => 'full',
    ];

    protected function getHeading(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $m = AdminDashboardAppointmentMetrics::data();

        return [
            Stat::make(__('Doctors'), number_format($m['doctors_total']))
                ->description(__('Service Provider'))
                ->color('primary')
                ->icon(Heroicon::OutlinedUsers)
                ->url(DoctorResource::getUrl()),
            Stat::make(__('Today'), number_format($m['users_today']))
                ->color('info')
                ->icon(Heroicon::OutlinedUserPlus),
            Stat::make(__('Last 7 days'), number_format($m['users_week']))
                ->color('success')
                ->icon(Heroicon::OutlinedUsers),
            Stat::make(__('Last 30 days'), number_format($m['users_month']))
                ->color('warning')
                ->icon(Heroicon::OutlinedUserGroup),
        ];
    }
}
