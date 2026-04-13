<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    public function searchPosts(array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        return $this->postService->list($filters, $viewer);
    }
}
