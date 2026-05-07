<?php

namespace App\Services\Settings;

use App\Models\AdminActionLog;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SettingsService
{
    public const CACHE_KEY = 'app_settings.all';

    public const SECRET_MASK = '********';

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->all()->get($key);

        if (! is_array($setting)) {
            return $default;
        }

        return $this->castValue($setting, $default);
    }

    public function set(string $key, mixed $value, array $meta = []): void
    {
        if (! $this->tableExists()) {
            return;
        }

        [$group, $settingKey] = $this->splitKey($key);
        $existing = AppSetting::query()
            ->where('group', $group)
            ->where('key', $settingKey)
            ->first();

        if (($value === self::SECRET_MASK || $value === '') && ($meta['is_secret'] ?? $existing?->is_secret)) {
            return;
        }

        $type = (string) ($meta['type'] ?? $existing?->type ?? $this->inferType($value));
        $isSecret = (bool) ($meta['is_secret'] ?? $existing?->is_secret ?? $this->looksSecret($key));
        $isEncrypted = (bool) ($meta['is_encrypted'] ?? $existing?->is_encrypted ?? $isSecret);

        $payload = [
            'value' => $this->serializeValue($value, $type, $isEncrypted),
            'type' => $type,
            'is_secret' => $isSecret,
            'is_encrypted' => $isEncrypted,
            'description' => $meta['description'] ?? $existing?->description,
            'options' => $meta['options'] ?? $existing?->options,
            'validation_rules' => $meta['validation_rules'] ?? $existing?->validation_rules,
            'is_public' => (bool) ($meta['is_public'] ?? $existing?->is_public ?? false),
            'updated_by' => $this->updatedBy($meta)?->id,
        ];

        AppSetting::query()->updateOrCreate([
            'group' => $group,
            'key' => $settingKey,
        ], $payload);

        $this->forgetCache();
        $this->logChange($key, $value, $isSecret, $meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        return $this->all()
            ->filter(fn (array $setting): bool => $setting['group'] === $group)
            ->mapWithKeys(fn (array $setting, string $key): array => [
                Str::after($key, $group.'.') => $this->castValue($setting),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings): void
    {
        DB::transaction(function () use ($settings): void {
            foreach ($settings as $key => $value) {
                $meta = [];

                if (is_array($value) && array_key_exists('value', $value)) {
                    $meta = Arr::except($value, ['value']);
                    $value = $value['value'];
                }

                $this->set((string) $key, $value, $meta);
            }
        });
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function secret(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function forgetCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            //
        }
    }

    public function warmCache(): void
    {
        $this->forgetCache();
        $this->all();
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function all(): Collection
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn (): Collection => $this->loadSettings());
        } catch (Throwable) {
            return $this->loadSettings();
        }
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function loadSettings(): Collection
    {
        if (! $this->tableExists()) {
            return collect();
        }

        try {
            return AppSetting::query()
                ->get()
                ->mapWithKeys(fn (AppSetting $setting): array => [
                    $setting->fullKey() => [
                        'group' => $setting->group,
                        'key' => $setting->key,
                        'value' => $setting->getRawOriginal('value'),
                        'type' => $setting->type,
                        'is_secret' => (bool) $setting->is_secret,
                        'is_encrypted' => (bool) $setting->is_encrypted,
                        'is_public' => (bool) $setting->is_public,
                    ],
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitKey(string $key): array
    {
        $group = Str::before($key, '.');
        $settingKey = Str::after($key, '.');

        if ($group === $key || $settingKey === '') {
            return ['general', $key];
        }

        return [$group, $settingKey];
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }

    private function serializeValue(mixed $value, string $type, bool $encrypt): ?string
    {
        if ($value === null) {
            return null;
        }

        $serialized = match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => (string) (int) $value,
            'float' => (string) (float) $value,
            'json', 'array' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };

        return $encrypt ? Crypt::encryptString($serialized) : $serialized;
    }

    /**
     * @param  array<string, mixed>  $setting
     */
    private function castValue(array $setting, mixed $default = null): mixed
    {
        $value = $setting['value'] ?? null;

        if ($value === null) {
            return $default;
        }

        if ($setting['is_encrypted'] ?? false) {
            try {
                $value = Crypt::decryptString((string) $value);
            } catch (Throwable) {
                return $default;
            }
        }

        return match ((string) ($setting['type'] ?? 'string')) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode((string) $value, true) ?? [],
            default => (string) $value,
        };
    }

    private function looksSecret(string $key): bool
    {
        return Str::contains(Str::lower($key), [
            'password',
            'secret',
            'token',
            'api_key',
            'account_key',
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function updatedBy(array $meta): ?User
    {
        $user = $meta['updated_by'] ?? Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function logChange(string $key, mixed $value, bool $isSecret, array $meta): void
    {
        $actor = $this->updatedBy($meta);

        if (! $actor instanceof User || ! Schema::hasTable('admin_action_logs')) {
            return;
        }

        try {
            AdminActionLog::query()->create([
                'actor_user_id' => $actor->id,
                'action' => 'setting.updated',
                'description' => "Updated runtime setting {$key}.",
                'metadata' => [
                    'key' => $key,
                    'value' => $isSecret ? '[masked]' : $value,
                ],
            ]);
        } catch (Throwable) {
            //
        }
    }
}
