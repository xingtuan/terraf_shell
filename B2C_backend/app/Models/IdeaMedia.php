<?php

namespace App\Models;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
use Database\Factories\IdeaMediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IdeaMedia extends Model
{
    /** @use HasFactory<IdeaMediaFactory> */
    use HasFactory;

    protected $table = 'idea_media';

    protected $fillable = [
        'post_id',
        'source_type',
        'media_type',
        'kind',
        'title',
        'alt_text',
        'disk',
        'original_name',
        'file_name',
        'extension',
        'mime_type',
        'size_bytes',
        'path',
        'url',
        'preview_url',
        'thumbnail_url',
        'external_url',
        'metadata',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $media): void {
            if ($media->sourceTypeValue() === IdeaMediaSourceType::ExternalUrl->value) {
                $media->disk = null;
                $media->original_name = null;
                $media->file_name = null;
                $media->extension = null;
                $media->mime_type = null;
                $media->size_bytes = null;
                $media->path = null;
                $media->media_type = IdeaMediaType::External3d->value;
                $media->kind = $media->kind ?: IdeaMediaKind::Model3d->value;
                $media->url = $media->external_url;
                $media->preview_url = null;
                $media->thumbnail_url = null;

                return;
            }

            if (blank($media->path)) {
                $media->url = null;
                $media->preview_url = null;
                $media->thumbnail_url = null;

                return;
            }

            $media->disk = $media->disk ?: (string) config('community.uploads.disk');
            $media->file_name = $media->file_name ?: basename($media->path);
            $media->extension = $media->extension ?: strtolower((string) pathinfo($media->file_name, PATHINFO_EXTENSION));
            $media->original_name = $media->original_name ?: $media->file_name;
            $media->media_type = $media->media_type ?: self::inferMediaTypeFromExtension($media->extension)->value;
            $media->kind = $media->kind ?: IdeaMediaKind::defaultForType($media->mediaType())->value;
            $media->url = Storage::disk($media->disk)->url($media->path);
            $media->preview_url = $media->isImage() ? ($media->preview_url ?: $media->url) : null;
            $media->thumbnail_url = $media->isImage() ? ($media->thumbnail_url ?: $media->url) : null;
        });

        static::deleting(function (self $media): void {
            if ($media->sourceTypeValue() !== IdeaMediaSourceType::Upload->value || blank($media->path)) {
                return;
            }

            Storage::disk($media->disk ?: (string) config('community.uploads.disk'))
                ->delete($media->path);
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function sourceTypeValue(): string
    {
        return (string) $this->source_type;
    }

    public function mediaType(): IdeaMediaType
    {
        return IdeaMediaType::tryFrom((string) $this->media_type) ?? IdeaMediaType::Document;
    }

    public function isImage(): bool
    {
        return $this->mediaType() === IdeaMediaType::Image;
    }

    public function isDocument(): bool
    {
        return $this->mediaType() === IdeaMediaType::Document;
    }

    public function isExternal(): bool
    {
        return $this->sourceTypeValue() === IdeaMediaSourceType::ExternalUrl->value;
    }

    public static function inferMediaTypeFromExtension(?string $extension): IdeaMediaType
    {
        $normalized = strtolower((string) $extension);

        if (in_array($normalized, config('community.idea_media.image_extensions', []), true)) {
            return IdeaMediaType::Image;
        }

        return IdeaMediaType::Document;
    }
}
