<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
use App\Enums\UserRole;
use App\Models\Favorite;
use App\Models\IdeaMedia;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly PostRankingService $postRankingService,
        private readonly NotificationService $notificationService,
        private readonly GovernanceService $governanceService,
    ) {}

    public function list(array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        $query = Post::query()->with(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign']);

        if (($filters['mine'] ?? false) && $viewer !== null) {
            $query->where('user_id', $viewer->id);
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } elseif ($viewer?->canModerate() && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } elseif (($filters['status'] ?? null) === ContentStatus::Approved->value) {
            $query->approved();
        } else {
            $query->publiclyVisible();
        }

        $this->applyKeywordSearch($query, $filters);
        $this->applyCategoryFilter($query, $filters);
        $this->applyCreatorFilter($query, $filters);
        $this->applyProfileFilters($query, $filters);

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
            ->with(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign'])
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

        $this->trackView($post, $viewer);
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
            $this->syncMedia($post, $data);
            $post = $this->postRankingService->refreshScores($post);
            $this->governanceService->flagSensitiveContent(
                $user,
                [
                    'title' => $post->title,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                ],
                'post',
                $post
            );

            return $this->reload($post, $user);
        });
    }

    public function update(User $user, Post $post, array $data): Post
    {
        return DB::transaction(function () use ($user, $post, $data): Post {
            $wasFeatured = (bool) $post->is_featured;

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
                    $post->featured_at = $post->is_featured ? now() : null;
                    $post->featured_by = $post->is_featured ? $user->id : null;
                }
            } else {
                $post->status = ContentStatus::Pending->value;
                $post->published_at = null;
            }

            $post->save();

            if (array_key_exists('tag_ids', $data)) {
                $post->tags()->sync($data['tag_ids'] ?? []);
            }

            $this->syncMedia($post, $data);
            $post = $this->postRankingService->refreshScores($post);
            $this->governanceService->flagSensitiveContent(
                $user,
                [
                    'title' => $post->title,
                    'content' => $post->content,
                    'excerpt' => $post->excerpt,
                ],
                'post',
                $post
            );

            if ($user->isAdmin() && ! $wasFeatured && $post->is_featured) {
                $this->notificationService->notifyPostFeatured($post, $user);
            }

            return $this->reload($post, $user);
        });
    }

    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $post->loadMissing('media');

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
        $post = $post->fresh()->load(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign']);
        $this->hydrateViewerState(collect([$post]), $viewer);

        return $post;
    }

    private function syncMedia(Post $post, array $data): void
    {
        $this->removeMedia($post, $data);
        $this->replaceMedia($post, $data['replace_media'] ?? []);

        $nextSortOrder = $this->nextSortOrder($post);
        $nextSortOrder = $this->storeLegacyImages(
            $post,
            $data['images'] ?? [],
            $data['image_alts'] ?? [],
            $nextSortOrder
        );
        $nextSortOrder = $this->storeAttachments(
            $post,
            $data['attachments'] ?? [],
            $data['attachment_titles'] ?? [],
            $data['attachment_alts'] ?? [],
            $data['attachment_kinds'] ?? [],
            $nextSortOrder
        );
        $this->storeExternalLinks(
            $post,
            $data['model_3d_links'] ?? [],
            $nextSortOrder
        );
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

    private function applyCreatorFilter($query, array $filters): void
    {
        if (empty($filters['creator'])) {
            if (empty($filters['creator_role'])) {
                return;
            }
        }

        $creator = isset($filters['creator']) ? (string) $filters['creator'] : null;
        $creatorRole = $filters['creator_role'] ?? null;

        $query->whereHas('user', function ($userQuery) use ($creator, $creatorRole): void {
            if ($creator !== null && $creator !== '') {
                $this->applyCaseInsensitiveLike($userQuery, ['username', 'name'], $creator);
            }

            if ($creatorRole === null || $creatorRole === '') {
                return;
            }

            if ($creatorRole === UserRole::Creator->value) {
                $userQuery->whereIn('role', [UserRole::Creator->value, 'user']);

                return;
            }

            $userQuery->where('role', $creatorRole);
        });
    }

    private function applyKeywordSearch($query, array $filters): void
    {
        if (empty($filters['q'])) {
            return;
        }

        $term = trim((string) $filters['q']);

        $query->where(function ($searchQuery) use ($term): void {
            $this->applyCaseInsensitiveLike($searchQuery, ['title', 'content', 'excerpt'], $term);

            $searchQuery->orWhereHas('user', function ($userQuery) use ($term): void {
                $this->applyCaseInsensitiveLike($userQuery, ['username', 'name'], $term);
            });

            $searchQuery->orWhereHas('user.profile', function ($profileQuery) use ($term): void {
                $this->applyCaseInsensitiveLike($profileQuery, ['school_or_company', 'region'], $term);
            });
        });
    }

    private function applyProfileFilters($query, array $filters): void
    {
        if (! empty($filters['school_or_company'])) {
            $query->whereHas('user.profile', function ($profileQuery) use ($filters): void {
                $profileQuery->where('school_or_company', 'like', '%'.$filters['school_or_company'].'%');
            });
        }

        if (! empty($filters['region'])) {
            $query->whereHas('user.profile', function ($profileQuery) use ($filters): void {
                $profileQuery->where('region', 'like', '%'.$filters['region'].'%');
            });
        }
    }

    private function applySort($query, ?string $sort): void
    {
        $query->orderByDesc('is_pinned');

        if (in_array($sort, ['hot', 'popular'], true)) {
            $query
                ->orderByDesc('engagement_score')
                ->orderByDesc('likes_count')
                ->orderByDesc('comments_count')
                ->orderByDesc('favorites_count')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        if ($sort === 'trending') {
            $query
                ->orderByDesc('trending_score')
                ->orderByDesc('engagement_score')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        if ($sort === 'most_liked') {
            $query
                ->orderByDesc('likes_count')
                ->orderByDesc('engagement_score')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        if (in_array($sort, ['most_commented', 'most_discussed'], true)) {
            $query
                ->orderByDesc('comments_count')
                ->orderByDesc('engagement_score')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        $query->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function applyCaseInsensitiveLike($query, array $columns, string $term): void
    {
        $driver = DB::connection()->getDriverName();

        $query->where(function ($nestedQuery) use ($columns, $term, $driver): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                if ($driver === 'pgsql') {
                    $nestedQuery->{$method}($column, 'ilike', '%'.$term.'%');

                    continue;
                }

                $rawMethod = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $nestedQuery->{$rawMethod}('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($term).'%']);
            }
        });
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

    private function removeMedia(Post $post, array $data): void
    {
        $mediaIds = collect(array_merge(
            $data['remove_image_ids'] ?? [],
            $data['remove_media_ids'] ?? []
        ))
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        if ($mediaIds->isEmpty()) {
            return;
        }

        $media = $post->media()
            ->whereIn('id', $mediaIds)
            ->get();

        if ($media->count() !== $mediaIds->count()) {
            throw ValidationException::withMessages([
                'media' => ['One or more media items do not belong to this post.'],
            ]);
        }

        foreach ($media as $item) {
            $item->delete();
        }
    }

    private function replaceMedia(Post $post, array $replacements): void
    {
        if ($replacements === []) {
            return;
        }

        $replacementIds = collect($replacements)
            ->pluck('id')
            ->filter()
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        if ($replacementIds->isEmpty()) {
            return;
        }

        $existingMedia = $post->media()
            ->whereIn('id', $replacementIds)
            ->get()
            ->keyBy('id');

        if ($existingMedia->count() !== $replacementIds->count()) {
            throw ValidationException::withMessages([
                'replace_media' => ['One or more media items do not belong to this post.'],
            ]);
        }

        foreach ($replacements as $replacement) {
            $media = $existingMedia->get((int) $replacement['id']);

            if (! $media instanceof IdeaMedia) {
                continue;
            }

            if (! empty($replacement['external_url'])) {
                $this->replaceWithExternalLink($media, $replacement);

                continue;
            }

            if (! isset($replacement['file'])) {
                continue;
            }

            $this->replaceWithUpload($media, $replacement);
        }
    }

    private function replaceWithUpload(IdeaMedia $media, array $replacement): void
    {
        $type = $this->detectUploadType($replacement['file']);
        $kind = $this->normalizeKind($replacement['kind'] ?? null, $type);
        $upload = $this->mediaService->storeIdeaAttachment(
            $replacement['file'],
            $this->mediaDirectory($media->post_id)
        );

        $this->mediaService->deletePath($media->path, $media->disk);

        $media->fill([
            'source_type' => IdeaMediaSourceType::Upload->value,
            'media_type' => $type->value,
            'kind' => $kind->value,
            'title' => $replacement['title'] ?? $media->title,
            'alt_text' => $replacement['alt_text'] ?? ($type === IdeaMediaType::Image ? $media->alt_text : null),
            'external_url' => null,
            'metadata' => $media->metadata,
            ...$upload,
        ]);

        $media->save();
    }

    private function replaceWithExternalLink(IdeaMedia $media, array $replacement): void
    {
        $this->mediaService->deletePath($media->path, $media->disk);

        $media->fill([
            'source_type' => IdeaMediaSourceType::ExternalUrl->value,
            'media_type' => IdeaMediaType::External3d->value,
            'kind' => IdeaMediaKind::Model3d->value,
            'title' => $replacement['title'] ?? $media->title,
            'alt_text' => $replacement['alt_text'] ?? $media->alt_text,
            'disk' => null,
            'original_name' => null,
            'file_name' => null,
            'extension' => null,
            'mime_type' => null,
            'size_bytes' => null,
            'path' => null,
            'url' => $replacement['external_url'],
            'preview_url' => null,
            'thumbnail_url' => null,
            'external_url' => $replacement['external_url'],
            'metadata' => [
                'host' => parse_url((string) $replacement['external_url'], PHP_URL_HOST),
            ],
        ]);

        $media->save();
    }

    private function storeLegacyImages(Post $post, array $files, array $alts, int $nextSortOrder): int
    {
        return $this->storeAttachments(
            $post,
            $files,
            [],
            $alts,
            [],
            $nextSortOrder,
            IdeaMediaKind::ConceptImage
        );
    }

    private function storeAttachments(
        Post $post,
        array $files,
        array $titles,
        array $alts,
        array $kinds,
        int $nextSortOrder,
        ?IdeaMediaKind $defaultKind = null,
    ): int {
        foreach ($files as $index => $file) {
            $type = $this->detectUploadType($file);
            $kind = $this->normalizeKind($kinds[$index] ?? $defaultKind?->value, $type, $defaultKind);
            $upload = $this->mediaService->storeIdeaAttachment($file, $this->mediaDirectory($post->id));

            $post->media()->create([
                'source_type' => IdeaMediaSourceType::Upload->value,
                'media_type' => $type->value,
                'kind' => $kind->value,
                'title' => $titles[$index] ?? null,
                'alt_text' => $alts[$index] ?? ($type === IdeaMediaType::Image ? $post->title : null),
                'metadata' => null,
                'sort_order' => $nextSortOrder,
                ...$upload,
            ]);

            $nextSortOrder++;
        }

        return $nextSortOrder;
    }

    private function storeExternalLinks(Post $post, array $links, int $nextSortOrder): int
    {
        foreach ($links as $link) {
            $post->media()->create([
                'source_type' => IdeaMediaSourceType::ExternalUrl->value,
                'media_type' => IdeaMediaType::External3d->value,
                'kind' => IdeaMediaKind::Model3d->value,
                'title' => $link['title'] ?? null,
                'alt_text' => $link['alt_text'] ?? null,
                'url' => $link['url'],
                'external_url' => $link['url'],
                'metadata' => [
                    'host' => parse_url((string) $link['url'], PHP_URL_HOST),
                ],
                'sort_order' => $nextSortOrder,
            ]);

            $nextSortOrder++;
        }

        return $nextSortOrder;
    }

    private function nextSortOrder(Post $post): int
    {
        return (int) ($post->media()->max('sort_order') ?? -1) + 1;
    }

    private function normalizeKind(
        ?string $kind,
        IdeaMediaType $type,
        ?IdeaMediaKind $fallback = null,
    ): IdeaMediaKind {
        $mediaKind = IdeaMediaKind::tryFrom((string) $kind);

        if ($mediaKind !== null && $mediaKind->supportsType($type)) {
            return $mediaKind;
        }

        if ($fallback !== null && $fallback->supportsType($type)) {
            return $fallback;
        }

        return IdeaMediaKind::defaultForType($type);
    }

    private function detectUploadType($file): IdeaMediaType
    {
        return IdeaMedia::inferMediaTypeFromExtension(
            strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension()))
        );
    }

    private function mediaDirectory(int $postId): string
    {
        return rtrim((string) config('community.idea_media.directory', 'ideas'), '/').'/'.$postId;
    }

    private function trackView(Post $post, ?User $viewer = null): void
    {
        if ($post->status !== ContentStatus::Approved->value) {
            return;
        }

        if ($viewer !== null && ($viewer->canModerate() || $viewer->is($post->user))) {
            return;
        }

        Post::query()->whereKey($post->id)->increment('views_count');
        $post->views_count = (int) $post->views_count + 1;
    }
}
