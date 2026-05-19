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
        'disk',
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
            'disk' => 'string',
            'size' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => StorageUrl::resolve(
                $attributes['path'] ?? null,
                self::storageDiskFromAttributes($attributes),
            ) ?? StorageUrl::normalizePublicUrl($value),
        );
    }

    public function storageDisk(): string
    {
        return self::storageDiskFromAttributes($this->attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function storageDiskFromAttributes(array $attributes): string
    {
        $disk = trim((string) ($attributes['disk'] ?? ''));

        if ($disk !== '') {
            return $disk === 'local' ? 'public' : $disk;
        }

        $fallback = trim((string) config('community.uploads.disk', config('filesystems.default', 'public')));

        return $fallback !== '' && $fallback !== 'local' ? $fallback : 'public';
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
