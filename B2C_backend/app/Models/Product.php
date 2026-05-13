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

    protected array $localizedAttributes = [
        'name',
        'subtitle',
        'short_description',
        'full_description',
        'features' => 'array',
        'availability_text',
        'lead_time',
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
        'is_bestseller',
        'is_new',
        'sort_order',
        'media_path',
        'media_url',
        'image_url',
        'price_from',
        'currency',
        'is_active',
        'inquiry_only',
        'sample_request_enabled',
        'lead_time',
        'lead_time_translations',
        'weight_grams',
        'certifications',
        'certifications_translations',
        'technical_downloads',
        'care_instructions',
        'care_instructions_translations',
        'material_benefits',
        'material_benefits_translations',
        'selling_points',
        'shipping_notes',
        'return_notes',
        'product_faqs',
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
            'certifications' => 'array',
            'certifications_translations' => 'array',
            'technical_downloads' => 'array',
            'care_instructions' => 'array',
            'care_instructions_translations' => 'array',
            'material_benefits' => 'array',
            'material_benefits_translations' => 'array',
            'selling_points' => 'array',
            'shipping_notes' => 'array',
            'return_notes' => 'array',
            'product_faqs' => 'array',
            'seo_title_translations' => 'array',
            'seo_description_translations' => 'array',
            'featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
            'price_from' => 'decimal:2',
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
            if (blank($product->subtitle) && filled($product->short_description)) {
                $product->subtitle = $product->short_description;
            }

            if (blank($product->lead_time) && filled($product->availability_text)) {
                $product->lead_time = $product->availability_text;
            }

            $rawMediaUrl = $product->getAttributes()['media_url'] ?? null;
            $rawImageUrl = $product->getAttributes()['image_url'] ?? null;

            if (filled($rawImageUrl) && blank($product->media_path) && blank($rawMediaUrl)) {
                $product->media_url = $rawImageUrl;
            }

            if (filled($rawMediaUrl) && blank($rawImageUrl)) {
                $product->image_url = $rawMediaUrl;
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
        $variant = $this->defaultVariant();

        return $this->is_active
            && $this->isPublished()
            && ! $this->inquiry_only
            && $variant?->isPurchasable() === true;
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
        $variant = $this->defaultVariant();

        return $variant?->availabilityLabel()
            ?? ($this->inquiry_only ? 'Inquiry only' : 'Unavailable');
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

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->ordered();
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->active()
            ->ordered();
    }

    public function attributeAssignments(): HasMany
    {
        return $this->hasMany(ProductAttributeAssignment::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_assignments',
            'product_id',
            'product_attribute_value_id',
        )->withPivot('attribute_definition_id', 'value_text', 'value_number', 'value_boolean', 'value_json')
            ->withTimestamps();
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

    public function defaultVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('variants')) {
            $variants = $this->variants;

            return $variants->first(fn (ProductVariant $variant): bool => $variant->is_active && $variant->is_default)
                ?? $variants->first(fn (ProductVariant $variant): bool => $variant->is_active);
        }

        return $this->variants()
            ->where('is_active', true)
            ->ordered()
            ->first();
    }

    public function hasVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->isNotEmpty();
        }

        return $this->variants()->where('is_active', true)->exists();
    }

    public function effectiveSku(): ?string
    {
        return $this->defaultVariant()?->sku;
    }

    public function effectivePrice(): ?float
    {
        $variant = $this->defaultVariant();

        return $variant?->effectivePrice();
    }

    public function effectiveCompareAtPrice(): ?float
    {
        $variant = $this->defaultVariant();

        return $variant?->effectiveCompareAtPrice();
    }

    public function effectiveCurrency(): string
    {
        return $this->defaultVariant()?->currency ?? $this->currency ?? 'NZD';
    }

    public function effectiveStockQuantity(): ?int
    {
        return $this->defaultVariant()?->stock_quantity;
    }

    public function effectiveStockStatus(): ?string
    {
        return $this->defaultVariant()?->stock_status;
    }

    public function effectiveImageUrl(): ?string
    {
        return $this->defaultVariant()?->image_url ?? $this->primaryImageUrl();
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

}
