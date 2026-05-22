<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function store(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->favoritePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'favorites_count' => (int) $post->favorites_count,
            'is_favorited' => true,
        ], __('api.community.favorite_added'));
    }

    public function destroy(Post $post, InteractionService $interactionService): JsonResponse
    {
        $post = $interactionService->unfavoritePost($post, request()->user());

        return $this->successResponse([
            'post_id' => $post->id,
            'favorites_count' => (int) $post->favorites_count,
            'is_favorited' => false,
        ], __('api.community.favorite_removed'));
    }
}
