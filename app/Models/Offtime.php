<?php

namespace App\Models;

use App\Enums\OfftimeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

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
                $query->where('start', '>', $date->format('Y-m-d'))
                    ->where('end', '<', $date->format('Y-m-d'));
                })
            ->first();
    }


}
