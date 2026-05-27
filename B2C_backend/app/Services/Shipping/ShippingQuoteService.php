<?php

namespace App\Services\Shipping;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Settings\SettingsService;
use App\Services\Store\CartPricingService;
use App\Services\Store\TaxService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ShippingQuoteService
{
    public function __construct(
        private readonly NzPostClient $nzPostClient,
        private readonly TaxService $taxService,
        private readonly SettingsService $settings,
        private readonly CartPricingService $pricing,
    ) {}

    /**
     * @param  array<string, mixed>  $address
     * @return array{options: array<int, array<string, mixed>>, tax: array<string, mixed>, totals: array<string, mixed>}
     */
    public function quote(Cart $cart, array $address): array
    {
        $cart->loadMissing(['items.product', 'items.variant']);
        $this->guardStoreAndShippingEnabled();
        $this->validateAddress($address);

        $subtotal = $this->pricing->subtotal($cart);
        $rateSource = $this->settings->string('shipping.rate_source', 'auto');

        if ($rateSource === 'manual') {
            $options = $this->fallbackOptions($cart, $address);
        } elseif ($rateSource === 'nzpost') {
            $options = $this->nzPostOptions($cart, $address);
            if ($options === []) {
                throw ValidationException::withMessages([
                    'shipping' => [__('api.shipping.nzpost_unavailable')],
                ]);
            }
        } else {
            // auto: NZ Post priority, fallback if unavailable
            $options = $this->nzPostOptions($cart, $address);
            if ($options === []) {
                $options = $this->fallbackOptions($cart, $address);
            }
        }

        $options = $this->applyFreeShippingPolicy($options, $subtotal);

        $defaultOption = collect($options)->first(fn (array $option): bool => (bool) ($option['is_default'] ?? false))
            ?? $options[0]
            ?? null;

        $shipping = (float) ($defaultOption['amount'] ?? 0);
        $tax = $this->pricing->taxSnapshot($subtotal, $shipping);
        $totals = $this->totalsPayload($subtotal, $shipping, $tax);

        return [
            'options' => $options,
            'tax' => [
                'label' => $tax['label'],
                'rate' => $tax['rate'],
                'amount' => $this->formatMoney((float) $tax['amount']),
                'included' => $tax['included'],
            ],
            'totals' => $totals,
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
                'shipping_method_code' => [__('api.shipping.method_unavailable')],
            ]);
        }

        $subtotal = $this->pricing->subtotal($cart);
        $shipping = (float) $selected['amount'];
        $tax = $this->pricing->taxSnapshot($subtotal, $shipping);
        $totals = $this->totalsPayload($subtotal, $shipping, $tax);
        $taxPayload = [
            'label' => $tax['label'],
            'rate' => $tax['rate'],
            'amount' => $this->formatMoney((float) $tax['amount']),
            'included' => $tax['included'],
        ];

        return [
            'option' => $selected,
            'tax' => $taxPayload,
            'totals' => $totals,
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
                'tax' => $taxPayload,
                'totals' => $totals,
                'source' => (string) ($selected['source'] ?? 'fallback'),
            ],
        ];
    }

    /**
     * Estimate the standard non-rural fallback shipping amount when checkout has no address yet.
     *
     * @return array{
     *     amount: float|null,
     *     free_shipping_threshold: float|null,
     *     free_shipping_remaining: float,
     *     free_shipping_applied: bool,
     *     notice: string
     * }
     */
    public function estimateForCart(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'items.variant']);
        $subtotal = $this->pricing->subtotal($cart);
        $threshold = $this->freeShippingThreshold();
        $remaining = $threshold > 0 ? max(0.0, round($threshold - $subtotal, 2)) : 0.0;

        if ($cart->items->isEmpty()) {
            return [
                'amount' => 0.0,
                'free_shipping_threshold' => $threshold > 0 ? $threshold : null,
                'free_shipping_remaining' => $remaining,
                'free_shipping_applied' => false,
                'notice' => 'Shipping calculated at checkout.',
            ];
        }

        if (! $this->settings->boolean('feature.b2c_store_enabled', true)) {
            return [
                'amount' => null,
                'free_shipping_threshold' => $threshold > 0 ? $threshold : null,
                'free_shipping_remaining' => $remaining,
                'free_shipping_applied' => false,
                'notice' => __('api.errors.feature_disabled'),
            ];
        }

        if (! $this->settings->boolean('shipping.enabled', true)) {
            return [
                'amount' => null,
                'free_shipping_threshold' => $threshold > 0 ? $threshold : null,
                'free_shipping_remaining' => $remaining,
                'free_shipping_applied' => false,
                'notice' => __('api.shipping.disabled'),
            ];
        }

        if (! $this->settings->boolean('shipping.estimate_without_address', true)) {
            return [
                'amount' => null,
                'free_shipping_threshold' => $threshold > 0 ? $threshold : null,
                'free_shipping_remaining' => $remaining,
                'free_shipping_applied' => false,
                'notice' => 'Shipping calculated at checkout.',
            ];
        }

        $options = $this->applyFreeShippingPolicy($this->fallbackOptions($cart, [
            'line1' => null,
            'city' => $this->settings->string('shipping.origin_city', (string) config('store.shipping.origin.city', '')),
            'region' => null,
            'postcode' => $this->settings->string('shipping.origin_postcode', (string) config('store.shipping.origin.postcode', '')),
            'country' => 'NZ',
            'is_rural' => false,
        ]), $subtotal);

        $standard = collect($options)->first(fn (array $option): bool => (string) ($option['code'] ?? '') === 'standard')
            ?? collect($options)->first(fn (array $option): bool => (bool) ($option['is_default'] ?? false))
            ?? $options[0]
            ?? null;

        $amount = $standard !== null ? (float) ($standard['amount'] ?? 0) : null;

        return [
            'amount' => $amount,
            'free_shipping_threshold' => $threshold > 0 ? $threshold : null,
            'free_shipping_remaining' => $remaining,
            'free_shipping_applied' => (bool) ($standard['free_shipping_applied'] ?? false),
            'notice' => $amount === null ? 'Shipping calculated at checkout.' : __('api.shipping.standard_description'),
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function validateAddress(array $address): void
    {
        $country = strtoupper(trim((string) ($address['country'] ?? '')));
        $nzOnly = $this->settings->boolean('shipping.nz_only', true);

        if ($nzOnly && $country !== 'NZ') {
            throw ValidationException::withMessages([
                'shipping_country' => [__('api.shipping.nz_only')],
            ]);
        }

        if (blank($address['postcode'] ?? null)) {
            throw ValidationException::withMessages([
                'shipping_postal_code' => [__('api.shipping.postcode_required')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<int, array<string, mixed>>
     */
    private function nzPostOptions(Cart $cart, array $address): array
    {
        $originPostcode = $this->settings->string(
            'shipping.origin_postcode',
            (string) config('store.shipping.origin.postcode', ''),
        );
        $originCity = $this->settings->string(
            'shipping.origin_city',
            (string) config('store.shipping.origin.city', ''),
        );

        $payload = $this->nzPostClient->shippingOptions([
            'origin' => [
                'postcode' => $originPostcode,
                'city' => $originCity,
                'country' => config('store.shipping.origin.country', 'NZ'),
            ],
            'destination' => [
                'postcode' => $address['postcode'] ?? null,
                'city' => $address['city'] ?? null,
                'country' => strtoupper((string) ($address['country'] ?? 'NZ')),
            ],
            'items' => $cart->items->map(fn (CartItem $item): array => [
                'quantity' => $item->quantity,
                'weight_grams' => $item->variant?->weight_grams ?? 500,
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
                    'label' => (string) (data_get($service, 'label') ?? data_get($service, 'name') ?? __('api.shipping.standard_label')),
                    'description' => (string) (data_get($service, 'description') ?? __('api.shipping.standard_description')),
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
        $ruralSurcharge = $this->isRural($address)
            ? $this->settingFloat(['shipping.rural_surcharge'], (float) config('store.shipping.rural_surcharge', 5))
            : 0.0;

        $standardBase = $this->settingFloat(
            ['shipping.standard_rate', 'shipping.fallback_standard_amount'],
            (float) config('store.shipping.standard_rate', 8),
        );

        $expressBase = $this->settingFloat(
            ['shipping.express_rate', 'shipping.fallback_express_amount'],
            (float) config('store.shipping.express_rate', 14),
        );

        return [
            [
                'code' => 'standard',
                'label' => __('api.shipping.standard_label'),
                'description' => $ruralSurcharge > 0
                    ? __('api.shipping.standard_description_rural')
                    : __('api.shipping.standard_description'),
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
                'label' => __('api.shipping.express_label'),
                'description' => $ruralSurcharge > 0
                    ? __('api.shipping.express_description_rural')
                    : __('api.shipping.express_description'),
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

    private function guardStoreAndShippingEnabled(): void
    {
        if (! $this->settings->boolean('feature.b2c_store_enabled', true)) {
            throw ValidationException::withMessages([
                'shipping' => [__('api.errors.feature_disabled')],
            ]);
        }

        if (! $this->settings->boolean('shipping.enabled', true)) {
            throw ValidationException::withMessages([
                'shipping' => [__('api.shipping.disabled')],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<int, array<string, mixed>>
     */
    private function applyFreeShippingPolicy(array $options, float $subtotal): array
    {
        $threshold = $this->freeShippingThreshold();

        if ($threshold <= 0 || $subtotal + 0.0001 < $threshold) {
            return $options;
        }

        $includeExpress = $this->settings->boolean('shipping.free_shipping_includes_express', false);

        return collect($options)
            ->map(function (array $option) use ($includeExpress): array {
                if (! $this->freeShippingEligible($option, $includeExpress)) {
                    return $option;
                }

                $amount = (float) ($option['amount'] ?? 0);
                $meta = is_array($option['meta'] ?? null) ? $option['meta'] : [];

                $option['original_amount'] = $option['original_amount'] ?? $this->formatMoney($amount);
                $option['amount'] = $this->formatMoney(0.0);
                $option['free_shipping_applied'] = true;
                $option['meta'] = array_merge($meta, [
                    'free_shipping_applied' => true,
                ]);

                return $option;
            })
            ->values()
            ->all();
    }

    private function freeShippingEligible(array $option, bool $includeExpress): bool
    {
        if (! $includeExpress && $this->isExpressLike($option)) {
            return false;
        }

        return (bool) ($option['is_default'] ?? false) || $this->isStandardLike($option);
    }

    private function isStandardLike(array $option): bool
    {
        $text = strtolower(implode(' ', array_filter([
            $option['code'] ?? null,
            $option['service_code'] ?? null,
            $option['label'] ?? null,
            $option['description'] ?? null,
        ])));

        return str_contains($text, 'standard')
            || str_contains($text, 'economy')
            || str_contains($text, 'courier');
    }

    private function isExpressLike(array $option): bool
    {
        $text = strtolower(implode(' ', array_filter([
            $option['code'] ?? null,
            $option['service_code'] ?? null,
            $option['label'] ?? null,
            $option['description'] ?? null,
        ])));

        return str_contains($text, 'express')
            || str_contains($text, 'priority')
            || str_contains($text, 'overnight');
    }

    private function freeShippingThreshold(): float
    {
        $threshold = $this->settingFloat(
            ['shipping.free_shipping_threshold'],
            (float) config('store.shipping.free_shipping_threshold', 200),
        );

        return $threshold > 0 ? $threshold : 0.0;
    }

    /**
     * @param  array{label: string, rate: float, amount: float, included: bool}  $tax
     * @return array{subtotal: string, shipping: string, tax: string, total: string, currency: string}
     */
    private function totalsPayload(float $subtotal, float $shipping, array $tax): array
    {
        return [
            'subtotal' => $this->formatMoney($subtotal),
            'shipping' => $this->formatMoney($shipping),
            'tax' => $this->formatMoney((float) $tax['amount']),
            'total' => $this->formatMoney($this->pricing->total($subtotal, $shipping)),
            'currency' => $this->currency(),
        ];
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function settingFloat(array $keys, float $default): float
    {
        foreach ($keys as $key) {
            $value = $this->settings->get($key);

            if ($value !== null && is_numeric($value)) {
                return (float) $value;
            }
        }

        return $default;
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
        return $this->pricing->formatMoney($amount);
    }
}
