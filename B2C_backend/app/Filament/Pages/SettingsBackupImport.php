<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\AppSetting;
use App\Services\Settings\SettingsService;
use Database\Seeders\DefaultAppSettingsSeeder;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SettingsBackupImport extends Page
{
    public ?array $data = [];

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::SystemSettings;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'settings-backup-import';

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.settings_backup_import');
    }

    public function getTitle(): string
    {
        return __('admin.pages.settings_backup_import');
    }

    public function mount(): void
    {
        $this->form->fill([
            'import_json' => '',
            'reset_group' => 'feature',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make(__('admin.settings_tools.sections.import'))
                ->schema([
                    Textarea::make('import_json')
                        ->label(__('admin.settings_tools.fields.import_json'))
                        ->rows(12)
                        ->helperText(__('admin.settings_tools.help.import_json')),
                ]),
            Section::make(__('admin.settings_tools.sections.reset'))
                ->schema([
                    Select::make('reset_group')
                        ->label(__('admin.settings_tools.fields.reset_group'))
                        ->options(fn (): array => AppSetting::query()
                            ->where('is_secret', false)
                            ->select('group')
                            ->distinct()
                            ->orderBy('group')
                            ->pluck('group', 'group')
                            ->all())
                        ->required(),
                ]),
        ]);
    }

    public function importSettings(SettingsService $settings): void
    {
        $raw = trim((string) ($this->data['import_json'] ?? ''));
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $this->notify(false, __('admin.settings_tools.messages.invalid_json'));

            return;
        }

        $payload = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded;
        $allowed = AppSetting::query()
            ->where('is_secret', false)
            ->get()
            ->keyBy(fn (AppSetting $setting): string => $setting->fullKey());
        $unknown = collect(array_keys($payload))->diff($allowed->keys());

        if ($unknown->isNotEmpty()) {
            $this->notify(false, __('admin.settings_tools.messages.unknown_keys', ['keys' => $unknown->take(5)->implode(', ')]));

            return;
        }

        foreach ($payload as $key => $value) {
            $record = $allowed->get($key);

            if (! $record instanceof AppSetting) {
                continue;
            }

            $settings->set((string) $key, $value, [
                'type' => $record->type,
                'is_public' => (bool) $record->is_public,
                'updated_by' => PanelAccess::user(),
            ]);
        }

        $settings->warmCache();
        $this->form->fill(['import_json' => '', 'reset_group' => $this->data['reset_group'] ?? 'feature']);
        $this->notify(true, __('admin.settings_tools.messages.imported'));
    }

    public function resetGroup(SettingsService $settings): void
    {
        $group = (string) ($this->data['reset_group'] ?? '');

        if ($group === '') {
            $this->notify(false, __('admin.settings_tools.messages.reset_group_required'));

            return;
        }

        AppSetting::query()
            ->where('group', $group)
            ->where('is_secret', false)
            ->delete();

        Artisan::call('db:seed', [
            '--class' => DefaultAppSettingsSeeder::class,
            '--force' => true,
        ]);

        $settings->warmCache();
        $this->notify(true, __('admin.settings_tools.messages.group_reset', ['group' => $group]));
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('importSettings')
                ->footer([Actions::make($this->getFormActions())]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('downloadExport')
                ->label(__('admin.settings_tools.actions.export'))
                ->url(fn (): string => route('admin.settings.export'))
                ->openUrlInNewTab(),
            Action::make('downloadHandoverSummary')
                ->label(__('admin.settings_tools.actions.handover_summary'))
                ->url(fn (): string => route('admin.settings.handover-summary'))
                ->openUrlInNewTab(),
            Action::make('importSettings')
                ->label(__('admin.settings_tools.actions.import'))
                ->action('importSettings')
                ->requiresConfirmation(),
            Action::make('resetGroup')
                ->label(__('admin.settings_tools.actions.reset_group'))
                ->action('resetGroup')
                ->requiresConfirmation()
                ->color('danger'),
        ];
    }

    private function notify(bool $ok, string $message): void
    {
        try {
            Notification::make()
                ->title($message)
                ->{$ok ? 'success' : 'danger'}()
                ->send();
        } catch (Throwable) {
            //
        }
    }
}
