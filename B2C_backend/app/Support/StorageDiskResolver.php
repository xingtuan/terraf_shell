<?php

namespace App\Support;

final class StorageDiskResolver
{
    /**
     * @param  array<string, array<string, mixed>>  $disks
     */
    public function __construct(
        private readonly array $disks,
        private readonly bool $hasS3Adapter,
        private readonly bool $hasAzureAdapter,
    ) {}

    /**
     * Resolve the first usable disk from the preferred disk and fallback chain.
     *
     * @param  list<string>  $fallbackDisks
     */
    public function resolve(string $preferredDisk, array $fallbackDisks = []): string
    {
        $candidates = [];

        foreach ([$preferredDisk, ...$fallbackDisks, 'azure', 'public', 'local'] as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '' || in_array($candidate, $candidates, true)) {
                continue;
            }

            $candidates[] = $candidate;
        }

        foreach ($candidates as $candidate) {
            if ($this->isUsable($candidate)) {
                return $candidate;
            }
        }

        foreach (array_keys($this->disks) as $candidate) {
            if ($this->isUsable((string) $candidate)) {
                return (string) $candidate;
            }
        }

        return 'local';
    }

    public function isUsable(string $disk): bool
    {
        $config = $this->disks[$disk] ?? null;

        if (! is_array($config)) {
            return false;
        }

        return match ((string) ($config['driver'] ?? '')) {
            'local' => true,
            'azure' => $this->hasAzureAdapter && $this->hasAzureConfiguration($config),
            's3' => $this->hasS3Adapter && $this->hasS3Configuration($config),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function hasAzureConfiguration(array $config): bool
    {
        return $this->filled($config['name'] ?? null)
            && $this->filled($config['key'] ?? null)
            && $this->filled($config['container'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function hasS3Configuration(array $config): bool
    {
        return $this->filled($config['bucket'] ?? null);
    }

    private function filled(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }
}
