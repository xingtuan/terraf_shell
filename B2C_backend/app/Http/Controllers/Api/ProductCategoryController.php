<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedProductCategoriesRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Services\ProductCatalogService;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(
        ListPublishedProductCategoriesRequest $request,
        ProductCatalogService $productCatalogService
    ): JsonResponse {
        $categories = $productCatalogService->listPublicCategories();

        return $this->successResponse(ProductCategoryResource::collection($categories));
    }
}
