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
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'quantity' => $this->quantity,
            'unit_price_usd' => number_format((float) $this->unit_price_usd, 2, '.', ''),
            'subtotal_usd' => number_format((float) $this->subtotal_usd, 2, '.', ''),
            'product' => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'image_url' => $this->product->image_url,
                'in_stock' => (bool) $this->product->in_stock,
                'price_usd' => number_format((float) $this->product->price_usd, 2, '.', ''),
            ] : null,
        ];
    }
}
