<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Offtime;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class OfftimeChart extends ChartWidget
{
    protected ?string $maxHeight = '180px';
    public ?string $filter = 'y';
    protected ?string $pollingInterval = null;

    protected int | string | array $columnSpan = [
        'sm' => 12,
        'xl' => 6,
    ];

    public function getHeading(): string
    {
        return trans_choice('offtime', 2);
    }

    public function getDescription(): string
    {
        return __('daysWithoutWorkDuty');
    }

    protected function getData(): array
    {
        $invoices = Invoice::whereNotNull('paid_at')->whereNot('transitory')->oldest('paid_at')->get();
        $period = Carbon::parse($invoices->first()?->paid_at)->startOfYear()->yearsUntil(now()->addYear());
        $labels = iterator_to_array($period->map(fn(Carbon $date) => $date->format('Y')));
        array_pop($labels);
        $period = $period->toArray();

        $weekendData = array_fill(0, count($period)-1, 0);
        $plannedData = array_fill(0, count($period)-1, 0);
        $unplannedData = array_fill(0, count($period)-1, 0);
        $totalData = array_fill(0, count($period)-1, 0);

        foreach ($period as $i => $date) {
            if ($i == count($period)-1) break;
            [$w, $p, $u, $t] = Offtime::daysCountByYear(intval($date->format('Y')));
            $weekendData[$i] = $w;
            $plannedData[$i] = $p;
            $unplannedData[$i] = $u;
            $totalData[$i] = $t;
        }

        return [
            'datasets' => [
                [
                    'label' => __('weekendDays'),
                    'data' => $weekendData,
                    'fill' => 'start',
                    'backgroundColor' => '#3b82f622',
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => __('plannedDaysOff'),
                    'data' => $plannedData,
                    'fill' => 'start',
                    'backgroundColor' => '#9ae60022',
                    'borderColor' => '#9ae600',
                ],
                [
                    'label' => __('unplannedDaysOff'),
                    'data' => $unplannedData,
                    'fill' => 'start',
                    'backgroundColor' => '#ff205622',
                    'borderColor' => '#ff2056',
                ],
                [
                    'label' => __('totalDaysOff'),
                    'data' => $totalData,
                    'fill' => 'start',
                    'backgroundColor' => '#64748b22',
                    'borderColor' => '#64748b',
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
        return [];
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
                            borderColor: context.dataset.borderColor,
                            backgroundColor: context.dataset.borderColor + '33',
                        }),
                    },
                },
            },
            hover: {
                mode: 'index',
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
