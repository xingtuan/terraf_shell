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
        );

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product'])),
            'Item added to cart.',
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function updateItem(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->updateItem($cart, $productId, (int) $request->validated('quantity'));

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product'])),
            'Cart updated.',
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->removeItem($cart, $productId);

        $response = $this->successResponse(
            new CartResource($cart->fresh(['items.product'])),
            'Item removed from cart.',
        );

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $this->cartService->clearCart($cart);

        $response = $this->successResponse([
            'cleared' => true,
        ], 'Cart cleared.');

        return $this->withGuestCartCookie($response, $request, $cart->session_key);
    }

    public function merge(MergeCartRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->cartService->mergeGuestCart($validated['session_key'], $request->user()->id);
        $cart = $this->cartService->getOrCreateCart($request);

        return $this->successResponse(
            new CartResource($cart),
            'Guest cart merged successfully.',
        )->withoutCookie(CartService::COOKIE_NAME)
            ->cookie(Cookie::forget(CartService::COOKIE_NAME));
    }

    private function withGuestCartCookie(
        JsonResponse $response,
        Request $request,
        ?string $sessionKey,
    ): JsonResponse {
        if ($request->user() !== null || blank($sessionKey)) {
            return $response;
        }

        return $response->cookie(
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
}
