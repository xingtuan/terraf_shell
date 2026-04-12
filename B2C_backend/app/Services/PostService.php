<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function list(array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $query = Post::query()->with(['user.profile', 'category', 'tags', 'images']);

        if (($filters['mine'] ?? false) && $viewer !== null) {
            $query->where('user_id', $viewer->id);
        } elseif ($viewer?->isAdmin() && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->publiclyVisible();
        }

        $this->applyCategoryFilter($query, $filters);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', (bool) $filters['featured']);
        }

        if (array_key_exists('pinned', $filters)) {
            $query->where('is_pinned', (bool) $filters['pinned']);
        }

        if (! empty($filters['tag'])) {
            $tag = $filters['tag'];
            $query->whereHas('tags', function ($tagQuery) use ($tag): void {
                $tagQuery->where('slug', $tag);

                if (is_numeric($tag)) {
                    $tagQuery->orWhere('id', (int) $tag);
                }
            });
        }

        $this->applySort($query, $filters['sort'] ?? null);

        $paginator = $query
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();

        $this->hydrateViewerState($paginator->getCollection(), $viewer);

        return $paginator;
    }

    public function findForDisplay(string $identifier, ?User $viewer = null): Post
    {
        $post = Post::query()
            ->with(['user.profile', 'category', 'tags', 'images'])
            ->where(function ($query) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $query->whereKey((int) $identifier);
                }

                $query->orWhere('slug', $identifier);
            })
            ->first();

        if ($post === null || ! $post->isVisibleTo($viewer)) {
            throw $this->notFound(Post::class, $identifier);
        }

        $this->hydrateViewerState(collect([$post]), $viewer);

        return $post;
    }

    public function create(User $user, array $data): Post
    {
        return DB::transaction(function () use ($user, $data): Post {
            $isAdmin = $user->isAdmin();
            $status = $isAdmin ? ContentStatus::Approved->value : ContentStatus::Pending->value;

            $post = Post::query()->create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['content']), 180),
                'status' => $status,
                'is_pinned' => $isAdmin ? (bool) ($data['is_pinned'] ?? false) : false,
                'is_featured' => $isAdmin ? (bool) ($data['is_featured'] ?? false) : false,
                'published_at' => $status === ContentStatus::Approved->value ? now() : null,
            ]);

            $post->tags()->sync($data['tag_ids'] ?? []);
            $this->storeImages($post, $data['images'] ?? [], $data['image_alts'] ?? []);

            return $this->reload($post, $user);
        });
    }

    public function update(User $user, Post $post, array $data): Post
    {
        return DB::transaction(function () use ($user, $post, $data): Post {
            if (array_key_exists('title', $data) && $data['title'] !== $post->title) {
                $post->slug = $this->uniqueSlug($data['title'], $post->id);
            }

            $post->fill([
                'title' => $data['title'] ?? $post->title,
                'content' => $data['content'] ?? $post->content,
                'excerpt' => $data['excerpt'] ?? $post->excerpt,
                'category_id' => $data['category_id'] ?? $post->category_id,
            ]);

            if ($user->isAdmin()) {
                if (array_key_exists('is_pinned', $data)) {
                    $post->is_pinned = (bool) $data['is_pinned'];
                }

                if (array_key_exists('is_featured', $data)) {
                    $post->is_featured = (bool) $data['is_featured'];
                }
            } else {
                $post->status = ContentStatus::Pending->value;
                $post->published_at = null;
            }

            $post->save();

            if (array_key_exists('tag_ids', $data)) {
                $post->tags()->sync($data['tag_ids'] ?? []);
            }

            if (! empty($data['remove_image_ids'])) {
                $images = $post->images()->whereIn('id', $data['remove_image_ids'])->get();

                foreach ($images as $image) {
                    $this->mediaService->deletePath($image->path);
                    $image->delete();
                }
            }

            $this->storeImages($post, $data['images'] ?? [], $data['image_alts'] ?? []);

            return $this->reload($post, $user);
        });
    }

    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $post->loadMissing('images');

            foreach ($post->images as $image) {
                $this->mediaService->deletePath($image->path);
            }

            $post->delete();
        });
    }

    public function hydrateViewerState(Collection $posts, ?User $viewer = null): void
    {
        if ($posts->isEmpty()) {
            return;
        }

        if ($viewer === null) {
            $posts->each(function (Post $post): void {
                $post->setAttribute('is_liked', false);
                $post->setAttribute('is_favorited', false);
            });

            return;
        }

        $postIds = $posts->pluck('id');
        $liked = PostLike::query()
            ->where('user_id', $viewer->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $favorited = Favorite::query()
            ->where('user_id', $viewer->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip();

        $posts->each(function (Post $post) use ($liked, $favorited): void {
            $post->setAttribute('is_liked', $liked->has($post->id));
            $post->setAttribute('is_favorited', $favorited->has($post->id));
        });
    }

    private function reload(Post $post, ?User $viewer = null): Post
    {
        $post = $post->fresh()->load(['user.profile', 'category', 'tags', 'images']);
        $this->hydrateViewerState(collect([$post]), $viewer);

        return $post;
    }

    private function storeImages(Post $post, array $files, array $alts): void
    {
        if ($files === []) {
            return;
        }

        $sortOrder = (int) ($post->images()->max('sort_order') ?? -1) + 1;

        foreach ($files as $index => $file) {
            $this->mediaService->storePostImage(
                $file,
                $post,
                $sortOrder + $index,
                $alts[$index] ?? $post->title
            );
        }
    }

    private function applyCategoryFilter($query, array $filters): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);

            return;
        }

        if (empty($filters['category'])) {
            return;
        }

        $category = $filters['category'];

        $query->whereHas('category', function ($categoryQuery) use ($category): void {
            $categoryQuery->where('slug', $category);

            if (is_numeric($category)) {
                $categoryQuery->orWhere('id', (int) $category);
            }
        });
    }

    private function applySort($query, ?string $sort): void
    {
        $query->orderByDesc('is_pinned');

        if ($sort === 'hot') {
            $query
                ->orderByDesc('likes_count')
                ->orderByDesc('comments_count')
                ->orderByDesc('favorites_count')
                ->orderByDesc('created_at');

            return;
        }

        $query->orderByDesc('created_at');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base === '' ? 'post' : $base;
        $original = $slug;
        $counter = 2;

        while (
            Post::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
