<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
    public function label(): Attribute
    {
        return Attribute::make(fn() => __($this->field));
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
}
