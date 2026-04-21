<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly PostRankingService $postRankingService,
        private readonly GovernanceService $governanceService,
    ) {}

    public function listForPost(Post $post, ?User $viewer = null): Collection
    {
        if (! $post->isVisibleTo($viewer)) {
            throw $this->notFound(Post::class, $post->id);
        }

        $query = Comment::query()
            ->where('post_id', $post->id)
            ->with('user.profile')
            ->orderBy('created_at');

        if ($viewer?->isAdmin()) {
            $comments = $query->get();
        } elseif ($viewer !== null) {
            $comments = $query
                ->where(function ($commentQuery) use ($viewer): void {
                    $commentQuery
                        ->where('status', ContentStatus::Approved->value)
                        ->orWhere('user_id', $viewer->id);
                })
                ->get();
        } else {
            $comments = $query->where('status', ContentStatus::Approved->value)->get();
        }

        $this->hydrateViewerState($comments, $viewer);

        return $this->buildTree($comments);
    }

    public function create(Post $post, User $user, array $data): Comment
    {
        if ($post->status !== ContentStatus::Approved->value && ! $user->isAdmin() && $post->user_id !== $user->id) {
            throw $this->notFound(Post::class, $post->id);
        }

        return DB::transaction(function () use ($post, $user, $data): Comment {
            $comment = Comment::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => $data['content'],
                'status' => $user->isAdmin() ? ContentStatus::Approved->value : ContentStatus::Pending->value,
            ]);

            if ($comment->status === ContentStatus::Approved->value) {
                $post->increment('comments_count');
                $this->notificationService->notifyCommentCreated($post, $comment, $user);
            }

            $this->governanceService->flagSensitiveContent(
                $user,
                ['content' => $comment->content],
                'comment',
                $comment
            );

            $this->postRankingService->refreshScores($post->fresh());

            return $this->reload($comment, $user);
        });
    }

    public function reply(Comment $parent, User $user, array $data): Comment
    {
        $parent->loadMissing(['post', 'user']);

        if (! $parent->isVisibleTo($user) || ($parent->post->status !== ContentStatus::Approved->value && ! $user->isAdmin() && $parent->post->user_id !== $user->id)) {
            throw $this->notFound(Comment::class, $parent->id);
        }

        return DB::transaction(function () use ($parent, $user, $data): Comment {
            $reply = Comment::query()->create([
                'post_id' => $parent->post_id,
                'user_id' => $user->id,
                'parent_id' => $parent->id,
                'content' => $data['content'],
                'status' => $user->isAdmin() ? ContentStatus::Approved->value : ContentStatus::Pending->value,
            ]);

            if ($reply->status === ContentStatus::Approved->value) {
                $parent->post->increment('comments_count');
                $this->notificationService->notifyReplyCreated($parent, $reply, $user);
            }

            $this->governanceService->flagSensitiveContent(
                $user,
                ['content' => $reply->content],
                'comment',
                $reply
            );

            $this->postRankingService->refreshScores($parent->post->fresh());

            return $this->reload($reply, $user);
        });
    }

    public function update(Comment $comment, User $user, array $data): Comment
    {
        return DB::transaction(function () use ($comment, $user, $data): Comment {
            $comment->loadMissing(['post', 'user.profile', 'post.user.profile']);
            $previousStatus = $comment->status;
            $comment->content = $data['content'];

            if (! $user->isAdmin()) {
                $comment->status = ContentStatus::Pending->value;

                if ($previousStatus === ContentStatus::Approved->value) {
                    $comment->post->decrement('comments_count');
                }
            }

            $comment->save();
            $this->governanceService->flagSensitiveContent(
                $user,
                ['content' => $comment->content],
                'comment',
                $comment
            );
            $this->postRankingService->refreshScores($comment->post->fresh());

            return $this->reload($comment, $user);
        });
    }

    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $comment->loadMissing('post');

            if ($comment->status === ContentStatus::Approved->value) {
                $comment->post->decrement('comments_count');
            }

            $comment->delete();
            $this->postRankingService->refreshScores($comment->post->fresh());
        });
    }

    public function hydrateViewerState(Collection $comments, ?User $viewer = null): void
    {
        if ($comments->isEmpty()) {
            return;
        }

        if ($viewer === null) {
            $comments->each(fn (Comment $comment) => $comment->setAttribute('is_liked', false));

            return;
        }

        $commentIds = $comments->pluck('id');
        $liked = CommentLike::query()
            ->where('user_id', $viewer->id)
            ->whereIn('comment_id', $commentIds)
            ->pluck('comment_id')
            ->flip();

        $comments->each(fn (Comment $comment) => $comment->setAttribute('is_liked', $liked->has($comment->id)));
    }

    private function reload(Comment $comment, ?User $viewer = null): Comment
    {
        $comment = $comment->fresh()->load(['user.profile', 'post']);
        $comment->setRelation('replies', collect());
        $this->hydrateViewerState(collect([$comment]), $viewer);

        return $comment;
    }

    private function buildTree(Collection $comments): Collection
    {
        $grouped = $comments->groupBy('parent_id');
        $availableIds = $comments->pluck('id')->flip();

        $attachReplies = function (Comment $comment) use (&$attachReplies, $grouped): void {
            $replies = $grouped->get($comment->id, collect())->values();
            $replies->each($attachReplies);
            $comment->setRelation('replies', $replies);
        };

        $roots = $comments
            ->filter(fn (Comment $comment): bool => $comment->parent_id === null || ! $availableIds->has($comment->parent_id))
            ->values();

        $roots->each($attachReplies);

        return $roots;
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
