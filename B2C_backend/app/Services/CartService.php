<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const COOKIE_NAME = 'shellfin_cart_session';

    public const COOKIE_TTL_MINUTES = 43_200;

    public function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user('sanctum');

        if ($user !== null) {
            $cart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['expires_at' => null],
            );

            return $cart->load(['items.product']);
        }

        $sessionKey = trim((string) $request->cookie(self::COOKIE_NAME, ''));
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

        return $cart->load(['items.product']);
    }

    public function addItem(Cart $cart, int $productId, int $quantity = 1): CartItem
    {
        $product = Product::query()->whereKey($productId)->first();

        if ($product === null || ! $product->canBePurchased()) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }

        $normalizedQuantity = max(1, $quantity);
        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
        ]);

        if ($item->exists) {
            $item->quantity += $normalizedQuantity;
        } else {
            $item->quantity = $normalizedQuantity;
            $item->unit_price_usd = $product->price_usd;
        }

        $this->guardStockAvailability($product, $item->quantity);
        $item->save();

        return $item->fresh(['product']);
    }

    public function updateItem(Cart $cart, int $productId, int $quantity): ?CartItem
    {
        $item = $cart->items()->where('product_id', $productId)->first();

        if ($item === null) {
            throw ValidationException::withMessages([
                'product_id' => ['This item is not in the cart.'],
            ]);
        }

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $product = Product::query()->whereKey($productId)->first();

        if ($product === null || ! $product->canBePurchased()) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }

        $this->guardStockAvailability($product, $quantity);
        $item->quantity = $quantity;
        $item->save();

        return $item->fresh(['product']);
    }

    public function removeItem(Cart $cart, int $productId): void
    {
        $cart->items()->where('product_id', $productId)->delete();
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
            ]);

            if ($userItem->exists) {
                $userItem->quantity += $guestItem->quantity;
            } else {
                $userItem->quantity = $guestItem->quantity;
                $userItem->unit_price_usd = $guestItem->unit_price_usd;
            }

            $userItem->save();
        }

        $guestCart->delete();
    }

    public function getCartSummary(Cart $cart): array
    {
        $cart->loadMissing(['items.product']);

        return [
            'items' => $cart->items,
            'subtotal' => $cart->total(),
            'item_count' => $cart->itemCount(),
        ];
    }

    private function guardStockAvailability(Product $product, int $desiredQuantity): void
    {
        if (
            $product->stock_quantity !== null
            && in_array($product->stock_status, ['in_stock', 'low_stock'], true)
            && $desiredQuantity > $product->stock_quantity
        ) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds current stock availability.'],
            ]);
        }
    }
}
