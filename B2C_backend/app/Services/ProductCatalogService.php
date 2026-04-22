<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductCatalogService
{
    public function listPublicCategories(): Collection
    {
        return ProductCategory::query()
            ->active()
            ->whereHas('products', fn ($query) => $query->publicVisible())
            ->withCount([
                'products' => fn ($query) => $query->publicVisible(),
            ])
            ->ordered()
            ->get();
    }

    public function listPublicProducts(array $filters = []): Collection
    {
        return Product::query()
            ->publicVisible()
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
                filled($filters['model'] ?? null),
                fn ($query) => $query->where('model', $filters['model'])
            )
            ->when(
                filled($filters['finish'] ?? null),
                fn ($query) => $query->where('finish', $filters['finish'])
            )
            ->when(
                filled($filters['color'] ?? null),
                fn ($query) => $query->where('color', $filters['color'])
            )
            ->when(
                filled($filters['stock_status'] ?? null),
                fn ($query) => $query->where('stock_status', $filters['stock_status'])
            )
            ->when(
                filled($filters['use_case'] ?? null),
                fn ($query) => $query->whereJsonContains('use_cases', $filters['use_case'])
            )
            ->when(
                filled($filters['search'] ?? null),
                function ($query) use ($filters): void {
                    $search = '%'.trim((string) $filters['search']).'%';

                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search)
                            ->orWhere('subtitle', 'like', $search)
                            ->orWhere('short_description', 'like', $search)
                            ->orWhere('full_description', 'like', $search);
                    });
                }
            )
            ->when(
                filled($filters['price_min'] ?? null),
                fn ($query) => $query->where('price_usd', '>=', (float) $filters['price_min'])
            )
            ->when(
                filled($filters['price_max'] ?? null),
                fn ($query) => $query->where('price_usd', '<=', (float) $filters['price_max'])
            )
            ->when(
                array_key_exists('featured', $filters) && $filters['featured'] !== null,
                fn ($query) => $query->where('featured', (bool) $filters['featured'])
            )
            ->when(
                ($filters['sort'] ?? null) === 'newest',
                fn ($query) => $query->orderByDesc('is_new')->orderByDesc('published_at')->orderByDesc('created_at')->orderBy('sort_order')->orderBy('id'),
                fn ($query) => $query->ordered()
            )
            ->when(
                ($filters['sort'] ?? null) === 'best_selling',
                fn ($query) => $query->withSum('orderItems as units_sold', 'quantity')->orderByDesc('units_sold')->ordered(),
                fn ($query) => $query
            )
            ->when(
                ($filters['sort'] ?? null) === 'price_low_to_high',
                fn ($query) => $query->orderBy('price_usd')->ordered(),
                fn ($query) => $query
            )
            ->when(
                ($filters['sort'] ?? null) === 'price_high_to_low',
                fn ($query) => $query->orderByDesc('price_usd')->ordered(),
                fn ($query) => $query->ordered()
            )
            ->get();
    }

    public function findPublicProduct(string $identifier): Product
    {
        return $this->findByIdentifier(
            $identifier,
            Product::query()
                ->publicVisible()
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
