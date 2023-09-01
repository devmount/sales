<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'type',
        'attributes',
        'sort',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    /**
     * Translated setting label
     */
    public function getLabelAttribute()
    {
        return __($this->key);
    }
}
