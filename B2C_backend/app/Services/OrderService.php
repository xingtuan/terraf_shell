<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
        private readonly ShippingQuoteService $shippingQuoteService,
    ) {}

    public function createFromCart(
        Cart $cart,
        array $shippingData,
        string $note = '',
        ?string $guestEmail = null,
        string $shippingMethodCode = 'standard',
    ): Order {
        $cart->loadMissing(['user', 'items.product']);

        if ($cart->user_id === null && blank($guestEmail)) {
            throw ValidationException::withMessages([
                'guest_email' => ['An email address is required for guest checkout.'],
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
        $quoteAddress = [
            'line1' => $shippingData['shipping_address_line1'],
            'line2' => $shippingData['shipping_address_line2'] ?? null,
            'city' => $shippingData['shipping_city'],
            'region' => $shippingData['shipping_state_province'] ?? null,
            'postcode' => $shippingData['shipping_postal_code'] ?? null,
            'country' => $shippingData['shipping_country'],
            'is_rural' => $shippingData['shipping_is_rural'] ?? null,
        ];
        $selectedShipping = $this->shippingQuoteService->selectedOption(
            $cart,
            $quoteAddress,
            $shippingMethodCode,
        );
        $shippingOption = $selectedShipping['option'];
        $shipping = (float) $selectedShipping['totals']['shipping'];
        $tax = (float) $selectedShipping['totals']['tax'];
        $total = (float) $selectedShipping['totals']['total'];

        $order = DB::transaction(function () use (
            $cart,
            $shippingData,
            $note,
            $guestEmail,
            $items,
            $subtotal,
            $shipping,
            $tax,
            $total,
            $shippingOption,
            $selectedShipping,
        ): Order {
            $order = Order::query()->create([
                'user_id' => $cart->user_id,
                'guest_email' => $cart->user_id === null ? Str::lower((string) $guestEmail) : null,
                'guest_order_token' => $cart->user_id === null ? Str::random(64) : null,
                'status' => OrderStatus::Pending,
                'subtotal_usd' => $subtotal,
                'shipping_usd' => $shipping,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'total_amount' => $total,
                'total_usd' => $total,
                'currency' => (string) $selectedShipping['totals']['currency'],
                'shipping_name' => $shippingData['shipping_name'],
                'shipping_phone' => $shippingData['shipping_phone'] ?? null,
                'shipping_address_line1' => $shippingData['shipping_address_line1'],
                'shipping_address_line2' => $shippingData['shipping_address_line2'] ?? null,
                'shipping_city' => $shippingData['shipping_city'],
                'shipping_state_province' => $shippingData['shipping_state_province'] ?? null,
                'shipping_postal_code' => $shippingData['shipping_postal_code'] ?? null,
                'shipping_country' => $shippingData['shipping_country'],
                'shipping_method_code' => $shippingOption['code'],
                'shipping_method_label' => $shippingOption['label'],
                'shipping_service_code' => $shippingOption['service_code'] ?? null,
                'shipping_eta_min_days' => $shippingOption['eta_min_days'] ?? null,
                'shipping_eta_max_days' => $shippingOption['eta_max_days'] ?? null,
                'shipping_quote_snapshot' => $selectedShipping['snapshot'],
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

            $this->emailDispatchService->sendEventSafely('order.created', $payload, [
                'related' => $order,
                'idempotency_key' => 'order.created:'.$order->id,
            ]);

            $this->emailDispatchService->sendEventSafely('order.admin_new_order', $payload, [
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
                'order' => ['Only pending order requests can be cancelled.'],
            ]);
        }

        $order->forceFill([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();

        $order = $order->fresh(['user', 'items.product']);

        DB::afterCommit(fn () => $this->emailDispatchService->sendEventSafely(
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

        DB::afterCommit(fn () => $this->emailDispatchService->sendEventSafely(
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
