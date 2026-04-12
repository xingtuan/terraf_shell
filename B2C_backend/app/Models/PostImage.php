<?php

namespace App\Models;

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
use Database\Factories\PostImageFactory;
use Illuminate\Database\Eloquent\Builder;

class PostImage extends IdeaMedia
{
    /** @use HasFactory<PostImageFactory> */
    protected $table = 'idea_media';

    protected $attributes = [
        'source_type' => IdeaMediaSourceType::Upload->value,
        'media_type' => IdeaMediaType::Image->value,
        'kind' => IdeaMediaKind::ConceptImage->value,
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('image_only', function (Builder $query): void {
            $query->where('media_type', IdeaMediaType::Image->value);
        });
    }
}
