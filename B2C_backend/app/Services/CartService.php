<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const COOKIE_NAME = 'oxp_cart_session';

    public const LEGACY_COOKIE_NAME = 'shell'.'fin_cart_session';

    public const COOKIE_TTL_MINUTES = 43_200;

    public function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user('sanctum');

        if ($user !== null) {
            $cart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['expires_at' => null],
            );

            return $cart->load(['items.product.variants', 'items.variant']);
        }

        $sessionKey = $this->sessionKeyFromRequest($request);
        $cart = null;

        if ($sessionKey !== '') {
            $cart = Cart::query()->forSession($sessionKey)->first();

            if ($cart?->expires_at?->isPast()) {
                $cart->delete();
                $cart = null;
            }
        }

        if ($cart === null) {
            $sessionKey = $sessionKey !== '' ? $sessionKey : (string) Str::uuid();

            $cart = Cart::query()->create([
                'session_key' => $sessionKey,
                'expires_at' => now()->addDays(7),
            ]);
        } else {
            $cart->forceFill([
                'expires_at' => now()->addDays(7),
            ])->save();
        }

        return $cart->load(['items.product.variants', 'items.variant']);
    }

    public function addItem(Cart $cart, int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $product = Product::query()
            ->with('variants')
            ->whereKey($productId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }

        $variant = $this->resolveVariant($product, $variantId);
        $this->guardVariantPurchasable($product, $variant);

        $normalizedQuantity = max(1, $quantity);
        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        if ($item->exists) {
            $item->quantity += $normalizedQuantity;
        } else {
            $item->quantity = $normalizedQuantity;
            $item->unit_price_usd = $variant->effectivePrice();
            $item->unit_price_amount = $variant->effectivePrice();
            $item->currency = $variant->currency ?: 'NZD';
        }

        $this->guardStockAvailability($variant, $item->quantity);
        $item->save();

        return $item->fresh(['product.variants', 'variant']);
    }

    public function updateItem(Cart $cart, int $productId, int $quantity, ?int $variantId = null): ?CartItem
    {
        $item = $this->findCartItem($cart, $productId, $variantId);

        if ($item === null) {
            throw ValidationException::withMessages([
                'product_id' => ['This item is not in the cart.'],
            ]);
        }

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $product = Product::query()
            ->with('variants')
            ->whereKey($productId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }

        $variant = $this->resolveVariant($product, $item->product_variant_id ? (int) $item->product_variant_id : $variantId);
        $this->guardVariantPurchasable($product, $variant);
        $this->guardStockAvailability($variant, $quantity);
        $item->quantity = $quantity;
        $item->unit_price_usd = $variant->effectivePrice();
        $item->unit_price_amount = $variant->effectivePrice();
        $item->currency = $variant->currency ?: 'NZD';
        $item->save();

        return $item->fresh(['product.variants', 'variant']);
    }

    public function removeItem(Cart $cart, int $productId, ?int $variantId = null): void
    {
        $item = $this->findCartItem($cart, $productId, $variantId);

        $item?->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeGuestCart(string $sessionKey, int $userId): void
    {
        $guestCart = Cart::query()
            ->forSession($sessionKey)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        if ($guestCart === null) {
            return;
        }

        if ($guestCart->expires_at?->isPast()) {
            $guestCart->delete();

            return;
        }

        $userCart = Cart::query()->firstOrCreate(
            ['user_id' => $userId],
            ['expires_at' => null],
        );

        foreach ($guestCart->items as $guestItem) {
            $userItem = $userCart->items()->firstOrNew([
                'product_id' => $guestItem->product_id,
                'product_variant_id' => $guestItem->product_variant_id,
            ]);

            if ($userItem->exists) {
                $userItem->quantity += $guestItem->quantity;
            } else {
                $userItem->quantity = $guestItem->quantity;
                $userItem->unit_price_usd = $guestItem->unit_price_usd;
                $userItem->unit_price_amount = $guestItem->unit_price_amount ?? $guestItem->unit_price_usd;
                $userItem->currency = $guestItem->currency ?: 'NZD';
            }

            $userItem->save();
        }

        $guestCart->delete();
    }

    public function getCartSummary(Cart $cart): array
    {
        $cart->loadMissing(['items.product.variants', 'items.variant']);

        return [
            'items' => $cart->items,
            'subtotal' => $cart->total(),
            'item_count' => $cart->itemCount(),
        ];
    }

    private function resolveVariant(Product $product, ?int $variantId): ProductVariant
    {
        if ($variantId !== null) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereKey($variantId)
                ->first();

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'variant_id' => ['The selected variant does not belong to this product.'],
                ]);
            }

            return $variant;
        }

        return $product->defaultVariant() ?? $product->ensureDefaultVariant();
    }

    private function guardVariantPurchasable(Product $product, ProductVariant $variant): void
    {
        if (! $product->is_active || ! $product->isPublished() || $product->inquiry_only || ! $variant->isPurchasable()) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }
    }

    private function guardStockAvailability(ProductVariant $variant, int $desiredQuantity): void
    {
        if (! $variant->canFulfillQuantity($desiredQuantity)) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds current stock availability.'],
            ]);
        }
    }

    private function findCartItem(Cart $cart, int $productId, ?int $variantId = null): ?CartItem
    {
        $query = $cart->items()->where('product_id', $productId);

        if ($variantId !== null) {
            return $query->where('product_variant_id', $variantId)->first();
        }

        $items = $query->get();

        if ($items->count() === 1) {
            return $items->first();
        }

        $product = Product::query()->with('variants')->find($productId);
        $defaultVariant = $product?->defaultVariant();

        if ($defaultVariant !== null) {
            return $items->firstWhere('product_variant_id', $defaultVariant->id);
        }

        return $items->first();
    }

    private function sessionKeyFromRequest(Request $request): string
    {
        $sessionKey = trim((string) $request->cookie(self::COOKIE_NAME, ''));

        if ($sessionKey !== '') {
            return $sessionKey;
        }

        return trim((string) $request->cookie(self::LEGACY_COOKIE_NAME, ''));
    }
}
