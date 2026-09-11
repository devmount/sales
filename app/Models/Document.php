<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
    ];

    /**
     * Get the model this document is attached to.
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }

    protected function casts(): array
    {
        return [
            'size'       => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * File extension derived from the stored filename.
     */
    protected function extension(): Attribute
    {
        return Attribute::make(fn(): string => strtolower(pathinfo($this->filename, PATHINFO_EXTENSION)));
    }
}
