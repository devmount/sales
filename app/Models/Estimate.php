<?php

namespace App\Models;

use App\Enums\PricingUnit;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'weight',
    ];

    /**
     * Get the client that ordered the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function casts(): array
    {
        return [
            'title'       => 'string',
            'description' => 'string',
            'amount'      => 'float',
            'weight'      => 'int',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    /**
     * Total net of the estimate
     */
    protected function net(): Attribute
    {
        if (!$this->project) {
            return Attribute::make(fn(): float => 0.0);
        }

        $net = 0;
        if ($this->project->pricing_unit === PricingUnit::Project) {
            $net = $this->project->estimated_net / $this->project->estimated_hours * $this->amount;
        } else {
            $net += $this->amount * $this->project->price / $this->project->pricing_hours;
        }
        return Attribute::make(fn(): float => round($net, 2));
    }
}
