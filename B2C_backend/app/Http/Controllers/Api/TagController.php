<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(TaxonomyService $taxonomyService): JsonResponse
    {
        $tags = $taxonomyService->listPublicTags();

        return $this->successResponse(TagResource::collection($tags));
    }
}
