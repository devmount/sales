<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_at',
        'amount',
        'subject',
        'name',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'amount'      => 'float',
            'subject'     => 'string',
            'name'        => 'string',
            'email'       => 'string',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    /**
     * Calculate array holding all years having gifts
     * sorted from current to past
     *
     * @return array<int>
     */
    public static function getYearList(): array
    {
        $firstDate = self::oldest('received_at')->first()?->received_at;
        $period = Carbon::parse($firstDate)->startOfYear()->yearsUntil(now());
        $years = array_reverse(
            iterator_to_array(
                $period->map(fn(Carbon $date) => $date->format('Y'))
            )
        );
        return array_combine($years, $years);
    }
}
