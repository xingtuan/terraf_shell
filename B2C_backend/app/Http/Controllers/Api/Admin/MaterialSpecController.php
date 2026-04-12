<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertMaterialSpecRequest;
use App\Http\Resources\MaterialSpecResource;
use App\Models\MaterialSpec;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class MaterialSpecController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $specs = $contentManagementService->listMaterialSpecsForAdmin($request->validated());

        return $this->paginatedResponse(
            $specs,
            MaterialSpecResource::collection($specs->getCollection())
        );
    }

    public function store(
        UpsertMaterialSpecRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $spec = $contentManagementService->createMaterialSpec($request->validated());

        return $this->successResponse(
            new MaterialSpecResource($spec),
            'Material spec created successfully.',
            201
        );
    }

    public function show(MaterialSpec $materialSpec): JsonResponse
    {
        return $this->successResponse(new MaterialSpecResource($materialSpec->load('material')));
    }

    public function update(
        UpsertMaterialSpecRequest $request,
        MaterialSpec $materialSpec,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $materialSpec = $contentManagementService->updateMaterialSpec($materialSpec, $request->validated());

        return $this->successResponse(
            new MaterialSpecResource($materialSpec),
            'Material spec updated successfully.'
        );
    }

    public function destroy(MaterialSpec $materialSpec, ContentManagementService $contentManagementService): JsonResponse
    {
        $contentManagementService->deleteMaterialSpec($materialSpec);

        return $this->successResponse(null, 'Material spec deleted successfully.');
    }
}
