<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        return round($this->price * $this->quantity, 2);
    }

    /**
     * Net amount of this expense
     */
    public function getNetAttribute()
    {
        $rate = $this->taxable ? $this->vat_rate : 0;
        return round($this->gross / (1 + $rate), 2);
    }

    /**
     * Vat amount of this expense
     */
    public function getVatAttribute()
    {
        return $this->gross - $this->net;
    }

    /**
     * Sum of net and vat amounts of given time range
     */
    public static function ofTime(Carbon $d, TimeUnit $u, ?ExpenseCategory $category = null): array
    {
        $start = match ($u) {
            TimeUnit::MONTH => $d->startOfMonth()->toDateString(),
            TimeUnit::QUARTER => $d->startOfQuarter()->toDateString(),
            TimeUnit::YEAR => $d->startOfYear()->toDateString(),
        };
        $end = match ($u) {
            TimeUnit::MONTH => $d->endOfMonth()->toDateString(),
            TimeUnit::QUARTER => $d->endOfQuarter()->toDateString(),
            TimeUnit::YEAR => $d->endOfYear()->toDateString(),
        };
        $categories = $category ? [$category] : ExpenseCategory::deliverableCategories();
        $records = self::where('expended_at', '>=', $start)
            ->where('expended_at', '<=', $end)
            ->whereIn('category', $categories)
            ->get();
        $net = array_sum($records->map(fn (self $r) => $r->net)->toArray());
        $vat = array_sum($records->map(fn (self $r) => $r->vat)->toArray());
        return [$net, $vat];
    }
}
