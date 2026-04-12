<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Database\Factories\HomeSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    /** @use HasFactory<HomeSectionFactory> */
    use HasFactory, HasPublishStatus;

    protected $fillable = [
        'key',
        'title',
        'subtitle',
        'content',
        'cta_label',
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
            'payload' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
