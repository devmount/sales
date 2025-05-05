<?php

namespace App\Models;

use App\Enums\OfftimeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Offtime extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'category' => OfftimeCategory::class,
        ];
    }

    /**
     * Year the time off is assigned to
     */
    public function getYearAttribute(): int
    {
        return intval($this->start->format('Y'));
    }

    /**
     * Year the time off is assigned to
     */
    public function getDaysCountAttribute(): int
    {
        return $this->end
            ? $this->start->diffInDays($this->end) + 1
            : 1;
    }

    /**
     * Get a time off on a given date or null if none exists
     */
    public static function byDate(Carbon $date): ?self
    {
        return Offtime::where('start', $date->format('Y-m-d'))
            ->orWhere('end', $date->format('Y-m-d'))
            ->orWhere(function (Builder $query) use ($date) {
                $query->where('start', '<', $date)->where('end', '>', $date);
            })
            ->first();
    }

    /**
     * Calculate number of days of for a given year
     */
    public static function daysCountByYear(int $year): int
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);
        $offDays = collect();

        // Get number of weekend days
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekend()) {
                $offDays->put($date->format('Y-m-d'), true);
            }
        }

        // Get number of manual off times
        $records = Offtime::where('start', '>=', $start)->where('start', '<=', $end)->get();
        foreach ($records as $offtime) {
            if (!$offtime->end) {
                $offDays->put($offtime->start->format('Y-m-d'), true);
                continue;
            }
            foreach (CarbonPeriod::create($offtime->start, $offtime->end) as $date) {
                if ($date->year === $year) {
                    $offDays->put($date->format('Y-m-d'), true);
                }
            }
        }

        return $offDays->count();
    }


}
