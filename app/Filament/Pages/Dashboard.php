<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\PositionResource\Widgets as ResourceWidgets;
use App\Filament\Widgets as AppWidgets;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'tabler-chart-pie';

    public function getColumns(): int | string | array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AppWidgets\StatsOverview::class,
            ResourceWidgets\RecentPositionsChart::class,
            AppWidgets\SalesChart::class,
            AppWidgets\TaxOverview::class,
            AppWidgets\ClientProfitDistributionChart::class,
            AppWidgets\HourlyRateChart::class,
            AppWidgets\MonthlyIncomeChart::class,
            AppWidgets\SumProductiveHoursChart::class,
            AppWidgets\WeeklyHoursChart::class,
        ];
    }
}
