<?php

namespace App\Models;

use App\Support\StorageUrl;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    public const STOCK_STATUS_OPTIONS = [
        'in_stock' => 'In Stock',
        'low_stock' => 'Low Stock',
        'preorder' => 'Pre-order',
        'made_to_order' => 'Made to Order',
        'sold_out' => 'Sold Out',
    ];

    public const INVENTORY_POLICY_OPTIONS = [
        'deny' => 'Deny overselling',
        'continue' => 'Continue selling',
        'preorder' => 'Pre-order',
        'inquiry_only' => 'Inquiry only',
    ];

    protected $fillable = [
        'product_id',
        'sku',
        'title',
        'option_values',
        'price_amount',
        'compare_at_price_amount',
        'currency',
        'stock_quantity',
        'stock_status',
        'inventory_policy',
        'low_stock_threshold',
        'weight_grams',
        'dimensions',
        'image_url',
        'media_path',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'price_amount' => 'decimal:2',
            'compare_at_price_amount' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
            'dimensions' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant): void {
            $variant->currency = strtoupper($variant->currency ?: 'NZD');
            $variant->inventory_policy = array_key_exists((string) $variant->inventory_policy, self::INVENTORY_POLICY_OPTIONS)
                ? (string) $variant->inventory_policy
                : 'deny';
            $variant->stock_status = array_key_exists((string) $variant->stock_status, self::STOCK_STATUS_OPTIONS)
                ? (string) $variant->stock_status
                : 'in_stock';
            $variant->low_stock_threshold = max(0, (int) ($variant->low_stock_threshold ?? 5));

            if ($variant->stock_quantity !== null && $variant->inventory_policy === 'deny') {
                $variant->stock_quantity = max(0, (int) $variant->stock_quantity);

                if ($variant->stock_quantity <= 0) {
                    $variant->stock_status = 'sold_out';
                } elseif ($variant->stock_quantity <= $variant->low_stock_threshold) {
                    $variant->stock_status = 'low_stock';
                } elseif (in_array($variant->stock_status, ['sold_out', 'low_stock'], true)) {
                    $variant->stock_status = 'in_stock';
                }
            }
        });

        static::saved(function (self $variant): void {
            if (! $variant->is_default) {
                return;
            }

            static::query()
                ->where('product_id', $variant->product_id)
                ->whereKeyNot($variant->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $variant->syncLegacyProductFields();
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => StorageUrl::resolve($attributes['media_path'] ?? null) ?? $value,
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc($query->qualifyColumn('is_default'))
            ->orderBy($query->qualifyColumn('sort_order'))
            ->orderBy($query->qualifyColumn('id'));
    }

    public function isPurchasable(): bool
    {
        if (! $this->is_active || $this->inventory_policy === 'inquiry_only') {
            return false;
        }

        if (in_array($this->inventory_policy, ['continue', 'preorder'], true)) {
            return true;
        }

        return $this->isInStock();
    }

    public function isInStock(): bool
    {
        if (! in_array($this->stock_status, ['in_stock', 'low_stock', 'made_to_order'], true)) {
            return false;
        }

        return $this->stock_quantity === null || $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity !== null
            && $this->stock_quantity > 0
            && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function displayTitle(): string
    {
        if (filled($this->title) && $this->title !== 'Default') {
            return (string) $this->title;
        }

        $options = collect($this->option_values ?? [])
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value, string|int $key): string => str($key)->headline().': '.str((string) $value)->headline())
            ->values()
            ->implode(' / ');

        return $options !== '' ? $options : 'Default';
    }

    public function effectivePrice(): float
    {
        return (float) $this->price_amount;
    }

    public function effectiveCompareAtPrice(): ?float
    {
        return $this->compare_at_price_amount !== null
            ? (float) $this->compare_at_price_amount
            : null;
    }

    public function availabilityLabel(): string
    {
        if ($this->inventory_policy === 'inquiry_only') {
            return 'Inquiry only';
        }

        if ($this->inventory_policy === 'preorder' || $this->stock_status === 'preorder') {
            return 'Pre-order';
        }

        return Product::labelForOption(self::STOCK_STATUS_OPTIONS, $this->stock_status) ?? 'Unavailable';
    }

    public function canFulfillQuantity(int $quantity): bool
    {
        $quantity = max(1, $quantity);

        if (! $this->is_active || $this->inventory_policy === 'inquiry_only') {
            return false;
        }

        if (in_array($this->inventory_policy, ['continue', 'preorder'], true)) {
            return true;
        }

        if ($this->stock_quantity === null) {
            return $this->stock_status !== 'sold_out';
        }

        return $quantity <= $this->stock_quantity;
    }

    public function adjustStock(
        int $changeQuantity,
        string $reason = 'manual_adjustment',
        ?string $note = null,
        ?int $createdBy = null,
    ): InventoryAdjustment {
        $before = $this->stock_quantity;
        $after = $before !== null ? max(0, $before + $changeQuantity) : null;

        if ($after !== null) {
            $this->forceFill([
                'stock_quantity' => $after,
            ])->save();
        }

        return $this->inventoryAdjustments()->create([
            'change_quantity' => $changeQuantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => $reason,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    private function syncLegacyProductFields(): void
    {
        $product = $this->product;

        if ($product === null) {
            return;
        }

        $product->forceFill([
            'sku' => $this->sku,
            'price_usd' => $this->price_amount,
            'price_from' => $this->price_amount,
            'compare_at_price_usd' => $this->compare_at_price_amount,
            'currency' => $this->currency,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'in_stock' => $this->stock_status !== 'sold_out',
            'weight_grams' => $this->weight_grams ?? $product->weight_grams,
            'image_url' => $this->image_url ?? $product->image_url,
        ])->saveQuietly();
    }
}
