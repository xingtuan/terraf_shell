<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class MaterialController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $materials = $contentManagementService->listMaterialsForAdmin($request->validated());

        return $this->paginatedResponse(
            $materials,
            MaterialResource::collection($materials->getCollection())
        );
    }

    public function store(
        UpsertMaterialRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $material = $contentManagementService->createMaterial($request->validated());

        return $this->successResponse(
            new MaterialResource($material),
            'Material created successfully.',
            201
        );
    }

    public function show(Material $material, ContentManagementService $contentManagementService): JsonResponse
    {
        return $this->successResponse(
            new MaterialResource($contentManagementService->loadMaterial($material))
        );
    }

    public function update(
        UpsertMaterialRequest $request,
        Material $material,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $material = $contentManagementService->updateMaterial($material, $request->validated());

        return $this->successResponse(
            new MaterialResource($material),
            'Material updated successfully.'
        );
    }

    public function destroy(Material $material, ContentManagementService $contentManagementService): JsonResponse
    {
        $contentManagementService->deleteMaterial($material);

        return $this->successResponse(null, 'Material deleted successfully.');
    }
}
