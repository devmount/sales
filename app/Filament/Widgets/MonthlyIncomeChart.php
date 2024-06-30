<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class MonthlyIncomeChart extends ChartWidget
{
    protected int | string | array $columnSpan = 4;
    public ?string $filter = 'net';
    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('monthlyIncome');
    }

    protected function getData(): array
    {
        $invoices = Invoice::whereNotNull('paid_at')
            ->whereNot('transitory')
            ->oldest('paid_at')
            ->get();
        $taxes = Expense::whereNotNull('expended_at')
            ->whereIn('category', ['vat', 'tax'])
            ->oldest('expended_at')
            ->get();
        $period = Carbon::parse($invoices[0]->paid_at)->startOfYear()->yearsUntil(now()->addYear());
        $labels = iterator_to_array($period->map(fn(Carbon $date) => $date->format('Y')));
        array_pop($labels);
        $period = $period->toArray();
        $invoiceData = array_fill(0, count($period)-1, 0);
        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            foreach ($invoices as $obj) {
                if (CarbonPeriod::create($date, $period[$i+1])->contains($obj->paid_at)) {
                    $invoiceData[$i] += match($this->filter) {
                        'net' => $obj->net,
                        'gross' => $obj->gross,
                    };
                }
            }
            foreach ($taxes as $obj) {
                if (
                    CarbonPeriod::create($date, $period[$i+1])->contains(Carbon::parse($obj->expended_at)->subYear()) &&
                    $this->filter === 'net'
                ) {
                    $invoiceData[$i] -= $obj->net;
                }
            }
            $invoiceData[$i] = round($invoiceData[$i]/($i == count($period)-2 ? now()->month : 12), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => match($this->filter) {
                        'net' => __('netIncome'),
                        'gross' => __('grossIncome'),
                    },
                    'data' => $invoiceData,
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
            'net' => __('net'),
            'gross' => __('gross'),
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
                        label: (context) => ' ' + context.formattedValue + ' € ' + context.dataset.label,
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
                        callback: (value) => value/1000 + ' k€',
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
