<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'field';

    public $incrementing = false;

    protected $fillable = [
        'field',
        'value',
        'type',
        'attributes',
        'weight',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    public static function get(string $field) {
        return self::find($field)?->value;
    }

    /**
     * Translated setting label
     */
    public function getLabelAttribute()
    {
        return __($this->field);
    }
}
