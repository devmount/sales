<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasEmptyStateChart;
use App\Models\Gift;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class GiftSubjectDistributionChart extends ChartWidget
{
    use HasEmptyStateChart;
    public ?string $filter = 'all';

    protected ?string $maxHeight = '180px';
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 4,
    ];

    public function getHeading(): string
    {
        return __('sumGifts');
    }

    public function getDescription(): string
    {
        return __('sumAmountPerYear');
    }

    protected function getData(): array
    {
        if ($this->filter === 'all') {
            $gifts = Gift::all();
        } else {
            $year = $this->filter ? (int) $this->filter : now()->year;
            $from = Carbon::create($year, 1, 31, 12, 0, 0)->startOfYear();
            $to = Carbon::create($year, 1, 31, 12, 0, 0)->endOfYear();
            $gifts = Gift::whereBetween('received_at', [$from, $to])->get();
        }
        $amounts = [];
        foreach ($gifts as $obj) {
            $subject = $obj->subject ?? __('n/a');
            $amounts[$subject] = array_key_exists($subject, $amounts)
                ? $amounts[$subject] + $obj->amount
                : $obj->amount;
        }
        $sum = array_sum($amounts);
        $labels = array_map(fn($subject, $a) => '(' . round($a / $sum * 100, 1) . '%) ' . $subject, array_keys($amounts), $amounts);
        $colors = array_map(fn($subject) => self::colorForSubject($subject), array_keys($amounts));

        return [
            'datasets' => [
                [
                    'data' => array_values($amounts),
                    'borderColor' => $colors,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return ['all' => __('all')] + Gift::getYearList();
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

    /**
     * Deterministically derive a color from a subject name, so that
     * the same subject always gets the same slice color across renders.
     */
    private static function colorForSubject(string $subject): string
    {
        $hue = crc32($subject) % 360;
        return "hsl({$hue}, 65%, 55%)";
    }
}
