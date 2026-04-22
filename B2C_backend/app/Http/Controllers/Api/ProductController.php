<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedProductsRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\PaginatesResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(ListPublishedProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = min((int) ($validated['per_page'] ?? 12), 50);
        $sort = (string) ($validated['sort'] ?? 'featured');

        $query = Product::query()
            ->publicVisible()
            ->with([
                'category',
                'images',
            ])
            ->withSum('orderItems as units_sold', 'quantity');

        $this->applyCatalogFilters($query, $validated);
        $this->applyCatalogSort($query, $sort);

        $products = $query
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => ProductResource::collection($products->getCollection())->resolve($request),
            'meta' => array_merge(
                PaginatesResources::meta($products),
                [
                    'sort' => $sort,
                    'sort_options' => $this->sortOptions(),
                    'facets' => [
                        'categories' => $this->categoryFacetOptions($request),
                        'models' => $this->attributeFacetOptions('model', Product::MODEL_OPTIONS),
                        'finishes' => $this->attributeFacetOptions('finish', Product::FINISH_OPTIONS),
                        'colors' => $this->attributeFacetOptions('color', Product::COLOR_OPTIONS),
                        'stock_statuses' => $this->attributeFacetOptions('stock_status', Product::STOCK_STATUS_OPTIONS),
                        'use_cases' => $this->useCaseFacetOptions(),
                        'price_range' => $this->priceRangeFacet(),
                    ],
                    'applied_filters' => $this->appliedFilters($validated, $sort),
                    'applied_filter_chips' => $this->appliedFilterChips($validated),
                ],
            ),
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::query()
            ->publicVisible()
            ->with(['category', 'images'])
            ->where(fn ($query) => $query->where('featured', true)->orWhere('is_bestseller', true))
            ->ordered()
            ->limit(4)
            ->get();

        return $this->successResponse(ProductResource::collection($products));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->publicVisible()
            ->with([
                'category',
                'images',
                'relatedProducts' => fn ($query) => $query
                    ->publicVisible()
                    ->with(['category', 'images'])
                    ->ordered()
                    ->limit(4),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($product->relatedProducts->isEmpty()) {
            $fallbackProducts = Product::query()
                ->publicVisible()
                ->with(['category', 'images'])
                ->where('id', '!=', $product->id)
                ->where('category', $product->category)
                ->ordered()
                ->limit(4)
                ->get();

            $product->setRelation('relatedProducts', $fallbackProducts);
        }

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCatalogFilters(Builder $query, array $filters): void
    {
        if (filled($filters['search'] ?? null)) {
            $search = '%'.trim((string) $filters['search']).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search)
                    ->orWhere('subtitle', 'like', $search)
                    ->orWhere('short_description', 'like', $search)
                    ->orWhere('full_description', 'like', $search);
            });
        }

        if (filled($filters['category'] ?? null)) {
            $category = (string) $filters['category'];

            $query->where(function (Builder $builder) use ($category): void {
                $builder
                    ->where('category', $category)
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category));
            });
        }

        foreach (['model', 'finish', 'color', 'stock_status'] as $column) {
            if (filled($filters[$column] ?? null)) {
                $query->where($column, $filters[$column]);
            }
        }

        if (filled($filters['use_case'] ?? null)) {
            $query->whereJsonContains('use_cases', $filters['use_case']);
        }

        if (filled($filters['price_min'] ?? null)) {
            $query->where('price_usd', '>=', (float) $filters['price_min']);
        }

        if (filled($filters['price_max'] ?? null)) {
            $query->where('price_usd', '<=', (float) $filters['price_max']);
        }
    }

    private function applyCatalogSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query
                ->orderByDesc('is_new')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'best_selling' => $query
                ->orderByDesc('units_sold')
                ->orderByDesc('is_bestseller')
                ->ordered(),
            'price_low_to_high' => $query
                ->orderBy('price_usd')
                ->ordered(),
            'price_high_to_low' => $query
                ->orderByDesc('price_usd')
                ->ordered(),
            default => $query
                ->orderByDesc('featured')
                ->orderByDesc('is_bestseller')
                ->ordered(),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sortOptions(): array
    {
        return [
            ['value' => 'featured', 'label' => 'Featured'],
            ['value' => 'newest', 'label' => 'Newest'],
            ['value' => 'best_selling', 'label' => 'Best Selling'],
            ['value' => 'price_low_to_high', 'label' => 'Price Low to High'],
            ['value' => 'price_high_to_low', 'label' => 'Price High to Low'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attributeFacetOptions(string $column, array $labels): array
    {
        $counts = Product::query()
            ->publicVisible()
            ->selectRaw("{$column}, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->all();

        return collect($labels)
            ->map(function (string $label, string $value) use ($counts): array {
                return [
                    'value' => $value,
                    'label' => $label,
                    'count' => (int) ($counts[$value] ?? 0),
                ];
            })
            ->filter(fn (array $option): bool => $option['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function useCaseFacetOptions(): array
    {
        $counts = [];

        Product::query()
            ->publicVisible()
            ->get(['use_cases'])
            ->each(function (Product $product) use (&$counts): void {
                foreach ($product->use_cases ?? [] as $useCase) {
                    if (! is_string($useCase) || ! isset(Product::USE_CASE_OPTIONS[$useCase])) {
                        continue;
                    }

                    $counts[$useCase] = ($counts[$useCase] ?? 0) + 1;
                }
            });

        return collect(Product::USE_CASE_OPTIONS)
            ->map(function (string $label, string $value) use ($counts): array {
                return [
                    'value' => $value,
                    'label' => $label,
                    'count' => (int) ($counts[$value] ?? 0),
                ];
            })
            ->filter(fn (array $option): bool => $option['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function priceRangeFacet(): array
    {
        $range = Product::query()
            ->publicVisible()
            ->selectRaw('MIN(price_usd) as min_price, MAX(price_usd) as max_price')
            ->first();

        return [
            'min' => number_format((float) ($range?->min_price ?? 0), 2, '.', ''),
            'max' => number_format((float) ($range?->max_price ?? 0), 2, '.', ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryFacetOptions(Request $request): array
    {
        $categories = ProductCategory::query()
            ->active()
            ->whereHas('products', fn (Builder $query) => $query->publicVisible())
            ->withCount([
                'products' => fn (Builder $query) => $query->publicVisible(),
            ])
            ->ordered()
            ->get();

        return ProductCategoryResource::collection($categories)->resolve($request);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function appliedFilters(array $validated, string $sort): array
    {
        return collect([
            'search' => $validated['search'] ?? null,
            'sort' => $sort,
            'category' => $validated['category'] ?? null,
            'model' => $validated['model'] ?? null,
            'finish' => $validated['finish'] ?? null,
            'color' => $validated['color'] ?? null,
            'stock_status' => $validated['stock_status'] ?? null,
            'use_case' => $validated['use_case'] ?? null,
            'price_min' => isset($validated['price_min']) ? number_format((float) $validated['price_min'], 2, '.', '') : null,
            'price_max' => isset($validated['price_max']) ? number_format((float) $validated['price_max'], 2, '.', '') : null,
        ])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, string>>
     */
    private function appliedFilterChips(array $validated): array
    {
        $chips = collect([
            'search' => $validated['search'] ?? null,
            'category' => $validated['category'] ?? null,
            'model' => $validated['model'] ?? null,
            'finish' => $validated['finish'] ?? null,
            'color' => $validated['color'] ?? null,
            'stock_status' => $validated['stock_status'] ?? null,
            'use_case' => $validated['use_case'] ?? null,
        ])
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(function (mixed $value, string $key): ?array {
                $display = $this->appliedFilterDisplay($key, (string) $value);

                if ($display === null) {
                    return null;
                }

                return [
                    'key' => $key,
                    'value' => (string) $value,
                    'display' => $display,
                ];
            })
            ->filter()
            ->values();

        $priceChip = $this->priceFilterChip($validated);

        if ($priceChip !== null) {
            $chips->push($priceChip);
        }

        return $chips->all();
    }

    private function appliedFilterDisplay(string $key, string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        return match ($key) {
            'search' => trim($value),
            'category' => ProductCategory::query()
                ->where('slug', $value)
                ->value('name')
                ?? Product::labelForOption(Product::CATEGORY_OPTIONS, $value)
                ?? Str::headline($value),
            'model' => Product::labelForOption(Product::MODEL_OPTIONS, $value) ?? Str::headline($value),
            'finish' => Product::labelForOption(Product::FINISH_OPTIONS, $value) ?? Str::headline($value),
            'color' => Product::labelForOption(Product::COLOR_OPTIONS, $value) ?? Str::headline($value),
            'stock_status' => Product::labelForOption(Product::STOCK_STATUS_OPTIONS, $value) ?? Str::headline($value),
            'use_case' => Product::labelForOption(Product::USE_CASE_OPTIONS, $value) ?? Str::headline($value),
            default => Str::headline($value),
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>|null
     */
    private function priceFilterChip(array $validated): ?array
    {
        $priceMin = isset($validated['price_min'])
            ? number_format((float) $validated['price_min'], 2, '.', '')
            : null;
        $priceMax = isset($validated['price_max'])
            ? number_format((float) $validated['price_max'], 2, '.', '')
            : null;

        if ($priceMin === null && $priceMax === null) {
            return null;
        }

        $display = match (true) {
            $priceMin !== null && $priceMax !== null => '$'.$priceMin.' - $'.$priceMax,
            $priceMin !== null => 'From $'.$priceMin,
            default => 'Up to $'.$priceMax,
        };

        return [
            'key' => 'price',
            'value' => implode(':', array_filter([$priceMin, $priceMax], fn (?string $value): bool => $value !== null)),
            'display' => $display,
        ];
    }
}
