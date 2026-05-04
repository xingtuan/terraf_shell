<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use App\Support\StorePricing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
    ) {}

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

        $order = $order->fresh(['user', 'items.product']);

        DB::afterCommit(function () use ($order): void {
            $payload = $this->emailPayloadFactory->forOrder($order);

            $this->emailDispatchService->sendEvent('order.created', $payload, [
                'related' => $order,
                'idempotency_key' => 'order.created:'.$order->id,
            ]);

            $this->emailDispatchService->sendEvent('order.admin_new_order', $payload, [
                'related' => $order,
                'idempotency_key' => 'order.admin_new_order:'.$order->id,
            ]);
        });

        return $order;
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

        $order = $order->fresh(['user', 'items.product']);

        DB::afterCommit(fn () => $this->emailDispatchService->sendEvent(
            'order.cancelled',
            $this->emailPayloadFactory->forOrder($order),
            [
                'related' => $order,
                'idempotency_key' => 'order.cancelled:'.$order->id,
            ],
        ));

        return $order;
    }

    public function dispatchStatusChangedEmail(Order $order, ?string $previousStatus = null): void
    {
        $order->loadMissing(['user', 'items.product']);
        $status = $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status;
        $eventKey = $status === OrderStatus::Shipped->value ? 'order.shipped' : 'order.status_changed';

        DB::afterCommit(fn () => $this->emailDispatchService->sendEvent(
            $eventKey,
            $this->emailPayloadFactory->forOrder($order, [
                'order' => [
                    'previous_status' => $previousStatus,
                ],
            ]),
            [
                'related' => $order,
                'idempotency_key' => $eventKey.':'.$order->id.':'.$status,
            ],
        ));
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
