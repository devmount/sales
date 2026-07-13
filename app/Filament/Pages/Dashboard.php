<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Resources\PositionResource\Widgets\RecentPositionsChart;
use App\Filament\Resources\ProjectResource\Widgets\CurrentProjects;
use App\Filament\Widgets;
use App\Livewire\HeadingWidget;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-chart-pie';

    public function getColumns(): int|array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            Widgets\StatsOverview::class,
            RecentPositionsChart::class,
            CurrentProjects::class,
            HeadingWidget::make(['heading' => __('sales')]),
            Widgets\SalesChart::class,
            Widgets\MonthlyIncomeChart::class,
            Widgets\HourlyRateChart::class,
            HeadingWidget::make(['heading' => trans_choice('client', 2)]),
            Widgets\ClientProfitDistributionChart::class,
            Widgets\ClientProfitChart::class,
            Widgets\ClientHoursChart::class,
            HeadingWidget::make(['heading' => __('workingHours')]),
            Widgets\SumProductiveHoursChart::class,
            Widgets\WeeklyHoursChart::class,
            Widgets\OfftimeChart::class,
            HeadingWidget::make(['heading' => trans_choice('gift', 2)]),
            Widgets\GiftSubjectDistributionChart::class,
            Widgets\SumGiftsChart::class,
        ];
    }
}
