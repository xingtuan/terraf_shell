<?php

namespace App\Services;

use App\Exceptions\CartStockLimitException;
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

    /**
     * @var array<string, mixed>|null
     */
    private ?array $lastQuantityAdjustment = null;

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
        $this->lastQuantityAdjustment = null;

        $product = Product::query()
            ->with('variants')
            ->whereKey($productId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart.product_unavailable')],
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

        $item->quantity = $this->quantityForAdd($variant, $item->quantity);
        $item->save();

        return $item->fresh(['product.variants', 'variant']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pullQuantityAdjustment(): ?array
    {
        $adjustment = $this->lastQuantityAdjustment;
        $this->lastQuantityAdjustment = null;

        return $adjustment;
    }

    public function updateItem(Cart $cart, int $productId, int $quantity, ?int $variantId = null): ?CartItem
    {
        $item = $this->findCartItem($cart, $productId, $variantId);

        if ($item === null) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart.item_not_in_cart')],
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
                'product_id' => [__('api.cart.product_unavailable')],
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
        $sessionKey = $this->normalizeSessionKey($sessionKey);

        if ($sessionKey === '') {
            return;
        }

        $guestCart = Cart::query()
            ->forSession($sessionKey)
            ->whereNull('user_id')
            ->with(['items.product.variants', 'items.variant'])
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
            $product = $guestItem->product;

            if ($product === null) {
                continue;
            }

            try {
                $variant = $this->resolveVariant(
                    $product,
                    $guestItem->product_variant_id ? (int) $guestItem->product_variant_id : null,
                );
                $this->guardVariantPurchasable($product, $variant);
            } catch (ValidationException) {
                continue;
            }

            $userItem = $userCart->items()->firstOrNew([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
            ]);
            $desiredQuantity = (int) ($userItem->exists ? $userItem->quantity : 0)
                + max(1, (int) $guestItem->quantity);
            $mergedQuantity = $this->clampQuantityForMerge($variant, $desiredQuantity);

            if ($mergedQuantity <= 0) {
                if ($userItem->exists) {
                    $userItem->delete();
                }

                continue;
            }

            $this->guardStockAvailability($variant, $mergedQuantity);

            $userItem->quantity = $mergedQuantity;
            $userItem->unit_price_usd = $variant->effectivePrice();
            $userItem->unit_price_amount = $variant->effectivePrice();
            $userItem->currency = $variant->currency ?: 'NZD';
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
                    'variant_id' => [__('api.cart.variant_not_for_product')],
                ]);
            }

            return $variant;
        }

        $variant = $product->defaultVariant();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart.no_active_variant')],
            ]);
        }

        return $variant;
    }

    private function guardVariantPurchasable(Product $product, ProductVariant $variant): void
    {
        if (! $product->is_active || ! $product->isPublished() || $product->inquiry_only || ! $variant->isPurchasable()) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart.product_unavailable')],
            ]);
        }
    }

    private function guardStockAvailability(ProductVariant $variant, int $desiredQuantity): void
    {
        if (! $variant->canFulfillQuantity($desiredQuantity)) {
            throw new CartStockLimitException(
                $variant,
                $desiredQuantity,
                $this->availableQuantityForStockLimit($variant),
            );
        }
    }

    private function quantityForAdd(ProductVariant $variant, int $desiredQuantity): int
    {
        $desiredQuantity = max(1, $desiredQuantity);

        if ($variant->canFulfillQuantity($desiredQuantity)) {
            return $desiredQuantity;
        }

        if ($variant->stock_quantity !== null && $variant->inventory_policy === 'deny') {
            $availableQuantity = max(0, (int) $variant->stock_quantity);

            if ($availableQuantity > 0) {
                $this->lastQuantityAdjustment = [
                    'type' => 'quantity_clamped',
                    'available_quantity' => $availableQuantity,
                    'requested_quantity' => $desiredQuantity,
                    'product_variant_id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'stock_status' => $variant->stock_status,
                    'inventory_policy' => $variant->inventory_policy,
                    'message' => __('api.cart.quantity_clamped', ['count' => $availableQuantity]),
                ];

                return $availableQuantity;
            }
        }

        $this->guardStockAvailability($variant, $desiredQuantity);

        return $desiredQuantity;
    }

    private function clampQuantityForMerge(ProductVariant $variant, int $desiredQuantity): int
    {
        $desiredQuantity = max(1, $desiredQuantity);

        if (in_array($variant->inventory_policy, ['continue', 'preorder'], true)) {
            return $desiredQuantity;
        }

        if ($variant->stock_quantity !== null) {
            return min($desiredQuantity, max(0, (int) $variant->stock_quantity));
        }

        return $desiredQuantity;
    }

    private function availableQuantityForStockLimit(ProductVariant $variant): int
    {
        if ($variant->stock_quantity !== null) {
            return max(0, (int) $variant->stock_quantity);
        }

        return 0;
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
        foreach ([self::COOKIE_NAME, self::LEGACY_COOKIE_NAME] as $cookieName) {
            $sessionKey = $this->normalizeSessionKey($request->cookie($cookieName, ''));

            if ($sessionKey !== '') {
                return $sessionKey;
            }
        }

        return '';
    }

    private function normalizeSessionKey(mixed $sessionKey): string
    {
        $sessionKey = trim((string) $sessionKey);

        if ($sessionKey === '' || strlen($sessionKey) > 64 || ! Str::isUuid($sessionKey)) {
            return '';
        }

        return $sessionKey;
    }
}
