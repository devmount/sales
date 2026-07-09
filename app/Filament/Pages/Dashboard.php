<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Filament\Resources\ProjectResource\Widgets\CurrentProjects;
use App\Filament\Widgets as AppWidgets;
use App\Livewire\HeadingWidget;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'tabler-chart-pie';

    public function getColumns(): int|array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AppWidgets\StatsOverview::class,
            RecentPositionsChart::class,
            CurrentProjects::class,
            HeadingWidget::make(['heading' => __('sales'),]),
            AppWidgets\SalesChart::class,
            AppWidgets\MonthlyIncomeChart::class,
            AppWidgets\HourlyRateChart::class,
            HeadingWidget::make(['heading' => trans_choice('client', 2),]),
            AppWidgets\ClientProfitDistributionChart::class,
            AppWidgets\ClientProfitChart::class,
            HeadingWidget::make(['heading' => trans_choice('hour', 2),]),
            AppWidgets\SumProductiveHoursChart::class,
            AppWidgets\WeeklyHoursChart::class,
            AppWidgets\OfftimeChart::class,
        ];
    }
}
