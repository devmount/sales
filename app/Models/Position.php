<?php

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_at',
        'finished_at',
        'pause_duration',
        'description',
        'remote',
    ];

    /**
     * Get the invoice this position was made for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'started_at'     => 'datetime',
            'finished_at'    => 'datetime',
            'pause_duration' => 'float',
            'description'    => 'string',
            'remote'         => 'bool',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    /**
     * Total duration of the position in hours
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            fn(): float => Carbon::parse($this->started_at)
                ->diffInMinutes(Carbon::parse($this->finished_at)) / 60 - $this->pause_duration,
        );
    }

    /**
     * Total net of the position
     */
    protected function net(): Attribute
    {
        if (!$this->invoice) {
            return Attribute::make(fn(): float => 0.0);
        }

        $net = 0;
        if ($this->invoice->pricing_unit === PricingUnit::Project) {
            $net = $this->invoice->hours / $this->invoice->net * $this->duration;
        } else {
            $net += $this->duration * $this->invoice->price / $this->invoice->pricing_hours;
        }
        return Attribute::make(fn(): float => round($net, 2));
    }

    /**
     * Human readable time range
     */
    protected function timeRange(): Attribute
    {
        return Attribute::make(
            fn(): string => Carbon::parse($this->started_at)->isoFormat('lll')
                . Carbon::parse($this->finished_at)->format(' - H.i'),
        );
    }
}
