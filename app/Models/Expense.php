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
        return substr($this->expanded_at, 0 ,4);
    }
}
