<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialApplication extends Model
{
    /** @use HasFactory<MaterialApplicationFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'title',
        'subtitle',
        'description',
        'audience',
        'cta_label',
    ];

    protected $fillable = [
        'material_id',
        'title',
        'title_translations',
        'subtitle',
        'subtitle_translations',
        'description',
        'description_translations',
        'audience',
        'audience_translations',
        'cta_label',
        'cta_label_translations',
        'cta_url',
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
            'description_translations' => 'array',
            'audience_translations' => 'array',
            'cta_label_translations' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
