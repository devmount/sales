<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Offtime;
use App\Models\Position;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class EventMatrix extends Widget
{
    public string $year = '';
    public string $mode = 'total';
    public bool $offtimes = true;

    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.event-matrix-widget';

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 8,
    ];

    /**
     * Events and values per day for the selected time range and mode, keyed by date
     */
    protected function getDays(): array
    {
        $start = $this->year ? Carbon::create((int) $this->year)->startOfYear() : now()->subDays(364)->startOfDay();
        $end = $this->year ? $start->copy()->endOfYear() : now()->endOfDay();
        $period = CarbonPeriod::create($start, $end);

        // Initialize all days of the range, placed in grid columns per week (after the weekday labels)
        // and rows per weekday (after the month labels)
        $days = [];
        $offset = $start->isoWeekday() - 1;
        foreach ($period as $i => $date) {
            $days[$date->format('Y-m-d')] = [
                'date' => $date->copy(),
                'col' => intdiv($offset + $i, 7) + 2,
                'row' => $date->isoWeekday() + 1,
                'value' => 0.0,
                'level' => 0,
                'negative' => false,
                'offtime' => null,
                'events' => [],
                'amounts' => 0,
            ];
        }

        // Positions
        if (in_array($this->mode, ['total', 'earnings', 'hours'])) {
            $positions = Position::with('invoice.project.client')
                ->whereBetween('started_at', [$start, $end])
                ->oldest('started_at')
                ->get();
            foreach ($positions as $p) {
                $key = $p->started_at->format('Y-m-d');
                $value = $this->mode === 'hours' ? $p->duration : $p->net;
                $days[$key]['value'] += $value;
                $days[$key]['amounts']++;
                $days[$key]['events'][] = $this->eventLine(
                    $p->invoice->project->client->color,
                    $p->invoice->project->title,
                    $value,
                );
            }
        }

        // Expenses
        if (in_array($this->mode, ['total', 'expenses', 'taxes'])) {
            $expenses = Expense::whereBetween('expended_at', [$start->toDateString(), $end->toDateString()])->get();
            foreach ($expenses as $e) {
                if ($e->category->isTax() !== ($this->mode === 'taxes')) {
                    continue;
                }
                $key = $e->expended_at->format('Y-m-d');
                $days[$key]['value'] += $this->mode === 'total' ? -$e->net : $e->net;
                $days[$key]['amounts']++;
                $days[$key]['events'][] = $this->eventLine(
                    $e->category->isTax() ? '#f97316' : '#f43f5e',
                    $e->category->getLabel() . ($e->description ? ': ' . $e->description : ''),
                    -$e->net,
                );
            }
        }

        // Times off
        if ($this->offtimes) {
            $offtimes = Offtime::overlapping($start, $end)->get();
            foreach ($offtimes as $o) {
                foreach (CarbonPeriod::create($o->start, $o->end ?? $o->start) as $date) {
                    $key = $date->format('Y-m-d');
                    if (!isset($days[$key])) {
                        continue;
                    }
                    $days[$key]['offtime'] = $o->category;
                    $days[$key]['events'][] = $this->eventLine(
                        $o->category->hexColor(),
                        $o->category->getLabel() . ($o->description ? ': ' . $o->description : ''),
                    );
                }
            }
        }

        // Intensity levels and tooltips
        $max = max(array_map(fn($d) => abs($d['value']), $days)) ?: 1;
        foreach ($days as $key => $d) {
            $days[$key]['value'] = round($d['value'], 2);
            $days[$key]['level'] = (int) ceil(abs($d['value']) / $max * 4);
            $days[$key]['negative'] = $d['value'] < 0;
            if ($d['amounts'] > 1) {
                $sum = in_array($this->mode, ['expenses', 'taxes']) ? -$d['value'] : $d['value'];
                $d['events'][] = $this->eventLine('var(--gray-400)', __('total'), $sum);
            }
            $days[$key]['tooltip'] = implode('<br>', [
                '<b>' . $d['date']->isoFormat('dd, D. MMM YYYY') . '</b>',
                ...$d['events'],
            ]);
            unset($days[$key]['events'], $days[$key]['amounts']);
        }

        return $days;
    }

    /**
     * Single tooltip line for an event with colored dot, label and optional amount (hours in hours mode)
     */
    private function eventLine(string $color, string $label, ?float $amount = null): string
    {
        $line = '<span style="color:' . e($color) . '">●</span> ' . e($label);
        if ($amount !== null) {
            $line .= ' (' . ($this->mode === 'hours'
                ? Number::format($amount, maxPrecision: 2, locale: app()->getLocale()) . ' ' . trans_choice('hour', $amount)
                : Number::currency($amount, 'EUR', locale: app()->getLocale())
            ) . ')';
        }
        return $line;
    }

    /**
     * Month labels keyed by the grid column of the week the month starts in
     */
    protected function getMonths(array $days): array
    {
        $months = [];
        foreach ($days as $i => $d) {
            // Also label a partial first month, if there is enough room before the next one
            if ($d['date']->day === 1 || ($i === array_key_first($days) && $d['date']->day <= 14)) {
                $months[$d['col']] = $d['date']->isoFormat('MMM');
            }
        }
        return $months;
    }

    protected function getViewData(): array
    {
        $days = $this->getDays();
        $firstYear = Position::min('started_at');
        $firstYear = $firstYear ? Carbon::parse($firstYear)->year : now()->year;

        return [
            'heading' => __('eventMatrix'),
            'description' => __('eventMatrixDescription'),
            'days' => $days,
            'months' => $this->getMonths($days),
            'lastCol' => end($days)['col'],
            'weekdays' => collect([1, 3, 5])->mapWithKeys(fn($d) => [$d + 1 => rtrim(now()->isoWeekday($d)->isoFormat('ddd'), '.')]),
            'modes' => [
                'total' => __('profit'),
                'earnings' => __('earnings'),
                'hours' => trans_choice('hour', 2),
                'expenses' => trans_choice('expense', 2),
                'taxes' => __('taxes'),
            ],
            'years' => ['' => __('last365Days')] + array_combine(
                range(now()->year, $firstYear),
                range(now()->year, $firstYear),
            ),
        ];
    }
}
