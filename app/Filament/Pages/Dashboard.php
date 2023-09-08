<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'tabler-chart-pie';

    public function getColumns(): int | string | array
    {
        return 12;
    }
}
