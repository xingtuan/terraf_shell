<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

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

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.feature_flags');
    }

    public function getTitle(): string
    {
        return __('admin.pages.feature_flags');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.feature_flags.sections.public_behavior'))->schema([
                Grid::make(2)->schema([
                    Toggle::make('b2c_store_enabled')->label(__('admin.feature_flags.fields.b2c_store_enabled')),
                    Toggle::make('b2b_inquiry_enabled')->label(__('admin.feature_flags.fields.b2b_inquiry_enabled')),
                    Toggle::make('community_enabled')->label(__('admin.feature_flags.fields.community_enabled')),
                    Toggle::make('funding_links_enabled')->label(__('admin.feature_flags.fields.funding_links_enabled')),
                    Toggle::make('guest_checkout_enabled')->label(__('admin.feature_flags.fields.guest_checkout_enabled')),
                    Toggle::make('email_sending_enabled')->label(__('admin.feature_flags.fields.email_sending_enabled')),
                    Toggle::make('maintenance_notice_enabled')->label(__('admin.feature_flags.fields.maintenance_notice_enabled')),
                    TextInput::make('maintenance_notice_message')->label(__('admin.feature_flags.fields.maintenance_notice_message'))->maxLength(255),
                    TextInput::make('maintenance_notice_level')->label(__('admin.feature_flags.fields.maintenance_notice_level'))->maxLength(20),
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
            'maintenance_notice_enabled' => ['key' => 'maintenance.notice_enabled', 'type' => 'boolean', 'default' => false],
            'maintenance_notice_message' => ['key' => 'maintenance.notice_message', 'type' => 'string', 'default' => ''],
            'maintenance_notice_level' => ['key' => 'maintenance.notice_level', 'type' => 'string', 'default' => 'info'],
        ];
    }
}
