<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\ListPostsRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(ListPostsRequest $request, PostService $postService): JsonResponse
    {
        $posts = $postService->list($request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $posts,
            PostResource::collection($posts->getCollection())
        );
    }

    public function store(StorePostRequest $request, PostService $postService): JsonResponse
    {
        $post = $postService->create($request->user(), $request->validated());

        return $this->successResponse(
            (new PostResource($post))->includeDetailFields(),
            'Post created successfully.',
            201
        );
    }

    public function show(Request $request, string $identifier, PostService $postService): JsonResponse
    {
        $post = $postService->findForDisplay($identifier, $request->user('sanctum'));

        return $this->successResponse((new PostResource($post))->includeDetailFields());
    }

    public function update(UpdatePostRequest $request, Post $post, PostService $postService): JsonResponse
    {
        $post = $postService->update($request->user(), $post, $request->validated());

        return $this->successResponse(
            (new PostResource($post))->includeDetailFields(),
            'Post updated successfully.'
        );
    }

    public function destroy(Post $post, PostService $postService): JsonResponse
    {
        $this->authorize('delete', $post);
        $postService->delete($post);

        return $this->successResponse(null, 'Post deleted successfully.');
    }
}
