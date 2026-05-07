<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'title' => $this->title,
            'display_title' => $this->displayTitle(),
            'option_values' => $this->option_values ?? [],
            'price_amount' => number_format((float) $this->price_amount, 2, '.', ''),
            'compare_at_price_amount' => $this->compare_at_price_amount !== null
                ? number_format((float) $this->compare_at_price_amount, 2, '.', '')
                : null,
            'currency' => $this->currency ?: 'NZD',
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'stock_status_label' => $this->availabilityLabel(),
            'inventory_policy' => $this->inventory_policy,
            'inventory_policy_label' => ProductVariant::INVENTORY_POLICY_OPTIONS[$this->inventory_policy] ?? $this->inventory_policy,
            'low_stock_threshold' => $this->low_stock_threshold,
            'weight_grams' => $this->weight_grams,
            'dimensions' => $this->dimensions,
            'image_url' => $this->image_url,
            'media_path' => $this->media_path,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'is_in_stock' => $this->isInStock(),
            'is_low_stock' => $this->isLowStock(),
            'can_add_to_cart' => $this->isPurchasable(),
            'availability_label' => $this->availabilityLabel(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
