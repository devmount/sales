<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ClientProfitChart extends ChartWidget
{
    protected ?string $maxHeight = '180px';
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 4,
    ];

    public function getHeading(): string
    {
        return __('clientsProfit');
    }

    public function getDescription(): string
    {
        return __('sumNetPerYear');
    }

    protected function getData(): array
    {
        $invoices = Invoice::whereNotNull('paid_at')
            ->where('transitory', 0)
            ->oldest('paid_at')
            ->get();
        $startYear = Carbon::parse($invoices->first()?->paid_at)->year;
        $labels = range($startYear, now()->year);

        $profits = [];
        foreach ($invoices as $obj) {
            $clientId = $obj->project->client->id;
            $index = $obj->paid_at->year - $startYear;
            $profits[$clientId][$index] = ($profits[$clientId][$index] ?? 0) + $obj->net;
        }

        $datasets = [];
        foreach ($profits as $clientId => $yearly) {
            $client = Client::find($clientId);
            $datasets[] = [
                'label' => $client->name,
                'data' => array_map(fn($i) => $yearly[$i] ?? 0, array_keys($labels)),
                'fill' => 'start',
                'borderColor' => $client->color,
                'backgroundColor' => $client->color . '22',
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => array_map(fn($year) => (string) $year, $labels),
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                    filter: (context) => context.raw > 0,
                    itemSort: (a, b) => b.raw - a.raw,
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
