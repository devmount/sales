<?php

namespace App\Models;

use App\Enums\OfftimeCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offtime extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'category' => OfftimeCategory::class,
        ];
    }

    /**
     * Year the time off is assigned to
     */
    public function getYearAttribute(): int
    {
        return intval($this->start->format('Y'));
    }

    /**
     * Year the time off is assigned to
     */
    public function getDaysCountAttribute(): int
    {
        return $this->end
            ? $this->start->diffInDays($this->end) + 1
            : 1;
    }

}
