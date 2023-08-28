<?php

namespace App\Models;

use App\Enums\PricingUnit;
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
}
