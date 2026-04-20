<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\HomeSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    /** @use HasFactory<HomeSectionFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'title',
        'subtitle',
        'content',
        'cta_label',
    ];

    protected $fillable = [
        'key',
        'title',
        'title_translations',
        'subtitle',
        'subtitle_translations',
        'content',
        'content_translations',
        'cta_label',
        'cta_label_translations',
        'cta_url',
        'payload',
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
            'subtitle_translations' => 'array',
            'content_translations' => 'array',
            'cta_label_translations' => 'array',
            'payload' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
