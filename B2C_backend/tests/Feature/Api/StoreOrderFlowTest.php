<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Cart;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_can_be_merged_into_user_cart_and_checked_out(): void
    {
        $product = $this->purchasableProduct(48.00);

        $guestCartResponse = $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 0)
            ->assertCookie('oxp_cart_session');

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->assertNotNull($sessionKey);

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal_usd', '96.00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cart/merge', [
            'session_key' => $sessionKey,
        ])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->with('items')->first();

        $this->assertNotNull($cart);
        $this->assertSame(2, $cart->itemCount());
        $this->assertDatabaseCount('cart_items', 1);

        $orderResponse = $this->postJson('/api/orders', [
            'shipping_method_code' => 'standard',
            'shipping_name' => 'OXP Buyer',
            'shipping_phone' => '+64-21-000-000',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_state_province' => 'Auckland',
            'shipping_postal_code' => '1010',
            'shipping_country' => 'NZ',
            'customer_note' => 'Please confirm the finish before shipping.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_method', 'manual')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.subtotal_usd', '96.00')
            ->assertJsonPath('data.shipping_usd', '8.00')
            ->assertJsonPath('data.tax_usd', '13.57')
            ->assertJsonPath('data.total_usd', '104.00')
            ->assertJsonPath('data.currency', 'NZD')
            ->assertJsonPath('data.shipping_method.code', 'standard')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2);

        $orderNumber = $orderResponse->json('data.order_number');

        $this->assertMatchesRegularExpression('/^OXP-\d{6}$/', $orderNumber);
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'shipping_country' => 'NZ',
            'shipping_method_code' => 'standard',
            'tax_amount' => '13.57',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_usd' => '48.00',
            'subtotal_usd' => '96.00',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'event_key' => 'order.created',
            'status' => EmailLog::STATUS_SKIPPED,
            'skip_reason' => 'global_disabled',
            'related_type' => 'order',
        ]);
        $this->assertDatabaseHas('email_logs', [
            'event_key' => 'order.admin_new_order',
            'status' => EmailLog::STATUS_SKIPPED,
            'skip_reason' => 'global_disabled',
            'related_type' => 'order',
        ]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_cart_can_create_guest_order_request(): void
    {
        $product = $this->purchasableProduct(48.00);

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $orderResponse = $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', [
                'guest_email' => 'guest@example.com',
                'shipping_method_code' => 'standard',
                'shipping_name' => 'Guest Buyer',
                'shipping_phone' => '+64 21 000 000',
                'shipping_address_line1' => '7 Queen Street',
                'shipping_address_line2' => 'Auckland Central',
                'shipping_city' => 'Auckland',
                'shipping_state_province' => 'Auckland',
                'shipping_postal_code' => '1010',
                'shipping_country' => 'NZ',
                'customer_note' => 'Please email delivery timing.',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_guest', true)
            ->assertJsonPath('data.guest_email', 'guest@example.com')
            ->assertJsonPath('data.payment_method', 'manual')
            ->assertJsonPath('data.shipping_address.name', 'Guest Buyer')
            ->assertJsonPath('data.shipping_address.phone', '+64 21 000 000')
            ->assertJsonPath('data.subtotal_usd', '96.00')
            ->assertJsonPath('data.shipping_usd', '8.00')
            ->assertJsonPath('data.tax_usd', '13.57')
            ->assertJsonPath('data.total_usd', '104.00')
            ->assertJsonPath('data.shipping_method.code', 'standard')
            ->assertJsonPath('data.items.0.quantity', 2);

        $orderNumber = $orderResponse->json('data.order_number');
        $guestToken = $orderResponse->json('data.guest_order_token');

        $this->assertNotEmpty($guestToken);
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'user_id' => null,
            'guest_email' => 'guest@example.com',
            'shipping_name' => 'Guest Buyer',
            'shipping_phone' => '+64 21 000 000',
            'shipping_country' => 'NZ',
            'shipping_method_code' => 'standard',
        ]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_order_lookup_requires_valid_token(): void
    {
        $product = $this->purchasableProduct(35.00);

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $orderResponse = $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', [
                'guest_email' => 'lookup@example.com',
                'shipping_method_code' => 'standard',
                'shipping_name' => 'Lookup Buyer',
                'shipping_phone' => '+64 21 000 001',
                'shipping_address_line1' => '100 Lambton Quay',
                'shipping_city' => 'Wellington',
                'shipping_state_province' => 'Wellington',
                'shipping_postal_code' => '6011',
                'shipping_country' => 'NZ',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated();

        $orderNumber = $orderResponse->json('data.order_number');
        $guestToken = $orderResponse->json('data.guest_order_token');

        $this->getJson("/api/orders/guest/{$orderNumber}?token={$guestToken}")
            ->assertOk()
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.guest_email', 'lookup@example.com');

        $this->getJson("/api/orders/guest/{$orderNumber}?token=wrong-token")
            ->assertNotFound();

        $this->postJson('/api/orders/guest/lookup', [
            'order_number' => strtolower((string) $orderNumber),
            'email' => 'LOOKUP@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.guest_email', 'lookup@example.com');

        $this->postJson('/api/orders/guest/lookup', [
            'order_number' => $orderNumber,
            'email' => 'wrong@example.com',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['order_number']);
    }

    public function test_guest_checkout_can_be_disabled_by_runtime_setting_without_removing_route(): void
    {
        app(SettingsService::class)->set('feature.guest_checkout_enabled', false, [
            'type' => 'boolean',
        ]);

        $product = $this->purchasableProduct(48.00);

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->postJson('/api/orders', [
                'guest_email' => 'guest@example.com',
                'shipping_method_code' => 'standard',
                'shipping_name' => 'Guest Buyer',
                'shipping_phone' => '+64 21 000 000',
                'shipping_address_line1' => '7 Queen Street',
                'shipping_city' => 'Auckland',
                'shipping_state_province' => 'Auckland',
                'shipping_postal_code' => '1010',
                'shipping_country' => 'NZ',
            ])
            ->assertForbidden();
    }

    public function test_order_creation_rejects_non_new_zealand_shipping(): void
    {
        $product = $this->purchasableProduct(48.00);

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', [
                'guest_email' => 'guest@example.com',
                'shipping_method_code' => 'standard',
                'shipping_name' => 'Guest Buyer',
                'shipping_phone' => '+61 400 000 000',
                'shipping_address_line1' => '1 George Street',
                'shipping_city' => 'Sydney',
                'shipping_state_province' => 'NSW',
                'shipping_postal_code' => '2000',
                'shipping_country' => 'AU',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shipping_country']);
    }

    public function test_selected_shipping_method_must_be_available_after_server_recalculation(): void
    {
        $product = $this->purchasableProduct(48.00);

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', [
                'guest_email' => 'guest@example.com',
                'shipping_method_code' => 'overnight',
                'shipping_name' => 'Guest Buyer',
                'shipping_phone' => '+64 21 000 000',
                'shipping_address_line1' => '7 Queen Street',
                'shipping_city' => 'Auckland',
                'shipping_state_province' => 'Auckland',
                'shipping_postal_code' => '1010',
                'shipping_country' => 'NZ',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shipping_method_code']);
    }

    public function test_shipping_and_address_lookup_fall_back_without_nz_post_credentials(): void
    {
        config()->set('store.nzpost.enabled', true);
        config()->set('store.nzpost.api_key', null);
        config()->set('store.nzpost.client_id', null);
        config()->set('store.nzpost.client_secret', null);

        $product = $this->purchasableProduct(48.00);

        $this->getJson('/api/store/address-search?query=Auckland')
            ->assertOk()
            ->assertJsonPath('data.unavailable', true)
            ->assertJsonPath('data.source', 'fallback')
            ->assertJsonPath('data.items.0.city', 'Auckland');

        $this->getJson('/api/cart')->assertOk();

        $sessionKey = Cart::query()
            ->whereNull('user_id')
            ->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/store/shipping-options', [
                'address' => [
                    'line1' => '7 Queen Street',
                    'city' => 'Auckland',
                    'region' => 'Auckland',
                    'postcode' => '1010',
                    'country' => 'NZ',
                    'is_rural' => false,
                ],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.options.0.code', 'standard')
            ->assertJsonPath('data.options.0.amount', '8.00')
            ->assertJsonPath('data.tax.label', 'GST included')
            ->assertJsonPath('data.totals.total', '56.00');
    }

    public function test_guest_cart_accepts_pre_rebrand_cookie_name(): void
    {
        $sessionKey = 'legacy-cart-session';

        Cart::query()->create([
            'session_key' => $sessionKey,
            'expires_at' => now()->addDays(7),
        ]);

        $this->withUnencryptedCookies([CartService::LEGACY_COOKIE_NAME => $sessionKey])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 0)
            ->assertCookie(CartService::COOKIE_NAME);
    }

    public function test_shipping_option_totals_endpoint_returns_tax_and_totals(): void
    {
        $product = $this->purchasableProduct(48.00);
        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/store/shipping-option/totals', [
                'shipping_method_code' => 'standard',
                'address' => $this->quoteAddress(),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.tax.label', 'GST included')
            ->assertJsonPath('data.totals.subtotal', '48.00')
            ->assertJsonPath('data.totals.shipping', '8.00')
            ->assertJsonPath('data.totals.tax', '7.30')
            ->assertJsonPath('data.totals.total', '56.00');
    }

    public function test_shipping_option_totals_endpoint_rejects_invalid_code(): void
    {
        $product = $this->purchasableProduct(48.00);
        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/store/shipping-option/totals', [
                'shipping_method_code' => 'overnight',
                'address' => $this->quoteAddress(),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shipping_method_code']);
    }

    public function test_shipping_option_totals_endpoint_rejects_empty_cart(): void
    {
        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->postJson('/api/store/shipping-option/totals', [
                'shipping_method_code' => 'standard',
                'address' => $this->quoteAddress(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cart']);
    }

    public function test_user_can_manage_addresses_and_cancel_pending_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $addressResponse = $this->postJson('/api/addresses', [
            'label' => 'Home',
            'recipient_name' => 'OXP Buyer',
            'phone' => '+64-21-000-000',
            'address_line1' => '123 Ocean Road',
            'city' => 'Auckland',
            'country' => 'NZ',
            'is_default' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default', true);

        $addressId = $addressResponse->json('data.id');

        $secondAddress = Address::query()->create([
            'user_id' => $user->id,
            'label' => 'Office',
            'recipient_name' => 'OXP Buyer',
            'address_line1' => '10 Harbour Street',
            'city' => 'Auckland',
            'country' => 'NZ',
            'is_default' => false,
        ]);

        $this->postJson("/api/addresses/{$secondAddress->id}/default")
            ->assertOk()
            ->assertJsonPath('data.id', $secondAddress->id)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('addresses', [
            'id' => $addressId,
            'is_default' => false,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'OXP-000001',
            'status' => 'pending',
            'subtotal_usd' => 48.00,
            'shipping_usd' => 15.00,
            'total_usd' => 63.00,
            'currency' => 'USD',
            'shipping_name' => 'OXP Buyer',
            'shipping_address_line1' => '123 Ocean Road',
            'shipping_city' => 'Auckland',
            'shipping_country' => 'NZ',
            'payment_method' => 'manual',
            'payment_status' => 'unpaid',
        ]);

        $this->deleteJson("/api/orders/{$order->order_number}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_order_creation_adds_gst_when_prices_are_exclusive(): void
    {
        app(SettingsService::class)->setMany([
            'shipping.standard_rate' => ['value' => 8.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
            'tax.gst_rate' => ['value' => 0.15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => false, 'type' => 'boolean'],
        ]);

        $product = $this->purchasableProduct(100.00);
        $this->getJson('/api/cart')->assertOk();
        $sessionKey = Cart::query()->whereNull('user_id')->value('session_key');

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', $this->guestOrderPayload(), [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal_usd', '100.00')
            ->assertJsonPath('data.shipping_usd', '8.00')
            ->assertJsonPath('data.tax_usd', '16.20')
            ->assertJsonPath('data.total_usd', '124.20');
    }

    public function test_order_total_uses_unit_price_amount_when_cart_item_has_no_variant_id(): void
    {
        app(SettingsService::class)->setMany([
            'shipping.standard_rate' => ['value' => 8.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => true, 'type' => 'boolean'],
        ]);

        $product = $this->purchasableProduct(100.00);
        $sessionKey = (string) Str::uuid();
        $cart = Cart::query()->create([
            'session_key' => $sessionKey,
            'expires_at' => now()->addDays(7),
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price_usd' => 0,
            'unit_price_amount' => 100,
            'currency' => 'NZD',
        ]);

        $this->withUnencryptedCookies([CartService::COOKIE_NAME => $sessionKey])
            ->post('/api/orders', $this->guestOrderPayload(), [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal_usd', '100.00')
            ->assertJsonPath('data.total_usd', '108.00');
    }

    public function test_order_service_recalculates_when_shipping_quote_subtotal_differs(): void
    {
        app(SettingsService::class)->set('tax.prices_include_gst', true, ['type' => 'boolean']);

        $product = $this->purchasableProduct(100.00);
        $variant = $product->defaultVariant();
        $cart = Cart::query()->create([
            'session_key' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => 1,
            'unit_price_usd' => 100,
            'unit_price_amount' => 100,
            'currency' => 'NZD',
        ]);

        $this->mock(ShippingQuoteService::class, function ($mock): void {
            $mock->shouldReceive('selectedOption')
                ->once()
                ->andReturn([
                    'option' => [
                        'code' => 'standard',
                        'label' => 'Standard NZ delivery',
                        'amount' => '8.00',
                        'currency' => 'NZD',
                        'service_code' => 'fallback_standard_nz',
                    ],
                    'tax' => [
                        'label' => 'GST included',
                        'rate' => 0.15,
                        'amount' => '0.00',
                        'included' => true,
                    ],
                    'totals' => [
                        'subtotal' => '0.00',
                        'shipping' => '8.00',
                        'tax' => '0.00',
                        'total' => '8.00',
                        'currency' => 'NZD',
                    ],
                    'snapshot' => [],
                ]);
        });

        $order = app(OrderService::class)->createFromCart(
            $cart,
            $this->shippingData(),
            '',
            'guest@example.com',
            'standard',
        );

        $this->assertSame('100.00', number_format((float) $order->subtotal_usd, 2, '.', ''));
        $this->assertSame('8.00', number_format((float) $order->shipping_usd, 2, '.', ''));
        $this->assertSame('108.00', number_format((float) $order->total_usd, 2, '.', ''));
        $this->assertSame('100.00', data_get($order->shipping_quote_snapshot, 'totals.subtotal'));
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

    /**
     * @return array<string, mixed>
     */
    private function guestOrderPayload(): array
    {
        return array_merge([
            'guest_email' => 'guest@example.com',
            'shipping_method_code' => 'standard',
        ], $this->shippingData());
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingData(): array
    {
        return [
            'shipping_name' => 'Guest Buyer',
            'shipping_phone' => '+64 21 000 000',
            'shipping_address_line1' => '7 Queen Street',
            'shipping_address_line2' => 'Auckland Central',
            'shipping_city' => 'Auckland',
            'shipping_state_province' => 'Auckland',
            'shipping_postal_code' => '1010',
            'shipping_country' => 'NZ',
            'shipping_is_rural' => false,
            'customer_note' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteAddress(): array
    {
        return [
            'line1' => '7 Queen Street',
            'line2' => 'Auckland Central',
            'city' => 'Auckland',
            'region' => 'Auckland',
            'postcode' => '1010',
            'country' => 'NZ',
            'is_rural' => false,
        ];
    }
}
