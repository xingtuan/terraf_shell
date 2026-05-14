<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected array $localizedAttributes = [
        'name',
        'description',
    ];

    protected $fillable = [
        'name',
        'name_translations',
        'description',
        'description_translations',
        'slug',
        'sort_order',
        'is_active',
        'is_demo_content',
        'seed_source',
        'seeded_at',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
            'description_translations' => 'array',
            'is_active' => 'boolean',
            'is_demo_content' => 'boolean',
            'seeded_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
