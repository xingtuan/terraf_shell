<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\AddressLookupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NzPostSettings extends Page
{
    use ManagesRuntimeSettings {
        save as saveRuntimeSettings;
    }

    protected static ?string $title = 'NZ Post Settings';

    protected static ?string $navigationLabel = 'NZ Post Settings';

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'nz-post-settings';

    public function mount(SettingsService $settings): void
    {
        $state = $this->formState($settings);
        $state['api_key'] = $settings->secret('nzpost.api_key') ? SettingsService::SECRET_MASK : null;
        $state['client_secret'] = $settings->secret('nzpost.client_secret') ? SettingsService::SECRET_MASK : null;
        $state['test_query'] = 'Auckland';
        $this->form->fill($state);
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('NZ Post API')->schema([
                Placeholder::make('configured')
                    ->label('Configured status')
                    ->content(fn (): string => filled($this->data['api_key'] ?? null) || filled($this->data['client_secret'] ?? null) ? 'Configured' : 'Not configured'),
                Grid::make(2)->schema([
                    Toggle::make('enabled')->label('Enabled'),
                    TextInput::make('base_url')->label('API base URL')->url(),
                    TextInput::make('client_id')->label('Client ID / API key ID'),
                    TextInput::make('client_secret')->label('Client secret')->password()->revealable()->helperText('Leave masked or empty to keep the current value.'),
                    TextInput::make('api_key')->label('API key')->password()->revealable()->helperText('Leave masked or empty to keep the current value.'),
                    TextInput::make('sender_postcode')->label('Sender postcode'),
                    TextInput::make('test_query')->label('Test lookup query'),
                ]),
            ]),
        ]);
    }

    public function save(SettingsService $settings): void
    {
        foreach (['api_key', 'client_secret'] as $secret) {
            if (($this->data[$secret] ?? null) === SettingsService::SECRET_MASK || blank($this->data[$secret] ?? null)) {
                unset($this->data[$secret]);
            }
        }

        $this->saveRuntimeSettings($settings);
    }

    public function testLookup(AddressLookupService $lookupService): void
    {
        $query = trim((string) ($this->data['test_query'] ?? ''));
        $result = $query !== '' ? $lookupService->search($query) : ['items' => []];
        $count = count($result['items'] ?? []);

        Notification::make()
            ->title("Address lookup returned {$count} result(s).")
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    Action::make('save')->label(__('admin.actions.save_settings'))->submit('save')->requiresConfirmation(),
                    Action::make('testLookup')->label('Test lookup')->action('testLookup'),
                ])]),
        ]);
    }

    protected function settingMap(): array
    {
        return [
            'enabled' => ['key' => 'nzpost.enabled', 'type' => 'boolean', 'default' => config('store.nzpost.enabled', false)],
            'base_url' => ['key' => 'nzpost.base_url', 'type' => 'string', 'default' => config('store.nzpost.base_url', 'https://api.nzpost.co.nz')],
            'client_id' => ['key' => 'nzpost.client_id', 'type' => 'string', 'default' => config('store.nzpost.client_id')],
            'client_secret' => ['key' => 'nzpost.client_secret', 'type' => 'string', 'is_secret' => true, 'default' => null],
            'api_key' => ['key' => 'nzpost.api_key', 'type' => 'string', 'is_secret' => true, 'default' => null],
            'sender_postcode' => ['key' => 'nzpost.sender_postcode', 'type' => 'string', 'default' => config('store.shipping.origin.postcode')],
        ];
    }
}
