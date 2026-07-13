<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasEmptyStateChart;
use App\Models\Gift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class SumGiftsChart extends ChartWidget
{
    use HasEmptyStateChart;
    public ?string $filter = 'y';

    protected ?string $maxHeight = '180px';
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 4,
    ];

    public function getHeading(): string
    {
        return __('sumGifts');
    }

    public function getDescription(): string
    {
        return __('temporalCourse');
    }

    protected function getData(): array
    {
        $gifts = Gift::oldest('received_at')->get();
        $start = $gifts->first()?->received_at;
        $period = match ($this->filter) {
            'y' => Carbon::parse($start)->startOfYear()->yearsUntil(now()->addYear()),
            'q' => Carbon::parse($start)->startOfQuarter()->quartersUntil(now()->addQuarter()),
            'm' => Carbon::parse($start)->startOfMonth()->monthsUntil(now()->addMonth()),
        };
        $labels = iterator_to_array($period->map(fn(Carbon $date) => match ($this->filter) {
            'y' => $date->format('Y'),
            'q' => $date->isoFormat('YYYY [Q]Q'),
            'm' => $date->isoFormat('YYYY MMM'),
        }));
        array_pop($labels);
        $period = $period->toArray();
        $amounts = array_fill(0, count($period) - 1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period) - 1) {
                break;
            }
            foreach ($gifts as $obj) {
                if (CarbonPeriod::create($date, $period[$i + 1])->contains($obj->received_at)) {
                    $amounts[$i] += $obj->amount;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $amounts,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f6',
                    'barPercentage' => 0.75,
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
            'y' => __('perYear'),
            'q' => __('perQuarter'),
            'm' => __('perMonth'),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            maintainAspectRatio: false,
            aspectRatio: 1.25,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    multiKeyBackground: '#000',
                    callbacks: {
                        label: (context) => ' ' + context.formattedValue + ' €',
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
