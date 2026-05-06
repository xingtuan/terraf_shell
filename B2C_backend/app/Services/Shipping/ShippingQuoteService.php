<?php

namespace App\Services\Shipping;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Store\TaxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ShippingQuoteService
{
    public function __construct(
        private readonly NzPostClient $nzPostClient,
        private readonly TaxService $taxService,
    ) {}

    /**
     * @param  array<string, mixed>  $address
     * @return array{options: array<int, array<string, mixed>>, tax: array<string, mixed>, totals: array<string, mixed>}
     */
    public function quote(Cart $cart, array $address): array
    {
        $cart->loadMissing(['items.product']);
        $this->validateAddress($address);

        $subtotal = $this->subtotal($cart);
        $options = $this->nzPostOptions($cart, $address);

        if ($options === []) {
            $options = $this->fallbackOptions($cart, $address);
        }

        $defaultOption = collect($options)->first(fn (array $option): bool => (bool) ($option['is_default'] ?? false))
            ?? $options[0]
            ?? null;

        $shipping = (float) ($defaultOption['amount'] ?? 0);
        $taxableBase = $subtotal + $shipping;
        $tax = $this->taxService->snapshot($taxableBase);
        $total = $this->total($taxableBase, (float) $tax['amount']);

        return [
            'options' => $options,
            'tax' => [
                'label' => $tax['label'],
                'rate' => $tax['rate'],
                'amount' => $this->formatMoney((float) $tax['amount']),
                'included' => $tax['included'],
            ],
            'totals' => [
                'subtotal' => $this->formatMoney($subtotal),
                'shipping' => $this->formatMoney($shipping),
                'tax' => $this->formatMoney((float) $tax['amount']),
                'total' => $this->formatMoney($total),
                'currency' => $this->currency(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{option: array<string, mixed>, tax: array<string, mixed>, totals: array<string, mixed>, snapshot: array<string, mixed>}
     */
    public function selectedOption(Cart $cart, array $address, string $code): array
    {
        $quote = $this->quote($cart, $address);
        $selected = collect($quote['options'])
            ->first(fn (array $option): bool => (string) ($option['code'] ?? '') === $code);

        if (! is_array($selected)) {
            throw ValidationException::withMessages([
                'shipping_method_code' => ['The selected shipping method is not available for this cart and address.'],
            ]);
        }

        $subtotal = $this->subtotal($cart);
        $shipping = (float) $selected['amount'];
        $taxableBase = $subtotal + $shipping;
        $tax = $this->taxService->snapshot($taxableBase);
        $total = $this->total($taxableBase, (float) $tax['amount']);

        return [
            'option' => $selected,
            'tax' => [
                'label' => $tax['label'],
                'rate' => $tax['rate'],
                'amount' => $this->formatMoney((float) $tax['amount']),
                'included' => $tax['included'],
            ],
            'totals' => [
                'subtotal' => $this->formatMoney($subtotal),
                'shipping' => $this->formatMoney($shipping),
                'tax' => $this->formatMoney((float) $tax['amount']),
                'total' => $this->formatMoney($total),
                'currency' => $this->currency(),
            ],
            'snapshot' => [
                'address' => Arr::only($address, [
                    'line1',
                    'line2',
                    'city',
                    'region',
                    'postcode',
                    'country',
                    'is_rural',
                ]),
                'selected_method' => $selected,
                'options' => $quote['options'],
                'tax' => $tax,
                'totals' => [
                    'subtotal' => $this->formatMoney($subtotal),
                    'shipping' => $this->formatMoney($shipping),
                    'tax' => $this->formatMoney((float) $tax['amount']),
                    'total' => $this->formatMoney($total),
                    'currency' => $this->currency(),
                ],
                'source' => (string) ($selected['source'] ?? 'fallback'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function validateAddress(array $address): void
    {
        $country = strtoupper(trim((string) ($address['country'] ?? '')));

        if ($country !== 'NZ') {
            throw ValidationException::withMessages([
                'shipping_country' => ['We currently deliver within New Zealand only.'],
            ]);
        }

        if (blank($address['postcode'] ?? null)) {
            throw ValidationException::withMessages([
                'shipping_postal_code' => ['A New Zealand postcode is required.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<int, array<string, mixed>>
     */
    private function nzPostOptions(Cart $cart, array $address): array
    {
        $payload = $this->nzPostClient->shippingOptions([
            'origin' => [
                'postcode' => config('store.shipping.origin.postcode'),
                'city' => config('store.shipping.origin.city'),
                'country' => config('store.shipping.origin.country', 'NZ'),
            ],
            'destination' => [
                'postcode' => $address['postcode'] ?? null,
                'city' => $address['city'] ?? null,
                'country' => 'NZ',
            ],
            'items' => $cart->items->map(fn (CartItem $item): array => [
                'quantity' => $item->quantity,
                'weight_grams' => $item->product?->weight_grams ?? 500,
            ])->values()->all(),
        ]);

        if ($payload === null) {
            return [];
        }

        $services = data_get($payload, 'options')
            ?? data_get($payload, 'services')
            ?? data_get($payload, 'rates')
            ?? [];

        return collect(Arr::wrap($services))
            ->map(function (mixed $service, int $index): ?array {
                if (! is_array($service)) {
                    return null;
                }

                $code = data_get($service, 'code') ?? data_get($service, 'service_code');
                $amount = data_get($service, 'amount') ?? data_get($service, 'price') ?? data_get($service, 'total');

                if (! filled($code) || ! is_numeric($amount)) {
                    return null;
                }

                return [
                    'code' => (string) $code,
                    'label' => (string) (data_get($service, 'label') ?? data_get($service, 'name') ?? 'NZ Post delivery'),
                    'description' => (string) (data_get($service, 'description') ?? 'Tracked delivery within New Zealand'),
                    'amount' => $this->formatMoney((float) $amount),
                    'currency' => (string) (data_get($service, 'currency') ?? $this->currency()),
                    'eta_min_days' => data_get($service, 'eta_min_days') ?? data_get($service, 'min_days'),
                    'eta_max_days' => data_get($service, 'eta_max_days') ?? data_get($service, 'max_days'),
                    'service_code' => (string) (data_get($service, 'service_code') ?? $code),
                    'is_default' => $index === 0,
                    'source' => 'nzpost',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<int, array<string, mixed>>
     */
    private function fallbackOptions(Cart $cart, array $address): array
    {
        $subtotal = $this->subtotal($cart);
        $ruralSurcharge = $this->isRural($address) ? (float) config('store.shipping.rural_surcharge', 5) : 0.0;
        $freeThreshold = (float) config('store.shipping.free_shipping_threshold', 200);
        $standardBase = $subtotal >= $freeThreshold ? 0.0 : (float) config('store.shipping.standard_rate', 8);
        $expressBase = (float) config('store.shipping.express_rate', 14);

        return [
            [
                'code' => 'standard',
                'label' => 'Standard NZ delivery',
                'description' => $ruralSurcharge > 0
                    ? 'Tracked delivery within New Zealand. Rural delivery surcharge included.'
                    : 'Tracked delivery within New Zealand',
                'amount' => $this->formatMoney($standardBase + $ruralSurcharge),
                'currency' => $this->currency(),
                'eta_min_days' => 2,
                'eta_max_days' => 5,
                'service_code' => 'fallback_standard_nz',
                'is_default' => true,
                'source' => 'fallback',
                'rural_surcharge' => $this->formatMoney($ruralSurcharge),
            ],
            [
                'code' => 'express',
                'label' => 'Priority NZ delivery',
                'description' => $ruralSurcharge > 0
                    ? 'Faster tracked delivery. Rural delivery surcharge included.'
                    : 'Faster tracked delivery within New Zealand',
                'amount' => $this->formatMoney($expressBase + $ruralSurcharge),
                'currency' => $this->currency(),
                'eta_min_days' => 1,
                'eta_max_days' => 3,
                'service_code' => 'fallback_priority_nz',
                'is_default' => false,
                'source' => 'fallback',
                'rural_surcharge' => $this->formatMoney($ruralSurcharge),
            ],
        ];
    }

    private function subtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(
            fn (CartItem $item): float => (float) $item->unit_price_usd * $item->quantity,
        );
    }

    private function total(float $taxableBase, float $tax): float
    {
        if ($this->taxService->pricesIncludeGst()) {
            return round($taxableBase, 2);
        }

        return round($taxableBase + $tax, 2);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function isRural(array $address): bool
    {
        if (array_key_exists('is_rural', $address) && $address['is_rural'] !== null) {
            return filter_var($address['is_rural'], FILTER_VALIDATE_BOOLEAN);
        }

        $addressText = strtolower(implode(' ', array_filter([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['region'] ?? null,
        ])));

        return str_contains($addressText, 'rural')
            || preg_match('/\brd\s*\d+\b/i', $addressText) === 1;
    }

    private function currency(): string
    {
        return (string) config('store.currency', 'NZD');
    }

    private function formatMoney(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }
}
