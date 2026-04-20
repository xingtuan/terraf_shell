<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'title',
        'excerpt',
        'content',
        'category',
    ];

    protected $fillable = [
        'title',
        'title_translations',
        'slug',
        'excerpt',
        'excerpt_translations',
        'content',
        'content_translations',
        'category',
        'category_translations',
        'status',
        'sort_order',
        'media_path',
        'media_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'excerpt_translations' => 'array',
            'content_translations' => 'array',
            'category_translations' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
