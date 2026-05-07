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
        $this->loadMissing(['items.product.variants', 'items.product.attributeAssignments.definition', 'items.product.attributeAssignments.attributeValue', 'items.variant']);
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
                $unitPrice = (float) ($item->unit_price_amount ?? $item->unit_price_usd);
                $lineTotal = $unitPrice * $item->quantity;

                return [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'unit_price_amount' => number_format($unitPrice, 2, '.', ''),
                    'unit_price_usd' => number_format($unitPrice, 2, '.', ''),
                    'currency' => $item->currency ?: (string) config('store.currency', 'NZD'),
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                    'variant_sku' => $item->variant?->sku,
                    'variant_title' => $item->variant?->displayTitle(),
                    'option_values' => $item->variant?->option_values ?? [],
                    'stock_status' => $item->variant?->stock_status,
                    'inventory_policy' => $item->variant?->inventory_policy,
                    'variant' => $item->variant ? (new ProductVariantResource($item->variant))->resolve($request) : null,
                    'product' => $product ? (new ProductResource($product))->resolve($request) : null,
                ];
            })->values()->all(),
        ];
    }
}
