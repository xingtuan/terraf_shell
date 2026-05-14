<?php

namespace Tests\Feature\Database;

use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seed_succeeds_and_marks_shop_catalog_demo_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Product::query()->count());
        $this->assertGreaterThan(0, ProductVariant::query()->count());
        $this->assertGreaterThan(0, ProductAttributeAssignment::query()->count());

        $this->assertSame(
            Product::query()->count(),
            Product::query()->where('is_demo_content', true)->where('seed_source', 'product_catalog_demo')->count(),
        );
        $this->assertGreaterThan(0, ProductCategory::query()->where('is_demo_content', true)->count());
        $this->assertGreaterThan(0, ProductImage::query()->where('is_demo_content', true)->count());
        $this->assertGreaterThan(0, ProductVariant::query()->where('is_demo_content', true)->count());
        $this->assertGreaterThan(0, ProductAttributeAssignment::query()->where('is_demo_content', true)->count());
    }
}
