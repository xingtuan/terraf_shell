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
            'product' => $this->product ? (new ProductResource($this->product))->resolve($request) : null,
        ];
    }
}
