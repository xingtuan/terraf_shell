<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use App\Services\Storage\StorageManagerService;
use App\Support\StorageUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class StorageSettings extends Page
{
    public ?array $data = [];

    public ?string $lastTestResult = null;

    protected static ?string $title = 'Storage Settings';

    protected static ?string $navigationLabel = 'Storage Settings';

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'storage-settings';

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($this->state($settings));
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Status')
                    ->schema([
                        Grid::make(5)->schema([
                            Placeholder::make('active_driver')
                                ->label('Active driver')
                                ->content(fn (): string => (string) ($this->data['default_driver'] ?? 'local')),
                            Placeholder::make('local_writable')
                                ->label('Local writable')
                                ->content(fn (): string => is_writable(storage_path('app/public')) ? 'OK' : 'Not writable'),
                            Placeholder::make('storage_link')
                                ->label('Storage link')
                                ->content(fn (): string => File::exists(public_path('storage')) ? 'Exists' : 'Missing'),
                            Placeholder::make('azure_configured')
                                ->label('Azure configured')
                                ->content(fn (): string => $this->azureConfigured() ? 'Configured' : 'Not configured'),
                            Placeholder::make('last_tested')
                                ->label('Last tested')
                                ->content(fn (): string => (string) ($this->data['last_tested_at'] ?? 'Not tested')),
                        ]),
                        Placeholder::make('driver_switch_notice')
                            ->hiddenLabel()
                            ->content('Switching the active driver affects new uploads only. Existing media remains on the disk recorded for that file.'),
                    ]),
                Section::make('Driver')
                    ->schema([
                        Select::make('default_driver')
                            ->label('Storage driver')
                            ->options([
                                'local' => 'Local',
                                'azure' => 'Azure Blob Storage',
                            ])
                            ->required()
                            ->live(),
                    ]),
                Section::make('Local')
                    ->visible(fn (Get $get): bool => $get('default_driver') === 'local')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('local_disk')
                                ->label('Local disk')
                                ->options(['public' => 'public'])
                                ->default('public')
                                ->required(),
                            Placeholder::make('local_public_url')
                                ->label('Public URL preview')
                                ->content(fn (): string => StorageUrl::publicResolve('health-checks/example.txt', $this->data['local_disk'] ?? 'public') ?? '-'),
                            Placeholder::make('storage_link_status')
                                ->label('Storage link status')
                                ->content(fn (): string => File::exists(public_path('storage')) ? 'Exists' : 'Missing'),
                        ]),
                    ]),
                Section::make('Azure')
                    ->visible(fn (Get $get): bool => $get('default_driver') === 'azure')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('azure_account_name')->label('Account name')->maxLength(255),
                            TextInput::make('azure_account_key')
                                ->label('Account key')
                                ->password()
                                ->revealable()
                                ->helperText('Leave masked or empty to keep the current key.'),
                            TextInput::make('azure_container')->label('Container')->maxLength(255),
                            TextInput::make('azure_url')->label('Storage URL')->url()->maxLength(255),
                            Toggle::make('azure_use_sas_urls')->label('Use SAS URLs'),
                            TextInput::make('azure_sas_ttl_minutes')->label('SAS TTL minutes')->numeric()->minValue(1),
                            Placeholder::make('azure_public_url')
                                ->label('Public URL preview')
                                ->content(fn (): string => StorageUrl::publicResolve('health-checks/example.txt', 'azure') ?? '-')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    public function save(SettingsService $settings): void
    {
        $state = $this->form->getState();
        $currentDriver = $settings->string('storage.default_driver', (string) config('community.uploads.disk', 'local'));

        if (($state['default_driver'] ?? null) !== $currentDriver) {
            $settings->set('storage.previous_driver', $currentDriver, ['type' => 'string']);
        }

        $payload = [
            'storage.default_driver' => ['value' => $state['default_driver'] ?? 'local', 'type' => 'string'],
            'storage.local.disk' => ['value' => $state['local_disk'] ?? 'public', 'type' => 'string'],
            'storage.azure.account_name' => ['value' => $state['azure_account_name'] ?? null, 'type' => 'string'],
            'storage.azure.container' => ['value' => $state['azure_container'] ?? 'uploads', 'type' => 'string'],
            'storage.azure.url' => ['value' => $state['azure_url'] ?? null, 'type' => 'string'],
            'storage.azure.use_sas_urls' => ['value' => (bool) ($state['azure_use_sas_urls'] ?? true), 'type' => 'boolean'],
            'storage.azure.sas_ttl_minutes' => ['value' => (int) ($state['azure_sas_ttl_minutes'] ?? 10080), 'type' => 'integer'],
        ];

        if (($state['azure_account_key'] ?? null) !== SettingsService::SECRET_MASK && filled($state['azure_account_key'] ?? null)) {
            $payload['storage.azure.account_key'] = [
                'value' => $state['azure_account_key'],
                'type' => 'string',
                'is_secret' => true,
            ];
        }

        $settings->setMany($payload);
        $settings->warmCache();
        $this->form->fill($this->state($settings));

        Notification::make()->title(__('admin.notifications.saved'))->success()->send();
    }

    public function testLocal(StorageManagerService $storage): void
    {
        $result = $storage->test($this->data['local_disk'] ?? 'public');
        $this->notifyResult($result->ok, $result->message);
    }

    public function testAzure(StorageManagerService $storage): void
    {
        $result = $storage->test('azure');
        $this->notifyResult($result->ok, $result->message);
    }

    public function testUpload(StorageManagerService $storage): void
    {
        $result = $storage->test($this->data['default_driver'] ?? null);
        $this->notifyResult($result->ok, $result->message);
    }

    public function createStorageLink(): void
    {
        try {
            Artisan::call('storage:link');
            $this->notifyResult(true, 'Storage link created or already exists.');
        } catch (Throwable $throwable) {
            $this->notifyResult(false, $throwable->getMessage());
        }
    }

    public function clearSettingsCache(SettingsService $settings): void
    {
        $settings->forgetCache();
        $this->notifyResult(true, 'Runtime settings cache cleared.');
    }

    public function rollbackDriver(SettingsService $settings): void
    {
        $previous = $settings->string('storage.previous_driver', '');

        if ($previous === '') {
            $this->notifyResult(false, 'No previous driver is recorded.');

            return;
        }

        $settings->set('storage.default_driver', $previous, ['type' => 'string']);
        $settings->warmCache();
        $this->form->fill($this->state($settings));
        $this->notifyResult(true, "Rolled back to {$previous}.");
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make($this->getFormActions())]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('admin.actions.save_settings'))->submit('save')->requiresConfirmation(),
            Action::make('testLocal')->label('Test local storage')->action('testLocal'),
            Action::make('testAzure')->label('Test Azure connection')->action('testAzure'),
            Action::make('testUpload')->label('Test upload')->action('testUpload'),
            Action::make('createStorageLink')->label('Create storage link')->action('createStorageLink'),
            Action::make('clearSettingsCache')->label('Clear settings cache')->action('clearSettingsCache'),
            Action::make('rollbackDriver')->label('Roll back driver')->action('rollbackDriver')->requiresConfirmation(),
            Action::make('migrateMedia')->label('Migrate media between disks')->disabled(),
        ];
    }

    private function state(SettingsService $settings): array
    {
        return [
            'default_driver' => $settings->string('storage.default_driver', config('community.uploads.disk') === 'azure' ? 'azure' : 'local'),
            'local_disk' => $settings->string('storage.local.disk', 'public'),
            'azure_account_name' => $settings->string('storage.azure.account_name', (string) config('filesystems.disks.azure.name', '')),
            'azure_account_key' => $settings->secret('storage.azure.account_key') ? SettingsService::SECRET_MASK : null,
            'azure_container' => $settings->string('storage.azure.container', (string) config('filesystems.disks.azure.container', 'uploads')),
            'azure_url' => $settings->string('storage.azure.url', (string) config('filesystems.disks.azure.storage_url', '')),
            'azure_use_sas_urls' => $settings->boolean('storage.azure.use_sas_urls', (bool) config('community.uploads.azure.use_sas_urls', true)),
            'azure_sas_ttl_minutes' => $settings->integer('storage.azure.sas_ttl_minutes', (int) config('community.uploads.azure.signed_url_ttl_minutes', 10080)),
            'last_tested_at' => $settings->string('storage.last_tested_at', ''),
        ];
    }

    private function azureConfigured(): bool
    {
        return filled($this->data['azure_account_name'] ?? null)
            && (($this->data['azure_account_key'] ?? null) === SettingsService::SECRET_MASK || filled($this->data['azure_account_key'] ?? null))
            && filled($this->data['azure_container'] ?? null);
    }

    private function notifyResult(bool $ok, string $message): void
    {
        $this->lastTestResult = $message;
        Notification::make()
            ->title($message)
            ->{$ok ? 'success' : 'danger'}()
            ->send();
    }
}
