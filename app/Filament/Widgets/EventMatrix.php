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

    protected ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.event-matrix-widget';

    protected int|string|array $columnSpan = 12;

    /**
     * Events and values per day for the selected time range and mode, keyed by date
     */
    protected function getDays(): array
    {
        $start = $this->year ? Carbon::create((int) $this->year)->startOfYear() : now()->subDays(364)->startOfDay();
        $end = $this->year ? $start->copy()->endOfYear() : now()->endOfDay();
        $period = CarbonPeriod::create($start, $end);

        // Initialize all days of the range
        $days = [];
        foreach ($period as $date) {
            $days[$date->format('Y-m-d')] = [
                'date' => $date->copy(),
                'value' => 0.0,
                'level' => 0,
                'negative' => false,
                'offtime' => null,
                'events' => [],
            ];
        }

        // Positions
        if (in_array($this->mode, ['total', 'earnings'])) {
            $positions = Position::with('invoice.project.client')
                ->whereBetween('started_at', [$start, $end])
                ->oldest('started_at')
                ->get();
            foreach ($positions as $p) {
                $key = $p->started_at->format('Y-m-d');
                $days[$key]['value'] += $p->net;
                $days[$key]['events'][] = $this->eventLine(
                    $p->invoice->project->client->color,
                    $p->invoice->project->title,
                    $p->net,
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
                $days[$key]['events'][] = $this->eventLine(
                    'var(--gray-400)',
                    $e->category->getLabel() . ($e->description ? ': ' . $e->description : ''),
                    -$e->net,
                );
            }
        }

        // Times off
        if (in_array($this->mode, ['total', 'offtimes'])) {
            $offtimes = Offtime::where('start', '<=', $end->toDateString())
                ->where(fn($q) => $q->where('end', '>=', $start->toDateString())
                    ->orWhere(fn($q) => $q->whereNull('end')->where('start', '>=', $start->toDateString())))
                ->get();
            foreach ($offtimes as $o) {
                foreach (CarbonPeriod::create($o->start, $o->end ?? $o->start) as $date) {
                    $key = $date->format('Y-m-d');
                    if (!isset($days[$key])) {
                        continue;
                    }
                    $days[$key]['offtime'] = $o->category;
                    if ($this->mode === 'offtimes') {
                        $days[$key]['value'] = 1.0;
                    }
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
            $days[$key]['tooltip'] = implode('<br>', [
                '<b>' . $d['date']->isoFormat('dd, D. MMM YYYY') . '</b>',
                ...$d['events'],
            ]);
            unset($days[$key]['events']);
        }

        return $days;
    }

    /**
     * Single tooltip line for an event with colored dot, label and optional amount
     */
    private function eventLine(string $color, string $label, ?float $amount = null): string
    {
        $line = '<span style="color:' . e($color) . '">●</span> ' . e($label);
        if ($amount !== null) {
            $line .= ' (' . Number::currency($amount, 'EUR', locale: app()->getLocale()) . ')';
        }
        return $line;
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
            'offset' => reset($days)['date']->isoWeekday() - 1,
            'modes' => [
                'total' => __('total'),
                'earnings' => __('earnings'),
                'expenses' => trans_choice('expense', 2),
                'taxes' => __('taxes'),
                'offtimes' => trans_choice('offtime', 2),
            ],
            'years' => ['' => __('last365Days')] + array_combine(
                range(now()->year, $firstYear),
                range(now()->year, $firstYear),
            ),
        ];
    }
}
