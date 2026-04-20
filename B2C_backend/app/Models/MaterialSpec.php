<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialSpecFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialSpec extends Model
{
    /** @use HasFactory<MaterialSpecFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'label',
        'value',
        'detail',
    ];

    protected $fillable = [
        'material_id',
        'key',
        'label',
        'label_translations',
        'value',
        'value_translations',
        'unit',
        'detail',
        'detail_translations',
        'icon',
        'status',
        'sort_order',
        'media_path',
        'media_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'label_translations' => 'array',
            'value_translations' => 'array',
            'detail_translations' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
