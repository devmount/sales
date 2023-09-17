<?php

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Position extends Model
{
    use HasFactory;

    /**
     * Get the invoice this position was made for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Total duration of the position in hours
     */
    public function getDurationAttribute()
    {
        return Carbon::parse($this->started_at)
            ->diffInMinutes(Carbon::parse($this->finished_at))/60 - $this->pause_duration;
    }

    /**
     * Total net of the position
     */
    public function getNetAttribute()
    {
        $net = 0;
        if ($this->invoice->pricing_unit === PricingUnit::Project) {
            $net = $this->invoice->hours/$this->invoice->net * $this->duration;
        } else {
            $net += $this->duration * $this->invoice->price / match ($this->invoice->pricing_unit) {
                PricingUnit::Hour => 1,
                PricingUnit::Day => 8,
            };
        }
        return round($net, 2);
    }

    /**
     * Human readable time range
     */
    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->started_at)->isoFormat('lll')
            . Carbon::parse($this->finished_at)->format(' - H.i');
    }
}
