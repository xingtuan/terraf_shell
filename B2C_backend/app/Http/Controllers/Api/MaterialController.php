<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedMaterialsRequest;
use App\Http\Resources\MaterialResource;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class MaterialController extends Controller
{
    public function index(
        ListPublishedMaterialsRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $materials = $contentManagementService->listPublicMaterials($request->validated());

        return $this->successResponse(MaterialResource::collection($materials));
    }

    public function show(string $identifier, ContentManagementService $contentManagementService): JsonResponse
    {
        $material = $contentManagementService->findPublicMaterial($identifier);

        return $this->successResponse(new MaterialResource($material));
    }
}
