<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ClientProfitDistributionChart extends ChartWidget
{
    protected ?string $maxHeight = '150px';
    public ?string $filter = '';
    protected ?string $pollingInterval = null;

    protected int | string | array $columnSpan = [
        'sm' => 12,
        'xl' => 4,
    ];

    public function getHeading(): string
    {
        return __('clientsProfitDistribution');
    }

    public function getDescription(): string
    {
        return __('sumNetPerYear');
    }

    protected function getData(): array
    {
        $year = $this->filter ? (int) $this->filter : now()->year;
        $from = Carbon::create($year, 1, 31, 12, 0, 0)->startOfYear();
        $to = Carbon::create($year, 1, 31, 12, 0, 0)->endOfYear();
        $invoices = Invoice::whereBetween('paid_at', [$from, $to])
            ->whereNot('transitory')
            ->get();
        $profit = [];
        foreach ($invoices as $obj) {
            $id = $obj->project->client->id;
            $profit[$id] = array_key_exists($id, $profit)
                ? $profit[$id] + $obj->net
                : $obj->net;
        }
        $sum = array_sum($profit);
        $labels = array_map(fn ($id, $p) => '(' . round($p/$sum*100, 1) . '%) ' . Client::find($id)->name, array_keys($profit), $profit);
        $colors = array_map(fn ($id) => Client::find($id)->color, array_keys($profit));

        return [
            'datasets' => [
                [
                    'data' => array_values($profit),
                    'borderColor' => $colors,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4
                ],
            ],
            'labels' => $labels
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return Invoice::getYearList();
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            borderWidth: 0,
            cutout: '60%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    multiKeyBackground: '#000',
                    callbacks: {
                        label: (context) => ' ' + context.formattedValue + ' €' + ' ' + context.label,
                        labelColor: (context) => ({
                            borderWidth: 2,
                            borderColor: context.dataset.borderColor[context.dataIndex],
                            backgroundColor: context.dataset.borderColor[context.dataIndex] + '33',
                        }),
                    },
                },
            },
            scales: {
                y: {
                    display: false,
                },
                x: {
                    display: false,
                }
            },
        }
        JS);
    }
}
