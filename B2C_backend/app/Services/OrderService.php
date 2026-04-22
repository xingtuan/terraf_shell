<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Support\StorePricing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createFromCart(Cart $cart, array $shippingData, string $note = ''): Order
    {
        $cart->loadMissing(['user', 'items.product']);

        if ($cart->user_id === null) {
            throw ValidationException::withMessages([
                'cart' => ['Orders can only be created for authenticated users.'],
            ]);
        }

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty.'],
            ]);
        }

        $items = $cart->items->map(function ($item) {
            $product = $item->product;

            if ($product === null || ! $product->canBePurchased()) {
                throw ValidationException::withMessages([
                    'cart' => ['One or more items in your cart are no longer available.'],
                ]);
            }

            if (
                $product->stock_quantity !== null
                && in_array($product->stock_status, ['in_stock', 'low_stock'], true)
                && $item->quantity > $product->stock_quantity
            ) {
                throw ValidationException::withMessages([
                    'cart' => ['One or more items exceed available stock.'],
                ]);
            }

            $unitPrice = (float) $product->price_usd;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku ?? $product->slug,
                'quantity' => $item->quantity,
                'unit_price_usd' => $unitPrice,
                'subtotal_usd' => $unitPrice * $item->quantity,
            ];
        });

        $subtotal = (float) $items->sum('subtotal_usd');
        $shipping = StorePricing::shippingForSubtotal($subtotal);
        $total = StorePricing::totalForSubtotal($subtotal);

        $order = DB::transaction(function () use ($cart, $shippingData, $note, $items, $subtotal, $shipping, $total): Order {
            $order = Order::query()->create([
                'user_id' => $cart->user_id,
                'status' => OrderStatus::Pending,
                'subtotal_usd' => $subtotal,
                'shipping_usd' => $shipping,
                'total_usd' => $total,
                'currency' => 'USD',
                'shipping_name' => $shippingData['shipping_name'],
                'shipping_phone' => $shippingData['shipping_phone'] ?? null,
                'shipping_address_line1' => $shippingData['shipping_address_line1'],
                'shipping_address_line2' => $shippingData['shipping_address_line2'] ?? null,
                'shipping_city' => $shippingData['shipping_city'],
                'shipping_state_province' => $shippingData['shipping_state_province'] ?? null,
                'shipping_postal_code' => $shippingData['shipping_postal_code'] ?? null,
                'shipping_country' => $shippingData['shipping_country'],
                'customer_note' => $note !== '' ? $note : null,
                'payment_method' => 'manual',
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $cart->items()->delete();

            return $order;
        });

        return $order->fresh(['user', 'items.product']);
    }

    public function cancelOrder(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => ['Only pending orders can be cancelled.'],
            ]);
        }

        $order->forceFill([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();

        return $order->fresh(['items.product']);
    }

    public function getOrdersForUser(int $userId): Collection
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with(['items.product'])
            ->latest()
            ->get();
    }
}
