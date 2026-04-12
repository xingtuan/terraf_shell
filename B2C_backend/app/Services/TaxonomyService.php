<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaxonomyService
{
    public function listPublicCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount(['posts as posts_count' => fn ($query) => $query->approved()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function listPublicTags(): Collection
    {
        return Tag::query()
            ->withCount(['posts as posts_count' => fn ($query) => $query->approved()])
            ->orderBy('name')
            ->get();
    }

    public function listCategoriesForAdmin(array $filters): LengthAwarePaginator
    {
        return Category::query()
            ->withCount(['posts as posts_count' => fn ($query) => $query->approved()])
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                })
            )
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', (bool) $filters['is_active'])
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function listTagsForAdmin(array $filters): LengthAwarePaginator
    {
        return Tag::query()
            ->withCount(['posts as posts_count' => fn ($query) => $query->approved()])
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                })
            )
            ->orderBy('name')
            ->paginate($this->perPage($filters['per_page'] ?? null))
            ->withQueryString();
    }

    public function createCategory(array $data): Category
    {
        return Category::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueCategorySlug($data['slug'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ])->fresh();
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->fill([
            'name' => $data['name'] ?? $category->name,
            'description' => $data['description'] ?? $category->description,
            'is_active' => $data['is_active'] ?? $category->is_active,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        if (array_key_exists('slug', $data) || array_key_exists('name', $data)) {
            $category->slug = $this->uniqueCategorySlug($data['slug'] ?? $data['name'] ?? $category->name, $category->id);
        }

        $category->save();

        return $category->fresh();
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }

    public function createTag(array $data): Tag
    {
        return Tag::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueTagSlug($data['slug'] ?? $data['name']),
        ])->fresh();
    }

    public function updateTag(Tag $tag, array $data): Tag
    {
        $tag->fill([
            'name' => $data['name'] ?? $tag->name,
        ]);

        if (array_key_exists('slug', $data) || array_key_exists('name', $data)) {
            $tag->slug = $this->uniqueTagSlug($data['slug'] ?? $data['name'] ?? $tag->name, $tag->id);
        }

        $tag->save();

        return $tag->fresh();
    }

    public function deleteTag(Tag $tag): void
    {
        $tag->delete();
    }

    private function uniqueCategorySlug(string $value, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(Category::class, $value, $ignoreId);
    }

    private function uniqueTagSlug(string $value, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(Tag::class, $value, $ignoreId);
    }

    private function uniqueSlug(string $modelClass, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base === '' ? 'item' : $base;
        $original = $slug;
        $counter = 2;

        while (
            $modelClass::query()
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
}
