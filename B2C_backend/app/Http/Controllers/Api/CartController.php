<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\MergeCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $response = $this->successResponse(new CartResource($cart));

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->addItem(
            $cart,
            (int) $request->validated('product_id'),
            (int) ($request->validated('quantity') ?? 1),
            $this->variantIdFromValidated($request->validated()),
        );
        $quantityAdjustment = $this->cartService->pullQuantityAdjustment();

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product.variants', 'items.variant'])),
            $quantityAdjustment['message'] ?? __('api.cart.item_added'),
            200,
            $quantityAdjustment !== null ? ['cart_adjustment' => $quantityAdjustment] : [],
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function updateItem(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->updateItem(
            $cart,
            $productId,
            (int) $request->validated('quantity'),
            $this->variantIdFromValidated($request->validated()),
        );

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product.variants', 'items.variant'])),
            __('api.cart.updated'),
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $variantId = $request->integer('variant_id') ?: $request->integer('product_variant_id') ?: null;
        $this->cartService->removeItem($cart, $productId, $variantId);

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product.variants', 'items.variant'])),
            __('api.cart.item_removed'),
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->clearCart($cart);

        $response = $this->successResponse([
            'cleared' => true,
        ], __('api.cart.cleared'));

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function merge(MergeCartRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->cartService->mergeGuestCart($validated['session_key'], $request->user('sanctum')->id);
        $cart = $this->cartService->getOrCreateCart($request);

        return $this->successResponse(
            new CartResource($cart),
            __('api.cart.guest_merged'),
        )->withoutCookie(CartService::COOKIE_NAME)
            ->withoutCookie(CartService::LEGACY_COOKIE_NAME)
            ->cookie(Cookie::forget(CartService::COOKIE_NAME))
            ->cookie(Cookie::forget(CartService::LEGACY_COOKIE_NAME));
    }

    private function withGuestCartCookie(
        JsonResponse $response,
        Request $request,
        ?string $sessionKey,
    ): JsonResponse {
        if ($request->user('sanctum') !== null || blank($sessionKey)) {
            return $response;
        }

        return $response
            ->withoutCookie(CartService::LEGACY_COOKIE_NAME)
            ->cookie(Cookie::forget(CartService::LEGACY_COOKIE_NAME))
            ->cookie(
                CartService::COOKIE_NAME,
                $sessionKey,
                CartService::COOKIE_TTL_MINUTES,
                '/',
                null,
                false,
                false,
                false,
                'lax',
            );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function variantIdFromValidated(array $validated): ?int
    {
        $variantId = $validated['variant_id'] ?? $validated['product_variant_id'] ?? null;

        return $variantId !== null ? (int) $variantId : null;
    }
}
