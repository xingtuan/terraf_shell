<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ListPublishedProductsRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Support\PaginatesResources;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'variants',
                'attributeAssignments.definition',
                'attributeAssignments.attributeValue',
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
                        'dynamic_attributes' => $this->dynamicAttributeFacets($request),
                        'stock_statuses' => $this->stockStatusFacetOptions(),
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
            ->with([
                'category',
                'images',
                'variants',
                'attributeAssignments.definition',
                'attributeAssignments.attributeValue',
            ])
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
                'variants',
                'attributeAssignments.definition',
                'attributeAssignments.attributeValue',
                'relatedProducts' => fn ($query) => $query
                    ->publicVisible()
                    ->with([
                        'category',
                        'images',
                        'variants',
                        'attributeAssignments.definition',
                        'attributeAssignments.attributeValue',
                    ])
                    ->ordered()
                    ->limit(4),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($product->relatedProducts->isEmpty()) {
            $fallbackProducts = Product::query()
                ->publicVisible()
                ->with([
                    'category',
                    'images',
                    'variants',
                    'attributeAssignments.definition',
                    'attributeAssignments.attributeValue',
                ])
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
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
                    ->orWhereHas('variants', fn (Builder $variantQuery) => $variantQuery
                        ->where('is_active', true)
                        ->where('sku', 'like', $search))
                    ->orWhere('subtitle', 'like', $search)
                    ->orWhere('short_description', 'like', $search)
                    ->orWhere('full_description', 'like', $search)
                    ->orWhereHas('attributeAssignments', function (Builder $assignmentQuery) use ($search): void {
                        $assignmentQuery
                            ->whereHas('definition', fn (Builder $definitionQuery) => $definitionQuery
                                ->where('is_active', true)
                                ->where('is_searchable', true))
                            ->where(function (Builder $valueQuery) use ($search): void {
                                $valueQuery
                                    ->where('value_text', 'like', $search)
                                    ->orWhereHas('attributeValue', fn (Builder $attributeValueQuery) => $attributeValueQuery
                                        ->where('label', 'like', $search)
                                        ->orWhere('value', 'like', $search));
                            });
                    });
            });
        }

        if (filled($filters['category'] ?? null)) {
            $category = (string) $filters['category'];

            $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category));
        }

        if (filled($filters['stock_status'] ?? null)) {
            $query->whereHas('variants', fn (Builder $variantQuery) => $variantQuery
                ->where('is_active', true)
                ->where('stock_status', $filters['stock_status']));
        }

        if (filled($filters['price_min'] ?? null)) {
            $query->whereHas('variants', fn (Builder $variantQuery) => $variantQuery
                ->where('is_active', true)
                ->where('price_amount', '>=', (float) $filters['price_min']));
        }

        if (filled($filters['price_max'] ?? null)) {
            $query->whereHas('variants', fn (Builder $variantQuery) => $variantQuery
                ->where('is_active', true)
                ->where('price_amount', '<=', (float) $filters['price_max']));
        }

        $this->applyDynamicAttributeFilters($query, $filters['attributes'] ?? []);
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
                ->addSelect(['catalog_price' => $this->defaultVariantPriceSubquery()])
                ->orderBy('catalog_price')
                ->ordered(),
            'price_high_to_low' => $query
                ->addSelect(['catalog_price' => $this->defaultVariantPriceSubquery()])
                ->orderByDesc('catalog_price')
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
    private function stockStatusFacetOptions(): array
    {
        $counts = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $query) => $query->publicVisible())
            ->selectRaw('stock_status, COUNT(DISTINCT product_id) as aggregate')
            ->groupBy('stock_status')
            ->pluck('aggregate', 'stock_status')
            ->all();

        return collect(ProductVariant::STOCK_STATUS_OPTIONS)
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
    private function dynamicAttributeFacets(Request $request): array
    {
        $locale = (string) $request->query('locale', 'en');

        return ProductAttributeDefinition::query()
            ->active()
            ->where('is_filterable', true)
            ->with(['values' => fn ($query) => $query->active()->ordered()])
            ->ordered()
            ->get()
            ->map(function (ProductAttributeDefinition $definition) use ($locale): array {
                return [
                    'key' => $definition->key,
                    'label' => $this->translated($definition->label_translations, $definition->label, $locale),
                    'type' => $definition->type,
                    'unit' => $definition->unit,
                    'group' => $definition->group,
                    'options' => $this->dynamicAttributeFacetOptions($definition, $locale),
                    'display_order' => (int) $definition->sort_order,
                ];
            })
            ->filter(fn (array $facet): bool => $facet['options'] !== [] || $facet['type'] === 'number')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dynamicAttributeFacetOptions(ProductAttributeDefinition $definition, string $locale): array
    {
        if ($definition->type === 'boolean') {
            return collect([true => 'Yes', false => 'No'])
                ->map(function (string $label, bool|int $value) use ($definition): array {
                    $normalized = (bool) $value;

                    return [
                        'value' => $normalized ? 'true' : 'false',
                        'label' => $label,
                        'count' => Product::query()
                            ->publicVisible()
                            ->whereHas('attributeAssignments', fn (Builder $query) => $query
                                ->where('attribute_definition_id', $definition->id)
                                ->where('value_boolean', $normalized))
                            ->count(),
                    ];
                })
                ->filter(fn (array $option): bool => $option['count'] > 0)
                ->values()
                ->all();
        }

        if ($definition->values->isNotEmpty()) {
            return $definition->values
                ->map(function ($value) use ($definition, $locale): array {
                    return [
                        'value' => $value->value,
                        'label' => $this->translated($value->label_translations, $value->label, $locale),
                        'count' => Product::query()
                            ->publicVisible()
                            ->whereHas('attributeAssignments', fn (Builder $query) => $query
                                ->where('attribute_definition_id', $definition->id)
                                ->where('product_attribute_value_id', $value->id))
                            ->count(),
                    ];
                })
                ->filter(fn (array $option): bool => $option['count'] > 0)
                ->values()
                ->all();
        }

        if (! in_array($definition->type, ['text', 'rich_text'], true)) {
            return [];
        }

        return DB::table('product_attribute_assignments')
            ->join('products', 'products.id', '=', 'product_attribute_assignments.product_id')
            ->where('product_attribute_assignments.attribute_definition_id', $definition->id)
            ->whereNotNull('product_attribute_assignments.value_text')
            ->where('products.status', 'published')
            ->where('products.is_active', true)
            ->selectRaw('product_attribute_assignments.value_text as value, COUNT(DISTINCT products.id) as aggregate')
            ->groupBy('product_attribute_assignments.value_text')
            ->orderBy('product_attribute_assignments.value_text')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->value,
                'label' => (string) $row->value,
                'count' => (int) $row->aggregate,
            ])
            ->filter(fn (array $option): bool => trim($option['value']) !== '' && $option['count'] > 0)
            ->values()
            ->all();
    }

    private function applyDynamicAttributeFilters(Builder $query, mixed $attributeFilters): void
    {
        if (! is_array($attributeFilters) || $attributeFilters === []) {
            return;
        }

        $definitions = ProductAttributeDefinition::query()
            ->active()
            ->where('is_filterable', true)
            ->whereIn('key', array_keys($attributeFilters))
            ->with('values')
            ->get()
            ->keyBy('key');

        foreach ($attributeFilters as $key => $rawValue) {
            if (! is_string($key)) {
                continue;
            }

            $definition = $definitions->get($key);

            if ($definition === null || $this->filterIsEmpty($rawValue)) {
                continue;
            }

            $query->whereHas('attributeAssignments', function (Builder $assignmentQuery) use ($definition, $rawValue): void {
                $assignmentQuery->where('attribute_definition_id', $definition->id);

                match ($definition->type) {
                    'select',
                    'multiselect' => $this->applyPredefinedAttributeFilter($assignmentQuery, $rawValue),
                    'number' => $this->applyNumberAttributeFilter($assignmentQuery, $rawValue),
                    'boolean' => $this->applyBooleanAttributeFilter($assignmentQuery, $rawValue),
                    default => $this->applyTextAttributeFilter($assignmentQuery, $rawValue),
                };
            });
        }
    }

    private function applyPredefinedAttributeFilter(Builder $query, mixed $rawValue): void
    {
        $values = collect(is_array($rawValue) ? $rawValue : [$rawValue])
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value): string => Str::slug((string) $value, '_'))
            ->values()
            ->all();

        if ($values === []) {
            return;
        }

        $query->whereHas('attributeValue', fn (Builder $valueQuery) => $valueQuery->whereIn('value', $values));
    }

    private function applyTextAttributeFilter(Builder $query, mixed $rawValue): void
    {
        if (! is_scalar($rawValue) || trim((string) $rawValue) === '') {
            return;
        }

        $query->where('value_text', trim((string) $rawValue));
    }

    private function applyNumberAttributeFilter(Builder $query, mixed $rawValue): void
    {
        if (is_array($rawValue)) {
            if (isset($rawValue['min']) && is_numeric($rawValue['min'])) {
                $query->where('value_number', '>=', (float) $rawValue['min']);
            }

            if (isset($rawValue['max']) && is_numeric($rawValue['max'])) {
                $query->where('value_number', '<=', (float) $rawValue['max']);
            }

            return;
        }

        if (is_numeric($rawValue)) {
            $query->where('value_number', (float) $rawValue);
        }
    }

    private function applyBooleanAttributeFilter(Builder $query, mixed $rawValue): void
    {
        $boolean = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolean === null) {
            return;
        }

        $query->where('value_boolean', $boolean);
    }

    private function filterIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->every(fn (mixed $item): bool => $this->filterIsEmpty($item));
        }

        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * @return array<string, string>
     */
    private function priceRangeFacet(): array
    {
        $range = Product::query()
            ->publicVisible()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->where('product_variants.is_active', true)
            ->selectRaw('MIN(product_variants.price_amount) as min_price, MAX(product_variants.price_amount) as max_price')
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
            'stock_status' => $validated['stock_status'] ?? null,
            'attributes' => $validated['attributes'] ?? [],
            'price_min' => isset($validated['price_min']) ? number_format((float) $validated['price_min'], 2, '.', '') : null,
            'price_max' => isset($validated['price_max']) ? number_format((float) $validated['price_max'], 2, '.', '') : null,
        ])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])
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
            'stock_status' => $validated['stock_status'] ?? null,
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

        foreach ($this->attributeFilterChips($validated['attributes'] ?? []) as $chip) {
            $chips->push($chip);
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
                ?? Str::headline($value),
            'stock_status' => Product::labelForOption(ProductVariant::STOCK_STATUS_OPTIONS, $value) ?? Str::headline($value),
            default => Str::headline($value),
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function attributeFilterChips(mixed $attributeFilters): array
    {
        if (! is_array($attributeFilters) || $attributeFilters === []) {
            return [];
        }

        $definitions = ProductAttributeDefinition::query()
            ->active()
            ->where('is_filterable', true)
            ->whereIn('key', array_keys($attributeFilters))
            ->with('values')
            ->get()
            ->keyBy('key');

        return collect($attributeFilters)
            ->map(function (mixed $value, string $key) use ($definitions): ?array {
                $definition = $definitions->get($key);

                if ($definition === null || $this->filterIsEmpty($value)) {
                    return null;
                }

                $displayValue = $this->attributeFilterDisplayValue($definition, $value);

                if ($displayValue === null) {
                    return null;
                }

                return [
                    'key' => 'attributes.'.$key,
                    'value' => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value,
                    'display' => $definition->label.': '.$displayValue,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function attributeFilterDisplayValue(ProductAttributeDefinition $definition, mixed $value): ?string
    {
        if (is_array($value) && $definition->type === 'number') {
            $min = isset($value['min']) && is_numeric($value['min']) ? number_format((float) $value['min'], 2, '.', '') : null;
            $max = isset($value['max']) && is_numeric($value['max']) ? number_format((float) $value['max'], 2, '.', '') : null;

            return match (true) {
                $min !== null && $max !== null => $min.' - '.$max,
                $min !== null => 'From '.$min,
                $max !== null => 'Up to '.$max,
                default => null,
            };
        }

        $values = collect(is_array($value) ? $value : [$value])
            ->filter(fn (mixed $item): bool => is_scalar($item) && trim((string) $item) !== '')
            ->map(fn (mixed $item): string => (string) $item)
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        if (in_array($definition->type, ['select', 'multiselect'], true)) {
            $labels = $definition->values
                ->whereIn('value', $values->map(fn (string $item): string => Str::slug($item, '_'))->all())
                ->pluck('label')
                ->values();

            return $labels->isNotEmpty()
                ? $labels->implode(', ')
                : $values->map(fn (string $item): string => Str::headline($item))->implode(', ');
        }

        if ($definition->type === 'boolean') {
            return filter_var($values->first(), FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
        }

        return $values->implode(', ');
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

    private function defaultVariantPriceSubquery()
    {
        return ProductVariant::query()
            ->select('price_amount')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(1);
    }

    /**
     * @param  array<string, string>|null  $translations
     */
    private function translated(?array $translations, ?string $fallback, string $locale): ?string
    {
        return $translations[$locale] ?? $translations['en'] ?? $fallback;
    }
}
