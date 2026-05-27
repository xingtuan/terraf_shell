<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPricingSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_summary_estimates_shipping_tax_and_total_for_gst_inclusive_prices(): void
    {
        $this->settings()->setMany([
            'shipping.standard_rate' => ['value' => 8.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 5.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
            'tax.gst_enabled' => ['value' => true, 'type' => 'boolean'],
            'tax.gst_rate' => ['value' => 0.15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => true, 'type' => 'boolean'],
        ]);

        $product = $this->purchasableProduct(100.00);
        $sessionKey = $this->guestCartSessionKey();

        $response = $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.subtotal_usd', '100.00')
            ->assertJsonPath('data.estimated_shipping_usd', '8.00')
            ->assertJsonPath('data.estimated_tax_usd', '14.09')
            ->assertJsonPath('data.estimated_total_usd', '108.00')
            ->assertJsonPath('data.free_shipping_applied', false);
    }

    public function test_cart_summary_applies_free_shipping_threshold(): void
    {
        $this->settings()->setMany([
            'shipping.standard_rate' => ['value' => 8.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 200.0, 'type' => 'float'],
            'tax.gst_rate' => ['value' => 0.15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => true, 'type' => 'boolean'],
        ]);

        $product = $this->purchasableProduct(250.00);
        $sessionKey = $this->guestCartSessionKey();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.subtotal_usd', '250.00')
            ->assertJsonPath('data.estimated_shipping_usd', '0.00')
            ->assertJsonPath('data.estimated_total_usd', '250.00')
            ->assertJsonPath('data.free_shipping_remaining_usd', '0.00')
            ->assertJsonPath('data.free_shipping_applied', true);
    }

    private function guestCartSessionKey(): string
    {
        $this->getJson('/api/cart')->assertOk();

        return (string) Cart::query()
            ->whereNull('user_id')
            ->value('session_key');
    }

    private function purchasableProduct(float $price): Product
    {
        $product = Product::factory()->published()->create([
            'is_active' => true,
        ]);

        $product->defaultVariant()?->forceFill([
            'price_amount' => $price,
            'stock_quantity' => 24,
            'stock_status' => 'in_stock',
            'inventory_policy' => 'deny',
        ])->save();

        return $product->fresh(['variants']);
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}
