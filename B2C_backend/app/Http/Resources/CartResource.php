<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Store\TaxService;
use App\Support\StorePricing;
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
        $subtotal = (float) $this->total();
        $taxService = app(TaxService::class);
        $estimatedTax = $taxService->taxForTotal($subtotal);

        return [
            'id' => $this->id,
            'item_count' => $this->itemCount(),
            'currency' => (string) config('store.currency', 'NZD'),
            'subtotal_usd' => number_format($subtotal, 2, '.', ''),
            'estimated_shipping_usd' => number_format(0, 2, '.', ''),
            'estimated_tax_usd' => number_format($estimatedTax, 2, '.', ''),
            'estimated_total_usd' => number_format($subtotal, 2, '.', ''),
            'free_shipping_threshold_usd' => number_format((float) config('store.shipping.free_shipping_threshold', StorePricing::FREE_SHIPPING_THRESHOLD), 2, '.', ''),
            'tax_label' => $taxService->label(),
            'prices_include_tax' => $taxService->pricesIncludeGst(),
            'shipping_notice' => 'Shipping calculated at checkout.',
            'items' => $this->items->map(function (CartItem $item) use ($request): array {
                $product = $item->product;
                $lineTotal = (float) $item->unit_price_usd * $item->quantity;

                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price_usd' => number_format((float) $item->unit_price_usd, 2, '.', ''),
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                    'product' => $product ? (new ProductResource($product))->resolve($request) : null,
                ];
            })->values()->all(),
        ];
    }
}
