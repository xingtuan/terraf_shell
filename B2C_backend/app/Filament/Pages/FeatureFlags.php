<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureFlags extends Page
{
    use ManagesRuntimeSettings;

    protected static ?string $title = 'Feature Flags';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'feature-flags';

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($this->formState($settings));
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Public behavior')->schema([
                Grid::make(2)->schema([
                    Toggle::make('b2c_store_enabled')->label('B2C store enabled'),
                    Toggle::make('b2b_inquiry_enabled')->label('B2B inquiry enabled'),
                    Toggle::make('community_enabled')->label('Community enabled'),
                    Toggle::make('funding_links_enabled')->label('Funding links enabled'),
                    Toggle::make('guest_checkout_enabled')->label('Guest checkout enabled'),
                    Toggle::make('email_sending_enabled')->label('Email sending enabled'),
                    Toggle::make('maintenance_notice_enabled')->label('Maintenance notice enabled'),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('admin.actions.save_settings'))->submit('save')->requiresConfirmation()])]),
        ]);
    }

    protected function settingMap(): array
    {
        return [
            'b2c_store_enabled' => ['key' => 'feature.b2c_store_enabled', 'type' => 'boolean', 'default' => true],
            'b2b_inquiry_enabled' => ['key' => 'feature.b2b_inquiry_enabled', 'type' => 'boolean', 'default' => true],
            'community_enabled' => ['key' => 'feature.community_enabled', 'type' => 'boolean', 'default' => true],
            'funding_links_enabled' => ['key' => 'feature.funding_links_enabled', 'type' => 'boolean', 'default' => true],
            'guest_checkout_enabled' => ['key' => 'feature.guest_checkout_enabled', 'type' => 'boolean', 'default' => true],
            'email_sending_enabled' => ['key' => 'feature.email_sending_enabled', 'type' => 'boolean', 'default' => false],
            'maintenance_notice_enabled' => ['key' => 'feature.maintenance_notice_enabled', 'type' => 'boolean', 'default' => false],
        ];
    }
}
