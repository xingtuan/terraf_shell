<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasLocalizedAttributes;
use App\Models\Concerns\HasOptionalMediaUrl;
use App\Support\StorageUrl;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public const STOCK_STATUS_OPTIONS = [
        'in_stock' => 'In Stock',
        'low_stock' => 'Low Stock',
        'preorder' => 'Pre-order',
        'made_to_order' => 'Made to Order',
        'sold_out' => 'Sold Out',
    ];

    public const USE_CASE_OPTIONS = [
        'home_dining' => 'Home Dining',
        'hospitality_service' => 'Hospitality Service',
        'retail_gifting' => 'Retail & Gifting',
        'interior_styling' => 'Interior Styling',
        'design_projects' => 'Design Projects',
    ];

    public const PURCHASABLE_STOCK_STATUSES = [
        'in_stock',
        'low_stock',
        'preorder',
        'made_to_order',
    ];

    protected array $localizedAttributes = [
        'name',
        'subtitle',
        'short_description',
        'full_description',
        'features' => 'array',
        'availability_text',
        'lead_time',
        'dimensions',
        'certifications' => 'array',
        'care_instructions' => 'array',
        'material_benefits' => 'array',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'name_translations',
        'subtitle',
        'subtitle_translations',
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
        'sku',
        'status',
        'featured',
        'is_bestseller',
        'is_new',
        'sort_order',
        'media_path',
        'media_url',
        'image_url',
        'price_from',
        'price_usd',
        'compare_at_price_usd',
        'currency',
        'in_stock',
        'stock_quantity',
        'stock_status',
        'is_active',
        'inquiry_only',
        'sample_request_enabled',
        'lead_time',
        'lead_time_translations',
        'dimensions',
        'dimensions_translations',
        'weight_grams',
        'specifications',
        'certifications',
        'certifications_translations',
        'care_instructions',
        'care_instructions_translations',
        'material_benefits',
        'material_benefits_translations',
        'use_cases',
        'seo_title',
        'seo_title_translations',
        'seo_description',
        'seo_description_translations',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
            'subtitle_translations' => 'array',
            'short_description_translations' => 'array',
            'full_description_translations' => 'array',
            'features' => 'array',
            'features_translations' => 'array',
            'availability_text_translations' => 'array',
            'lead_time_translations' => 'array',
            'dimensions_translations' => 'array',
            'specifications' => 'array',
            'certifications' => 'array',
            'certifications_translations' => 'array',
            'care_instructions' => 'array',
            'care_instructions_translations' => 'array',
            'material_benefits' => 'array',
            'material_benefits_translations' => 'array',
            'use_cases' => 'array',
            'seo_title_translations' => 'array',
            'seo_description_translations' => 'array',
            'featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
            'price_from' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'compare_at_price_usd' => 'decimal:2',
            'in_stock' => 'boolean',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'inquiry_only' => 'boolean',
            'sample_request_enabled' => 'boolean',
            'weight_grams' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            $category = null;
            $categoryFromRelation = null;
            $resolvedCategory = self::normalizeCategoryValue($product->category);

            if (filled($product->category_id)) {
                $category = ProductCategory::query()->find($product->category_id);
                $categoryFromRelation = self::normalizeCategoryValue($category?->slug);

                if ($product->isDirty('category_id') && ($categoryFromRelation !== null)) {
                    $resolvedCategory = $categoryFromRelation;
                } else {
                    $resolvedCategory ??= $categoryFromRelation;
                }
            }

            if (($category === null) && filled($product->category)) {
                $categorySlug = $resolvedCategory ?? Str::slug((string) $product->category);
                $categoryLabel = self::labelForOption(self::CATEGORY_OPTIONS, $resolvedCategory ?? $categorySlug)
                    ?? Str::headline((string) $product->category);

                $category = ProductCategory::query()->firstOrCreate(
                    ['slug' => $categorySlug],
                    [
                        'name' => $categoryLabel,
                        'description' => $categoryLabel.' category for the OXP catalog.',
                        'is_active' => true,
                    ],
                );
            }

            if ($category !== null) {
                $product->category_id = $category->id;
                $categoryFromRelation ??= self::normalizeCategoryValue($category->slug);
            }

            $product->category = $resolvedCategory
                ?? $categoryFromRelation
                ?? 'tableware';

            if (blank($product->sku) && filled($product->slug)) {
                $product->sku = self::normalizeSku((string) $product->slug);
            }

            if (blank($product->subtitle) && filled($product->short_description)) {
                $product->subtitle = $product->short_description;
            }

            if (blank($product->lead_time) && filled($product->availability_text)) {
                $product->lead_time = $product->availability_text;
            }

            if ($product->price_usd !== null) {
                $product->price_from = $product->price_usd;
                $product->currency = 'USD';
            }

            $rawMediaUrl = $product->getAttributes()['media_url'] ?? null;
            $rawImageUrl = $product->getAttributes()['image_url'] ?? null;

            if (filled($rawImageUrl) && blank($product->media_path) && blank($rawMediaUrl)) {
                $product->media_url = $rawImageUrl;
            }

            if (filled($rawMediaUrl) && blank($rawImageUrl)) {
                $product->image_url = $rawMediaUrl;
            }

            $product->stock_quantity = $product->stock_quantity !== null
                ? max(0, (int) $product->stock_quantity)
                : null;
            $product->stock_status = self::normalizeStockStatus(
                $product->stock_status,
                $product->stock_quantity,
                (bool) $product->in_stock,
            );

            if ($product->stock_status === 'sold_out') {
                $product->in_stock = false;
                $product->stock_quantity = 0;
            } elseif (in_array($product->stock_status, ['in_stock', 'low_stock'], true)) {
                $product->in_stock = true;
                $product->stock_quantity ??= $product->stock_status === 'low_stock' ? 4 : 24;
                if ($product->stock_quantity <= 5) {
                    $product->stock_status = 'low_stock';
                }
            } else {
                $product->in_stock = true;
            }

            if (blank($product->seo_title) && filled($product->name)) {
                $product->seo_title = $product->name;
            }

            if (blank($product->seo_description) && filled($product->short_description)) {
                $product->seo_description = $product->short_description;
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
        return $query->where($query->qualifyColumn('status'), ProductStatus::Published->value);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->published()->active();
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => StorageUrl::resolve($attributes['media_path'] ?? null) ?? $value,
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published->value;
    }

    public function canBePurchased(): bool
    {
        return $this->is_active
            && $this->isPublished()
            && ! $this->inquiry_only
            && in_array((string) $this->stock_status, self::PURCHASABLE_STOCK_STATUSES, true);
    }

    public function primaryImageUrl(): ?string
    {
        if ($this->relationLoaded('images')) {
            $primaryImage = $this->images->first();

            if ($primaryImage?->media_url) {
                return $primaryImage->media_url;
            }
        }

        return $this->image_url;
    }

    public function stockStatusLabel(): string
    {
        return self::labelForOption(self::STOCK_STATUS_OPTIONS, $this->stock_status) ?? 'Unavailable';
    }

    /**
     * @return array<int, string>
     */
    public function useCaseLabels(): array
    {
        return collect($this->use_cases ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => self::labelForOption(self::USE_CASE_OPTIONS, $value) ?? Str::headline($value))
            ->values()
            ->all();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc($query->qualifyColumn('featured'))
            ->orderByDesc($query->qualifyColumn('is_bestseller'))
            ->orderBy($query->qualifyColumn('sort_order'))
            ->orderByDesc($query->qualifyColumn('published_at'))
            ->orderByDesc($query->qualifyColumn('created_at'))
            ->orderBy($query->qualifyColumn('id'));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_related_products',
            'product_id',
            'related_product_id',
        )->withTimestamps();
    }

    public static function normalizeSku(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Za-z0-9]+/', '_', trim($value));

        return $normalized ? Str::upper(trim($normalized, '_')) : null;
    }

    public static function labelForOption(array $options, ?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $options[$value] ?? Str::headline($value);
    }

    public static function normalizeCategoryValue(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = Str::slug(trim($value), '_');

        if (isset(self::CATEGORY_OPTIONS[$normalized])) {
            return $normalized;
        }

        return match ($normalized) {
            'wellness_interior',
            'wellness_interiors',
            'home_objects',
            'gift_sets' => 'wellness_interior',
            default => null,
        };
    }

    private static function normalizeStockStatus(?string $stockStatus, ?int $stockQuantity, bool $inStock): string
    {
        $normalized = strtolower(trim((string) $stockStatus));

        if (isset(self::STOCK_STATUS_OPTIONS[$normalized])) {
            if (
                in_array($normalized, ['in_stock', 'low_stock'], true)
                && $stockQuantity !== null
                && $stockQuantity <= 0
            ) {
                return 'sold_out';
            }

            return $normalized;
        }

        if ($stockQuantity !== null) {
            if ($stockQuantity <= 0) {
                return 'sold_out';
            }

            if ($stockQuantity <= 5) {
                return 'low_stock';
            }

            return 'in_stock';
        }

        return $inStock ? 'in_stock' : 'sold_out';
    }
}
