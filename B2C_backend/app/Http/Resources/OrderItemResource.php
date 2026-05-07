<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_title' => $this->product_title ?? $this->product_name,
            'variant_title' => $this->variant_title,
            'variant_sku' => $this->variant_sku,
            'option_values' => $this->option_values ?? [],
            'quantity' => $this->quantity,
            'unit_price_amount' => number_format((float) ($this->unit_price_amount ?? $this->unit_price_usd), 2, '.', ''),
            'unit_price_usd' => number_format((float) ($this->unit_price_amount ?? $this->unit_price_usd), 2, '.', ''),
            'currency' => $this->currency ?: 'NZD',
            'subtotal_usd' => number_format((float) $this->subtotal_usd, 2, '.', ''),
            'variant' => $this->variant ? (new ProductVariantResource($this->variant))->resolve($request) : null,
            'product' => $this->product ? (new ProductResource($this->product))->resolve($request) : null,
        ];
    }
}
