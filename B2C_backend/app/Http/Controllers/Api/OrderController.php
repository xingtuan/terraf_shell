<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly ShippingQuoteService $shippingQuoteService,
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
        $user = $request->user('sanctum');
        $address = null;

        if (filled($validated['address_id'] ?? null)) {
            $address = Address::query()
                ->where('user_id', $user?->id)
                ->findOrFail((int) $validated['address_id']);

            if (strtoupper((string) $address->country) !== 'NZ') {
                throw ValidationException::withMessages([
                    'address_id' => ['Saved checkout addresses must be in New Zealand.'],
                ]);
            }

            if (blank($address->postal_code)) {
                throw ValidationException::withMessages([
                    'address_id' => ['Saved checkout addresses need a New Zealand postcode.'],
                ]);
            }
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
            'shipping_is_rural' => null,
        ] : [
            'shipping_name' => $validated['shipping_name'],
            'shipping_phone' => $validated['shipping_phone'] ?? null,
            'shipping_address_line1' => $validated['shipping_address_line1'],
            'shipping_address_line2' => $validated['shipping_address_line2'] ?? null,
            'shipping_city' => $validated['shipping_city'],
            'shipping_state_province' => $validated['shipping_state_province'] ?? null,
            'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
            'shipping_country' => $validated['shipping_country'],
            'shipping_is_rural' => $validated['shipping_is_rural'] ?? null,
        ];

        $this->shippingQuoteService->validateAddress([
            'line1' => $shippingData['shipping_address_line1'],
            'line2' => $shippingData['shipping_address_line2'] ?? null,
            'city' => $shippingData['shipping_city'],
            'region' => $shippingData['shipping_state_province'] ?? null,
            'postcode' => $shippingData['shipping_postal_code'] ?? null,
            'country' => $shippingData['shipping_country'],
            'is_rural' => $shippingData['shipping_is_rural'] ?? null,
        ]);

        $cart = $this->cartService->getOrCreateCart($request);
        $order = $this->orderService->createFromCart(
            $cart,
            $shippingData,
            (string) ($validated['customer_note'] ?? ''),
            $user === null ? (string) ($validated['guest_email'] ?? '') : null,
            (string) $validated['shipping_method_code'],
        );

        return $this->successResponse(
            new OrderResource($order),
            'Order request submitted successfully.',
            201,
        );
    }

    public function showGuest(Request $request, string $orderNumber): JsonResponse
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            abort(404);
        }

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->whereNull('user_id')
            ->where('guest_order_token', $token)
            ->with(['items.product'])
            ->firstOrFail();

        return $this->successResponse(new OrderResource($order));
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
            'Order request cancelled successfully.',
        );
    }
}
