<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_products_index_returns_dynamic_catalogue_and_supports_attribute_filters(): void
    {
        $this->catalogProduct(
            [
                'name' => 'Dinner Plate - Lite / Ocean Bone',
                'subtitle' => 'Everyday service plate',
                'slug' => 'dinner-plate-lite-ocean-bone',
                'category_slug' => 'tableware',
                'image_url' => 'https://placehold.co/600x400?text=Dinner+Plate',
                'sort_order' => 1,
            ],
            [
                'sku' => 'DINNER_PLATE_STANDARD',
                'price_amount' => 48.00,
                'compare_at_price_amount' => 58.00,
                'stock_quantity' => 24,
                'stock_status' => 'in_stock',
            ],
            [
                ['key' => 'finish', 'label' => 'Finish', 'type' => 'select', 'value' => 'glossy', 'display' => 'Glossy'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'select', 'value' => 'ocean_bone', 'display' => 'Ocean Bone'],
                ['key' => 'use_case', 'label' => 'Use Case', 'type' => 'multiselect', 'value' => 'home_dining', 'display' => 'Home Dining', 'allows_multiple' => true],
                ['key' => 'use_case', 'label' => 'Use Case', 'type' => 'multiselect', 'value' => 'hospitality_service', 'display' => 'Hospitality Service', 'allows_multiple' => true],
                ['key' => 'density', 'label' => 'Density', 'type' => 'number', 'number' => 1.35, 'unit' => 'g/cm3'],
                ['key' => 'food_safe', 'label' => 'Food Safe', 'type' => 'boolean', 'boolean' => true],
                ['key' => 'material_grade', 'label' => 'Material Grade', 'type' => 'text', 'text' => 'Premium service'],
            ],
        );

        $this->catalogProduct(
            [
                'name' => 'Planter S - Heritage / Forged Ash',
                'slug' => 'planter-s-heritage-forged-ash',
                'category_slug' => 'planters',
                'image_url' => 'https://placehold.co/600x400?text=Planter+S',
                'sort_order' => 2,
            ],
            [
                'sku' => 'PLANTER_S_STANDARD',
                'price_amount' => 55.00,
                'stock_quantity' => 5,
                'stock_status' => 'low_stock',
            ],
            [
                ['key' => 'finish', 'label' => 'Finish', 'type' => 'select', 'value' => 'matte', 'display' => 'Matte'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'select', 'value' => 'forged_ash', 'display' => 'Forged Ash'],
                ['key' => 'use_case', 'label' => 'Use Case', 'type' => 'multiselect', 'value' => 'design_projects', 'display' => 'Design Projects', 'allows_multiple' => true],
                ['key' => 'density', 'label' => 'Density', 'type' => 'number', 'number' => 1.9, 'unit' => 'g/cm3'],
                ['key' => 'food_safe', 'label' => 'Food Safe', 'type' => 'boolean', 'boolean' => false],
                ['key' => 'material_grade', 'label' => 'Material Grade', 'type' => 'text', 'text' => 'Interior object'],
            ],
        );

        Product::factory()->archived()->create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'category_id' => $this->category('tableware')->id,
            'is_active' => false,
        ]);

        $query = http_build_query([
            'search' => 'Dinner',
            'category' => 'tableware',
            'attributes' => [
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'use_case' => 'home_dining',
                'density' => ['min' => 1.2, 'max' => 1.5],
                'food_safe' => 'true',
                'material_grade' => 'Premium service',
            ],
            'stock_status' => 'in_stock',
            'price_min' => 40,
            'price_max' => 60,
            'sort' => 'price_low_to_high',
            'per_page' => 12,
        ]);

        $response = $this->getJson("/api/products?{$query}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.sort', 'price_low_to_high')
            ->assertJsonPath('meta.facets.price_range.min', '48.00')
            ->assertJsonPath('meta.facets.price_range.max', '55.00')
            ->assertJsonPath('data.0.title', 'Dinner Plate - Lite / Ocean Bone')
            ->assertJsonPath('data.0.sku', 'DINNER_PLATE_STANDARD')
            ->assertJsonPath('data.0.subtitle', 'Everyday service plate')
            ->assertJsonPath('data.0.category_slug', 'tableware')
            ->assertJsonPath('data.0.price_amount', '48.00')
            ->assertJsonPath('data.0.compare_at_price_amount', '58.00')
            ->assertJsonPath('data.0.stock_status', 'in_stock')
            ->assertJsonPath('data.0.stock_status_label', 'In Stock')
            ->assertJsonPath('data.0.can_add_to_cart', true)
            ->assertJsonPath('data.0.primary_image_url', 'https://placehold.co/600x400?text=Dinner+Plate')
            ->assertJsonPath('data.0.gallery_images.0.media_url', 'https://placehold.co/600x400?text=Dinner+Plate');

        $payload = $response->json();
        $product = $payload['data'][0];

        foreach ([
            'category',
            'category_label',
            'model',
            'model_label',
            'finish',
            'finish_label',
            'color',
            'color_label',
            'technique',
            'technique_label',
            'use_cases',
            'use_case_labels',
            'dimensions',
            'price_usd',
            'compare_at_price_usd',
        ] as $legacyField) {
            $this->assertArrayNotHasKey($legacyField, $product);
        }

        $attributes = collect($product['attributes']);
        $this->assertSame('glossy', $attributes->firstWhere('key', 'finish')['value']);
        $this->assertSame('Ocean Bone', $attributes->firstWhere('key', 'color')['display_label']);
        $this->assertTrue($attributes->contains(fn (array $attribute): bool => $attribute['key'] === 'food_safe' && $attribute['value'] === true));

        $dynamicFacets = collect($payload['meta']['facets']['dynamic_attributes']);
        $this->assertSame('select', $dynamicFacets->firstWhere('key', 'finish')['type']);
        $this->assertSame('glossy', collect($dynamicFacets->firstWhere('key', 'finish')['options'])->firstWhere('value', 'glossy')['value']);
        $this->assertSame('number', $dynamicFacets->firstWhere('key', 'density')['type']);

        $chips = collect($payload['meta']['applied_filter_chips']);
        $this->assertTrue($chips->contains(fn (array $chip): bool => $chip['key'] === 'attributes.finish' && $chip['display'] === 'Finish: Glossy'));
        $this->assertTrue($chips->contains(fn (array $chip): bool => $chip['key'] === 'attributes.density' && $chip['display'] === 'Density: 1.20 - 1.50'));
        $this->assertTrue($chips->contains(fn (array $chip): bool => $chip['key'] === 'price' && $chip['display'] === '$40.00 - $60.00'));
    }

    public function test_public_product_show_returns_dynamic_specifications_gallery_and_related_products(): void
    {
        $product = $this->catalogProduct(
            [
                'name' => 'Wall Panel Sample',
                'subtitle' => 'Architectural material review sample',
                'slug' => 'wall-panel-sample',
                'category_slug' => 'architectural',
                'inquiry_only' => true,
                'sample_request_enabled' => true,
                'media_url' => null,
                'image_url' => null,
                'seo_title' => 'Wall Panel Sample | OXP',
                'seo_description' => 'Architectural sample for OXP finish review.',
            ],
            [
                'sku' => 'WALL_PANEL_SAMPLE_DEFAULT',
                'price_amount' => 120.00,
                'stock_quantity' => null,
                'stock_status' => 'made_to_order',
                'inventory_policy' => 'inquiry_only',
            ],
            [
                ['key' => 'sample_format', 'label' => 'Sample Format', 'type' => 'text', 'text' => 'Panel tile', 'group' => 'Sampling'],
                ['key' => 'particle_size', 'label' => 'Particle Size', 'type' => 'select', 'value' => 'fine', 'display' => 'Fine', 'group' => 'Material'],
            ],
        );

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'media_url' => 'https://placehold.co/900x900?text=Wall+Panel+Main',
            'sort_order' => 1,
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'media_url' => 'https://placehold.co/900x900?text=Wall+Panel+Detail',
            'sort_order' => 2,
        ]);

        $relatedProduct = $this->catalogProduct(
            [
                'name' => 'Studio Sample Kit',
                'slug' => 'studio-sample-kit',
                'category_slug' => 'architectural',
            ],
            [
                'sku' => 'STUDIO_SAMPLE_KIT_DEFAULT',
                'price_amount' => 36.00,
                'stock_quantity' => null,
                'stock_status' => 'preorder',
                'inventory_policy' => 'preorder',
            ],
            [
                ['key' => 'sample_format', 'label' => 'Sample Format', 'type' => 'text', 'text' => 'Kit box', 'group' => 'Sampling'],
            ],
        );

        $product->relatedProducts()->sync([$relatedProduct->id]);

        $response = $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.title', 'Wall Panel Sample')
            ->assertJsonPath('data.sku', 'WALL_PANEL_SAMPLE_DEFAULT')
            ->assertJsonPath('data.stock_status', 'made_to_order')
            ->assertJsonPath('data.can_add_to_cart', false)
            ->assertJsonPath('data.sample_request_enabled', true)
            ->assertJsonPath('data.category_slug', 'architectural')
            ->assertJsonPath('data.gallery_images.0.media_url', 'https://placehold.co/900x900?text=Wall+Panel+Main')
            ->assertJsonPath('data.gallery_images.1.media_url', 'https://placehold.co/900x900?text=Wall+Panel+Detail')
            ->assertJsonPath('data.related_products.0.slug', 'studio-sample-kit')
            ->assertJsonPath('data.seo.title', 'Wall Panel Sample | OXP')
            ->assertJsonPath('data.seo.description', 'Architectural sample for OXP finish review.');

        $specifications = collect($response->json('data.specifications'));
        $this->assertSame('Sample Format', $specifications->firstWhere('key', 'sample_format')['label']);
        $this->assertSame('Panel tile', $specifications->firstWhere('key', 'sample_format')['value']);
    }

    public function test_product_primary_image_takes_precedence_over_gallery_images(): void
    {
        $product = $this->catalogProduct([
            'name' => 'Primary Image Product',
            'slug' => 'primary-image-product',
            'category_slug' => 'tableware',
            'image_url' => 'https://cdn.example.com/product-main.jpg',
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'media_url' => 'https://cdn.example.com/gallery-detail.jpg',
            'sort_order' => 1,
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.primary_image_url', 'https://cdn.example.com/product-main.jpg')
            ->assertJsonPath('data.image_url', 'https://cdn.example.com/product-main.jpg')
            ->assertJsonPath('data.gallery_images.0.media_url', 'https://cdn.example.com/product-main.jpg')
            ->assertJsonPath('data.gallery_images.1.media_url', 'https://cdn.example.com/gallery-detail.jpg');
    }

    public function test_category_id_singular_attribute_and_legacy_attribute_filters_work(): void
    {
        $product = $this->catalogProduct(
            [
                'name' => 'Matte Planter Reference',
                'slug' => 'matte-planter-reference',
                'category_slug' => 'planters',
            ],
            [
                'sku' => 'MATTE_PLANTER_REFERENCE',
                'price_amount' => 55.00,
            ],
            [
                ['key' => 'model', 'label' => 'Model', 'type' => 'select', 'value' => 'heritage_16', 'display' => 'Heritage 16'],
                ['key' => 'finish', 'label' => 'Finish', 'type' => 'select', 'value' => 'matte', 'display' => 'Matte'],
                ['key' => 'color', 'label' => 'Color', 'type' => 'select', 'value' => 'forged_ash', 'display' => 'Forged Ash'],
                ['key' => 'use_case', 'label' => 'Use Case', 'type' => 'multiselect', 'value' => 'design_projects', 'display' => 'Design Projects', 'allows_multiple' => true],
            ],
        );

        $categoryQuery = http_build_query([
            'category' => $product->category_id,
            'attribute' => [
                'finish' => 'matte',
            ],
        ]);

        $this->getJson("/api/products?{$categoryQuery}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'matte-planter-reference')
            ->assertJsonPath('meta.applied_filters.attributes.finish', 'matte');

        $legacyQuery = http_build_query([
            'model' => 'heritage_16',
            'finish' => 'matte',
            'color' => 'forged_ash',
            'use_case' => 'design_projects',
        ]);

        $this->getJson("/api/products?{$legacyQuery}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'matte-planter-reference')
            ->assertJsonPath('meta.applied_filters.attributes.model', 'heritage_16')
            ->assertJsonPath('meta.applied_filters.attributes.use_case', 'design_projects');
    }

    public function test_price_filtering_and_sorting_use_active_default_variant_price(): void
    {
        $expensiveDefault = $this->catalogProduct(
            [
                'name' => 'Expensive Default Variant Product',
                'slug' => 'expensive-default-variant-product',
                'sort_order' => 2,
            ],
            [
                'sku' => 'EXPENSIVE_DEFAULT_VARIANT_PRODUCT',
                'price_amount' => 100.00,
            ],
        );

        ProductVariant::factory()->create([
            'product_id' => $expensiveDefault->id,
            'sku' => 'CHEAP_NON_DEFAULT_VARIANT',
            'price_amount' => 10.00,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $affordableDefault = $this->catalogProduct(
            [
                'name' => 'Affordable Default Variant Product',
                'slug' => 'affordable-default-variant-product',
                'sort_order' => 1,
            ],
            [
                'sku' => 'AFFORDABLE_DEFAULT_VARIANT_PRODUCT',
                'price_amount' => 50.00,
            ],
        );

        $this->getJson('/api/products?price_max=60&sort=price_low_to_high')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $affordableDefault->id)
            ->assertJsonPath('data.0.price_amount', '50.00');
    }

    public function test_related_product_fallback_uses_product_category_id(): void
    {
        $product = $this->catalogProduct([
            'name' => 'Fallback Related Base',
            'slug' => 'fallback-related-base',
            'category_slug' => 'tableware',
        ]);

        $related = $this->catalogProduct([
            'name' => 'Fallback Related Match',
            'slug' => 'fallback-related-match',
            'category_slug' => 'tableware',
        ]);

        $this->catalogProduct([
            'name' => 'Fallback Related Miss',
            'slug' => 'fallback-related-miss',
            'category_slug' => 'planters',
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.related_products.0.id', $related->id)
            ->assertJsonPath('data.related_products.0.category_slug', 'tableware');
    }

    public function test_inactive_products_are_hidden_from_public_endpoints(): void
    {
        Product::factory()->published()->create([
            'slug' => 'inactive-oxp-product',
            'category_id' => $this->category('tableware')->id,
            'is_active' => false,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/products/inactive-oxp-product')
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $productAttributes
     * @param  array<string, mixed>  $variantAttributes
     * @param  array<int, array<string, mixed>>  $attributeAssignments
     */
    private function catalogProduct(
        array $productAttributes,
        array $variantAttributes = [],
        array $attributeAssignments = [],
    ): Product {
        $category = $this->category((string) ($productAttributes['category_slug'] ?? 'tableware'));
        unset($productAttributes['category_slug']);

        $product = Product::factory()
            ->published()
            ->create([
                'category_id' => $category->id,
                'is_active' => true,
                ...$productAttributes,
            ]);

        $product->defaultVariant()?->update([
            'sku' => $variantAttributes['sku'] ?? Product::normalizeSku($product->slug),
            'title' => $variantAttributes['title'] ?? 'Default',
            'price_amount' => $variantAttributes['price_amount'] ?? 48.00,
            'compare_at_price_amount' => $variantAttributes['compare_at_price_amount'] ?? null,
            'currency' => $variantAttributes['currency'] ?? 'NZD',
            'stock_quantity' => $variantAttributes['stock_quantity'] ?? 24,
            'stock_status' => $variantAttributes['stock_status'] ?? 'in_stock',
            'inventory_policy' => $variantAttributes['inventory_policy'] ?? 'deny',
            'low_stock_threshold' => $variantAttributes['low_stock_threshold'] ?? 5,
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach ($attributeAssignments as $assignment) {
            $this->assignDynamicAttribute($product, $assignment);
        }

        return $product->fresh(['variants', 'attributeAssignments.definition', 'attributeAssignments.attributeValue']);
    }

    private function category(string $slug): ProductCategory
    {
        return ProductCategory::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Str::headline($slug),
                'name_translations' => ['en' => Str::headline($slug)],
                'description' => Str::headline($slug).' products.',
                'description_translations' => ['en' => Str::headline($slug).' products.'],
                'sort_order' => 0,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $assignment
     */
    private function assignDynamicAttribute(Product $product, array $assignment): void
    {
        $key = (string) $assignment['key'];
        $label = (string) ($assignment['label'] ?? Str::headline($key));
        $type = (string) ($assignment['type'] ?? 'text');

        $definition = ProductAttributeDefinition::query()->updateOrCreate(
            ['key' => $key],
            [
                'label' => $label,
                'label_translations' => ['en' => $label],
                'type' => $type,
                'unit' => $assignment['unit'] ?? null,
                'group' => $assignment['group'] ?? 'Specifications',
                'help_text' => null,
                'is_variant_option' => (bool) ($assignment['is_variant_option'] ?? false),
                'is_filterable' => (bool) ($assignment['is_filterable'] ?? true),
                'is_searchable' => (bool) ($assignment['is_searchable'] ?? true),
                'is_specification' => (bool) ($assignment['is_specification'] ?? true),
                'is_required' => false,
                'allows_multiple' => (bool) ($assignment['allows_multiple'] ?? false),
                'sort_order' => (int) ($assignment['sort_order'] ?? 100),
                'is_active' => true,
            ],
        );

        if (in_array($type, ['select', 'multiselect'], true)) {
            $value = (string) $assignment['value'];
            $display = (string) ($assignment['display'] ?? Str::headline($value));
            $attributeValue = ProductAttributeValue::query()->updateOrCreate(
                [
                    'attribute_definition_id' => $definition->id,
                    'value' => $value,
                ],
                [
                    'label' => $display,
                    'label_translations' => ['en' => $display],
                    'sort_order' => 0,
                    'is_active' => true,
                ],
            );

            ProductAttributeAssignment::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_definition_id' => $definition->id,
                    'product_attribute_value_id' => $attributeValue->id,
                ],
                [
                    'value_text' => null,
                    'value_number' => null,
                    'value_boolean' => null,
                    'value_json' => null,
                ],
            );

            return;
        }

        ProductAttributeAssignment::query()->create([
            'product_id' => $product->id,
            'attribute_definition_id' => $definition->id,
            'product_attribute_value_id' => null,
            'value_text' => $assignment['text'] ?? null,
            'value_number' => $assignment['number'] ?? null,
            'value_boolean' => $assignment['boolean'] ?? null,
            'value_json' => $assignment['json'] ?? null,
        ]);
    }
}
