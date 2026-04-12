<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, HasPublishStatus;

    protected $fillable = [
        'title',
        'slug',
        'headline',
        'summary',
        'story_overview',
        'science_overview',
        'status',
        'is_featured',
        'sort_order',
        'media_path',
        'media_url',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function specs(): HasMany
    {
        return $this->hasMany(MaterialSpec::class)->orderBy('sort_order')->orderBy('id');
    }

    public function storySections(): HasMany
    {
        return $this->hasMany(MaterialStorySection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MaterialApplication::class)->orderBy('sort_order')->orderBy('id');
    }
}
