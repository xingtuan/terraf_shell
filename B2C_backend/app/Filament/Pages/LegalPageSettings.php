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
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Use',
    ];

    private const LOCALES = [
        'en' => 'English',
        'ko' => 'Korean',
        'zh' => 'Chinese',
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
        return 'Legal Pages';
    }

    public function getTitle(): string
    {
        return 'Legal Pages';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Legal page content')
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

        foreach (array_keys(self::PAGES) as $page) {
            foreach (array_keys(self::LOCALES) as $locale) {
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

        foreach (self::PAGES as $page => $label) {
            $tabs[] = Tab::make($label)
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

        foreach (self::LOCALES as $locale => $label) {
            $sections[] = Section::make($label)
                ->description('Leave fields empty to use the current frontend default text for this locale.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make($this->fieldName($page, $locale, 'meta_title'))
                            ->label('Meta title')
                            ->maxLength(255),
                        TextInput::make($this->fieldName($page, $locale, 'eyebrow'))
                            ->label('Eyebrow')
                            ->maxLength(120),
                        Textarea::make($this->fieldName($page, $locale, 'meta_description'))
                            ->label('Meta description')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make($this->fieldName($page, $locale, 'title'))
                            ->label('Page title')
                            ->maxLength(255),
                        TextInput::make($this->fieldName($page, $locale, 'last_updated_label'))
                            ->label('Last updated label')
                            ->maxLength(120),
                        TextInput::make($this->fieldName($page, $locale, 'last_updated'))
                            ->label('Last updated value')
                            ->placeholder('May 2026')
                            ->maxLength(120),
                        Textarea::make($this->fieldName($page, $locale, 'description'))
                            ->label('Intro summary')
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make($this->fieldName($page, $locale, 'body_html'))
                            ->label('Page body')
                            ->helperText('When this field has content, it replaces the default section list on the public page.')
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
}
