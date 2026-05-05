<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Models\Concerns\HasPublishStatus;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl, HasPublishStatus;

    protected array $localizedAttributes = [
        'title',
        'headline',
        'summary',
        'story_overview',
        'science_overview',
    ];

    protected $fillable = [
        'title',
        'title_translations',
        'slug',
        'headline',
        'headline_translations',
        'summary',
        'summary_translations',
        'story_overview',
        'story_overview_translations',
        'science_overview',
        'science_overview_translations',
        'certifications',
        'technical_downloads',
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
            'title_translations' => 'array',
            'headline_translations' => 'array',
            'summary_translations' => 'array',
            'story_overview_translations' => 'array',
            'science_overview_translations' => 'array',
            'certifications' => 'array',
            'technical_downloads' => 'array',
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
