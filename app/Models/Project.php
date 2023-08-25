<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    /**
	 * Get the client that ordered the project.
	 */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
