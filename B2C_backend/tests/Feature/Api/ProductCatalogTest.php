<?php

namespace Tests\Feature\Api;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_endpoints_return_localized_published_catalog_content(): void
    {
        $category = ProductCategory::factory()->create([
            'slug' => 'tableware',
            'name' => 'Tableware',
            'name_translations' => [
                'en' => 'Tableware',
                'ko' => 'KO Tableware',
                'zh' => 'ZH Tableware',
            ],
            'description' => 'English category description',
            'description_translations' => [
                'en' => 'English category description',
                'ko' => 'KO category description',
                'zh' => 'ZH category description',
            ],
            'sort_order' => 1,
        ]);

        ProductCategory::factory()->inactive()->create([
            'slug' => 'inactive-category',
        ]);

        $featuredProduct = Product::factory()
            ->published()
            ->featured()
            ->create([
                'category_id' => $category->id,
                'slug' => 'tidal-dinner-plate',
                'name' => 'Tidal Dinner Plate',
                'name_translations' => [
                    'en' => 'Tidal Dinner Plate',
                    'ko' => 'KO Tidal Dinner Plate',
                    'zh' => 'ZH Tidal Dinner Plate',
                ],
                'short_description' => 'English short description',
                'short_description_translations' => [
                    'en' => 'English short description',
                    'ko' => 'KO short description',
                    'zh' => 'ZH short description',
                ],
                'full_description' => 'English full description',
                'full_description_translations' => [
                    'en' => 'English full description',
                    'ko' => 'KO full description',
                    'zh' => 'ZH full description',
                ],
                'features' => ['English feature'],
                'features_translations' => [
                    'en' => ['English feature'],
                    'ko' => ['KO feature'],
                    'zh' => ['ZH feature'],
                ],
                'availability_text' => 'English availability',
                'availability_text_translations' => [
                    'en' => 'English availability',
                    'ko' => 'KO availability',
                    'zh' => 'ZH availability',
                ],
                'media_url' => 'https://example.com/cover.jpg',
                'price_from' => 88.50,
                'currency' => 'USD',
                'sort_order' => 1,
            ]);

        ProductImage::factory()->create([
            'product_id' => $featuredProduct->id,
            'alt_text' => 'English alt text',
            'alt_text_translations' => [
                'en' => 'English alt text',
                'ko' => 'KO alt text',
                'zh' => 'ZH alt text',
            ],
            'caption' => 'English caption',
            'caption_translations' => [
                'en' => 'English caption',
                'ko' => 'KO caption',
                'zh' => 'ZH caption',
            ],
            'media_url' => 'https://example.com/gallery.jpg',
            'sort_order' => 1,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'draft-product',
        ]);

        Product::factory()->archived()->create([
            'category_id' => $category->id,
            'slug' => 'archived-product',
        ]);

        $this->getJson('/api/product-categories?locale=ko')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'tableware')
            ->assertJsonPath('data.0.name', 'KO Tableware');

        $this->getJson('/api/products?locale=zh&featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'tidal-dinner-plate')
            ->assertJsonPath('data.0.name', 'ZH Tidal Dinner Plate')
            ->assertJsonPath('data.0.features.0', 'ZH feature');

        $this->getJson('/api/products?category=tableware&locale=ko')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.slug', 'tableware');

        $this->getJson('/api/products/featured?locale=ko')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'tidal-dinner-plate');

        $this->getJson('/api/products/tidal-dinner-plate?locale=ko')
            ->assertOk()
            ->assertJsonPath('data.slug', 'tidal-dinner-plate')
            ->assertJsonPath('data.name', 'KO Tidal Dinner Plate')
            ->assertJsonPath('data.short_description', 'KO short description')
            ->assertJsonPath('data.full_description', 'KO full description')
            ->assertJsonPath('data.availability_text', 'KO availability')
            ->assertJsonPath('data.category.name', 'KO Tableware')
            ->assertJsonCount(1, 'data.gallery_images')
            ->assertJsonPath('data.gallery_images.0.alt_text', 'KO alt text');
    }

    public function test_products_in_inactive_categories_are_hidden_from_public_endpoints(): void
    {
        $inactiveCategory = ProductCategory::factory()->inactive()->create([
            'slug' => 'hidden',
        ]);

        Product::factory()->published()->create([
            'category_id' => $inactiveCategory->id,
            'slug' => 'hidden-product',
            'status' => ProductStatus::Published->value,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/products/hidden-product')
            ->assertNotFound();
    }
}
