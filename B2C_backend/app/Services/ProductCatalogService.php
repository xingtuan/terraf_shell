<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductCatalogService
{
    public function listPublicCategories(): Collection
    {
        return ProductCategory::query()
            ->active()
            ->withCount([
                'products' => fn ($query) => $query->published(),
            ])
            ->ordered()
            ->get();
    }

    public function listPublicProducts(array $filters = []): Collection
    {
        return Product::query()
            ->published()
            ->with([
                'category',
                'images',
            ])
            ->whereHas('category', fn ($query) => $query->active())
            ->when(
                filled($filters['category'] ?? null),
                function ($query) use ($filters): void {
                    $category = (string) $filters['category'];

                    $query->where(function ($builder) use ($category): void {
                        if (ctype_digit($category)) {
                            $builder
                                ->where('category_id', (int) $category)
                                ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));

                            return;
                        }

                        $builder->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
                    });
                }
            )
            ->when(
                array_key_exists('featured', $filters),
                fn ($query) => $query->where('featured', (bool) $filters['featured'])
            )
            ->when(
                ($filters['sort'] ?? null) === 'newest',
                fn ($query) => $query->orderByDesc('published_at')->orderByDesc('created_at')->orderBy('sort_order')->orderBy('id'),
                fn ($query) => $query->ordered()
            )
            ->get();
    }

    public function findPublicProduct(string $identifier): Product
    {
        return $this->findByIdentifier(
            $identifier,
            Product::query()
                ->published()
                ->with([
                    'category',
                    'images',
                ])
                ->whereHas('category', fn ($query) => $query->active())
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     */
    private function findByIdentifier(string $identifier, $query): Product
    {
        $record = $query
            ->where(function ($builder) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $builder
                        ->whereKey((int) $identifier)
                        ->orWhere('slug', $identifier);

                    return;
                }

                $builder->where('slug', $identifier);
            })
            ->first();

        if ($record === null) {
            throw $this->notFound(Product::class, $identifier);
        }

        return $record;
    }

    private function notFound(string $model, int|string $id): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel($model, [$id]);
    }
}
