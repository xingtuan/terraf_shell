<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeSectionResource;
use App\Models\HomeSection;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeSectionController extends Controller
{
    public function index(Request $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'string', Rule::in(HomeSection::allowedPageKeys())],
            'page_key' => ['nullable', 'string', Rule::in(HomeSection::allowedPageKeys())],
        ]);

        $sections = $contentManagementService->listPublicHomeSections(
            $validated['page'] ?? $validated['page_key'] ?? 'home'
        );

        return $this->successResponse(HomeSectionResource::collection($sections));
    }
}
