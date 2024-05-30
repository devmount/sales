<?php

namespace App\Filament\Resources\PositionResource\Widgets;

use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class RecentPositionsChart extends ChartWidget
{
    protected int | string | array $columnSpan = 6;
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
                        'barPercentage' => 0.75,
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
            '30' => '30 ' . trans_choice('day', 2),
            '60' => '60 ' . trans_choice('day', 2),
            '90' => '90 ' . trans_choice('day', 2),
            '120' => '120 ' . trans_choice('day', 2),
            '365' => '365 ' . trans_choice('day', 2),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    intersect: true,
                    multiKeyBackground: '#000',
                    callbacks: {
                        label: (context) => ' ' + context.formattedValue + ' h ' + context.dataset.label,
                        labelColor: (context) => ({
                            borderWidth: 2,
                            borderColor: context.dataset.backgroundColor,
                            backgroundColor: context.dataset.backgroundColor + '33',
                        }),
                    },
                },
            },
            hover: {
                mode: 'index',
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
