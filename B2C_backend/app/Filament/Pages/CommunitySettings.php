<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ManagesRuntimeSettings;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunitySettings extends Page
{
    use ManagesRuntimeSettings;

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'community-settings';

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
        return __('admin.pages.community_settings');
    }

    public function getTitle(): string
    {
        return __('admin.pages.community_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.community_settings.sections.uploads_moderation'))->schema([
                Grid::make(3)->schema([
                    Toggle::make('allow_guest_upload')->label(__('admin.community_settings.fields.allow_guest_upload')),
                    TextInput::make('max_files')->numeric()->minValue(1)->label(__('admin.community_settings.fields.max_files')),
                    TextInput::make('max_file_size_kb')->numeric()->minValue(1)->label(__('admin.community_settings.fields.max_file_size_kb')),
                    TextInput::make('max_external_links')->numeric()->minValue(0)->label(__('admin.community_settings.fields.max_external_links')),
                    TextInput::make('submission_policy')->label(__('admin.community_settings.fields.submission_policy')),
                    Toggle::make('sensitive_words_enabled')->label(__('admin.community_settings.fields.sensitive_words_enabled')),
                ]),
                TagsInput::make('allowed_extensions')->label(__('admin.community_settings.fields.allowed_extensions')),
                TagsInput::make('sensitive_words')->label(__('admin.community_settings.fields.sensitive_words')),
                TextInput::make('default_funding_support_button_text')->label(__('admin.community_settings.fields.default_funding_support_button_text')),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('admin.actions.save_settings'))->submit('save')])]),
        ]);
    }

    protected function settingMap(): array
    {
        return [
            'allow_guest_upload' => ['key' => 'community.allow_guest_upload', 'type' => 'boolean', 'default' => config('community.uploads.allow_guest_upload', false)],
            'max_files' => ['key' => 'community.max_files', 'type' => 'integer', 'default' => config('community.idea_media.max_files', 12)],
            'max_file_size_kb' => ['key' => 'community.max_file_size_kb', 'type' => 'integer', 'default' => config('community.idea_media.max_file_size_kb', 10240)],
            'allowed_extensions' => ['key' => 'community.allowed_extensions', 'type' => 'json', 'default' => config('community.idea_media.allowed_extensions', [])],
            'max_external_links' => ['key' => 'community.max_external_links', 'type' => 'integer', 'default' => config('community.idea_media.max_external_links', 4)],
            'submission_policy' => ['key' => 'community.submission_policy', 'type' => 'string', 'default' => config('community.moderation.submission_policy', 'all_require_approval')],
            'sensitive_words_enabled' => ['key' => 'community.sensitive_words_enabled', 'type' => 'boolean', 'default' => config('community.moderation.sensitive_words.enabled', false)],
            'sensitive_words' => ['key' => 'community.sensitive_words', 'type' => 'json', 'default' => config('community.moderation.sensitive_words.terms', [])],
            'default_funding_support_button_text' => ['key' => 'community.default_funding_support_button_text', 'type' => 'string', 'default' => config('community.funding.default_support_button_text', 'Support this concept')],
        ];
    }
}
