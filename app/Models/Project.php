<?php

namespace App\Models;

use App\Enums\PricingUnit;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
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
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The estimates made for this project.
     */
    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    /**
     * The invoices created for this project.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Number of hours worked for this project
     */
    public function getHoursAttribute()
    {
        $hours = 0;
        foreach ($this->invoices as $invoice) {
            $hours += $invoice->hours;
        }
        return $hours;
    }

    /**
     * Labeled Number of hours
     */
    public function getHoursWithLabelAttribute()
    {
        return $this->hours . ' ' . trans_choice('hour', $this->hours);
    }

    /**
     * Show either scope or range from minimum to project limit in hours
     */
    public function getScopeRangeAttribute()
    {
        return $this->minimum != $this->scope
            ? (int)$this->minimum . ' - ' . (int)$this->scope . ' ' . trans_choice('hour', 2)
            : (int)$this->scope . ' ' . trans_choice('hour', (int)$this->scope);
    }

    /**
     * Show price per pricing unit
     */
    public function getPricePerUnitAttribute()
    {
        return $this->price . ' € / ' . match($this->pricing_unit) {
            PricingUnit::Hour => trans_choice('hour', 1),
            PricingUnit::Project => trans_choice('project', 1),
        };
    }

    /**
     * Show current progress based on worked hours in relation to scope in percent
     */
    public function getProgressPercentAttribute()
    {
        return round($this->hours/$this->scope*100, 1) . ' %';
    }

    /**
     * Number of hours estimated for this project
     */
    public function getEstimatedHoursAttribute()
    {
        $hours = 0;
        foreach ($this->estimates as $estimate) {
            $hours += $estimate->amount;
        }
        return $hours;
    }

    /**
     * Net amount of all assigned estimates
     */
    public function getEstimatedNetAttribute()
    {
        $net = 0;
        if ($this->pricing_unit === PricingUnit::Project) {
            $net = $this->price;
        } else {
            $net += $this->estimated_hours * $this->price / match ($this->pricing_unit) {
                PricingUnit::Hour => 1,
                PricingUnit::Day => 8,
            };
        }
        return round($net, 2);
    }

    /**
     * Vat amount of estimated net amount
     */
    public function getEstimatedVatAttribute()
    {
        return round($this->estimated_net * Setting::get('vatRate'), 2);
    }

    /**
     * Gross amount of all assigned estimates
     */
    public function getEstimatedGrossAttribute()
    {
        return $this->estimated_net + $this->estimated_vat;
    }
}
