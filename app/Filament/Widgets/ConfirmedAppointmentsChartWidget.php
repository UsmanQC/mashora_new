<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AdminDashboardAppointmentMetrics;
use Illuminate\Contracts\Support\Htmlable;

final class ConfirmedAppointmentsChartWidget extends LegacyDashboardChartWidget
{
    protected static ?int $sort = -99;

    protected ?string $maxHeight = '260px';

    protected string $color = 'success';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];

    public function getHeading(): string|Htmlable|null
    {
        return __('Confirmed appointments');
    }

    public function badgePrimary(): string
    {
        return number_format(
            AdminDashboardAppointmentMetrics::data()['confirmed_appointments_week'],
        );
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
                    'label' => __('Appointments'),
                    'data' => $d['chart_confirmed_counts'],
                ],
            ],
            'labels' => $d['chart_labels'],
        ];
    }
}
