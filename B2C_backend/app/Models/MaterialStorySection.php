<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialStorySectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialStorySection extends Model
{
    /** @use HasFactory<MaterialStorySectionFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'title',
        'subtitle',
        'content',
        'highlight',
    ];

    protected $fillable = [
        'material_id',
        'title',
        'title_translations',
        'subtitle',
        'subtitle_translations',
        'content',
        'content_translations',
        'highlight',
        'highlight_translations',
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
            'highlight_translations' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
