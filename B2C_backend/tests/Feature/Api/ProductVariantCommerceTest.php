<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_api_returns_default_variant_variants_attributes_and_selling_fields(): void
    {
        $product = Product::factory()->published()->create([
            'slug' => 'variant-api-product',
            'selling_points' => ['Made with recovered shell'],
            'shipping_notes' => ['Ships within New Zealand'],
            'return_notes' => ['Email support for return requests'],
            'product_faqs' => [
                ['question' => 'Can I order samples?', 'answer' => 'Yes.'],
            ],
            'technical_downloads' => [
                ['title' => 'Spec sheet', 'type' => 'product_specification_sheet', 'status' => 'available'],
            ],
        ]);
        $variant = $product->defaultVariant();

        $definition = ProductAttributeDefinition::query()->create([
            'key' => 'material_family_test',
            'label' => 'Material Family Test',
            'type' => 'select',
            'is_variant_option' => true,
            'is_filterable' => true,
            'is_specification' => true,
            'is_active' => true,
        ]);
        $value = ProductAttributeValue::query()->create([
            'attribute_definition_id' => $definition->id,
            'value' => 'oxp_test',
            'label' => 'OXP Test',
            'is_active' => true,
        ]);
        ProductAttributeAssignment::query()->create([
            'product_id' => $product->id,
            'attribute_definition_id' => $definition->id,
            'product_attribute_value_id' => $value->id,
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.currency', 'NZD')
            ->assertJsonPath('data.default_variant.id', $variant?->id)
            ->assertJsonPath('data.variants.0.id', $variant?->id)
            ->assertJsonPath('data.attributes.0.key', 'material_family_test')
            ->assertJsonPath('data.attributes.0.display_label', 'OXP Test')
            ->assertJsonPath('data.selling_points.0', 'Made with recovered shell')
            ->assertJsonPath('data.shipping_notes.0', 'Ships within New Zealand')
            ->assertJsonPath('data.return_notes.0', 'Email support for return requests')
            ->assertJsonPath('data.product_faqs.0.question', 'Can I order samples?')
            ->assertJsonPath('data.technical_downloads.0.title', 'Spec sheet');
    }

    public function test_cart_uses_default_variant_when_variant_is_missing_and_merges_same_variant(): void
    {
        $product = Product::factory()->published()->create();
        $product->defaultVariant()?->forceFill([
            'price_amount' => 30.00,
            'stock_quantity' => 8,
            'stock_status' => 'in_stock',
        ])->save();
        $variant = $product->defaultVariant()?->fresh();

        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.items.0.product_variant_id', $variant?->id);

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.item_count', 3)
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_cart_separates_different_variants_and_rejects_variant_from_another_product(): void
    {
        $product = Product::factory()->published()->create();
        $defaultVariant = $product->defaultVariant();
        $secondVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SECOND_VARIANT',
            'title' => 'Matte',
            'option_values' => ['finish' => 'matte'],
            'price_amount' => 42.00,
            'stock_quantity' => 5,
            'is_default' => false,
        ]);
        $otherProduct = Product::factory()->published()->create();
        $otherVariant = $otherProduct->defaultVariant();

        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $defaultVariant?->id,
                'quantity' => 1,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $secondVariant->id,
                'quantity' => 1,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonCount(2, 'data.items');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $otherVariant?->id,
                'quantity' => 1,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    }

    public function test_order_creation_snapshots_variant_and_deducts_deny_policy_stock(): void
    {
        $product = Product::factory()->published()->create();
        $variant = $product->defaultVariant();
        $variant?->forceFill([
            'sku' => 'ORDER_VARIANT',
            'title' => 'Warm Sand / M',
            'option_values' => ['color' => 'warm_sand', 'size' => 'm'],
            'price_amount' => 50.00,
            'stock_quantity' => 4,
            'inventory_policy' => 'deny',
        ])->save();

        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => 2,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', [
                'guest_email' => 'variant@example.com',
                'shipping_method_code' => 'standard',
                'shipping_name' => 'Variant Buyer',
                'shipping_phone' => '+64 21 123 4567',
                'shipping_address_line1' => '7 Queen Street',
                'shipping_city' => 'Auckland',
                'shipping_postal_code' => '1010',
                'shipping_country' => 'NZ',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.items.0.product_variant_id', $variant?->id)
            ->assertJsonPath('data.items.0.variant_sku', 'ORDER_VARIANT')
            ->assertJsonPath('data.items.0.option_values.color', 'warm_sand')
            ->assertJsonPath('data.items.0.unit_price_amount', '50.00');

        $this->assertSame(2, $variant?->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_adjustments', [
            'product_variant_id' => $variant?->id,
            'change_quantity' => -2,
            'reason' => 'order_created',
        ]);
    }

    public function test_inventory_policy_deny_blocks_overselling_but_continue_allows_it(): void
    {
        $denyProduct = Product::factory()->published()->create();
        $denyVariant = $denyProduct->defaultVariant();
        $denyVariant?->forceFill([
            'stock_quantity' => 1,
            'inventory_policy' => 'deny',
        ])->save();

        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $denyProduct->id,
                'variant_id' => $denyVariant?->id,
                'quantity' => 2,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $continueProduct = Product::factory()->published()->create();
        $continueVariant = $continueProduct->defaultVariant();
        $continueVariant?->forceFill([
            'stock_quantity' => 1,
            'inventory_policy' => 'continue',
            'stock_status' => 'in_stock',
        ])->save();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $continueProduct->id,
                'variant_id' => $continueVariant?->id,
                'quantity' => 2,
            ], ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_manual_variant_stock_adjustment_is_audited(): void
    {
        $product = Product::factory()->published()->create();
        $variant = $product->defaultVariant();
        $variant?->forceFill(['stock_quantity' => 3])->save();

        $adjustment = $variant?->adjustStock(5, 'stock_received', 'Received new stock.');

        $this->assertInstanceOf(InventoryAdjustment::class, $adjustment);
        $this->assertSame(8, $variant?->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_adjustments', [
            'product_variant_id' => $variant?->id,
            'change_quantity' => 5,
            'quantity_before' => 3,
            'quantity_after' => 8,
            'reason' => 'stock_received',
        ]);
    }
}
