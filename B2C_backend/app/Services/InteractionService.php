<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class InteractionService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly PostRankingService $postRankingService,
    ) {}

    public function likePost(Post $post, User $user): Post
    {
        $this->ensureApprovedPost($post);
        $post->loadMissing('user');

        $like = PostLike::query()->firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        if ($like->wasRecentlyCreated) {
            $post->increment('likes_count');
            $this->postRankingService->refreshScores($post->fresh());

            $this->notificationService->dispatch(
                $post->user,
                $user,
                NotificationType::Like,
                $post
            );
        }

        return $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
    }

    public function unlikePost(Post $post, User $user): Post
    {
        $this->ensureApprovedPost($post);

        $deleted = PostLike::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            $post->decrement('likes_count');
            $this->postRankingService->refreshScores($post->fresh());
        }

        return $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
    }

    public function likeComment(Comment $comment, User $user): Comment
    {
        $this->ensureApprovedComment($comment);
        $comment->loadMissing('user.profile');

        $like = CommentLike::query()->firstOrCreate([
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        if ($like->wasRecentlyCreated) {
            $comment->increment('likes_count');

            $this->notificationService->dispatch(
                $comment->user,
                $user,
                NotificationType::Like,
                $comment,
                ['post_id' => $comment->post_id]
            );
        }

        return $comment->fresh()->load('user.profile');
    }

    public function unlikeComment(Comment $comment, User $user): Comment
    {
        $this->ensureApprovedComment($comment);

        $deleted = CommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            $comment->decrement('likes_count');
        }

        return $comment->fresh()->load('user.profile');
    }

    public function favoritePost(Post $post, User $user): Post
    {
        $this->ensureApprovedPost($post);

        $favorite = Favorite::query()->firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        if ($favorite->wasRecentlyCreated) {
            $post->increment('favorites_count');
            $this->postRankingService->refreshScores($post->fresh());
        }

        return $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
    }

    public function unfavoritePost(Post $post, User $user): Post
    {
        $this->ensureApprovedPost($post);

        $deleted = Favorite::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            $post->decrement('favorites_count');
            $this->postRankingService->refreshScores($post->fresh());
        }

        return $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
    }

    public function follow(User $target, User $actor): User
    {
        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot follow yourself.'],
            ]);
        }

        Follow::query()->firstOrCreate([
            'follower_id' => $actor->id,
            'following_id' => $target->id,
        ]);

        $this->notificationService->dispatch(
            $target,
            $actor,
            NotificationType::Follow,
            $target
        );

        return $target->fresh()->load('profile');
    }

    public function unfollow(User $target, User $actor): User
    {
        Follow::query()
            ->where('follower_id', $actor->id)
            ->where('following_id', $target->id)
            ->delete();

        return $target->fresh()->load('profile');
    }

    private function ensureApprovedPost(Post $post): void
    {
        if ($post->status !== ContentStatus::Approved->value) {
            throw $this->notFound(Post::class, $post->id);
        }
    }

    private function ensureApprovedComment(Comment $comment): void
    {
        if ($comment->status !== ContentStatus::Approved->value) {
            throw $this->notFound(Comment::class, $comment->id);
        }
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
