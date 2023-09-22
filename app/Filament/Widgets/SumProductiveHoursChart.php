<?php

namespace App\Filament\Widgets;

use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class SumProductiveHoursChart extends ChartWidget
{
    protected int | string | array $columnSpan = 6;
    protected static ?string $maxHeight = '180px';
    public ?string $filter = 'y';
    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('productiveHours');
    }

    protected function getData(): array
    {
        $positions = Position::oldest('started_at')->get();
        $start = $positions[0]->started_at;
        $period = match($this->filter) {
            'y' => Carbon::parse($start)->startOfYear()->yearsUntil(now()->addYear()),
            'q' => Carbon::parse($start)->startOfQuarter()->quartersUntil(now()->addQuarter()),
            'm' => Carbon::parse($start)->startOfMonth()->monthsUntil(now()->addMonth()),
        };
        $labels = iterator_to_array($period->map(fn(Carbon $date) => match($this->filter) {
            'y' => $date->format('Y'),
            'q' => $date->isoFormat('YYYY [Q]Q'),
            'm' => $date->isoFormat('YYYY MMM'),
        }));
        array_pop($labels);
        $period = $period->toArray();
        $hours = array_fill(0, count($period)-1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            foreach ($positions as $obj) {
                if (CarbonPeriod::create($date, $period[$i+1])->contains($obj->started_at)) {
                    $hours[$i] += $obj->duration;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => trans_choice('hour', 2),
                    'data' => $hours,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f622',
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'y' => __('perYear'),
            'q' => __('perQuarter'),
            'm' => __('perMonth'),
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
                    mode: 'index',
                    intersect: false,
                    multiKeyBackground: '#000',
                    callbacks: {
                        label: (context) => ' ' + context.formattedValue + ' h',
                        labelColor: (context) => ({
                            borderWidth: 2,
                            borderColor: context.dataset.borderColor,
                            backgroundColor: context.dataset.borderColor + '33',
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
                        callback: (value) => value + ' h',
                    },
                },
            },
            datasets: {
                line: {
                    pointRadius: 0,
                    pointHoverRadius: 0,
                }
            },
            elements: {
                line: {
                    borderWidth: 2,
                    tension: 0.15,
                }
            }
        }
        JS);
    }
}
