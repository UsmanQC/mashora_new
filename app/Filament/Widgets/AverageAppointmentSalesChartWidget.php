<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AdminDashboardAppointmentMetrics;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Number;

/**
 * Legacy admin label; chart shows daily totals for the rolling week (same data as badge sum source).
 */
final class AverageAppointmentSalesChartWidget extends LegacyDashboardChartWidget
{
    protected static ?int $sort = -98;

    protected ?string $maxHeight = '260px';

    protected string $color = 'primary';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];

    public function getHeading(): string|Htmlable|null
    {
        return __('Average appointment sales');
    }

    public function badgePrimary(): string
    {
        $amount = AdminDashboardAppointmentMetrics::data()['totalrevenue_week'];

        return Number::format($amount, 2);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $d = AdminDashboardAppointmentMetrics::data();

        return [
            'datasets' => [
                [
                    'label' => __('Total price'),
                    'data' => $d['chart_daily_total_sales'],
                ],
            ],
            'labels' => $d['chart_labels'],
        ];
    }
}
