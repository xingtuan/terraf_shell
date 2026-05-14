<?php

namespace Tests\Feature\Models;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_product_status_stays_administrator_controlled(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'status' => ProductStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->assertSame(ProductStatus::Draft->value, $product->fresh()->status);
        $this->assertNull($product->fresh()->published_at);

        $product->update([
            'status' => ProductStatus::Published->value,
        ]);

        $this->assertSame(ProductStatus::Published->value, $product->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);

        $product->update([
            'status' => ProductStatus::Archived->value,
        ]);

        $this->assertSame(ProductStatus::Archived->value, $product->fresh()->status);
        $this->assertNull($product->fresh()->published_at);
    }
}
