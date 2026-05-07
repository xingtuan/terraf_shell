<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationSettings extends Page
{
    use ManagesRuntimeSettings;

    protected static ?string $title = 'Application Settings';

    protected static ?string $navigationLabel = 'Application Settings';

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'application-settings';

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
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Application')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('site_name')->label('Site name')->required()->maxLength(255),
                            TextInput::make('admin_brand_name')->label('Admin brand name')->maxLength(255),
                            TextInput::make('app_url')->label('App URL')->url()->maxLength(255),
                            TextInput::make('frontend_url')->label('Frontend URL')->url()->maxLength(255),
                            TextInput::make('default_locale')->label('Default locale')->maxLength(8),
                            TextInput::make('timezone')->label('Timezone')->maxLength(80),
                            TextInput::make('contact_email')->label('Contact email')->email()->maxLength(255),
                            TextInput::make('support_email')->label('Support email')->email()->maxLength(255),
                        ]),
                        TagsInput::make('supported_locales')
                            ->label('Supported locales')
                            ->placeholder('en'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('admin.actions.save_settings'))->submit('save'),
                    ]),
                ]),
        ]);
    }

    protected function settingMap(): array
    {
        return [
            'site_name' => ['key' => 'app.site_name', 'type' => 'string', 'default' => config('app.name')],
            'admin_brand_name' => ['key' => 'app.admin_brand_name', 'type' => 'string', 'default' => config('app.admin_brand_name', config('app.name'))],
            'app_url' => ['key' => 'app.url', 'type' => 'string', 'default' => config('app.url')],
            'frontend_url' => ['key' => 'app.frontend_url', 'type' => 'string', 'default' => config('services.frontend.url')],
            'default_locale' => ['key' => 'app.default_locale', 'type' => 'string', 'default' => config('app.locale', 'en')],
            'supported_locales' => ['key' => 'app.supported_locales', 'type' => 'json', 'default' => ['en', 'ko', 'zh']],
            'timezone' => ['key' => 'app.timezone', 'type' => 'string', 'default' => config('app.timezone', 'UTC')],
            'contact_email' => ['key' => 'app.contact_email', 'type' => 'string', 'default' => config('mail.from.address')],
            'support_email' => ['key' => 'app.support_email', 'type' => 'string', 'default' => config('mail.from.address')],
        ];
    }
}
