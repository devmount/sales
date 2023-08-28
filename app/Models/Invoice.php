<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    /**
     * Get the client that ordered the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
