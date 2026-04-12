<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertMaterialStorySectionRequest;
use App\Http\Resources\MaterialStorySectionResource;
use App\Models\MaterialStorySection;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class MaterialStorySectionController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $sections = $contentManagementService->listMaterialStorySectionsForAdmin($request->validated());

        return $this->paginatedResponse(
            $sections,
            MaterialStorySectionResource::collection($sections->getCollection())
        );
    }

    public function store(
        UpsertMaterialStorySectionRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $section = $contentManagementService->createMaterialStorySection($request->validated());

        return $this->successResponse(
            new MaterialStorySectionResource($section),
            'Material story section created successfully.',
            201
        );
    }

    public function show(MaterialStorySection $materialStorySection): JsonResponse
    {
        return $this->successResponse(
            new MaterialStorySectionResource($materialStorySection->load('material'))
        );
    }

    public function update(
        UpsertMaterialStorySectionRequest $request,
        MaterialStorySection $materialStorySection,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $materialStorySection = $contentManagementService->updateMaterialStorySection(
            $materialStorySection,
            $request->validated()
        );

        return $this->successResponse(
            new MaterialStorySectionResource($materialStorySection),
            'Material story section updated successfully.'
        );
    }

    public function destroy(
        MaterialStorySection $materialStorySection,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $contentManagementService->deleteMaterialStorySection($materialStorySection);

        return $this->successResponse(null, 'Material story section deleted successfully.');
    }
}
