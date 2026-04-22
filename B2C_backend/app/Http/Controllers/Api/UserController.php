<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\ListPostsRequest;
use App\Http\Requests\User\ListUserCommentsRequest;
use App\Http\Requests\User\ListUserRelationsRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserDirectoryService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function show(User $user, UserDirectoryService $userDirectoryService): JsonResponse
    {
        $user = $userDirectoryService->show($user, request()->user('sanctum'));

        return $this->successResponse(new UserResource($user));
    }

    public function posts(
        ListPostsRequest $request,
        User $user,
        UserDirectoryService $userDirectoryService
    ): JsonResponse {
        $posts = $userDirectoryService->listPosts($user, $request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $posts,
            PostResource::collection($posts->getCollection())
        );
    }

    public function favorites(
        ListPostsRequest $request,
        User $user,
        UserDirectoryService $userDirectoryService
    ): JsonResponse {
        $posts = $userDirectoryService->listFavorites($user, $request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $posts,
            PostResource::collection($posts->getCollection())
        );
    }

    public function comments(
        ListUserCommentsRequest $request,
        User $user,
        UserDirectoryService $userDirectoryService
    ): JsonResponse {
        $comments = $userDirectoryService->listComments($user, $request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $comments,
            CommentResource::collection($comments->getCollection())
        );
    }

    public function followers(
        ListUserRelationsRequest $request,
        User $user,
        UserDirectoryService $userDirectoryService
    ): JsonResponse {
        $followers = $userDirectoryService->listFollowers($user, $request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $followers,
            UserResource::collection($followers->getCollection())
        );
    }

    public function following(
        ListUserRelationsRequest $request,
        User $user,
        UserDirectoryService $userDirectoryService
    ): JsonResponse {
        $following = $userDirectoryService->listFollowing($user, $request->validated(), $request->user('sanctum'));

        return $this->paginatedResponse(
            $following,
            UserResource::collection($following->getCollection())
        );
    }
}
