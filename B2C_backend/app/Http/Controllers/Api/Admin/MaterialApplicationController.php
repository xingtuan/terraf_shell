<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertMaterialApplicationRequest;
use App\Http\Resources\MaterialApplicationResource;
use App\Models\MaterialApplication;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class MaterialApplicationController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $applications = $contentManagementService->listMaterialApplicationsForAdmin($request->validated());

        return $this->paginatedResponse(
            $applications,
            MaterialApplicationResource::collection($applications->getCollection())
        );
    }

    public function store(
        UpsertMaterialApplicationRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $application = $contentManagementService->createMaterialApplication($request->validated());

        return $this->successResponse(
            new MaterialApplicationResource($application),
            __('api.admin.material_application_created'),
            201
        );
    }

    public function show(MaterialApplication $materialApplication): JsonResponse
    {
        return $this->successResponse(
            new MaterialApplicationResource($materialApplication->load('material'))
        );
    }

    public function update(
        UpsertMaterialApplicationRequest $request,
        MaterialApplication $materialApplication,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $materialApplication = $contentManagementService->updateMaterialApplication(
            $materialApplication,
            $request->validated()
        );

        return $this->successResponse(
            new MaterialApplicationResource($materialApplication),
            __('api.admin.material_application_updated')
        );
    }

    public function destroy(
        MaterialApplication $materialApplication,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $contentManagementService->deleteMaterialApplication($materialApplication);

        return $this->successResponse(null, __('api.admin.material_application_deleted'));
    }
}
