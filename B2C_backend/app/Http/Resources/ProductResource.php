<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocalizedFields;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $primaryImageUrl = $this->primaryImageUrl();
        $galleryImages = $this->galleryImages($request, $title, $subtitle, $primaryImageUrl);
        $useCases = $this->normalizedUseCases();
        $category = $this->resource->relationLoaded('category')
            ? $this->resource->getRelation('category')
            : null;
        $relatedProducts = $this->resource->relationLoaded('relatedProducts')
            ? $this->resource->getRelation('relatedProducts')
            : collect();

        return [
            'id' => $this->id,
            'title' => $title,
            'name' => $title,
            'title_translations' => $this->localizedStringSet('name'),
            'name_translations' => $this->localizedStringSet('name'),
            'slug' => $this->slug,
            'sku' => $this->sku,
            'subtitle' => $subtitle,
            'subtitle_translations' => $this->localizedStringSet('subtitle') !== []
                ? $this->localizedStringSet('subtitle')
                : $this->localizedStringSet('short_description'),
            'short_description' => $shortDescription,
            'long_description' => $longDescription,
            'full_description' => $longDescription,
            'category' => $this->category,
            'category_label' => Product::labelForOption(Product::CATEGORY_OPTIONS, $this->category),
            'category_detail' => $category ? (new ProductCategoryResource($category))->resolve($request) : null,
            'model' => $this->model,
            'model_label' => Product::labelForOption(Product::MODEL_OPTIONS, $this->model),
            'finish' => $this->finish,
            'finish_label' => Product::labelForOption(Product::FINISH_OPTIONS, $this->finish),
            'color' => $this->color,
            'color_label' => Product::labelForOption(Product::COLOR_OPTIONS, $this->color),
            'technique' => $this->technique,
            'technique_label' => Product::labelForOption(Product::TECHNIQUE_OPTIONS, $this->technique),
            'currency' => $this->currency ?? 'USD',
            'price_usd' => number_format((float) $this->price_usd, 2, '.', ''),
            'price' => number_format((float) $this->price_usd, 2, '.', ''),
            'compare_at_price_usd' => $this->compare_at_price_usd !== null
                ? number_format((float) $this->compare_at_price_usd, 2, '.', '')
                : null,
            'compare_at_price' => $this->compare_at_price_usd !== null
                ? number_format((float) $this->compare_at_price_usd, 2, '.', '')
                : null,
            'on_sale' => $this->compare_at_price_usd !== null
                && (float) $this->compare_at_price_usd > (float) $this->price_usd,
            'featured' => (bool) $this->featured,
            'is_bestseller' => (bool) $this->is_bestseller,
            'is_new' => (bool) $this->is_new,
            'in_stock' => (bool) $this->in_stock,
            'can_add_to_cart' => $this->canBePurchased(),
            'inquiry_only' => (bool) $this->inquiry_only,
            'sample_request_enabled' => (bool) $this->sample_request_enabled,
            'stock_quantity' => $this->stock_quantity !== null ? (int) $this->stock_quantity : null,
            'stock_status' => $this->stock_status,
            'stock_status_label' => $this->stockStatusLabel(),
            'availability_text' => $this->localizedString($request, 'availability_text'),
            'lead_time' => $leadTime,
            'primary_image_url' => $primaryImageUrl,
            'image_url' => $primaryImageUrl,
            'gallery_images' => $galleryImages,
            'features' => $this->localizedArray($request, 'features'),
            'use_cases' => $useCases,
            'use_case_labels' => collect($useCases)
                ->map(fn (string $value): string => Product::labelForOption(Product::USE_CASE_OPTIONS, $value) ?? $value)
                ->values()
                ->all(),
            'dimensions' => $this->localizedString($request, 'dimensions'),
            'weight_grams' => $this->weight_grams !== null ? (int) $this->weight_grams : null,
            'specifications' => $this->specifications($request),
            'certifications' => $this->localizedArray($request, 'certifications'),
            'care_instructions' => $this->localizedArray($request, 'care_instructions'),
            'material_benefits' => $this->localizedArray($request, 'material_benefits'),
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
     * @return array<int, string>
     */
    private function normalizedUseCases(): array
    {
        return collect($this->use_cases ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function galleryImages(
        Request $request,
        string $title,
        ?string $subtitle,
        ?string $primaryImageUrl,
    ): array {
        if ($this->resource->relationLoaded('images')) {
            $images = ProductImageResource::collection($this->resource->getRelation('images'))->resolve($request);

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
    private function specifications(Request $request): array
    {
        $specifications = [];
        $useCaseLabels = implode(', ', $this->useCaseLabels());

        $this->appendSpecification(
            $specifications,
            'model',
            'Model',
            Product::labelForOption(Product::MODEL_OPTIONS, $this->model),
            null,
            'Product',
        );
        $this->appendSpecification(
            $specifications,
            'finish',
            'Finish',
            Product::labelForOption(Product::FINISH_OPTIONS, $this->finish),
            null,
            'Product',
        );
        $this->appendSpecification(
            $specifications,
            'color',
            'Color',
            Product::labelForOption(Product::COLOR_OPTIONS, $this->color),
            null,
            'Product',
        );
        $this->appendSpecification(
            $specifications,
            'technique',
            'Technique',
            Product::labelForOption(Product::TECHNIQUE_OPTIONS, $this->technique),
            null,
            'Material',
        );
        $this->appendSpecification(
            $specifications,
            'dimensions',
            'Dimensions',
            $this->localizedString($request, 'dimensions'),
            null,
            'Dimensions',
        );
        $this->appendSpecification(
            $specifications,
            'weight',
            'Weight',
            $this->weight_grams !== null ? (string) $this->weight_grams : null,
            $this->weight_grams !== null ? 'g' : null,
            'Dimensions',
        );
        $this->appendSpecification(
            $specifications,
            'intended_use',
            'Intended Use',
            $useCaseLabels !== '' ? $useCaseLabels : null,
            null,
            'Application',
        );

        foreach ($this->normalizedSpecificationEntries() as $entry) {
            $value = trim((string) ($entry['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            $this->appendSpecification(
                $specifications,
                isset($entry['key']) && is_string($entry['key']) ? $entry['key'] : Str::slug((string) ($entry['label'] ?? 'specification')),
                isset($entry['label']) && is_string($entry['label']) ? $entry['label'] : 'Specification',
                $value,
                isset($entry['unit']) && is_string($entry['unit']) ? trim($entry['unit']) : null,
                isset($entry['group']) && is_string($entry['group']) ? trim($entry['group']) : null,
            );
        }

        return $specifications;
    }

    /**
     * @param  array<int, array<string, string|null>>  $specifications
     */
    private function appendSpecification(
        array &$specifications,
        string $key,
        string $label,
        ?string $value,
        ?string $unit = null,
        ?string $group = null,
    ): void {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $specifications[] = [
            'key' => $key,
            'label' => $label,
            'value' => trim($value),
            'unit' => $unit,
            'group' => $group,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizedSpecificationEntries(): array
    {
        return collect($this->specifications ?? [])
            ->map(fn (mixed $entry): array => is_array($entry) ? $entry : [])
            ->filter(fn (array $entry): bool => Arr::has($entry, ['label', 'value']))
            ->values()
            ->all();
    }
}
