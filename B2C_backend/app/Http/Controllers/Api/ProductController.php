<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedProductsRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductCatalogService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(
        ListPublishedProductsRequest $request,
        ProductCatalogService $productCatalogService
    ): JsonResponse {
        $products = $productCatalogService->listPublicProducts($request->validated());

        return $this->successResponse(ProductResource::collection($products));
    }

    public function featured(
        ListPublishedProductsRequest $request,
        ProductCatalogService $productCatalogService
    ): JsonResponse {
        $products = $productCatalogService->listPublicProducts([
            ...$request->validated(),
            'featured' => true,
        ]);

        return $this->successResponse(ProductResource::collection($products));
    }

    public function show(string $identifier, ProductCatalogService $productCatalogService): JsonResponse
    {
        $product = $productCatalogService->findPublicProduct($identifier);

        return $this->successResponse(new ProductResource($product));
    }
}
