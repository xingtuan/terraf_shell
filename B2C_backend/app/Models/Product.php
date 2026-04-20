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

    public const CATEGORY_OPTIONS = [
        'tableware' => 'Tableware',
        'planters' => 'Planters',
        'wellness_interior' => 'Wellness & Interior',
        'architectural' => 'Architectural',
    ];

    public const MODEL_OPTIONS = [
        'lite_15' => '1.5 Lite',
        'heritage_16' => '1.6 Heritage',
    ];

    public const FINISH_OPTIONS = [
        'glossy' => 'Glossy',
        'matte' => 'Matte',
    ];

    public const COLOR_OPTIONS = [
        'ocean_bone' => 'Ocean Bone',
        'forged_ash' => 'Forged Ash',
    ];

    public const TECHNIQUE_OPTIONS = [
        'original_pure' => 'Original Pure',
        'precision_inlay' => 'Precision Inlay',
        'driftwood_blend' => 'Driftwood Blend',
    ];

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
        'category',
        'model',
        'finish',
        'color',
        'technique',
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
        'image_url',
        'price_from',
        'price_usd',
        'currency',
        'in_stock',
        'is_active',
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
            'price_usd' => 'decimal:2',
            'in_stock' => 'boolean',
            'is_active' => 'boolean',
            'inquiry_only' => 'boolean',
            'sample_request_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if (filled($product->category) && isset(self::CATEGORY_OPTIONS[$product->category])) {
                $category = ProductCategory::query()->firstOrCreate(
                    ['slug' => $product->category],
                    [
                        'name' => self::CATEGORY_OPTIONS[$product->category],
                        'description' => self::CATEGORY_OPTIONS[$product->category].' category for the Shellfin catalog.',
                        'is_active' => true,
                    ],
                );

                $product->category_id = $category->id;
            }

            if ($product->price_usd !== null) {
                $product->price_from = $product->price_usd;
                $product->currency = 'USD';
            }

            if (filled($product->image_url) && blank($product->media_url)) {
                $product->media_url = $product->image_url;
            }

            if (filled($product->media_url) && blank($product->image_url)) {
                $product->image_url = $product->media_url;
            }

            if ($product->is_active) {
                $product->status = ProductStatus::Published->value;
            } elseif (blank($product->status) || $product->status === ProductStatus::Published->value) {
                $product->status = ProductStatus::Archived->value;
            }

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
