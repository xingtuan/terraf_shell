<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasLocalizedAttributes, HasOptionalMediaUrl;

    protected array $localizedAttributes = [
        'name',
        'short_description',
        'full_description',
        'features' => 'array',
        'availability_text',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'name_translations',
        'short_description',
        'short_description_translations',
        'full_description',
        'full_description_translations',
        'features',
        'features_translations',
        'availability_text',
        'availability_text_translations',
        'slug',
        'status',
        'featured',
        'sort_order',
        'media_path',
        'media_url',
        'price_from',
        'currency',
        'inquiry_only',
        'sample_request_enabled',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
            'short_description_translations' => 'array',
            'full_description_translations' => 'array',
            'features' => 'array',
            'features_translations' => 'array',
            'availability_text_translations' => 'array',
            'featured' => 'boolean',
            'price_from' => 'decimal:2',
            'inquiry_only' => 'boolean',
            'sample_request_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if ($product->status === ProductStatus::Published->value && blank($product->published_at)) {
                $product->published_at = now();
            }

            if ($product->status !== ProductStatus::Published->value) {
                $product->published_at = null;
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published->value;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->orderBy('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
