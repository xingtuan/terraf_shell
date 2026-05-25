<?php

namespace App\Services\Store;

use App\Services\Settings\SettingsService;

class TaxService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function gstEnabled(): bool
    {
        return $this->settings->boolean('tax.gst_enabled', (bool) config('store.tax.gst_enabled', true));
    }

    public function gstRate(): float
    {
        if (! $this->gstEnabled()) {
            return 0.0;
        }

        $stored = $this->settings->get('tax.gst_rate');

        if ($stored !== null) {
            $rate = (float) $stored;

            // Normalise percentage inputs: 15 or "15" → 0.15, 0.15 stays 0.15.
            return $rate > 1 ? round($rate / 100, 6) : $rate;
        }

        return (float) config('store.tax.gst_rate', 0.15);
    }

    public function pricesIncludeGst(): bool
    {
        return $this->settings->boolean('tax.prices_include_gst', (bool) config('store.tax.prices_include_gst', true));
    }

    public function label(): string
    {
        return $this->settings->string('tax.label', (string) config('store.tax.label', 'GST included'));
    }

    public function taxForTotal(float $taxableTotal): float
    {
        $rate = $this->gstRate();

        if ($taxableTotal <= 0 || $rate <= 0) {
            return 0.0;
        }

        if ($this->pricesIncludeGst()) {
            return round($taxableTotal - ($taxableTotal / (1 + $rate)), 2);
        }

        return round($taxableTotal * $rate, 2);
    }

    /**
     * @return array{label: string, rate: float, amount: float, included: bool}
     */
    public function snapshot(float $taxableTotal): array
    {
        return [
            'label' => $this->label(),
            'rate' => $this->gstRate(),
            'amount' => $this->taxForTotal($taxableTotal),
            'included' => $this->pricesIncludeGst(),
        ];
    }
}
