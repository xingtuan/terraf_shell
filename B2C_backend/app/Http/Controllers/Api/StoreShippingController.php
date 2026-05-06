<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\Shipping\AddressLookupService;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreShippingController extends Controller
{
    public function __construct(
        private readonly AddressLookupService $addressLookupService,
        private readonly CartService $cartService,
        private readonly ShippingQuoteService $shippingQuoteService,
    ) {}

    public function addressSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return $this->successResponse($this->addressLookupService->search($validated['query']));
    }

    public function addressDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255'],
        ]);

        return $this->successResponse($this->addressLookupService->details($validated['id']));
    }

    public function shippingOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'array'],
            'address.line1' => ['nullable', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.region' => ['nullable', 'string', 'max:255'],
            'address.postcode' => ['required', 'string', 'max:20'],
            'address.country' => ['required', 'string', 'size:2', 'in:NZ'],
            'address.is_rural' => ['nullable', 'boolean'],
        ]);

        $cart = $this->cartService->getOrCreateCart($request);

        return $this->successResponse(
            $this->shippingQuoteService->quote($cart, $validated['address']),
        );
    }
}
