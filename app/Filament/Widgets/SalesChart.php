<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    // protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '180px';
    public ?string $filter = 'y';

    public function getHeading(): string
    {
        return __('incomeAndExpenses');
    }

    protected function getData(): array
    {
        $data = [];
        $invoices = Invoice::whereNotNull('paid_at')->oldest('paid_at')->get();
        foreach ($invoices as $i) {
            $d = Carbon::parse($i->paid_at);
            $x = match($this->filter) {
                'y' => $d->year,
                'q' => $d->year . ' Q' . $d->quarter,
                'm' => $d->year . ' ' . $d->locale(app()->getLocale())->shortMonthName,
            };
            if (!array_key_exists($x, $data)) {
                $data[$x] = $i->net;
            } else {
                $data[$x] += $i->net;
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'fill' => 'start',
                ],
            ],
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
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => value/1000 + ' k€',
                    },
                },
            },
            datasets: {
                line: {
                    pointRadius: 0,
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
