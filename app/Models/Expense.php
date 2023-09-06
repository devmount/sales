<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'category' => ExpenseCategory::class,
    ];

    /**
     * Serve year of expense
     */
    public function getYearAttribute()
    {
        return substr($this->expanded_at, 0, 4);
    }

    /**
     * Gross amount of this expense
     */
    public function getGrossAttribute()
    {
        return round($this->price * $this->quantity);
    }

    /**
     * Vat amount of this expense
     */
    public function getVatAttribute()
    {
        return $this->taxable
            ? round($this->gross * (1 - 1 / (1 + $this->vat_rate)), 2)
            : 0;
    }

    /**
     * Net amount of this expense
     */
    public function getNetAttribute()
    {
        return $this->gross - $this->vat;
    }
}
