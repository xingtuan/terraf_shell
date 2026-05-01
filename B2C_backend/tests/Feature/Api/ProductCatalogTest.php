<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_products_index_returns_richer_paginated_catalogue_and_supports_filters(): void
    {
        Product::factory()->published()->create([
            'name' => 'Dinner Plate - Lite / Ocean Bone',
            'subtitle' => 'Everyday service plate',
            'slug' => 'dinner-plate-lite-ocean-bone',
            'sku' => 'DINNER_PLATE_LITE_OCEAN_BONE',
            'category' => 'tableware',
            'model' => 'lite_15',
            'finish' => 'glossy',
            'color' => 'ocean_bone',
            'technique' => 'original_pure',
            'price_usd' => 48.00,
            'compare_at_price_usd' => 58.00,
            'image_url' => 'https://placehold.co/600x400?text=Dinner+Plate',
            'stock_quantity' => 24,
            'stock_status' => 'in_stock',
            'use_cases' => ['home_dining', 'hospitality_service'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::factory()->published()->create([
            'name' => 'Planter S - Heritage / Forged Ash',
            'slug' => 'planter-s-heritage-forged-ash',
            'sku' => 'PLANTER_S_HERITAGE_FORGED_ASH',
            'category' => 'planters',
            'model' => 'heritage_16',
            'finish' => 'matte',
            'color' => 'forged_ash',
            'technique' => 'original_pure',
            'price_usd' => 55.00,
            'image_url' => 'https://placehold.co/600x400?text=Planter+S',
            'stock_quantity' => 5,
            'stock_status' => 'low_stock',
            'use_cases' => ['design_projects'],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Product::factory()->archived()->create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'category' => 'tableware',
            'model' => 'lite_15',
            'finish' => 'glossy',
            'color' => 'ocean_bone',
            'technique' => 'original_pure',
            'price_usd' => 20.00,
            'is_active' => false,
        ]);

        $this->getJson(
            '/api/products?search=Dinner&category=tableware&model=lite_15&finish=glossy&color=ocean_bone&stock_status=in_stock&use_case=home_dining&price_min=40&price_max=60&sort=price_low_to_high&per_page=12'
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.sort', 'price_low_to_high')
            ->assertJsonPath('meta.facets.price_range.min', '48.00')
            ->assertJsonPath('meta.facets.price_range.max', '55.00')
            ->assertJsonPath('meta.applied_filter_chips.0.key', 'search')
            ->assertJsonPath('meta.applied_filter_chips.1.key', 'category')
            ->assertJsonPath('meta.applied_filter_chips.1.display', 'Tableware')
            ->assertJsonPath('meta.applied_filter_chips.7.key', 'price')
            ->assertJsonPath('meta.applied_filter_chips.7.display', '$40.00 - $60.00')
            ->assertJsonPath('data.0.title', 'Dinner Plate - Lite / Ocean Bone')
            ->assertJsonPath('data.0.sku', 'DINNER_PLATE_LITE_OCEAN_BONE')
            ->assertJsonPath('data.0.subtitle', 'Everyday service plate')
            ->assertJsonPath('data.0.category', 'tableware')
            ->assertJsonPath('data.0.category_label', 'Tableware')
            ->assertJsonPath('data.0.model_label', '1.5 Lite')
            ->assertJsonPath('data.0.finish_label', 'Glossy')
            ->assertJsonPath('data.0.color_label', 'Ocean Bone')
            ->assertJsonPath('data.0.stock_status', 'in_stock')
            ->assertJsonPath('data.0.stock_status_label', 'In Stock')
            ->assertJsonPath('data.0.can_add_to_cart', true)
            ->assertJsonPath('data.0.use_cases.0', 'home_dining')
            ->assertJsonPath('data.0.primary_image_url', 'https://placehold.co/600x400?text=Dinner+Plate')
            ->assertJsonPath('data.0.gallery_images.0.media_url', 'https://placehold.co/600x400?text=Dinner+Plate');
    }

    public function test_public_product_show_returns_requested_product_with_gallery_and_related_products(): void
    {
        $product = Product::factory()->published()->create([
            'name' => 'Wall Panel Sample',
            'subtitle' => 'Architectural material review sample',
            'slug' => 'wall-panel-sample',
            'sku' => 'WALL_PANEL_SAMPLE',
            'category' => 'architectural',
            'model' => 'heritage_16',
            'finish' => 'matte',
            'color' => 'forged_ash',
            'technique' => 'precision_inlay',
            'price_usd' => 120.00,
            'stock_status' => 'made_to_order',
            'stock_quantity' => null,
            'inquiry_only' => true,
            'sample_request_enabled' => true,
            'use_cases' => ['design_projects', 'hospitality_service'],
            'seo_title' => 'Wall Panel Sample | OXP',
            'seo_description' => 'Architectural sample for OXP finish review.',
            'is_active' => true,
            'in_stock' => true,
        ]);

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

        $relatedProduct = Product::factory()->published()->create([
            'name' => 'Studio Sample Kit',
            'slug' => 'studio-sample-kit',
            'sku' => 'STUDIO_SAMPLE_KIT',
            'category' => 'architectural',
            'model' => 'lite_15',
            'finish' => 'matte',
            'color' => 'ocean_bone',
            'technique' => 'original_pure',
            'price_usd' => 36.00,
            'is_active' => true,
            'in_stock' => true,
            'stock_status' => 'preorder',
        ]);

        $product->relatedProducts()->sync([$relatedProduct->id]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.title', 'Wall Panel Sample')
            ->assertJsonPath('data.sku', 'WALL_PANEL_SAMPLE')
            ->assertJsonPath('data.stock_status', 'made_to_order')
            ->assertJsonPath('data.can_add_to_cart', false)
            ->assertJsonPath('data.sample_request_enabled', true)
            ->assertJsonPath('data.gallery_images.0.media_url', 'https://placehold.co/900x900?text=Wall+Panel+Main')
            ->assertJsonPath('data.gallery_images.1.media_url', 'https://placehold.co/900x900?text=Wall+Panel+Detail')
            ->assertJsonPath('data.specifications.0.key', 'model')
            ->assertJsonPath('data.specifications.0.label', 'Model')
            ->assertJsonPath('data.related_products.0.slug', 'studio-sample-kit')
            ->assertJsonPath('data.seo.title', 'Wall Panel Sample | OXP')
            ->assertJsonPath('data.seo.description', 'Architectural sample for OXP finish review.');
    }

    public function test_inactive_products_are_hidden_from_public_endpoints(): void
    {
        Product::factory()->published()->create([
            'slug' => 'inactive-oxp-product',
            'category' => 'tableware',
            'model' => 'lite_15',
            'finish' => 'glossy',
            'color' => 'ocean_bone',
            'technique' => 'original_pure',
            'price_usd' => 48.00,
            'is_active' => false,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/products/inactive-oxp-product')
            ->assertNotFound();
    }
}
