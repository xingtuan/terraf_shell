<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['items.product']);

        return [
            'id' => $this->id,
            'item_count' => $this->itemCount(),
            'subtotal_usd' => $this->total(),
            'items' => $this->items->map(function (CartItem $item): array {
                $product = $item->product;
                $lineTotal = (float) $item->unit_price_usd * $item->quantity;

                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price_usd' => number_format((float) $item->unit_price_usd, 2, '.', ''),
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image_url' => $product->image_url,
                        'in_stock' => (bool) $product->in_stock,
                        'price_usd' => number_format((float) $product->price_usd, 2, '.', ''),
                    ] : null,
                ];
            })->values()->all(),
        ];
    }
}
