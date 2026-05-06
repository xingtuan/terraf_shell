<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class NzPostClient
{
    public function isEnabled(): bool
    {
        return (bool) config('store.nzpost.enabled', false);
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && filled(config('store.nzpost.base_url'))
            && (
                filled(config('store.nzpost.api_key'))
                || (filled(config('store.nzpost.client_id')) && filled(config('store.nzpost.client_secret')))
            );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchAddresses(string $query): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->safeGet('/parceladdress/2.0/domestic/addresses', [
            'q' => $query,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function addressDetails(string $id): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->safeGet('/parceladdress/2.0/domestic/addresses/'.rawurlencode($id));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function shippingOptions(array $payload): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return $this->safePost('/shippingoptions/2.0/domestic', $payload);
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('store.nzpost.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(8)
            ->retry(1, 200);

        $apiKey = config('store.nzpost.api_key');

        if (filled($apiKey)) {
            $request = $request->withHeaders([
                'client_id' => (string) $apiKey,
                'x-api-key' => (string) $apiKey,
            ]);
        }

        $clientId = config('store.nzpost.client_id');
        $clientSecret = config('store.nzpost.client_secret');

        if (filled($clientId) && filled($clientSecret)) {
            $request = $request->withBasicAuth((string) $clientId, (string) $clientSecret);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function safeGet(string $path, array $query = []): ?array
    {
        try {
            $response = $this->request()->get($path, $query);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            return is_array($json) ? Arr::wrap($json) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function safePost(string $path, array $payload): ?array
    {
        try {
            $response = $this->request()->post($path, $payload);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            return is_array($json) ? Arr::wrap($json) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
