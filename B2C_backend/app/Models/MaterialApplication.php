<?php

namespace App\Models;

use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialApplication extends Model
{
    /** @use HasFactory<MaterialApplicationFactory> */
    use HasFactory, HasOptionalMediaUrl, HasPublishStatus;

    protected $fillable = [
        'material_id',
        'title',
        'subtitle',
        'description',
        'audience',
        'cta_label',
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
            'published_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
