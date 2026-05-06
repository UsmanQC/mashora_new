<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    /**
     * Single column on small screens; `lg` = 3 columns (charts 1+1+1); `xl` = 6 columns for 2:4 and 2:2:2 splits.
     *
     * @return int | array<string, int | null>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 3,
            'xl' => 6,
        ];
    }
}
