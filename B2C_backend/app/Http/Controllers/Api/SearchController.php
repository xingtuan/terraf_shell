<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchPostsRequest;
use App\Http\Resources\PostResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function posts(SearchPostsRequest $request, SearchService $searchService): JsonResponse
    {
        $posts = $searchService->searchPosts(
            $request->validated(),
            $request->user(),
        );

        return $this->paginatedResponse(
            $posts,
            PostResource::collection($posts->getCollection())
        );
    }
}
