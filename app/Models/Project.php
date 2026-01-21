<?php

namespace App\Models;

use App\Enums\PricingUnit;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
     * Scope a query to only include active projects.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('start_at', '<=', now())->where('due_at', '>=', now())->where('aborted', false);
    }

    /**
     * Scope a query to only include upcoming projects.
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('start_at', '>', now())->where('aborted', false);
    }

    /**
     * Scope a query to only include finished projects.
     */
    #[Scope]
    protected function finished(Builder $query): void
    {
        $query->where('due_at', '<=', now())->where('aborted', false);
    }

    /**
     * Scope a query to only include aborted projects.
     */
    #[Scope]
    protected function aborted(Builder $query): void
    {
        $query->where('aborted', true);
    }

    /**
     * All assigned estimates sorted by weight
     */
    public function getSortedEstimatesAttribute()
    {
        return $this->estimates->sortBy('weight')->all();
    }


    /**
     * All sorted estimates split into chunks based on description lines count
     */
    public function getPaginatedEstimatesAttribute()
    {
        // Get estimates by page, one page has space for 50 lines (I know. Let me have my magic number here.)
        $paginated = [];
        $linesProcessed = 0;
        foreach ($this->sorted_estimates as $e) {
            // Take the description lines and the position title (2 lines) into account
            $lineCount = count(explode("\n", trim($e->description))) + 2;
            $linesProcessed += $lineCount;
            $i = floor($linesProcessed/50);
            if (key_exists($i,$paginated)) {
                $paginated[$i][] = $e;
            } else {
                $paginated[$i] = [$e];
            }
        }
        return $paginated;
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
            PricingUnit::Day => trans_choice('day', 1),
            PricingUnit::Project => trans_choice('project', 1),
        };
    }

    /**
     * Show current progress based on worked hours in relation to scope in percent
     */
    public function getProgressPercentAttribute()
    {
        return $this->scope > 0
            ? round($this->hours/$this->scope*100, 1) . ' %'
            : __('n/a');
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
