<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeSectionResource;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    public function index(Request $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $sections = $contentManagementService->listPublicHomeSections(
            $request->query('page', 'home')
        );

        return $this->successResponse(HomeSectionResource::collection($sections));
    }
}
