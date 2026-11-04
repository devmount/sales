<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

trait GroupsByPeriod
{
    /**
     * Key of the period (year, quarter or month, depending on the filter) a given date belongs to
     */
    protected function periodKey(Carbon $date): string
    {
        return match ($this->filter) {
            'y' => $date->format('Y'),
            'q' => $date->format('Y') . '-' . $date->quarter,
            'm' => $date->format('Y-m'),
        };
    }

    /**
     * Map of period keys to their dataset index for the given period start dates
     */
    protected function periodIndex(array $dates): array
    {
        return array_flip(array_map(fn(Carbon $date) => $this->periodKey($date), $dates));
    }
}
