<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AdminDashboardAppointmentMetrics;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Number;

final class AverageDailySalesChartWidget extends LegacyDashboardChartWidget
{
    protected static ?int $sort = -97;

    protected ?string $maxHeight = '260px';

    protected string $color = 'warning';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];

    public function getHeading(): string|Htmlable|null
    {
        return __('Average daily sales');
    }

    public function badgePrimary(): string
    {
        $avg = AdminDashboardAppointmentMetrics::data()['daily_avg_last7'];

        return Number::format($avg, 2);
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
                    'data' => $d['chart_daily_avg_sale'],
                ],
            ],
            'labels' => $d['chart_labels'],
        ];
    }
}
