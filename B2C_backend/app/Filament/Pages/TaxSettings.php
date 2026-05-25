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

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

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

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.tax_settings');
    }

    public function getTitle(): string
    {
        return __('admin.pages.tax_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.ui.gst'))->schema([
                Grid::make(2)->schema([
                    Toggle::make('gst_enabled')->label(__('admin.tax.fields.gst_enabled')),
                    Toggle::make('prices_include_gst')->label(__('admin.tax.fields.prices_include_gst')),
                    TextInput::make('gst_rate')->label(__('admin.tax.fields.gst_rate'))->numeric()->minValue(0)->maxValue(100)->hint(__('admin.tax.hints.gst_rate')),
                    TextInput::make('label')->label(__('admin.tax.fields.tax_label'))->maxLength(80),
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

    protected function mutateSettingsStateBeforeSave(array $state): array
    {
        if (isset($state['gst_rate']) && is_numeric($state['gst_rate'])) {
            $rate = (float) $state['gst_rate'];
            // Accept percentage inputs (e.g. 15 or 20) and convert to decimal rate (0.15, 0.20).
            if ($rate > 1) {
                $state['gst_rate'] = round($rate / 100, 6);
            }
        }

        return $state;
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
