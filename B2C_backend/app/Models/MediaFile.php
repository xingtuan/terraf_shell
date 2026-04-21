<?php

namespace App\Models;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'url',
        'type',
        'mime_type',
        'size',
        'category',
        'fileable_type',
        'fileable_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => StorageUrl::resolve($attributes['path'] ?? null) ?? $value,
        );
    }

    /**
     * Get the user that uploaded the media file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning model for the media file.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
