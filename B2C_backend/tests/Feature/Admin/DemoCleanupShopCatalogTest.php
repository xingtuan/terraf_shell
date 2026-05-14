<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\DemoCleanup;
use App\Models\AdminActionLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCleanupShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_cleanup_removes_only_unblocked_demo_products_and_preserves_order_history(): void
    {
        $metadata = [
            'is_demo_content' => true,
            'seed_source' => 'product_catalog_demo',
            'seeded_at' => now(),
        ];

        $category = ProductCategory::factory()->create($metadata);
        $demoProduct = Product::factory()->published()->create([
            'category_id' => $category->id,
            ...$metadata,
        ]);
        $demoVariant = $demoProduct->defaultVariant();
        $demoVariant?->update($metadata);

        ProductImage::factory()->create([
            'product_id' => $demoProduct->id,
            ...$metadata,
        ]);

        $definition = ProductAttributeDefinition::query()->create([
            'key' => 'cleanup_test_attribute',
            'label' => 'Cleanup Test Attribute',
            'type' => 'text',
            'is_active' => true,
        ]);

        ProductAttributeAssignment::query()->create([
            'product_id' => $demoProduct->id,
            'attribute_definition_id' => $definition->id,
            'value_text' => 'Demo value',
            ...$metadata,
        ]);

        $cart = Cart::query()->create([
            'session_key' => 'demo-cart',
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $demoProduct->id,
            'product_variant_id' => $demoVariant?->id,
            'quantity' => 1,
            'unit_price_usd' => 50.00,
            'unit_price_amount' => 50.00,
            'currency' => 'NZD',
        ]);

        $blockedProduct = Product::factory()->published()->create([
            'category_id' => $category->id,
            ...$metadata,
        ]);
        $blockedVariant = $blockedProduct->defaultVariant();
        $blockedVariant?->update($metadata);

        $order = Order::query()->create([
            'user_id' => null,
            'guest_email' => 'history@example.com',
            'subtotal_usd' => 75.00,
            'shipping_usd' => 0.00,
            'tax_amount' => 0.00,
            'shipping_amount' => 0.00,
            'total_amount' => 75.00,
            'total_usd' => 75.00,
            'currency' => 'NZD',
            'shipping_name' => 'History Buyer',
            'shipping_address_line1' => '1 Demo Street',
            'shipping_city' => 'Auckland',
            'shipping_country' => 'NZ',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $blockedProduct->id,
            'product_variant_id' => $blockedVariant?->id,
            'product_name' => $blockedProduct->name,
            'product_sku' => $blockedVariant?->sku,
            'product_title' => $blockedProduct->name,
            'variant_title' => $blockedVariant?->title,
            'variant_sku' => $blockedVariant?->sku,
            'quantity' => 1,
            'unit_price_usd' => 75.00,
            'unit_price_amount' => 75.00,
            'currency' => 'NZD',
            'subtotal_usd' => 75.00,
        ]);

        $realProduct = Product::factory()->published()->create([
            'category_id' => $category->id,
            'is_demo_content' => false,
            'seed_source' => null,
            'seeded_at' => null,
        ]);

        (new DemoCleanup)->cleanupShopCatalogDemoContent();

        $this->assertDatabaseMissing('products', ['id' => $demoProduct->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $demoProduct->id]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $demoProduct->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $demoProduct->id]);
        $this->assertDatabaseMissing('product_attribute_assignments', ['product_id' => $demoProduct->id]);

        $this->assertDatabaseHas('products', ['id' => $blockedProduct->id]);
        $this->assertDatabaseHas('products', ['id' => $realProduct->id]);
        $this->assertDatabaseHas('order_items', ['product_id' => $blockedProduct->id]);
        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('admin_action_logs', ['action' => 'demo_cleanup.shop_catalog']);

        $this->assertSame(1, AdminActionLog::query()->where('action', 'demo_cleanup.shop_catalog')->count());
    }
}
