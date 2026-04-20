<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_products_index_returns_paginated_shellfin_products_and_supports_filters(): void
    {
        Product::factory()->published()->create([
            'name' => 'Dinner Plate - Lite / Ocean Bone',
            'slug' => 'dinner-plate-lite-ocean-bone',
            'category' => 'tableware',
            'model' => 'lite_15',
            'finish' => 'glossy',
            'color' => 'ocean_bone',
            'technique' => 'original_pure',
            'price_usd' => 48.00,
            'image_url' => 'https://placehold.co/600x400?text=Dinner+Plate',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::factory()->published()->create([
            'name' => 'Planter S - Heritage / Forged Ash',
            'slug' => 'planter-s-heritage-forged-ash',
            'category' => 'planters',
            'model' => 'heritage_16',
            'finish' => 'matte',
            'color' => 'forged_ash',
            'technique' => 'original_pure',
            'price_usd' => 55.00,
            'image_url' => 'https://placehold.co/600x400?text=Planter+S',
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

        $this->getJson('/api/products?category=tableware&model=lite_15&color=ocean_bone&per_page=12')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dinner Plate - Lite / Ocean Bone')
            ->assertJsonPath('data.0.slug', 'dinner-plate-lite-ocean-bone')
            ->assertJsonPath('data.0.category', 'tableware')
            ->assertJsonPath('data.0.model', 'lite_15')
            ->assertJsonPath('data.0.finish', 'glossy')
            ->assertJsonPath('data.0.color', 'ocean_bone')
            ->assertJsonPath('data.0.technique', 'original_pure')
            ->assertJsonPath('data.0.price_usd', '48.00')
            ->assertJsonPath('data.0.in_stock', true)
            ->assertJsonPath('data.0.image_url', 'https://placehold.co/600x400?text=Dinner+Plate');
    }

    public function test_public_product_show_returns_requested_active_product(): void
    {
        $product = Product::factory()->published()->create([
            'name' => 'Wall Panel Sample',
            'slug' => 'wall-panel-sample',
            'category' => 'architectural',
            'model' => 'heritage_16',
            'finish' => 'matte',
            'color' => 'forged_ash',
            'technique' => 'original_pure',
            'price_usd' => 120.00,
            'image_url' => 'https://placehold.co/600x400?text=Wall+Panel+Sample',
            'is_active' => true,
            'in_stock' => true,
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Wall Panel Sample')
            ->assertJsonPath('data.slug', 'wall-panel-sample')
            ->assertJsonPath('data.category', 'architectural')
            ->assertJsonPath('data.model', 'heritage_16')
            ->assertJsonPath('data.finish', 'matte')
            ->assertJsonPath('data.color', 'forged_ash')
            ->assertJsonPath('data.technique', 'original_pure')
            ->assertJsonPath('data.price_usd', '120.00')
            ->assertJsonPath('data.in_stock', true)
            ->assertJsonPath('data.image_url', 'https://placehold.co/600x400?text=Wall+Panel+Sample');
    }

    public function test_inactive_products_are_hidden_from_public_endpoints(): void
    {
        Product::factory()->published()->create([
            'slug' => 'inactive-shellfin-product',
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

        $this->getJson('/api/products/inactive-shellfin-product')
            ->assertNotFound();
    }
}
