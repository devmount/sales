<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\TimeUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expended_at',
        'price',
        'taxable',
        'vat_rate',
        'quantity',
        'category',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'expended_at' => 'date',
            'price'       => 'float',
            'taxable'     => 'bool',
            'vat_rate'    => 'float',
            'quantity'    => 'int',
            'category'    => ExpenseCategory::class,
            'description' => 'string',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    /**
     * Serve year of expense
     */
    protected function year(): Attribute
    {
        return Attribute::make(fn(): int => intval(substr($this->expanded_at, 0, 4)));
    }

    /**
     * Gross amount of this expense
     */
    protected function gross(): Attribute
    {
        return Attribute::make(fn(): float => round($this->price * $this->quantity, 2));
    }

    /**
     * Net amount of this expense
     */
    protected function net(): Attribute
    {
        $rate = $this->taxable ? $this->vat_rate : 0;
        return Attribute::make(fn(): float => round($this->gross / (1 + $rate), 2));
    }

    /**
     * Vat amount of this expense
     */
    protected function vat(): Attribute
    {
        return Attribute::make(fn(): float => round($this->gross - $this->net, 2));
    }

    public static function lastAdvanceVatExists(): bool
    {
        $format = 'UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM');
        return self::where('description', $format)->first() !== null;
    }

    public static function saveLastAdvanceVat(): bool
    {
        [,, $vatIn] = Invoice::ofTime(now()->subMonth(), TimeUnit::MONTH);
        [, $vatOut] = self::ofTime(now()->subMonth(), TimeUnit::MONTH);
        $obj = new self([
            'expended_at' => now(),
            'category' => ExpenseCategory::Vat,
            'price' => $vatIn - $vatOut,
            'quantity' => 1,
            'taxable' => false,
            'vat_rate' => 0,
            'description' => 'UStVA ' . now()->year . '-' . now()->subMonth()->isoFormat('MM'),
        ]);
        return $obj->save();
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
