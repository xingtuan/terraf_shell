<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(TaxonomyService $taxonomyService): JsonResponse
    {
        $categories = $taxonomyService->listPublicCategories();

        return $this->successResponse(CategoryResource::collection($categories));
    }
}
