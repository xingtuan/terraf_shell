<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LegalPageSettings extends Page
{
    use ManagesRuntimeSettings;

    private const PAGES = [
        'privacy',
        'terms',
    ];

    private const LOCALES = [
        'en',
        'ko',
        'zh',
    ];

    private const FIELDS = [
        'meta_title' => ['type' => 'string', 'default' => ''],
        'meta_description' => ['type' => 'string', 'default' => ''],
        'eyebrow' => ['type' => 'string', 'default' => ''],
        'title' => ['type' => 'string', 'default' => ''],
        'description' => ['type' => 'string', 'default' => ''],
        'last_updated_label' => ['type' => 'string', 'default' => ''],
        'last_updated' => ['type' => 'string', 'default' => ''],
        'body_html' => ['type' => 'string', 'default' => ''],
    ];

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'legal-page-settings';

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
        return __('admin.pages.legal_page_settings');
    }

    public function getTitle(): string
    {
        return __('admin.pages.legal_page_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make(__('admin.legal_pages.tabs.content'))
                    ->persistTab()
                    ->tabs($this->pageTabs())
                    ->columnSpanFull(),
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
        $map = [];

        foreach (self::PAGES as $page) {
            foreach (self::LOCALES as $locale) {
                foreach (self::FIELDS as $field => $meta) {
                    $map[$this->fieldName($page, $locale, $field)] = [
                        'key' => "legal.{$page}.{$locale}.{$field}",
                        'type' => $meta['type'],
                        'default' => $meta['default'],
                        'is_public' => true,
                    ];
                }
            }
        }

        return $map;
    }

    /**
     * @return array<int, Tab>
     */
    private function pageTabs(): array
    {
        $tabs = [];

        foreach (self::PAGES as $page) {
            $tabs[] = Tab::make($this->pageLabel($page))
                ->schema($this->localeSections($page));
        }

        return $tabs;
    }

    /**
     * @return array<int, Section>
     */
    private function localeSections(string $page): array
    {
        $sections = [];

        foreach (self::LOCALES as $locale) {
            $sections[] = Section::make($this->localeLabel($locale))
                ->description(__('admin.legal_pages.help.locale_section'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make($this->fieldName($page, $locale, 'meta_title'))
                            ->label(__('admin.legal_pages.fields.meta_title'))
                            ->maxLength(255),
                        TextInput::make($this->fieldName($page, $locale, 'eyebrow'))
                            ->label(__('admin.legal_pages.fields.eyebrow'))
                            ->maxLength(120),
                        Textarea::make($this->fieldName($page, $locale, 'meta_description'))
                            ->label(__('admin.legal_pages.fields.meta_description'))
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make($this->fieldName($page, $locale, 'title'))
                            ->label(__('admin.legal_pages.fields.title'))
                            ->maxLength(255),
                        TextInput::make($this->fieldName($page, $locale, 'last_updated_label'))
                            ->label(__('admin.legal_pages.fields.last_updated_label'))
                            ->maxLength(120),
                        TextInput::make($this->fieldName($page, $locale, 'last_updated'))
                            ->label(__('admin.legal_pages.fields.last_updated'))
                            ->placeholder(__('admin.legal_pages.placeholders.last_updated'))
                            ->maxLength(120),
                        Textarea::make($this->fieldName($page, $locale, 'description'))
                            ->label(__('admin.legal_pages.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make($this->fieldName($page, $locale, 'body_html'))
                            ->label(__('admin.legal_pages.fields.body_html'))
                            ->helperText(__('admin.legal_pages.help.body'))
                            ->columnSpanFull(),
                    ]),
                ]);
        }

        return $sections;
    }

    private function fieldName(string $page, string $locale, string $field): string
    {
        return "{$page}_{$locale}_{$field}";
    }

    private function pageLabel(string $page): string
    {
        return __("admin.legal_pages.pages.{$page}");
    }

    private function localeLabel(string $locale): string
    {
        return __("admin.legal_pages.locales.{$locale}");
    }
}
