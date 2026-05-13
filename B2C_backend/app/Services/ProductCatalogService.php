<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

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
                'variants',
                'attributeAssignments.definition',
                'attributeAssignments.attributeValue',
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
                filled($filters['stock_status'] ?? null),
                fn ($query) => $query->whereHas('variants', fn ($variantQuery) => $variantQuery
                    ->where('is_active', true)
                    ->where('stock_status', $filters['stock_status']))
            )
            ->when(
                filled($filters['search'] ?? null),
                function ($query) use ($filters): void {
                    $search = '%'.trim((string) $filters['search']).'%';

                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('name', 'like', $search)
                            ->orWhereHas('variants', fn ($variantQuery) => $variantQuery
                                ->where('is_active', true)
                                ->where('sku', 'like', $search))
                            ->orWhere('subtitle', 'like', $search)
                            ->orWhere('short_description', 'like', $search)
                            ->orWhere('full_description', 'like', $search)
                            ->orWhereHas('attributeAssignments', function ($assignmentQuery) use ($search): void {
                                $assignmentQuery
                                    ->whereHas('definition', fn ($definitionQuery) => $definitionQuery
                                        ->where('is_active', true)
                                        ->where('is_searchable', true))
                                    ->where(function ($valueQuery) use ($search): void {
                                        $valueQuery
                                            ->where('value_text', 'like', $search)
                                            ->orWhereHas('attributeValue', fn ($attributeValueQuery) => $attributeValueQuery
                                                ->where('label', 'like', $search)
                                                ->orWhere('value', 'like', $search));
                                    });
                            });
                    });
                }
            )
            ->when(
                filled($filters['price_min'] ?? null),
                fn ($query) => $query->whereHas('variants', fn ($variantQuery) => $variantQuery
                    ->where('is_active', true)
                    ->where('price_amount', '>=', (float) $filters['price_min']))
            )
            ->when(
                filled($filters['price_max'] ?? null),
                fn ($query) => $query->whereHas('variants', fn ($variantQuery) => $variantQuery
                    ->where('is_active', true)
                    ->where('price_amount', '<=', (float) $filters['price_max']))
            )
            ->when(true, function ($query) use ($filters) {
                $this->applyDynamicAttributeFilters($query, $filters['attributes'] ?? []);

                return $query;
            })
            ->when(
                ($filters['sort'] ?? null) === 'price_low_to_high' || ($filters['sort'] ?? null) === 'price_high_to_low',
                fn ($query) => $query->addSelect(['catalog_price' => $this->defaultVariantPriceSubquery()])
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
                fn ($query) => $query->orderBy('catalog_price')->ordered(),
                fn ($query) => $query
            )
            ->when(
                ($filters['sort'] ?? null) === 'price_high_to_low',
                fn ($query) => $query->orderByDesc('catalog_price')->ordered(),
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
                    'variants',
                    'attributeAssignments.definition',
                    'attributeAssignments.attributeValue',
                ])
                ->whereHas('category', fn ($query) => $query->active())
        );
    }

    /**
     * @param  Builder<Product>  $query
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

    private function applyDynamicAttributeFilters(Builder $query, mixed $attributeFilters): void
    {
        if (! is_array($attributeFilters) || $attributeFilters === []) {
            return;
        }

        $definitions = ProductAttributeDefinition::query()
            ->active()
            ->where('is_filterable', true)
            ->whereIn('key', array_keys($attributeFilters))
            ->get()
            ->keyBy('key');

        foreach ($attributeFilters as $key => $rawValue) {
            if (! is_string($key) || $this->filterIsEmpty($rawValue)) {
                continue;
            }

            $definition = $definitions->get($key);

            if ($definition === null) {
                continue;
            }

            $query->whereHas('attributeAssignments', function ($assignmentQuery) use ($definition, $rawValue): void {
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

        if ($values !== []) {
            $query->whereHas('attributeValue', fn ($valueQuery) => $valueQuery->whereIn('value', $values));
        }
    }

    private function applyTextAttributeFilter(Builder $query, mixed $rawValue): void
    {
        if (is_scalar($rawValue) && trim((string) $rawValue) !== '') {
            $query->where('value_text', trim((string) $rawValue));
        }
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

        if ($boolean !== null) {
            $query->where('value_boolean', $boolean);
        }
    }

    private function filterIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->every(fn (mixed $item): bool => $this->filterIsEmpty($item));
        }

        return $value === null || (is_string($value) && trim($value) === '');
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
}
