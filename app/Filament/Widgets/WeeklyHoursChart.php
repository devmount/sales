<?php

namespace App\Filament\Widgets;

use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class WeeklyHoursChart extends ChartWidget
{
    protected int | string | array $columnSpan = 6;
    protected static ?string $maxHeight = '180px';
    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('averageWeeklyHours');
    }

    protected function getData(): array
    {
        $positions = Position::oldest('started_at')->get();
        $period = Carbon::parse($positions[0]->started_at)->startOfYear()->yearsUntil(now()->addYear());
        $labels = iterator_to_array($period->map(fn(Carbon $date) => $date->format('Y')));
        array_pop($labels);
        $period = $period->toArray();
        $countWeeks = array_fill(0, count($period)-1, 0);
        $avgWeeklyHours = array_fill(0, count($period)-1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            $weeks = [];
            $hours = 0;
            foreach ($positions as $obj) {
                if (CarbonPeriod::create($date, $period[$i+1])->contains($obj->started_at)) {
                    $weeks[Carbon::parse($obj->started_at)->isoWeek()] = 1;
                    $hours += $obj->duration;
                }
            }
            $countWeeks[$i] = count($weeks);
            $avgWeeklyHours[$i] = round($hours/count($weeks));
        }

        return [
            'datasets' => [
                [
                    'label' => __('hours/week'),
                    'data' => $avgWeeklyHours,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f6',
                    'barPercentage' => 0.75,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => __('weeksWithWorkingDays'),
                    'data' => $countWeeks,
                    'fill' => 'start',
                    'backgroundColor' => '#64748b',
                    'barPercentage' => 0.75,
                    'yAxisID' => 'y2',
                ],
            ],
            'labels' => $labels
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                        label: (context) => ' ' + context.formattedValue + ' ' + context.dataset.label,
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
                        callback: (value) => value + ' h',
                    },
                },
                y2: {
                    type: 'linear',
                    position: 'right',
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                }
            },
            elements: {
                bar: {
                    borderWidth: 0,
                }
            }
        }
        JS);
    }
}
