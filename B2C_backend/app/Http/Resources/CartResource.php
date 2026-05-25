<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
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

        $estimatedTotal = $taxService->pricesIncludeGst()
            ? $subtotal
            : $subtotal + $estimatedTax;

        return [
            'id' => $this->id,
            'item_count' => $this->itemCount(),
            'currency' => (string) config('store.currency', 'NZD'),
            'subtotal_usd' => number_format($subtotal, 2, '.', ''),
            'estimated_shipping_usd' => number_format(0, 2, '.', ''),
            'estimated_tax_usd' => number_format($estimatedTax, 2, '.', ''),
            'estimated_total_usd' => number_format($estimatedTotal, 2, '.', ''),
            'free_shipping_threshold_usd' => number_format((float) config('store.shipping.free_shipping_threshold', StorePricing::FREE_SHIPPING_THRESHOLD), 2, '.', ''),
            'tax_rate' => $taxService->gstRate(),
            'tax_label' => $taxService->label(),
            'prices_include_tax' => $taxService->pricesIncludeGst(),
            'shipping_notice' => 'Shipping calculated at checkout.',
            'items' => $this->items->map(function (CartItem $item) use ($request): array {
                $product = $item->product;
                $quantityLimit = $this->quantityLimitForItem($item);
                $unitPrice = (float) ($item->unit_price_amount ?? $item->unit_price_usd);
                $lineTotal = $unitPrice * $item->quantity;

                return [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'max_quantity' => $quantityLimit['max_quantity'],
                    'available_quantity' => $quantityLimit['available_quantity'],
                    'can_increase_quantity' => $quantityLimit['can_increase_quantity'],
                    'quantity_error_message' => $quantityLimit['quantity_error_message'],
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

    /**
     * @return array{
     *     max_quantity: int|null,
     *     available_quantity: int|null,
     *     can_increase_quantity: bool,
     *     quantity_error_message: string|null
     * }
     */
    private function quantityLimitForItem(CartItem $item): array
    {
        $variant = $item->variant ?? $item->product?->defaultVariant();

        if (! $variant instanceof ProductVariant) {
            return [
                'max_quantity' => null,
                'available_quantity' => null,
                'can_increase_quantity' => false,
                'quantity_error_message' => null,
            ];
        }

        if (in_array($variant->inventory_policy, ['continue', 'preorder'], true)) {
            return [
                'max_quantity' => null,
                'available_quantity' => null,
                'can_increase_quantity' => true,
                'quantity_error_message' => null,
            ];
        }

        $availableQuantity = $variant->stock_quantity !== null
            ? max(0, (int) $variant->stock_quantity)
            : null;

        if ($availableQuantity === null && $item->product !== null) {
            $productQuantity = $item->product->effectiveStockQuantity();
            $availableQuantity = $productQuantity !== null
                ? max(0, (int) $productQuantity)
                : null;
        }

        if ($availableQuantity === null) {
            return [
                'max_quantity' => null,
                'available_quantity' => null,
                'can_increase_quantity' => $variant->isPurchasable(),
                'quantity_error_message' => null,
            ];
        }

        return [
            'max_quantity' => $availableQuantity,
            'available_quantity' => $availableQuantity,
            'can_increase_quantity' => $item->quantity < $availableQuantity,
            'quantity_error_message' => $item->quantity > $availableQuantity
                ? "Only {$availableQuantity} units are available."
                : null,
        ];
    }
}
