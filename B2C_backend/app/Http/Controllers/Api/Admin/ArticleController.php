<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\ListContentRequest;
use App\Http\Requests\Admin\Cms\UpsertArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ContentManagementService;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(ListContentRequest $request, ContentManagementService $contentManagementService): JsonResponse
    {
        $articles = $contentManagementService->listArticlesForAdmin($request->validated());

        return $this->paginatedResponse(
            $articles,
            ArticleResource::collection($articles->getCollection())
        );
    }

    public function store(
        UpsertArticleRequest $request,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $article = $contentManagementService->createArticle($request->validated());

        return $this->successResponse(
            new ArticleResource($article),
            'Article created successfully.',
            201
        );
    }

    public function show(Article $article): JsonResponse
    {
        return $this->successResponse(new ArticleResource($article));
    }

    public function update(
        UpsertArticleRequest $request,
        Article $article,
        ContentManagementService $contentManagementService
    ): JsonResponse {
        $article = $contentManagementService->updateArticle($article, $request->validated());

        return $this->successResponse(
            new ArticleResource($article),
            'Article updated successfully.'
        );
    }

    public function destroy(Article $article, ContentManagementService $contentManagementService): JsonResponse
    {
        $contentManagementService->deleteArticle($article);

        return $this->successResponse(null, 'Article deleted successfully.');
    }
}
