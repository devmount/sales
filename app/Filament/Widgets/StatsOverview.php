<?php

namespace App\Filament\Widgets;

use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Colors\Color;

use function Filament\Support\format_money;
use function Filament\Support\format_number;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        [$revenue, $hours] = $this->getData();
        $revenueStat = format_money($revenue[count($revenue)-1], 'eur');
        $revenueDiff = $revenue[count($revenue)-1] - $revenue[count($revenue)-2];
        $revenueIncrease = $revenueDiff >= 0;
        $revenueDiffPercent = round(abs($revenueDiff)/$revenue[count($revenue)-2]*100);
        $hoursStat = $hours[count($hours)-1];
        $hoursDiff = $hours[count($hours)-1] - $hours[count($hours)-2];
        $hoursIncrease = $hoursDiff >= 0;
        $hoursDiffPercent = round(abs($hoursDiff)/$hours[count($hours)-2]*100);
        return [
            Stat::make('weeklyRevenue', $revenueStat)
                ->label(__('weeklyRevenue'))
                ->description($revenueDiffPercent . '% ' . ($revenueIncrease ? __('increase') : __('decrease')))
                ->descriptionIcon($revenueIncrease ? 'tabler-trending-up': 'tabler-trending-down')
                ->chart($revenue)
                ->color($revenueIncrease ? Color::Blue : Color::Red),
            Stat::make('weeklyWorkingHours', format_number($hoursStat))
                ->label(__('weeklyWorkingHours'))
                ->description($hoursDiffPercent . '% ' . ($hoursIncrease ? __('increase') : __('decrease')))
                ->descriptionIcon($hoursIncrease ? 'tabler-trending-up': 'tabler-trending-down')
                ->chart($hours)
                ->color($hoursIncrease ? Color::Blue : Color::Red),
            Stat::make('info', filament()->getBrandName() . ' v' . config('app.version'))
                ->label('Filament ' . \Composer\InstalledVersions::getPrettyVersion('filament/filament'))
                ->description('Laravel ' . \Composer\InstalledVersions::getPrettyVersion('laravel/framework')),
        ];
    }

    protected function getData(): array
    {
        $positions = Position::where('started_at', '>', now()->subWeeks(7)->startOfWeek(Carbon::MONDAY))->get();
        $period = now()->subWeeks(8)->startOfWeek()->weeksUntil(now()->addWeek()->endOfWeek(Carbon::SUNDAY))->toArray();
        $revenue = array_fill(0, count($period)-1, 0);
        $hours = array_fill(0, count($period)-1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            foreach ($positions as $obj) {
                if (CarbonPeriod::create($date, $period[$i+1])->contains($obj->started_at)) {
                    $revenue[$i] += $obj->net;
                    $hours[$i] += $obj->duration;
                }
            }
        }

        return [$revenue, $hours];
    }
}
