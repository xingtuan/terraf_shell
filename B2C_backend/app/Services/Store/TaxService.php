<?php

namespace App\Services\Store;

class TaxService
{
    public function gstRate(): float
    {
        return (float) config('store.tax.gst_rate', 0.15);
    }

    public function pricesIncludeGst(): bool
    {
        return (bool) config('store.tax.prices_include_gst', true);
    }

    public function label(): string
    {
        return (string) config('store.tax.label', 'GST included');
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
