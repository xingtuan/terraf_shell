<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\MediaFile;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaStorageScan extends Page
{
    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::MediaLibrary;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'media-storage-scan';

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.media_storage_scan');
    }

    public function getTitle(): string
    {
        return __('admin.pages.media_storage_scan');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.media_scan.sections.summary'))
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('total_files')
                            ->label(__('admin.media_scan.fields.total_files'))
                            ->content(fn (): string => number_format(MediaFile::query()->count())),
                        Placeholder::make('local_files')
                            ->label(__('admin.media_scan.fields.local_files'))
                            ->content(fn (): string => number_format($this->countForDisk('public') + $this->countForDisk('local'))),
                        Placeholder::make('azure_files')
                            ->label(__('admin.media_scan.fields.azure_files'))
                            ->content(fn (): string => number_format($this->countForDisk('azure'))),
                        Placeholder::make('total_size')
                            ->label(__('admin.media_scan.fields.total_size'))
                            ->content(fn (): string => number_format((int) MediaFile::query()->sum('size') / 1024 / 1024, 2).' MB'),
                    ]),
                ]),
            Section::make(__('admin.media_scan.sections.integrity'))
                ->description(__('admin.media_scan.help.scan_limit'))
                ->schema([
                    Placeholder::make('missing_files')
                        ->label(__('admin.media_scan.fields.missing_files'))
                        ->content(fn (): string => (string) $this->missingFileCount()),
                ]),
        ]);
    }

    public function dryRunLocalToAzure(): void
    {
        $this->dryRun('public', 'azure');
    }

    public function dryRunAzureToLocal(): void
    {
        $this->dryRun('azure', 'public');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->footer([Actions::make([
                    Action::make('exportReport')
                        ->label(__('admin.media_scan.actions.export_report'))
                        ->url(fn (): string => route('admin.media-scan.export'))
                        ->openUrlInNewTab(),
                    Action::make('dryRunLocalToAzure')
                        ->label(__('admin.media_scan.actions.dry_run_local_to_azure'))
                        ->action('dryRunLocalToAzure'),
                    Action::make('dryRunAzureToLocal')
                        ->label(__('admin.media_scan.actions.dry_run_azure_to_local'))
                        ->action('dryRunAzureToLocal'),
                ])]),
        ]);
    }

    private function countForDisk(string $disk): int
    {
        return (int) MediaFile::query()->where('disk', $disk)->count();
    }

    private function missingFileCount(): int
    {
        return MediaFile::query()
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->filter(function (MediaFile $mediaFile): bool {
                try {
                    return ! Storage::disk($mediaFile->disk ?: (string) config('community.uploads.disk'))->exists($mediaFile->path);
                } catch (Throwable) {
                    return true;
                }
            })
            ->count();
    }

    private function dryRun(string $from, string $to): void
    {
        $count = $this->countForDisk($from);

        Notification::make()
            ->title(__('admin.media_scan.messages.dry_run_ready', ['count' => $count, 'from' => $from, 'to' => $to]))
            ->warning()
            ->send();
    }
}
