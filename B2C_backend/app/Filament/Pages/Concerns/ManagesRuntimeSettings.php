<?php

namespace App\Filament\Pages\Concerns;

use App\Services\Settings\SettingsService;
use Filament\Notifications\Notification;

trait ManagesRuntimeSettings
{
    public ?array $data = [];

    public function save(SettingsService $settings): void
    {
        $payload = [];

        foreach ($this->settingMap() as $field => $meta) {
            if (($meta['is_secret'] ?? false) && (
                ! array_key_exists($field, $this->data ?? [])
                || ($this->data[$field] ?? null) === SettingsService::SECRET_MASK
                || blank($this->data[$field] ?? null)
            )) {
                continue;
            }

            $payload[$meta['key']] = array_merge($meta, [
                'value' => $this->data[$field] ?? null,
            ]);
        }

        $settings->setMany($payload);
        $settings->warmCache();

        $this->form->fill($this->formState($settings));

        Notification::make()
            ->title(__('admin.notifications.saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    abstract protected function settingMap(): array;

    protected function formState(SettingsService $settings): array
    {
        $state = [];

        foreach ($this->settingMap() as $field => $meta) {
            $state[$field] = $settings->get($meta['key'], $meta['default'] ?? null);
        }

        return $state;
    }
}
