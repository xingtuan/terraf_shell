<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(
        ListPublishedArticlesRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $articles = $contentManagementService->listPublicArticles($request->validated());

        return $this->paginatedResponse(
            $articles,
            ArticleResource::collection($articles->getCollection())
        );
    }

    public function show(string $identifier, ContentManagementService $contentManagementService): JsonResponse
    {
        $article = $contentManagementService->findPublicArticle($identifier);

        return $this->successResponse(new ArticleResource($article));
    }
}
