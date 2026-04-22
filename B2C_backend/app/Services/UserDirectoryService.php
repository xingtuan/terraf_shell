<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserDirectoryService
{
    public function __construct(
        private readonly PostService $postService,
        private readonly CommentService $commentService,
    ) {}

    public function show(User $user, ?User $viewer = null): User
    {
        $user->load('profile');
        $this->loadPublicCounts($user);
        $this->hydrateFollowingState(new Collection([$user]), $viewer);

        return $user;
    }

    public function listPosts(User $user, array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $filters['user_id'] = $user->id;
        unset($filters['mine']);

        if ($this->canViewPrivateContent($user, $viewer)) {
            $filters['include_private'] = true;
        }

        return $this->postService->list($filters, $viewer);
    }

    public function listFavorites(User $user, array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $filters['favorited_by'] = $user->username;
        unset($filters['mine']);

        return $this->postService->list($filters, $viewer);
    }

    public function listComments(User $user, array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $query = Comment::query()
            ->where('user_id', $user->id)
            ->with(['user.profile', 'post'])
            ->orderByDesc('created_at');

        if ($this->canViewPrivateContent($user, $viewer)) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->where('status', ContentStatus::Approved->value);
        }

        $comments = $query
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();

        $this->commentService->hydrateViewerState($comments->getCollection(), $viewer);

        return $comments;
    }

    public function listFollowers(User $user, array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $followers = $user->followerUsers()
            ->with('profile')
            ->withCount([
                'followers as followers_count',
                'following as following_count',
                'posts as posts_count' => fn ($query) => $query->approved(),
                'comments as comments_count' => fn ($query) => $query->approved(),
            ])
            ->orderByDesc('follows.created_at')
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();

        $this->hydrateFollowingState($followers->getCollection(), $viewer);

        return $followers;
    }

    public function listFollowing(User $user, array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $following = $user->followingUsers()
            ->with('profile')
            ->withCount([
                'followers as followers_count',
                'following as following_count',
                'posts as posts_count' => fn ($query) => $query->approved(),
                'comments as comments_count' => fn ($query) => $query->approved(),
            ])
            ->orderByDesc('follows.created_at')
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();

        $this->hydrateFollowingState($following->getCollection(), $viewer);

        return $following;
    }

    public function hydrateFollowingState(Collection $users, ?User $viewer = null): void
    {
        if ($users->isEmpty()) {
            return;
        }

        if ($viewer === null) {
            $users->each(fn (User $listedUser) => $listedUser->setAttribute('is_following', false));

            return;
        }

        $following = Follow::query()
            ->where('follower_id', $viewer->id)
            ->whereIn('following_id', $users->pluck('id'))
            ->pluck('following_id')
            ->flip();

        $users->each(
            fn (User $listedUser) => $listedUser->setAttribute('is_following', $following->has($listedUser->id))
        );
    }

    private function loadPublicCounts(User $user): void
    {
        $user->loadCount([
            'followers as followers_count',
            'following as following_count',
            'posts as posts_count' => fn ($query) => $query->approved(),
            'comments as comments_count' => fn ($query) => $query->approved(),
        ]);
    }

    private function canViewPrivateContent(User $user, ?User $viewer = null): bool
    {
        return $viewer !== null && ($viewer->isAdmin() || $viewer->is($user));
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
