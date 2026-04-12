<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\HomeSectionResource;
use App\Http\Resources\MaterialResource;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function show(ContentManagementService $contentManagementService): JsonResponse
    {
        $content = $contentManagementService->getHomepageContent();

        return $this->successResponse([
            'home_sections' => HomeSectionResource::collection($content['home_sections']),
            'materials' => MaterialResource::collection($content['materials']),
            'articles' => ArticleResource::collection($content['articles']),
        ]);
    }
}
