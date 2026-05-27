<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailPayloadFactory;
use App\Services\Shipping\ShippingQuoteService;
use App\Services\Store\CartPricingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly EmailDispatchService $emailDispatchService,
        private readonly EmailPayloadFactory $emailPayloadFactory,
        private readonly ShippingQuoteService $shippingQuoteService,
        private readonly CartPricingService $pricing,
    ) {}

    public function createFromCart(
        Cart $cart,
        array $shippingData,
        string $note = '',
        ?string $guestEmail = null,
        string $shippingMethodCode = 'standard',
    ): Order {
        $cart->loadMissing(['user', 'items.product.variants', 'items.variant']);

        if ($cart->user_id === null && blank($guestEmail)) {
            throw ValidationException::withMessages([
                'guest_email' => [__('api.orders.guest_email_required')],
            ]);
        }

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [__('api.orders.cart_empty')],
            ]);
        }

        $items = $cart->items->map(function (CartItem $item): array {
            $product = $item->product;

            $variant = $item->variant ?? $product?->defaultVariant();

            if (
                $product === null
                || $variant === null
                || ! $product->is_active
                || ! $product->isPublished()
                || $product->inquiry_only
                || ! $variant->isPurchasable()
            ) {
                throw ValidationException::withMessages([
                    'cart' => [__('api.orders.items_unavailable')],
                ]);
            }

            if (! $variant->canFulfillQuantity((int) $item->quantity)) {
                throw ValidationException::withMessages([
                    'cart' => [__('api.orders.stock_exceeded')],
                ]);
            }

            $unitPrice = $this->pricing->lineUnitPrice($item);

            return [
                'variant' => $variant,
                'snapshot' => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant->sku,
                    'product_title' => $product->name,
                    'variant_title' => $variant->displayTitle(),
                    'variant_sku' => $variant->sku,
                    'option_values' => $variant->option_values ?? [],
                    'quantity' => $item->quantity,
                    'unit_price_usd' => $unitPrice,
                    'unit_price_amount' => $unitPrice,
                    'currency' => $variant->currency ?: 'NZD',
                    'subtotal_usd' => $unitPrice * $item->quantity,
                ],
            ];
        });

        $subtotal = round((float) $items->sum(fn (array $item): float => (float) $item['snapshot']['subtotal_usd']), 2);
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
        $shipping = (float) ($shippingOption['amount'] ?? 0);
        $quoteSubtotal = (float) data_get($selectedShipping, 'totals.subtotal', $subtotal);

        if (abs($quoteSubtotal - $subtotal) > 0.01) {
            Log::warning('Shipping quote subtotal differed from order subtotal during order creation.', [
                'cart_id' => $cart->id,
                'quote_subtotal' => $quoteSubtotal,
                'order_subtotal' => $subtotal,
                'shipping_method_code' => $shippingMethodCode,
            ]);
        }

        $taxSnapshot = $this->pricing->taxSnapshot($subtotal, $shipping);
        $tax = (float) $taxSnapshot['amount'];
        $total = $this->pricing->total($subtotal, $shipping);
        $currency = (string) data_get($selectedShipping, 'totals.currency', config('store.currency', 'NZD'));
        $authoritativeTax = [
            'label' => $taxSnapshot['label'],
            'rate' => $taxSnapshot['rate'],
            'amount' => $this->pricing->formatMoney($tax),
            'included' => $taxSnapshot['included'],
        ];
        $authoritativeTotals = [
            'subtotal' => $this->pricing->formatMoney($subtotal),
            'shipping' => $this->pricing->formatMoney($shipping),
            'tax' => $this->pricing->formatMoney($tax),
            'total' => $this->pricing->formatMoney($total),
            'currency' => $currency,
        ];

        $selectedShipping['tax'] = $authoritativeTax;
        $selectedShipping['totals'] = $authoritativeTotals;
        if (! isset($selectedShipping['snapshot']) || ! is_array($selectedShipping['snapshot'])) {
            $selectedShipping['snapshot'] = [];
        }
        $selectedShipping['snapshot']['tax'] = $authoritativeTax;
        $selectedShipping['snapshot']['totals'] = $authoritativeTotals;

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
                $snapshot = $item['snapshot'];
                $variant = $item['variant'];

                $order->items()->create($snapshot);
                $this->deductVariantStock($variant, (int) $snapshot['quantity']);
            }

            $cart->items()->delete();

            return $order;
        });

        $order = $order->fresh(['user', 'items.product.variants', 'items.variant']);

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
                'order' => [__('api.orders.not_cancellable')],
            ]);
        }

        DB::transaction(function () use ($order): void {
            $order->loadMissing(['items.variant']);

            foreach ($order->items as $item) {
                $variant = $item->variant;

                if ($variant === null || $variant->inventory_policy !== 'deny' || $variant->stock_quantity === null) {
                    continue;
                }

                $variant->adjustStock((int) $item->quantity, 'order_cancelled', 'Order '.$order->order_number.' cancelled.');
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();
        });

        $order = $order->fresh(['user', 'items.product.variants', 'items.variant']);

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
        $order->loadMissing(['user', 'items.product.variants', 'items.variant']);
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
            ->with(['items.product.variants', 'items.variant'])
            ->latest()
            ->get();
    }

    private function deductVariantStock(ProductVariant $variant, int $quantity): void
    {
        if ($variant->inventory_policy !== 'deny' || $variant->stock_quantity === null) {
            return;
        }

        $variant->adjustStock(
            -1 * max(1, $quantity),
            'order_created',
            'Stock reserved during order creation.',
        );
    }
}
