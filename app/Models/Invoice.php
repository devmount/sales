<?php

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Net amount of all assigned positions
     */
    public function getNetAttribute()
    {
        $net = 0;
        if ($this->pricing_unit === PricingUnit::Project) {
            $net = $this->price;
        } else {
            foreach ($this->positions as $position) {
                $net += $position->duration * $this->price / match ($this->pricing_unit) {
                    PricingUnit::Hour => 1,
                    PricingUnit::Day => 8,
                };
            }
        }
        return $net - $this->discount;
    }

    /**
     * Gross amount of all assigned positions
     */
    public function getGrossAttribute()
    {
        return $this->taxable
            ? $this->net * ($this->vat+1)
            : $this->net;
    }

    /**
     * Final total amount of invoice
     */
    public function getFinalAttribute()
    {
        return $this->gross - $this->deduction;
    }
}
