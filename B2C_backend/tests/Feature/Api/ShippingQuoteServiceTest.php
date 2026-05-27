<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\NzPostClient;
use App\Services\Shipping\ShippingQuoteService;
use App\Services\Store\CartPricingService;
use App\Services\Store\TaxService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShippingQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    private ShippingQuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(SettingsService::class);
        $this->service = app(ShippingQuoteService::class);

        $this->settings->setMany([
            'nzpost.enabled' => ['value' => false, 'type' => 'boolean'],
            'shipping.nz_only' => ['value' => true, 'type' => 'boolean'],
            'shipping.rate_source' => ['value' => 'auto', 'type' => 'string'],
        ]);
    }

    private function makeEmptyCart(): Cart
    {
        $cart = Cart::query()->make();
        $cart->setRelation('items', new EloquentCollection);

        return $cart;
    }

    private function makeCartWithSubtotal(float $subtotal): Cart
    {
        $cart = Cart::query()->make();
        $item = new CartItem([
            'product_id' => 1,
            'quantity' => 1,
            'unit_price_usd' => $subtotal,
            'unit_price_amount' => $subtotal,
            'currency' => 'NZD',
        ]);

        $cart->setRelation('items', new EloquentCollection([$item]));

        return $cart;
    }

    /** @param array<string, mixed> $overrides */
    private function nzAddress(array $overrides = []): array
    {
        return array_merge([
            'line1' => '123 Test Street',
            'city' => 'Auckland',
            'region' => 'Auckland',
            'postcode' => '1010',
            'country' => 'NZ',
            'is_rural' => false,
        ], $overrides);
    }

    public function test_fallback_rates_are_read_from_settings_not_config(): void
    {
        $this->settings->setMany([
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 7.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeEmptyCart(), $this->nzAddress());
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('22.00', $options['standard']['amount']);
        $this->assertSame('35.00', $options['express']['amount']);
    }

    public function test_rural_surcharge_is_added_when_address_is_rural(): void
    {
        $this->settings->setMany([
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 7.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeEmptyCart(), $this->nzAddress(['is_rural' => true]));
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('29.00', $options['standard']['amount']); // 22 + 7
        $this->assertSame('42.00', $options['express']['amount']); // 35 + 7
    }

    public function test_standard_shipping_is_free_when_subtotal_meets_threshold(): void
    {
        $this->settings->setMany([
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 0.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 20.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeCartWithSubtotal(25.0), $this->nzAddress());
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('0.00', $options['standard']['amount']);
        $this->assertSame('22.00', $options['standard']['original_amount']);
        $this->assertTrue($options['standard']['free_shipping_applied']);
        $this->assertSame('35.00', $options['express']['amount']); // express not affected by threshold
    }

    public function test_free_threshold_with_rural_makes_standard_option_free_only(): void
    {
        $this->settings->setMany([
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 7.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 20.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeCartWithSubtotal(25.0), $this->nzAddress(['is_rural' => true]));
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('0.00', $options['standard']['amount']);
        $this->assertSame('29.00', $options['standard']['original_amount']);
        $this->assertSame('42.00', $options['express']['amount']); // 35 + 7
    }

    public function test_free_shipping_threshold_zero_disables_free_shipping(): void
    {
        $this->settings->setMany([
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 0.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 0.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeCartWithSubtotal(25.0), $this->nzAddress());
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('22.00', $options['standard']['amount']);
        $this->assertArrayNotHasKey('free_shipping_applied', $options['standard']);
    }

    public function test_free_shipping_policy_applies_to_standard_nzpost_quotes(): void
    {
        $this->settings->setMany([
            'shipping.rate_source' => ['value' => 'nzpost', 'type' => 'string'],
            'shipping.free_shipping_threshold' => ['value' => 20.0, 'type' => 'float'],
        ]);

        $client = new class($this->settings) extends NzPostClient {
            public function isConfigured(): bool
            {
                return true;
            }

            public function shippingOptions(array $payload): ?array
            {
                return [
                    'options' => [
                        [
                            'code' => 'standard_nzpost',
                            'label' => 'Standard NZ Post',
                            'amount' => 18,
                            'currency' => 'NZD',
                        ],
                        [
                            'code' => 'express_nzpost',
                            'label' => 'Express NZ Post',
                            'amount' => 30,
                            'currency' => 'NZD',
                        ],
                    ],
                ];
            }
        };

        $service = new ShippingQuoteService(
            $client,
            app(TaxService::class),
            $this->settings,
            app(CartPricingService::class),
        );

        $quote = $service->quote($this->makeCartWithSubtotal(25.0), $this->nzAddress());
        $options = collect($quote['options'])->keyBy('code');

        $this->assertSame('0.00', $options['standard_nzpost']['amount']);
        $this->assertSame('18.00', $options['standard_nzpost']['original_amount']);
        $this->assertSame('30.00', $options['express_nzpost']['amount']);
    }

    public function test_nz_only_true_rejects_non_nz_address(): void
    {
        $this->settings->set('shipping.nz_only', true, ['type' => 'boolean']);

        $this->expectException(ValidationException::class);

        $this->service->quote($this->makeEmptyCart(), $this->nzAddress(['country' => 'AU']));
    }

    public function test_nz_only_false_controller_does_not_reject_non_nz_country(): void
    {
        $this->settings->setMany([
            'shipping.nz_only' => ['value' => false, 'type' => 'boolean'],
            'shipping.fallback_standard_amount' => ['value' => 10.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 20.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 0.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
        ]);

        $response = $this->postJson('/api/store/shipping-options', [
            'address' => [
                'line1' => '123 Test St',
                'city' => 'Sydney',
                'postcode' => '2000',
                'country' => 'AU',
                'is_rural' => false,
            ],
        ]);

        // Not blocked at controller level by in:NZ; ShippingQuoteService allows it
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['options']]);
    }

    public function test_nz_only_true_controller_still_rejects_non_nz_via_service(): void
    {
        $this->settings->set('shipping.nz_only', true, ['type' => 'boolean']);

        $response = $this->postJson('/api/store/shipping-options', [
            'address' => [
                'line1' => '123 Test St',
                'city' => 'Sydney',
                'postcode' => '2000',
                'country' => 'AU',
                'is_rural' => false,
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_country']);
    }

    public function test_nzpost_enabled_reads_from_settings_not_env(): void
    {
        config(['store.nzpost.enabled' => false]);
        $this->settings->set('nzpost.enabled', true, ['type' => 'boolean']);

        // NzPostClient resolves fresh from container using the SettingsService
        $client = app(NzPostClient::class);

        $this->assertTrue($client->isEnabled());
    }

    public function test_nzpost_disabled_via_settings_falls_back_to_manual_rates(): void
    {
        $this->settings->setMany([
            'nzpost.enabled' => ['value' => false, 'type' => 'boolean'],
            'shipping.fallback_standard_amount' => ['value' => 22.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 35.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 0.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeEmptyCart(), $this->nzAddress());

        foreach ($quote['options'] as $option) {
            $this->assertSame('fallback', $option['source'], "Option {$option['code']} should be fallback source");
        }

        $options = collect($quote['options'])->keyBy('code');
        $this->assertSame('22.00', $options['standard']['amount']);
        $this->assertSame('35.00', $options['express']['amount']);
    }

    public function test_rate_source_manual_always_uses_fallback_even_if_nzpost_would_be_available(): void
    {
        $this->settings->setMany([
            'shipping.rate_source' => ['value' => 'manual', 'type' => 'string'],
            'shipping.fallback_standard_amount' => ['value' => 15.0, 'type' => 'float'],
            'shipping.fallback_express_amount' => ['value' => 25.0, 'type' => 'float'],
            'shipping.rural_surcharge' => ['value' => 0.0, 'type' => 'float'],
            'shipping.free_shipping_threshold' => ['value' => 9999.0, 'type' => 'float'],
        ]);

        $quote = $this->service->quote($this->makeEmptyCart(), $this->nzAddress());

        foreach ($quote['options'] as $option) {
            $this->assertSame('fallback', $option['source'], "Manual mode must return fallback source");
        }

        $options = collect($quote['options'])->keyBy('code');
        $this->assertSame('15.00', $options['standard']['amount']);
        $this->assertSame('25.00', $options['express']['amount']);
    }

    public function test_rate_source_nzpost_only_throws_when_nzpost_unavailable(): void
    {
        $this->settings->setMany([
            'shipping.rate_source' => ['value' => 'nzpost', 'type' => 'string'],
            'nzpost.enabled' => ['value' => false, 'type' => 'boolean'],
        ]);

        $this->expectException(ValidationException::class);

        $this->service->quote($this->makeEmptyCart(), $this->nzAddress());
    }
}
