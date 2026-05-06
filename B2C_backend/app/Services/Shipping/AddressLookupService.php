<?php

namespace App\Services\Shipping;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AddressLookupService
{
    public function __construct(
        private readonly NzPostClient $nzPostClient,
    ) {}

    /**
     * @return array{items: array<int, array<string, mixed>>, unavailable: bool, source: string}
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [
                'items' => [],
                'unavailable' => false,
                'source' => 'local',
            ];
        }

        $payload = $this->nzPostClient->searchAddresses($query);

        if ($payload !== null) {
            return [
                'items' => $this->normalizeSearchResults($payload),
                'unavailable' => false,
                'source' => 'nzpost',
            ];
        }

        return [
            'items' => $this->fallbackSearchResults($query),
            'unavailable' => true,
            'source' => 'fallback',
        ];
    }

    /**
     * @return array{address: array<string, mixed>|null, unavailable: bool, source: string}
     */
    public function details(string $id): array
    {
        $id = trim($id);

        if ($id === '') {
            return [
                'address' => null,
                'unavailable' => true,
                'source' => 'fallback',
            ];
        }

        $payload = $this->nzPostClient->addressDetails($id);

        if ($payload !== null) {
            return [
                'address' => $this->normalizeAddress($payload),
                'unavailable' => false,
                'source' => 'nzpost',
            ];
        }

        return [
            'address' => $this->fallbackAddress($id),
            'unavailable' => true,
            'source' => 'fallback',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSearchResults(array $payload): array
    {
        $items = data_get($payload, 'addresses')
            ?? data_get($payload, 'results')
            ?? data_get($payload, 'items')
            ?? data_get($payload, 'data')
            ?? [];

        return collect(Arr::wrap($items))
            ->map(function (mixed $item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $id = data_get($item, 'id')
                    ?? data_get($item, 'address_id')
                    ?? data_get($item, 'dpid')
                    ?? data_get($item, 'value');
                $label = data_get($item, 'label')
                    ?? data_get($item, 'full_address')
                    ?? data_get($item, 'address')
                    ?? data_get($item, 'display');

                if (! filled($id) || ! filled($label)) {
                    return null;
                }

                return [
                    'id' => (string) $id,
                    'label' => (string) $label,
                    'postcode' => data_get($item, 'postcode') ?? data_get($item, 'postal_code'),
                    'city' => data_get($item, 'city') ?? data_get($item, 'town'),
                    'is_rural' => $this->toNullableBool(data_get($item, 'rural') ?? data_get($item, 'is_rural')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $payload): array
    {
        $address = data_get($payload, 'address');
        $source = is_array($address) ? $address : $payload;

        $line1 = data_get($source, 'address_line1')
            ?? data_get($source, 'line1')
            ?? data_get($source, 'delivery_address_1')
            ?? data_get($source, 'street');
        $line2 = data_get($source, 'address_line2')
            ?? data_get($source, 'line2')
            ?? data_get($source, 'suburb')
            ?? data_get($source, 'delivery_address_2');

        return [
            'line1' => $line1 ? (string) $line1 : '',
            'line2' => $line2 ? (string) $line2 : null,
            'suburb' => data_get($source, 'suburb'),
            'city' => (string) (
                data_get($source, 'city')
                ?? data_get($source, 'town')
                ?? data_get($source, 'mailtown')
                ?? ''
            ),
            'region' => data_get($source, 'region') ?? data_get($source, 'province'),
            'postcode' => (string) (
                data_get($source, 'postcode')
                ?? data_get($source, 'postal_code')
                ?? ''
            ),
            'country' => 'NZ',
            'is_rural' => $this->toNullableBool(data_get($source, 'rural') ?? data_get($source, 'is_rural')),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackSearchResults(string $query): array
    {
        return collect($this->fallbackAddresses())
            ->filter(fn (array $address): bool => Str::contains(
                Str::lower((string) $address['label']),
                Str::lower($query),
            ))
            ->map(fn (array $address): array => Arr::only($address, ['id', 'label', 'postcode', 'city', 'is_rural']))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fallbackAddress(string $id): ?array
    {
        $address = collect($this->fallbackAddresses())
            ->first(fn (array $item): bool => (string) $item['id'] === $id);

        if (! is_array($address)) {
            return null;
        }

        return Arr::except($address, ['id', 'label']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fallbackAddresses(): array
    {
        return [
            [
                'id' => 'mock-nz-auckland-1010',
                'label' => 'Commercial Bay, Auckland Central, Auckland 1010',
                'line1' => '7 Queen Street',
                'line2' => 'Auckland Central',
                'suburb' => 'Auckland Central',
                'city' => 'Auckland',
                'region' => 'Auckland',
                'postcode' => '1010',
                'country' => 'NZ',
                'is_rural' => false,
            ],
            [
                'id' => 'mock-nz-wellington-6011',
                'label' => 'Lambton Quay, Wellington Central, Wellington 6011',
                'line1' => '100 Lambton Quay',
                'line2' => 'Wellington Central',
                'suburb' => 'Wellington Central',
                'city' => 'Wellington',
                'region' => 'Wellington',
                'postcode' => '6011',
                'country' => 'NZ',
                'is_rural' => false,
            ],
            [
                'id' => 'mock-nz-rural-3288',
                'label' => 'RD 2, Matamata, Waikato 3288',
                'line1' => '125 Rural Delivery Road',
                'line2' => 'RD 2',
                'suburb' => null,
                'city' => 'Matamata',
                'region' => 'Waikato',
                'postcode' => '3288',
                'country' => 'NZ',
                'is_rural' => true,
            ],
        ];
    }

    private function toNullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
