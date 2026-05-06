<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Doctor;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Chart replaces legacy table; query matches Mashorapwa dashboard doctors stats.
 *
 * @see DashboardController::getStatsDoctors
 */
final class TopSellingDoctorsChartWidget extends ChartWidget
{
    protected static ?int $sort = -95;

    protected string $view = 'filament.widgets.top-selling-doctors-chart';

    protected static bool $isLazy = false;

    protected string $color = 'warning';

    protected ?string $maxHeight = '440px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 4,
    ];

    /** @var int|null Month 1–12; drives chart + query. */
    public ?int $month = null;

    public function mount(): void
    {
        $this->month ??= now()->month;

        parent::mount();
    }

    public function updatedMonth(): void
    {
        $this->cachedData = null;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $doctors = $this->doctorQuery()->get();

        if ($doctors->isEmpty()) {
            return [
                'labels' => [__('No doctors')],
                'datasets' => [
                    [
                        'label' => __('Total price'),
                        'data' => [0],
                        'xAxisID' => 'x',
                    ],
                    [
                        'label' => __('Number of appointments'),
                        'data' => [0],
                        'xAxisID' => 'x1',
                    ],
                ],
            ];
        }

        $labels = $doctors->map(fn (Doctor $d) => $d->name)->all();
        $revenue = $doctors->map(fn (Doctor $d) => round((float) ($d->revenue_month ?? 0), 2))->all();
        $counts = $doctors->map(fn (Doctor $d) => (int) ($d->appointments_month_count ?? 0))->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Total price'),
                    'data' => $revenue,
                    'xAxisID' => 'x',
                ],
                [
                    'label' => __('Number of appointments'),
                    'data' => $counts,
                    'xAxisID' => 'x1',
                ],
            ],
        ];
    }

    /**
     * Horizontal bars: doctor names on Y; price + appointment counts on separate X scales.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'position' => 'bottom',
                    'title' => [
                        'display' => true,
                        'text' => __('Total price'),
                    ],
                ],
                'x1' => [
                    'position' => 'top',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => __('Number of appointments'),
                    ],
                ],
            ],
        ];
    }

    protected function doctorQuery(): Builder
    {
        $month = (int) ($this->month ?? now()->month);

        return Doctor::query()
            ->where('status', 'approved')
            ->withSum([
                'appointments as revenue_month' => fn ($query) => $query
                    ->whereIn('status', ['new', 'in_process', 'completed'])
                    ->whereMonth('appointment_date', $month),
            ], 'total')
            ->withCount([
                'appointments as appointments_month_count' => fn ($query) => $query
                    ->whereIn('status', ['new', 'in_process', 'completed'])
                    ->whereMonth('appointment_date', $month),
            ])
            ->orderByDesc('appointments_month_count')
            ->limit(20);
    }
}
