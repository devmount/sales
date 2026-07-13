<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'field';

    protected $fillable = [
        'field',
        'value',
        'type',
        'attributes',
        'weight',
    ];

    public static function get(string $field)
    {
        return self::find($field)?->value;
    }

    /**
     * Address field
     */
    public static function address()
    {
        $name = self::get('name');
        $street = self::get('street');
        $zip = self::get('zip');
        $city = self::get('city');
        return "$name, $street, $zip $city";
    }

    protected function casts(): array
    {
        return [
            'field'      => 'string',
            'value'      => 'string',
            'type'       => 'string',
            'attributes' => 'array',
            'weight'     => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Translated setting label
     */
    protected function label(): Attribute
    {
        return Attribute::make(fn(): string => __($this->field));
    }
}
