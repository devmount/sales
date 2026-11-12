<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseCategory;
use App\Filament\Widgets\Concerns\GroupsByPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    use GroupsByPeriod;

    public ?string $filter = 'y';
    protected ?string $maxHeight = '180px';
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 5,
    ];

    public function getHeading(): string
    {
        return __('revenueAndExpenses');
    }

    public function getDescription(): string
    {
        return __('revenueExpensesAndTaxesNet');
    }

    protected function getData(): array
    {
        $invoices = Invoice::with('positions')
            ->whereNotNull('paid_at')
            ->where('transitory', 0)
            ->oldest('paid_at')
            ->get();
        $expenses = Expense::whereNotNull('expended_at')
            ->whereIn('category', ExpenseCategory::deliverableCategories())
            ->oldest('expended_at')
            ->get();
        $taxes = Expense::whereNotNull('expended_at')
            ->whereIn('category', ExpenseCategory::taxCategories())
            ->oldest('expended_at')
            ->get();
        $period = match ($this->filter) {
            'y' => Carbon::parse($invoices->first()?->paid_at)->startOfYear()->yearsUntil(now()->addYear()),
            'q' => Carbon::parse($invoices->first()?->paid_at)->startOfQuarter()->quartersUntil(now()->addQuarter()),
            'm' => Carbon::parse($invoices->first()?->paid_at)->startOfMonth()->monthsUntil(now()->addMonth()),
        };
        $labels = iterator_to_array($period->map(fn(Carbon $date) => match ($this->filter) {
            'y' => $date->format('Y'),
            'q' => $date->isoFormat('YYYY [Q]Q'),
            'm' => $date->isoFormat('YYYY MMM'),
        }));
        array_pop($labels);
        $period = $period->toArray();
        $invoiceData = array_fill(0, count($period) - 1, 0);
        $expenseData = array_fill(0, count($period) - 1, 0);
        $taxData = array_fill(0, count($period) - 1, 0);
        $index = $this->periodIndex(array_slice($period, 0, -1));
        foreach ($invoices as $obj) {
            if (isset($index[$key = $this->periodKey($obj->paid_at)])) {
                $invoiceData[$index[$key]] += $obj->net;
            }
        }
        foreach ($expenses as $obj) {
            if (isset($index[$key = $this->periodKey($obj->expended_at)])) {
                $expenseData[$index[$key]] += $obj->deductibleNet;
            }
        }
        foreach ($taxes as $obj) {
            if (isset($index[$key = $this->periodKey($obj->expended_at)])) {
                $taxData[$index[$key]] += $obj->net;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => __('revenue'),
                    'data' => $invoiceData,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f622',
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => trans_choice('expense', 2),
                    'data' => $expenseData,
                    'fill' => 'start',
                    'backgroundColor' => '#f43f5e22',
                    'borderColor' => '#f43f5e',
                ],
                [
                    'label' => __('taxes'),
                    'data' => $taxData,
                    'fill' => 'start',
                    'backgroundColor' => '#f9731622',
                    'borderColor' => '#f97316',
                ],
            ],
            'labels' => $labels,
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
            maintainAspectRatio: false,
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
