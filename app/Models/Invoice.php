<?php

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pricing_unit' => PricingUnit::class,
    ];

    /**
     * Get the client that ordered the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The positions of this project.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * Number of hours worked for this invoice
     */
    public function getHoursAttribute()
    {
        $hours = 0;
        foreach ($this->positions as $position) {
            $hours += $position->duration;
        }
        return $hours;
    }

    /**
     * Number of hours worked for this invoice formatted
     */
    public function getHoursFormattedAttribute()
    {
        return $this->hours . ' ' . trans_choice('hour', $this->hours);
    }

    /**
     * Net amount of all assigned positions
     */
    public function getNetAttribute()
    {
        $net = 0;
        if ($this->pricing_unit === PricingUnit::Project) {
            $net = $this->price;
        } else {
            $net += $this->hours * $this->price / match ($this->pricing_unit) {
                PricingUnit::Hour => 1,
                PricingUnit::Day => 8,
            };
        }
        return round($net, 2) - $this->discount;
    }

    /**
     * Net amount of all assigned positions formatted
     */
    public function getNetFormattedAttribute()
    {
        return Number::currency($this->net, 'eur');
    }

    /**
     * Vat amount of current net amount
     */
    public function getVatAttribute()
    {
        return round($this->net * $this->vat_rate, 2);
    }

    /**
     * Gross amount of all assigned positions
     */
    public function getGrossAttribute()
    {
        return $this->taxable
            ? $this->net + $this->vat
            : $this->net;
    }

    /**
     * Final total amount of invoice
     */
    public function getFinalAttribute()
    {
        return $this->gross - $this->deduction;
    }

    /**
     * Calculate the current invoice number of format YYYYMMDD##ID
     */
    public function getCurrentNumberAttribute()
    {
        return now()->format('Ymd') . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}
