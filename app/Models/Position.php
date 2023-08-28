<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
     * Total duration of the position
     */
    public function getDurationAttribute()
    {
        return Carbon::parse($this->started_at)->diffInMinutes(Carbon::parse($this->finished_at))/60 - $this->pause_duration;
    }

    /**
     * Total duration of the position
     */
    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->started_at)->isoFormat('lll')
            . Carbon::parse($this->finished_at)->format(' - H.i');
    }
}
