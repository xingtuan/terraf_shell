<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialStorySectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialStorySection extends Model
{
    /** @use HasFactory<MaterialStorySectionFactory> */
    use HasFactory, HasPublishStatus;

    protected $fillable = [
        'material_id',
        'title',
        'subtitle',
        'content',
        'highlight',
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
