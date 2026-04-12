<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListCategoriesRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(ListCategoriesRequest $request, TaxonomyService $taxonomyService): JsonResponse
    {
        $this->ensureAdmin($request);

        $categories = $taxonomyService->listCategoriesForAdmin($request->validated());

        return $this->paginatedResponse(
            $categories,
            CategoryResource::collection($categories->getCollection())
        );
    }

    public function store(StoreCategoryRequest $request, TaxonomyService $taxonomyService): JsonResponse
    {
        $category = $taxonomyService->createCategory($request->validated());

        return $this->successResponse(
            new CategoryResource($category),
            'Category created successfully.',
            201
        );
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $this->ensureAdmin($request);

        $category->loadCount(['posts as posts_count' => fn ($query) => $query->approved()]);

        return $this->successResponse(new CategoryResource($category));
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        TaxonomyService $taxonomyService
    ): JsonResponse {
        $category = $taxonomyService->updateCategory($category, $request->validated());
        $category->loadCount(['posts as posts_count' => fn ($query) => $query->approved()]);

        return $this->successResponse(
            new CategoryResource($category),
            'Category updated successfully.'
        );
    }

    public function destroy(Request $request, Category $category, TaxonomyService $taxonomyService): JsonResponse
    {
        $this->ensureAdmin($request);
        $taxonomyService->deleteCategory($category);

        return $this->successResponse(null, 'Category deleted successfully.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
