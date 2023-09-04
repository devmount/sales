<?php

namespace App\Filament\Resources\PositionResource\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RecentPositionsChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '180px';
    public ?string $filter = '60';

    public function getHeading(): string
    {
        return __('productiveHours');
    }

    protected function getData(): array
    {
        $labels = [];
        $datasets = [];
        $period = CarbonPeriod::create(Carbon::now()->subDays((int)$this->filter), '1 day', 'now');
        foreach ($period as $i => $date) {
            $labels[] = $date->isoFormat('dd, D. MMM');
            $positions = Position::where('started_at', 'like', $date->format('Y-m-d') . '%')->get();
            foreach ($positions as $p) {
                $project = $p->invoice->project;
                if (isset($datasets[$project->id])) {
                    if (isset($datasets[$project->id][$i])) {
                        $datasets[$project->id]['data'][$i] += $p->duration;
                    } else {
                        $datasets[$project->id]['data'][$i] = $p->duration;
                    }
                } else {
                    $datasets[$project->id] = [
                        'label' => $project->title,
                        'data' =>  array_fill(0, count($period), 0),
                        'backgroundColor' => $project->client->color,
                    ];
                    $datasets[$project->id]['data'][$i] = $p->duration;
                }
            }
        }
        return [
            'datasets' => array_values($datasets),
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
                    stacked: true
                },
                x: {
                    stacked: true
                }
            },
            elements: {
                bar: {
                    borderWidth: 0
                }
            }
        }
        JS);
    }
}
