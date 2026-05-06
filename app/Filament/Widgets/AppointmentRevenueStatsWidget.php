<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AdminDashboardAppointmentMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

final class AppointmentRevenueStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -100;

    /** @var int | array<string, ?int> | null */
    protected int|array|null $columns = [
        'default' => 1,
        '@sm' => 2,
        '@lg' => 3,
        '@xl' => 5,
    ];

    protected int|string|array $columnSpan = [
        'default' => 'full',
    ];

    protected function getHeading(): ?string
    {
        return __('Total revenue (appointments)');
    }

    protected function getStats(): array
    {
        $m = AdminDashboardAppointmentMetrics::data();

        return [
            Stat::make(__('Today'), $this->money($m['totalrevenue_today']))
                ->color('primary')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make(__('Last 7 days'), $this->money($m['totalrevenue_week']))
                ->color('success')
                ->icon(Heroicon::OutlinedBars3),
            Stat::make(__('Last 30 days'), $this->money($m['totalrevenue_month']))
                ->color('info')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make(__('This year'), $this->money($m['totalrevenue_year']))
                ->description((string) now()->year)
                ->color('warning')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make(__('Total for all days'), $this->money($m['totalrevenue_all']))
                ->color('gray')
                ->icon(Heroicon::OutlinedBanknotes),
        ];
    }

    private function money(float $amount): HtmlString
    {
        return new HtmlString(
            Number::format($amount, 2)
            .' <img src="'.asset('images/saudi_riyal.svg').'" alt="Saudi Riyal" style="height:14px;display:inline-block;vertical-align:middle;margin-inline-start:4px;">'
        );
    }
}
