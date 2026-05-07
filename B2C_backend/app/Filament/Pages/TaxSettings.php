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

class TaxSettings extends Page
{
    use ManagesRuntimeSettings;

    protected static ?string $title = 'Tax Settings';

    protected static ?string $navigationLabel = 'Tax Settings';

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'tax-settings';

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
            Section::make('GST')->schema([
                Grid::make(2)->schema([
                    Toggle::make('gst_enabled')->label('GST enabled'),
                    Toggle::make('prices_include_gst')->label('Prices include GST'),
                    TextInput::make('gst_rate')->label('GST rate')->numeric()->minValue(0)->maxValue(1),
                    TextInput::make('label')->label('Tax label')->maxLength(80),
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
            'gst_enabled' => ['key' => 'tax.gst_enabled', 'type' => 'boolean', 'default' => true],
            'gst_rate' => ['key' => 'tax.gst_rate', 'type' => 'float', 'default' => config('store.tax.gst_rate', 0.15)],
            'prices_include_gst' => ['key' => 'tax.prices_include_gst', 'type' => 'boolean', 'default' => config('store.tax.prices_include_gst', true)],
            'label' => ['key' => 'tax.label', 'type' => 'string', 'default' => config('store.tax.label', 'GST included')],
        ];
    }
}
