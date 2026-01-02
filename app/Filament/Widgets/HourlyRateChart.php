<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class HourlyRateChart extends ChartWidget
{
    protected int | string | array $columnSpan = 4;
    protected ?string $maxHeight = '150px';
    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('hourlyRate');
    }

    public function getDescription(): string
    {
        return __('averageValuesPerYear');
    }

    protected function getData(): array
    {
        $invoices = Invoice::whereNotNull('paid_at')
            ->whereNot('transitory')
            ->oldest('paid_at')
            ->get();
        $period = Carbon::parse($invoices[0]->paid_at)->startOfYear()->yearsUntil(now()->addYear());
        $labels = iterator_to_array($period->map(fn(Carbon $date) => $date->format('Y')));
        array_pop($labels);
        $period = $period->toArray();
        $count = array_fill(0, count($period)-1, 0);
        $rates = array_fill(0, count($period)-1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            foreach ($invoices as $obj) {
                if (CarbonPeriod::create($date, $period[$i+1])->contains($obj->paid_at)) {
                    $rates[$i] += $obj->net;
                    $count[$i] += $obj->hours;
                }
            }
        }
        foreach ($rates as $i => $rate) {
            $rates[$i] = $count[$i] != 0 ? round($rate/$count[$i]) : 0;
        }

        return [
            'datasets' => [
                [
                    'data' => $rates,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f6',
                    'barPercentage' => 0.75,
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
                        label: (context) => ' ' + context.formattedValue + ' €/h',
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
                        callback: (value) => value + ' €',
                    },
                },
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
