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

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

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

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.storage_settings');
    }

    public function getTitle(): string
    {
        return __('admin.pages.storage_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin.storage.sections.status'))
                    ->schema([
                        Grid::make(5)->schema([
                            Placeholder::make('active_driver')
                                ->label(__('admin.storage.fields.active_driver'))
                                ->content(fn (): string => (string) ($this->data['default_driver'] ?? 'local')),
                            Placeholder::make('local_writable')
                                ->label(__('admin.storage.fields.local_writable'))
                                ->content(fn (): string => is_writable(storage_path('app/public')) ? __('admin.system.ok') : __('admin.system.not_configured')),
                            Placeholder::make('storage_link')
                                ->label(__('admin.storage.fields.storage_link'))
                                ->content(fn (): string => File::exists(public_path('storage')) ? __('admin.system.yes') : __('admin.system.no')),
                            Placeholder::make('azure_configured')
                                ->label(__('admin.storage.fields.azure_configured'))
                                ->content(fn (): string => $this->azureConfigured() ? __('admin.system.configured') : __('admin.system.not_configured')),
                            Placeholder::make('last_tested')
                                ->label(__('admin.storage.fields.last_tested'))
                                ->content(fn (): string => (string) ($this->data['last_tested_at'] ?: __('admin.storage.not_tested'))),
                        ]),
                        Placeholder::make('driver_switch_notice')
                            ->hiddenLabel()
                            ->content(__('admin.storage.driver_switch_notice')),
                        Grid::make(3)->schema([
                            Placeholder::make('local_last_test')
                                ->label(__('admin.storage.fields.local_last_test'))
                                ->content(fn (): string => $this->formatTestSummary('local')),
                            Placeholder::make('azure_last_test')
                                ->label(__('admin.storage.fields.azure_last_test'))
                                ->content(fn (): string => $this->formatTestSummary('azure')),
                            Placeholder::make('overall_last_test')
                                ->label(__('admin.storage.fields.overall_last_test'))
                                ->content(fn (): string => $this->formatTestSummary('overall')),
                        ]),
                    ]),
                Section::make(__('admin.storage.sections.driver'))
                    ->schema([
                        Select::make('default_driver')
                            ->label(__('admin.storage.fields.storage_driver'))
                            ->options([
                                'local' => __('admin.storage.drivers.local'),
                                'azure' => __('admin.storage.drivers.azure'),
                            ])
                            ->required()
                            ->live(),
                    ]),
                Section::make(__('admin.storage.sections.local'))
                    ->visible(fn (Get $get): bool => $get('default_driver') === 'local')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('local_disk')
                                ->label(__('admin.storage.fields.local_disk'))
                                ->options(['public' => 'public'])
                                ->default('public')
                                ->required(),
                            Placeholder::make('local_public_url')
                                ->label(__('admin.storage.fields.public_url_preview'))
                                ->content(fn (): string => StorageUrl::publicResolve('health-checks/example.txt', $this->data['local_disk'] ?? 'public') ?? '-'),
                            Placeholder::make('storage_link_status')
                                ->label(__('admin.storage.fields.storage_link_status'))
                                ->content(fn (): string => File::exists(public_path('storage')) ? __('admin.system.yes') : __('admin.system.no')),
                        ]),
                    ]),
                Section::make(__('admin.storage.sections.azure'))
                    ->visible(fn (Get $get): bool => $get('default_driver') === 'azure')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('azure_account_name')->label(__('admin.storage.fields.azure_account_name'))->maxLength(255),
                            TextInput::make('azure_account_key')
                                ->label(__('admin.storage.fields.azure_account_key'))
                                ->password()
                                ->revealable()
                                ->helperText(__('admin.storage.help.keep_secret')),
                            TextInput::make('azure_container')->label(__('admin.storage.fields.azure_container'))->maxLength(255),
                            TextInput::make('azure_url')->label(__('admin.storage.fields.azure_url'))->url()->maxLength(255),
                            Toggle::make('azure_use_sas_urls')->label(__('admin.storage.fields.azure_use_sas_urls')),
                            TextInput::make('azure_sas_ttl_minutes')->label(__('admin.storage.fields.azure_sas_ttl_minutes'))->numeric()->minValue(1),
                            Placeholder::make('azure_public_url')
                                ->label(__('admin.storage.fields.public_url_preview'))
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

    public function testLocal(StorageManagerService $storage, SettingsService $settings): void
    {
        $result = $storage->test($this->data['local_disk'] ?? 'public');
        $this->persistStorageTest($settings, 'local', $result->ok, $result->message);
        $this->notifyResult($result->ok, $result->message);
    }

    public function testAzure(StorageManagerService $storage, SettingsService $settings): void
    {
        $result = $storage->test('azure');
        $this->persistStorageTest($settings, 'azure', $result->ok, $result->message);
        $this->notifyResult($result->ok, $result->message);
    }

    public function testUpload(StorageManagerService $storage, SettingsService $settings): void
    {
        $result = $storage->test($this->data['default_driver'] ?? null);
        $scope = ($this->data['default_driver'] ?? 'local') === 'azure' ? 'azure' : 'local';
        $this->persistStorageTest($settings, $scope, $result->ok, $result->message);
        $this->notifyResult($result->ok, $result->message);
    }

    public function createStorageLink(): void
    {
        try {
            Artisan::call('storage:link');
            $this->notifyResult(true, __('admin.storage.messages.storage_link_created'));
        } catch (Throwable $throwable) {
            $this->notifyResult(false, $throwable->getMessage());
        }
    }

    public function clearSettingsCache(SettingsService $settings): void
    {
        $settings->forgetCache();
        $this->notifyResult(true, __('admin.storage.messages.cache_cleared'));
    }

    public function rollbackDriver(SettingsService $settings): void
    {
        $previous = $settings->string('storage.previous_driver', '');

        if ($previous === '') {
            $this->notifyResult(false, __('admin.storage.messages.no_previous_driver'));

            return;
        }

        $settings->set('storage.default_driver', $previous, ['type' => 'string']);
        $settings->warmCache();
        $this->form->fill($this->state($settings));
        $this->notifyResult(true, __('admin.storage.messages.driver_rolled_back', ['driver' => $previous]));
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
            Action::make('testLocal')->label(__('admin.storage.actions.test_local'))->action('testLocal'),
            Action::make('testAzure')->label(__('admin.storage.actions.test_azure'))->action('testAzure'),
            Action::make('testUpload')->label(__('admin.storage.actions.test_upload'))->action('testUpload'),
            Action::make('createStorageLink')->label(__('admin.storage.actions.create_storage_link'))->action('createStorageLink'),
            Action::make('clearSettingsCache')->label(__('admin.storage.actions.clear_settings_cache'))->action('clearSettingsCache'),
            Action::make('rollbackDriver')->label(__('admin.storage.actions.rollback_driver'))->action('rollbackDriver')->requiresConfirmation(),
            Action::make('migrateMedia')->label(__('admin.storage.actions.migrate_media'))->disabled(),
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
            'last_test_status' => $settings->string('storage.last_test_status', ''),
            'last_test_message' => $settings->string('storage.last_test_message', ''),
            'local_last_tested_at' => $settings->string('storage.local.last_tested_at', ''),
            'local_last_test_status' => $settings->string('storage.local.last_test_status', ''),
            'local_last_test_message' => $settings->string('storage.local.last_test_message', ''),
            'azure_last_tested_at' => $settings->string('storage.azure.last_tested_at', ''),
            'azure_last_test_status' => $settings->string('storage.azure.last_test_status', ''),
            'azure_last_test_message' => $settings->string('storage.azure.last_test_message', ''),
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
        $message = $this->safeStorageMessage($message);
        $this->lastTestResult = $message;
        Notification::make()
            ->title($message)
            ->{$ok ? 'success' : 'danger'}()
            ->send();
    }

    private function persistStorageTest(SettingsService $settings, string $scope, bool $ok, string $message): void
    {
        $safeMessage = $this->safeStorageMessage($message);
        $timestamp = now()->toISOString();
        $status = $ok ? 'ok' : 'error';

        $settings->setMany([
            "storage.{$scope}.last_tested_at" => ['value' => $timestamp, 'type' => 'string'],
            "storage.{$scope}.last_test_status" => ['value' => $status, 'type' => 'string'],
            "storage.{$scope}.last_test_message" => ['value' => $safeMessage, 'type' => 'string'],
            'storage.last_tested_at' => ['value' => $timestamp, 'type' => 'string'],
            'storage.last_test_status' => ['value' => $status, 'type' => 'string'],
            'storage.last_test_message' => ['value' => $safeMessage, 'type' => 'string'],
        ]);

        $settings->warmCache();
        $this->form->fill($this->state($settings));
    }

    private function formatTestSummary(string $scope): string
    {
        $prefix = $scope === 'overall' ? '' : "{$scope}_";
        $status = (string) ($this->data[$prefix.'last_test_status'] ?? '');
        $testedAt = (string) ($this->data[$prefix.'last_tested_at'] ?? '');
        $message = (string) ($this->data[$prefix.'last_test_message'] ?? '');

        if ($status === '' && $testedAt === '') {
            return __('admin.storage.not_tested');
        }

        return trim(collect([$status, $testedAt, $message])->filter()->implode(' | '));
    }

    private function safeStorageMessage(string $message): string
    {
        return str($message)
            ->replaceMatches('/[A-Za-z0-9+\/=]{32,}/', '[masked]')
            ->limit(500)
            ->toString();
    }
}
