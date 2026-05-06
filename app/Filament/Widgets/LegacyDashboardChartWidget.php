<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Mashorapwa-style chart card: title left, green badge + “This week” on the right (afterHeader).
 */
abstract class LegacyDashboardChartWidget extends ChartWidget
{
    protected string $view = 'filament.widgets.legacy-chart-widget';

    abstract public function badgePrimary(): string;

    public function getDescription(): string|Htmlable|null
    {
        return null;
    }
}
