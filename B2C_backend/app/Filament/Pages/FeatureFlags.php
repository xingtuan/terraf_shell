<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                ]),
            ]),
            Section::make(__('admin.feature_flags.sections.maintenance'))->schema([
                Grid::make(2)->schema([
                    Toggle::make('maintenance_mode_enabled')
                        ->label(__('admin.feature_flags.fields.maintenance_mode_enabled'))
                        ->helperText(__('admin.feature_flags.fields.maintenance_mode_enabled_help')),
                    Toggle::make('maintenance_notice_enabled')
                        ->label(__('admin.feature_flags.fields.maintenance_notice_enabled'))
                        ->helperText(__('admin.feature_flags.fields.maintenance_notice_enabled_help')),
                    Select::make('maintenance_notice_level')
                        ->label(__('admin.feature_flags.fields.maintenance_notice_level'))
                        ->helperText(__('admin.feature_flags.fields.maintenance_notice_level_help'))
                        ->options([
                            'info' => __('admin.feature_flags.level_options.info'),
                            'warning' => __('admin.feature_flags.level_options.warning'),
                            'error' => __('admin.feature_flags.level_options.error'),
                        ])
                        ->default('info')
                        ->required(),
                ]),
                Grid::make(3)->schema([
                    Textarea::make('maintenance_notice_message_en')
                        ->label(__('admin.feature_flags.fields.maintenance_notice_message_en'))
                        ->helperText(__('admin.feature_flags.fields.maintenance_notice_message_help'))
                        ->rows(2)
                        ->maxLength(500),
                    Textarea::make('maintenance_notice_message_ko')
                        ->label(__('admin.feature_flags.fields.maintenance_notice_message_ko'))
                        ->rows(2)
                        ->maxLength(500),
                    Textarea::make('maintenance_notice_message_zh')
                        ->label(__('admin.feature_flags.fields.maintenance_notice_message_zh'))
                        ->rows(2)
                        ->maxLength(500),
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
            'maintenance_mode_enabled' => ['key' => 'maintenance.mode_enabled', 'type' => 'boolean', 'default' => false],
            'maintenance_notice_enabled' => ['key' => 'maintenance.notice_enabled', 'type' => 'boolean', 'default' => false],
            'maintenance_notice_level' => ['key' => 'maintenance.notice_level', 'type' => 'string', 'default' => 'info'],
            'maintenance_notice_message_en' => ['key' => 'maintenance.notice_message_en', 'type' => 'string', 'default' => ''],
            'maintenance_notice_message_ko' => ['key' => 'maintenance.notice_message_ko', 'type' => 'string', 'default' => ''],
            'maintenance_notice_message_zh' => ['key' => 'maintenance.notice_message_zh', 'type' => 'string', 'default' => ''],
        ];
    }
}
