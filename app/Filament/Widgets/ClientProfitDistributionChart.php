<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ClientProfitDistributionChart extends ChartWidget
{
    // protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '185px';
    public ?string $filter = '';
    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('clientsProfitDistribution');
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
        $labels = array_map(fn ($p) => Client::find($p)->name, array_keys($profit));
        $colors = array_map(fn ($p) => Client::find($p)->color, array_keys($profit));

        return [
            'datasets' => [
                [
                    'label' => 'My First Dataset',
                    'data' => array_values($profit),
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
        $firstDate = Invoice::whereNotNull('paid_at')
            ->whereNot('transitory')
            ->oldest('paid_at')
            ->first()
            ->paid_at;
        $period = Carbon::parse($firstDate)->startOfYear()->yearsUntil(now());
        $years = array_reverse(iterator_to_array($period->map(fn(Carbon $date) => $date->format('Y'))));
        return array_combine($years, $years);
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            // plugins: {
            //     legend: {
            //         display: false
            //     },
            //     tooltip: {
            //         mode: 'index',
            //         intersect: false,
            //         multiKeyBackground: '#000',
            //         callbacks: {
            //             label: (context) => ' ' + context.formattedValue + ' €',
            //             labelColor: (context) => ({
            //                 borderWidth: 2,
            //                 borderColor: context.dataset.borderColor,
            //                 backgroundColor: context.dataset.borderColor + '33',
            //             }),
            //         },
            //     },
            // },
            // hover: {
            //     mode: 'index',
            // },
            // scales: {
            //     y: {
            //         ticks: {
            //             callback: (value) => value/1000 + ' k€',
            //         },
            //     },
            // },
            // datasets: {
            //     line: {
            //         pointRadius: 0,
            //         pointHoverRadius: 0,
            //     }
            // },
            // elements: {
            //     line: {
            //         borderWidth: 2,
            //         tension: 0.15,
            //     }
            // }
        }
        JS);
    }
}
