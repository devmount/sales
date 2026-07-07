<?php

namespace App\Models;

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
            'received_at' => 'datetime',
            'amount'      => 'float',
            'subject'     => 'string',
            'name'        => 'string',
            'email'       => 'string',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }
}
