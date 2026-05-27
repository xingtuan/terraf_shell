<?php

namespace Tests\Feature\Api;

use App\Services\Settings\SettingsService;
use App\Services\Store\CartPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTaxTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gst_inclusive_prices_do_not_add_tax_to_total(): void
    {
        $this->settings()->setMany([
            'tax.gst_enabled' => ['value' => true, 'type' => 'boolean'],
            'tax.gst_rate' => ['value' => 0.15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => true, 'type' => 'boolean'],
        ]);

        $pricing = app(CartPricingService::class);
        $tax = $pricing->taxSnapshot(100.0, 8.0);

        $this->assertSame(108.0, $pricing->total(100.0, 8.0));
        $this->assertSame(14.09, $tax['amount']);
    }

    public function test_gst_exclusive_prices_add_tax_to_total(): void
    {
        $this->settings()->setMany([
            'tax.gst_enabled' => ['value' => true, 'type' => 'boolean'],
            'tax.gst_rate' => ['value' => 0.15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => false, 'type' => 'boolean'],
        ]);

        $pricing = app(CartPricingService::class);
        $tax = $pricing->taxSnapshot(100.0, 8.0);

        $this->assertSame(124.2, $pricing->total(100.0, 8.0));
        $this->assertSame(16.2, $tax['amount']);
    }

    public function test_gst_rate_accepts_percentage_and_decimal_values(): void
    {
        $this->settings()->setMany([
            'tax.gst_enabled' => ['value' => true, 'type' => 'boolean'],
            'tax.gst_rate' => ['value' => 15, 'type' => 'float'],
            'tax.prices_include_gst' => ['value' => true, 'type' => 'boolean'],
        ]);

        $percentageTax = app(CartPricingService::class)->taxSnapshot(100.0, 8.0)['amount'];

        $this->settings()->set('tax.gst_rate', 0.15, ['type' => 'float']);

        $decimalTax = app(CartPricingService::class)->taxSnapshot(100.0, 8.0)['amount'];

        $this->assertSame($decimalTax, $percentageTax);
    }

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}
