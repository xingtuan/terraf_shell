<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeSectionResource;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class HomeSectionController extends Controller
{
    public function index(ContentManagementService $contentManagementService): JsonResponse
    {
        $sections = $contentManagementService->listPublicHomeSections();

        return $this->successResponse(HomeSectionResource::collection($sections));
    }
}
