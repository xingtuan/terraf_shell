<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse(
            $orders,
            OrderResource::collection($orders->getCollection()),
        );
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->with(['items.product'])
            ->firstOrFail();

        return $this->successResponse(new OrderResource($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $address = null;

        if (filled($validated['address_id'] ?? null)) {
            $address = Address::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail((int) $validated['address_id']);
        }

        $shippingData = $address ? [
            'shipping_name' => $address->recipient_name,
            'shipping_phone' => $address->phone,
            'shipping_address_line1' => $address->address_line1,
            'shipping_address_line2' => $address->address_line2,
            'shipping_city' => $address->city,
            'shipping_state_province' => $address->state_province,
            'shipping_postal_code' => $address->postal_code,
            'shipping_country' => $address->country,
        ] : [
            'shipping_name' => $validated['shipping_name'],
            'shipping_phone' => $validated['shipping_phone'] ?? null,
            'shipping_address_line1' => $validated['shipping_address_line1'],
            'shipping_address_line2' => $validated['shipping_address_line2'] ?? null,
            'shipping_city' => $validated['shipping_city'],
            'shipping_state_province' => $validated['shipping_state_province'] ?? null,
            'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
            'shipping_country' => $validated['shipping_country'],
        ];

        $cart = $this->cartService->getOrCreateCart($request);
        $order = $this->orderService->createFromCart(
            $cart,
            $shippingData,
            (string) ($validated['customer_note'] ?? ''),
        );

        return $this->successResponse(
            new OrderResource($order),
            'Order created successfully.',
            201,
        );
    }

    public function destroy(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $order = $this->orderService->cancelOrder($order);

        return $this->successResponse(
            new OrderResource($order),
            'Order cancelled successfully.',
        );
    }
}
