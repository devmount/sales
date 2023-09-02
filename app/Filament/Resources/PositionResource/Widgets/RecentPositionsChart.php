<?php

namespace App\Filament\Resources\PositionResource\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RecentPositionsChart extends ChartWidget
{
    // protected static ?string $heading = 'chart';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '300px';
    public ?string $filter = '30';

        public function getHeading(): string
        {
            return __('productiveHours');
        }

    protected function getData(): array
    {
        $labels = [];
        $data = [];
        $period = CarbonPeriod::create(Carbon::now()->subDays((int)$this->filter), '1 day', 'now');
        foreach ($period as $date) {
            $labels[] = $date->format('m-d');
            $positions = Position::where('started_at', 'like', $date->format('Y-m-d') . '%')->get();
            $data[] = array_sum($positions->map(fn ($p) => $p->duration)->toArray());
        }
        return [
            'datasets' => [
                [
                    'label' => __('productiveHours'),
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            '30' => '30 ' . __('days'),
            '60' => '60 ' . __('days'),
            '90' => '90 ' . __('days'),
            '120' => '120 ' . __('days'),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => value + 'h',
                    },
                },
            },
        }
    JS);
    }
}
