<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertHomeSectionRequest;
use App\Http\Resources\HomeSectionResource;
use App\Models\HomeSection;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class HomeSectionController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $sections = $contentManagementService->listHomeSectionsForAdmin($request->validated());

        return $this->paginatedResponse(
            $sections,
            HomeSectionResource::collection($sections->getCollection())
        );
    }

    public function store(
        UpsertHomeSectionRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $section = $contentManagementService->createHomeSection($request->validated());

        return $this->successResponse(
            new HomeSectionResource($section),
            'Home section created successfully.',
            201
        );
    }

    public function show(HomeSection $homeSection): JsonResponse
    {
        return $this->successResponse(new HomeSectionResource($homeSection));
    }

    public function update(
        UpsertHomeSectionRequest $request,
        HomeSection $homeSection,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $homeSection = $contentManagementService->updateHomeSection($homeSection, $request->validated());

        return $this->successResponse(
            new HomeSectionResource($homeSection),
            'Home section updated successfully.'
        );
    }

    public function destroy(HomeSection $homeSection, ContentManagementService $contentManagementService): JsonResponse
    {
        $contentManagementService->deleteHomeSection($homeSection);

        return $this->successResponse(null, 'Home section deleted successfully.');
    }
}
