<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    public function searchPosts(string $query, ?User $viewer = null, null|int|string $requestedPerPage = null): LengthAwarePaginator
    {
        $builder = Post::query()
            ->with(['user.profile', 'category', 'tags', 'images', 'media', 'fundingCampaign'])
            ->publiclyVisible();

        $term = trim($query);
        $driver = DB::connection()->getDriverName();

        $builder->where(function ($searchQuery) use ($term, $driver): void {
            if ($driver === 'pgsql') {
                $searchQuery
                    ->where('title', 'ilike', '%'.$term.'%')
                    ->orWhere('content', 'ilike', '%'.$term.'%');

                return;
            }

            $lowerTerm = '%'.mb_strtolower($term).'%';
            $searchQuery
                ->whereRaw('LOWER(title) LIKE ?', [$lowerTerm])
                ->orWhereRaw('LOWER(content) LIKE ?', [$lowerTerm]);
        });

        $paginator = $builder
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($requestedPerPage))
            ->withQueryString();

        $this->postService->hydrateViewerState($paginator->getCollection(), $viewer);

        return $paginator;
    }

    private function perPage(null|int|string $requested): int
    {
        $default = (int) config('community.pagination.default_per_page', 20);
        $max = (int) config('community.pagination.max_per_page', 50);
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
