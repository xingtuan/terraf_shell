<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(ListPublishedProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = min((int) ($validated['per_page'] ?? 12), 50);

        $products = Product::query()
            ->active()
            ->when(
                filled($validated['category'] ?? null),
                fn ($query) => $query->where('category', $validated['category'])
            )
            ->when(
                filled($validated['model'] ?? null),
                fn ($query) => $query->where('model', $validated['model'])
            )
            ->when(
                filled($validated['color'] ?? null),
                fn ($query) => $query->where('color', $validated['color'])
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse(
            $products,
            ProductResource::collection($products->getCollection())
        );
    }

    public function featured(): JsonResponse
    {
        $products = Product::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get();

        return $this->successResponse(ProductResource::collection($products));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->successResponse(new ProductResource($product));
    }
}
