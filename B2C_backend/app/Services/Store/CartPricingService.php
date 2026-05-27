<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class CartPricingService
{
    public function __construct(
        private readonly TaxService $taxService,
    ) {}

    public function lineUnitPrice(CartItem $item): float
    {
        if ($item->product_variant_id !== null) {
            $variant = $item->relationLoaded('variant')
                ? $item->variant
                : $item->variant()->first();

            if ($variant instanceof ProductVariant) {
                return round((float) $variant->effectivePrice(), 2);
            }
        }

        if ($item->unit_price_amount !== null) {
            return round((float) $item->unit_price_amount, 2);
        }

        if ($item->unit_price_usd !== null) {
            return round((float) $item->unit_price_usd, 2);
        }

        Log::warning('Cart item has no usable unit price.', [
            'cart_item_id' => $item->id,
            'cart_id' => $item->cart_id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
        ]);

        return 0.0;
    }

    public function subtotal(Cart $cart): float
    {
        $cart->loadMissing(['items.variant']);

        $subtotal = $cart->items->sum(
            fn (CartItem $item): float => $this->lineUnitPrice($item) * max(0, (int) $item->quantity),
        );

        return round((float) $subtotal, 2);
    }

    public function taxableBase(float $subtotal, float $shipping): float
    {
        return round(max(0.0, $subtotal) + max(0.0, $shipping), 2);
    }

    /**
     * @return array{label: string, rate: float, amount: float, included: bool}
     */
    public function taxSnapshot(float $subtotal, float $shipping): array
    {
        return $this->taxService->snapshot($this->taxableBase($subtotal, $shipping));
    }

    public function total(float $subtotal, float $shipping): float
    {
        $tax = $this->taxSnapshot($subtotal, $shipping);
        $taxableBase = $this->taxableBase($subtotal, $shipping);

        if ($this->taxService->pricesIncludeGst()) {
            return round($taxableBase, 2);
        }

        return round($taxableBase + (float) $tax['amount'], 2);
    }

    public function formatMoney(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }
}
