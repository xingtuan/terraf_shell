<?php

namespace App\Services\Shipping;

use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class NzPostClient
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->boolean('nzpost.enabled', (bool) config('store.nzpost.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && filled($this->baseUrl())
            && (
                filled($this->apiKey())
                || (filled($this->clientId()) && filled($this->clientSecret()))
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

    private function baseUrl(): string
    {
        return $this->settings->string(
            'nzpost.base_url',
            (string) config('store.nzpost.base_url', 'https://api.nzpost.co.nz'),
        );
    }

    private function apiKey(): ?string
    {
        $fromSettings = $this->settings->secret('nzpost.api_key');
        if (filled($fromSettings)) {
            return $fromSettings;
        }

        $fromConfig = config('store.nzpost.api_key');

        return filled($fromConfig) ? (string) $fromConfig : null;
    }

    private function clientId(): ?string
    {
        $fromSettings = $this->settings->secret('nzpost.client_id');
        if (filled($fromSettings)) {
            return $fromSettings;
        }

        $fromConfig = config('store.nzpost.client_id');

        return filled($fromConfig) ? (string) $fromConfig : null;
    }

    private function clientSecret(): ?string
    {
        $fromSettings = $this->settings->secret('nzpost.client_secret');
        if (filled($fromSettings)) {
            return $fromSettings;
        }

        $fromConfig = config('store.nzpost.client_secret');

        return filled($fromConfig) ? (string) $fromConfig : null;
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim($this->baseUrl(), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(8)
            ->retry(1, 200);

        $apiKey = $this->apiKey();

        if (filled($apiKey)) {
            $request = $request->withHeaders([
                'client_id' => (string) $apiKey,
                'x-api-key' => (string) $apiKey,
            ]);
        }

        $clientId = $this->clientId();
        $clientSecret = $this->clientSecret();

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
