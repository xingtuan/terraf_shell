<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Product;
use App\Support\StorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Product */
class ProductResource extends JsonResource
{
    use ResolvesLocalizedFields;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $title = $this->localizedString($request, 'name') ?? '';
        $subtitle = $this->localizedString($request, 'subtitle')
            ?? $this->localizedString($request, 'short_description');
        $shortDescription = $this->localizedString($request, 'short_description');
        $longDescription = $this->localizedString($request, 'full_description');
        $leadTime = $this->localizedString($request, 'lead_time')
            ?? $this->localizedString($request, 'availability_text');
        $productPrimaryImageUrl = $this->image_url;
        $primaryImageUrl = $this->primaryImageUrl();
        $galleryImages = $this->galleryImages(
            $request,
            $title,
            $subtitle,
            $productPrimaryImageUrl,
            $primaryImageUrl,
        );
        $defaultVariant = $this->defaultVariant();
        $price = $this->effectivePrice();
        $compareAtPrice = $this->effectiveCompareAtPrice();
        $currency = $this->effectiveCurrency();
        $stockStatus = $this->effectiveStockStatus();
        $stockQuantity = $this->effectiveStockQuantity();
        $attributes = $this->normalizedAttributes($request);
        $category = $this->resource->relationLoaded('category')
            ? $this->resource->getRelation('category')
            : null;
        $relatedProducts = $this->resource->relationLoaded('relatedProducts')
            ? $this->resource->getRelation('relatedProducts')
            : collect();
        $includeVariants = $request->route()?->parameter('slug') !== null
            || $request->boolean('include_variants');

        return [
            'id' => $this->id,
            'title' => $title,
            'name' => $title,
            'title_translations' => $this->localizedStringSet('name'),
            'name_translations' => $this->localizedStringSet('name'),
            'slug' => $this->slug,
            'sku' => $this->effectiveSku(),
            'subtitle' => $subtitle,
            'subtitle_translations' => $this->localizedStringSet('subtitle') !== []
                ? $this->localizedStringSet('subtitle')
                : $this->localizedStringSet('short_description'),
            'short_description' => $shortDescription,
            'long_description' => $longDescription,
            'full_description' => $longDescription,
            'category_id' => $this->category_id,
            'category_slug' => $category?->slug,
            'category_detail' => $category ? (new ProductCategoryResource($category))->resolve($request) : null,
            'currency' => $currency,
            'price_amount' => $price !== null ? number_format($price, 2, '.', '') : null,
            'compare_at_price_amount' => $compareAtPrice !== null
                ? number_format($compareAtPrice, 2, '.', '')
                : null,
            'on_sale' => $price !== null && $compareAtPrice !== null && $compareAtPrice > $price,
            'featured' => (bool) $this->featured,
            'is_bestseller' => (bool) $this->is_bestseller,
            'is_new' => (bool) $this->is_new,
            'in_stock' => $defaultVariant?->isInStock() ?? false,
            'can_add_to_cart' => $this->canBePurchased(),
            'inquiry_only' => (bool) $this->inquiry_only,
            'sample_request_enabled' => (bool) $this->sample_request_enabled,
            'stock_quantity' => $stockQuantity !== null ? (int) $stockQuantity : null,
            'stock_status' => $stockStatus,
            'stock_status_label' => $this->stockStatusLabel(),
            'availability_text' => $this->localizedString($request, 'availability_text'),
            'lead_time' => $leadTime,
            'primary_image_url' => $primaryImageUrl,
            'image_url' => $primaryImageUrl,
            'gallery_images' => $galleryImages,
            'features' => $this->localizedArray($request, 'features'),
            'weight_grams' => $defaultVariant?->weight_grams !== null ? (int) $defaultVariant->weight_grams : null,
            'specifications' => $this->specifications($attributes),
            'attributes' => $attributes,
            'default_variant' => $defaultVariant
                ? (new ProductVariantResource($defaultVariant))->resolve($request)
                : null,
            'variants' => $includeVariants && $this->resource->relationLoaded('variants')
                ? ProductVariantResource::collection($this->resource->getRelation('variants')->where('is_active', true))->resolve($request)
                : [],
            'certifications' => $this->normalizedFileUrlEntries($this->certifications ?? [], 'document_url', 'document_path'),
            'technical_downloads' => $this->normalizedFileUrlEntries($this->technical_downloads ?? [], 'url', 'file_path'),
            'care_instructions' => $this->localizedArray($request, 'care_instructions'),
            'material_benefits' => $this->localizedArray($request, 'material_benefits'),
            'selling_points' => $this->localizedArray($request, 'selling_points'),
            'shipping_notes' => $this->localizedArray($request, 'shipping_notes'),
            'return_notes' => $this->localizedArray($request, 'return_notes'),
            'product_faqs' => $this->normalizedJsonEntries($this->product_faqs ?? []),
            'seo' => [
                'title' => $this->localizedString($request, 'seo_title') ?? $title,
                'description' => $this->localizedString($request, 'seo_description') ?? $shortDescription,
            ],
            'related_products' => ProductResource::collection($relatedProducts)->resolve($request),
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function galleryImages(
        Request $request,
        string $title,
        ?string $subtitle,
        ?string $productPrimaryImageUrl,
        ?string $primaryImageUrl,
    ): array {
        if ($this->resource->relationLoaded('images')) {
            $images = ProductImageResource::collection($this->resource->getRelation('images'))->resolve($request);

            if ($productPrimaryImageUrl !== null) {
                $primaryImage = [
                    'id' => 0,
                    'product_id' => $this->id,
                    'alt_text' => $title,
                    'caption' => $subtitle,
                    'media_url' => $productPrimaryImageUrl,
                    'sort_order' => -1,
                    'created_at' => null,
                    'updated_at' => null,
                ];

                $galleryAlreadyContainsPrimary = collect($images)
                    ->contains(fn (array $image): bool => ($image['media_url'] ?? null) === $productPrimaryImageUrl);

                if (! $galleryAlreadyContainsPrimary) {
                    array_unshift($images, $primaryImage);
                }
            }

            if ($images !== []) {
                return $images;
            }
        }

        if ($primaryImageUrl === null) {
            return [];
        }

        return [[
            'id' => 0,
            'product_id' => $this->id,
            'alt_text' => $title,
            'caption' => $subtitle,
            'media_url' => $primaryImageUrl,
            'sort_order' => 0,
            'created_at' => null,
            'updated_at' => null,
        ]];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function specifications(array $attributes): array
    {
        return collect($attributes)
            ->filter(fn (array $attribute): bool => (bool) ($attribute['is_specification'] ?? false))
            ->groupBy(fn (array $attribute): string => (string) ($attribute['key'] ?? ''))
            ->filter(fn ($items, string $key): bool => $key !== '')
            ->map(function ($items, string $key): ?array {
                $first = $items->first();
                $values = $items
                    ->map(fn (array $attribute): ?string => $this->attributeDisplayValue($attribute))
                    ->filter(fn (?string $value): bool => is_string($value) && trim($value) !== '')
                    ->unique()
                    ->values();

                if ($values->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => (string) ($first['label'] ?? Str::headline($key)),
                    'value' => $values->implode(', '),
                    'unit' => isset($first['unit']) && is_string($first['unit']) ? $first['unit'] : null,
                    'group' => isset($first['group']) && is_string($first['group']) ? $first['group'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function attributeDisplayValue(array $attribute): ?string
    {
        $displayLabel = $attribute['display_label'] ?? null;

        if (is_scalar($displayLabel) && trim((string) $displayLabel) !== '') {
            return (string) $displayLabel;
        }

        $value = $attribute['value'] ?? null;

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value) && trim((string) $value) !== '') {
            return (string) $value;
        }

        if (is_array($value) && $value !== []) {
            return collect($value)
                ->map(fn (mixed $item, string|int $key): ?string => is_scalar($item)
                    ? (is_string($key) ? Str::headline((string) $key).': '.$item : (string) $item)
                    : null)
                ->filter()
                ->implode(', ');
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizedAttributes(Request $request): array
    {
        if (! $this->resource->relationLoaded('attributeAssignments')) {
            return [];
        }

        return ProductAttributeResource::collection(
            $this->resource->getRelation('attributeAssignments')
                ->filter(fn ($assignment): bool => $assignment->definition !== null)
                ->sortBy([
                    fn ($assignment): int => (int) ($assignment->definition?->sort_order ?? 0),
                    fn ($assignment): string => (string) ($assignment->definition?->label ?? ''),
                ])
                ->values(),
        )->resolve($request);
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizedJsonEntries(mixed $entries): array
    {
        if (! is_array($entries)) {
            return [];
        }

        return collect($entries)
            ->filter(function (mixed $entry): bool {
                if (is_string($entry)) {
                    return trim($entry) !== '';
                }

                return is_array($entry) && collect($entry)
                    ->filter(fn (mixed $value): bool => filled($value))
                    ->isNotEmpty();
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizedFileUrlEntries(mixed $entries, string $urlKey, string $pathKey): array
    {
        return collect($this->normalizedJsonEntries($entries))
            ->map(function (mixed $entry) use ($urlKey, $pathKey): mixed {
                if (! is_array($entry)) {
                    return $entry;
                }

                $resolvedUrl = $this->resolveStoredFileUrl($entry[$pathKey] ?? null);

                if ($resolvedUrl !== null) {
                    $entry[$urlKey] = $resolvedUrl;
                } elseif (isset($entry[$urlKey]) && is_string($entry[$urlKey])) {
                    $entry[$urlKey] = trim($entry[$urlKey]) !== '' ? trim($entry[$urlKey]) : null;
                }

                unset($entry[$pathKey]);

                return $entry;
            })
            ->values()
            ->all();
    }

    private function resolveStoredFileUrl(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return StorageUrl::resolve(ltrim(trim($path), '/'));
    }
}
