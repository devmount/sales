<?php

namespace App\Filament\Resources\PositionResource\Widgets;

use App\Models\Offtime;
use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class RecentPositionsChart extends ChartWidget
{
    public ?string $filter = '60';

    public int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 6,
    ];
    protected ?string $maxHeight = '180px';

    public function getHeading(): string
    {
        return __('productiveHoursPerProject');
    }

    public function getDescription(): string
    {
        return __('mostRecentWorkingHoursPerDay');
    }

    protected function getData(): array
    {
        $labels = [];
        $datasets = [];
        $period = CarbonPeriod::create(Carbon::now()->subDays((int) $this->filter), '1 day', 'now');
        $start = $period->getStartDate()->copy()->startOfDay();
        $end = now()->endOfDay();
        $positionsByDay = Position::with('invoice.project.client')
            ->whereBetween('started_at', [$start, $end])
            ->get()
            ->groupBy(fn(Position $p) => $p->started_at->format('Y-m-d'));
        $offtimes = Offtime::overlapping($start, $end)->get();
        foreach ($period as $i => $date) {
            // X-Axis labels
            $labels[] = $date->isoFormat('dd, D. MMM');

            // Positions per project
            foreach ($positionsByDay[$date->format('Y-m-d')] ?? [] as $p) {
                $project = $p->invoice->project;
                $datasets[$project->id] ??= [
                    'label' => $project->title,
                    'data' => array_fill(0, count($period), 0),
                    'backgroundColor' => $project->client->color,
                    'barPercentage' => 0.75,
                ];
                $datasets[$project->id]['data'][$i] += $p->duration;
            }

            // Also show times off
            $day = $date->format('Y-m-d');
            $offtime = $offtimes->first(
                fn(Offtime $o) => $o->start->format('Y-m-d') <= $day && ($o->end ?? $o->start)->format('Y-m-d') >= $day,
            );
            if ($offtime) {
                if (isset($datasets[$offtime->category->value])) {
                    $datasets[$offtime->category->value]['data'][$i] = 8;
                } else {
                    $datasets[$offtime->category->value] = [
                        'label' => $offtime->category->getLabel(),
                        'data' => array_fill(0, count($period), 0),
                        'borderColor' => $offtime->category->hexColor(),
                        'backgroundColor' => $offtime->category->hexColor() . '33',
                        'barPercentage' => 0.85,
                        'borderWidth' => 2,
                    ];
                    $datasets[$offtime->category->value]['data'][$i] = 8;
                }
            }
        }

        return [
            'datasets' => array_values($datasets),
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
            '30' => '30 ' . trans_choice('day', 2),
            '60' => '60 ' . trans_choice('day', 2),
            '90' => '90 ' . trans_choice('day', 2),
            '120' => '120 ' . trans_choice('day', 2),
            '185' => '185 ' . trans_choice('day', 2),
            '365' => '365 ' . trans_choice('day', 2),
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
                    intersect: true,
                    multiKeyBackground: '#000',
                    callbacks: {
                        label: (context) => ' ' + context.formattedValue + ' h ' + context.dataset.label,
                        labelColor: (context) => ({
                            borderWidth: 2,
                            borderColor: context.dataset.borderColor ?? context.dataset.backgroundColor,
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
                        callback: (value) => value + 'h',
                    },
                    stacked: true
                },
                x: {
                    stacked: true
                }
            },
            elements: {
                bar: {
                    borderWidth: 0
                }
            }
        }
        JS);
    }
}
