<?php

namespace App\Filament\Resources\MediaFiles;

use App\Filament\Resources\MediaFiles\Pages\ListMediaFiles;
use App\Filament\Resources\MediaFiles\Pages\ViewMediaFile;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\MediaFile;
use App\Support\StorageUrl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFileResource extends Resource
{
    use HasAdminResourceTranslations;

    protected static ?string $model = MediaFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::MediaLibrary;

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('url')
                    ->label(__('admin.fields.preview'))
                    ->state(fn (MediaFile $record): ?string => str_starts_with((string) $record->mime_type, 'image/') ? $record->url : null)
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x96?text=File'),
                TextColumn::make('original_name')
                    ->label(__('admin.fields.file'))
                    ->searchable()
                    ->description(fn (MediaFile $record): string => $record->path ?: ($record->url ?: '-'))
                    ->limit(50),
                TextColumn::make('disk')
                    ->label(__('admin.ui.disk'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('path')
                    ->label(__('admin.fields.path'))
                    ->copyable()
                    ->limit(45)
                    ->toggleable(),
                TextColumn::make('public_url')
                    ->label(__('admin.ui.public_url'))
                    ->state(fn (MediaFile $record): ?string => $record->url)
                    ->copyable()
                    ->limit(45)
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('admin.fields.type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('category')
                    ->label(__('admin.ui.category'))
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('mime_type')
                    ->label(__('admin.fields.mime_type'))
                    ->toggleable(),
                TextColumn::make('linked_object')
                    ->label(__('admin.fields.linked_object'))
                    ->state(fn (MediaFile $record): string => $record->fileable_type
                        ? class_basename($record->fileable_type).' #'.$record->fileable_id
                        : __('admin.placeholders.unlinked'))
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label(__('admin.fields.uploaded_by'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label(__('admin.fields.size'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state / 1024, 1).' KB')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.uploaded'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.fields.type'))
                    ->options([
                        'images' => __('admin.media.type.image'),
                        'documents' => __('admin.media.type.document'),
                        'videos' => __('admin.media.type.video'),
                        'audios' => __('admin.media.type.audio'),
                        'others' => __('admin.media.type.other'),
                    ]),
                SelectFilter::make('disk')
                    ->label(__('admin.ui.disk'))
                    ->options(fn (): array => MediaFile::query()
                        ->whereNotNull('disk')
                        ->select('disk')
                        ->distinct()
                        ->orderBy('disk')
                        ->pluck('disk', 'disk')
                        ->all()),
                SelectFilter::make('category')
                    ->label(__('admin.ui.category'))
                    ->options(fn (): array => MediaFile::query()
                        ->whereNotNull('category')
                        ->select('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label(__('admin.ui.uploaded_from')),
                        DatePicker::make('created_until')->label(__('admin.ui.uploaded_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $builder, string $date): Builder => $builder->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('copyPublicUrl')
                    ->label(__('admin.ui.copy_public_url'))
                    ->icon('heroicon-o-clipboard')
                    ->action(function (MediaFile $record): void {
                        Notification::make()
                            ->title(__('admin.ui.public_url_is_copyable_from_the_url_column'))
                            ->body((string) $record->url)
                            ->success()
                            ->send();
                    }),
                Action::make('testExists')
                    ->label(__('admin.ui.test_file_exists'))
                    ->icon('heroicon-o-check-circle')
                    ->action(function (MediaFile $record): void {
                        $exists = Storage::disk($record->disk ?: (string) config('community.uploads.disk'))->exists($record->path);

                        Notification::make()
                            ->title($exists ? __('admin.ui.file_exists_on_disk') : __('admin.ui.file_missing_from_disk'))
                            ->{$exists ? 'success' : 'danger'}()
                            ->send();
                    }),
                Action::make('regenerateUrl')
                    ->label(__('admin.ui.regenerate_url'))
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (MediaFile $record): void {
                        $record->forceFill([
                            'url' => StorageUrl::publicResolve($record->path, $record->disk),
                        ])->save();

                        Notification::make()
                            ->title(__('admin.ui.public_url_regenerated'))
                            ->success()
                            ->send();
                    }),
                Action::make('download')
                    ->label(__('admin.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MediaFile $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (MediaFile $record): bool => filled($record->url)),
                Action::make('deleteSafe')
                    ->label(__('admin.actions.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MediaFile $record): bool => PanelAccess::isAdmin() && blank($record->fileable_type) && blank($record->fileable_id))
                    ->action(function (MediaFile $record): void {
                        Storage::disk($record->disk ?: (string) config('community.uploads.disk'))->delete($record->path);
                        $record->delete();

                        Notification::make()
                            ->title(__('admin.notifications.deleted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.preview'))
                    ->schema([
                        ImageEntry::make('url')
                            ->label(__('admin.fields.image_preview'))
                            ->visible(fn (MediaFile $record): bool => str_starts_with((string) $record->mime_type, 'image/'))
                            ->height(240),
                    ]),
                Section::make(__('admin.sections.file'))
                    ->schema([
                        TextEntry::make('original_name')
                            ->label(__('admin.fields.file')),
                        TextEntry::make('type')
                            ->label(__('admin.fields.type'))
                            ->badge(),
                        TextEntry::make('disk')
                            ->label(__('admin.ui.disk'))
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('category')
                            ->label(__('admin.ui.category'))
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('mime_type')
                            ->label(__('admin.fields.mime_type'))
                            ->placeholder('-'),
                        TextEntry::make('size')
                            ->label(__('admin.fields.size'))
                            ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state / 1024, 1).' KB'),
                        TextEntry::make('path')
                            ->label(__('admin.fields.path'))
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('url')
                            ->label(__('admin.ui.public_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.ownership'))
                    ->schema([
                        TextEntry::make('user.email')
                            ->label(__('admin.fields.uploaded_by'))
                            ->placeholder('-'),
                        TextEntry::make('fileable_type')
                            ->label(__('admin.fields.linked_type'))
                            ->placeholder('-'),
                        TextEntry::make('fileable_id')
                            ->label(__('admin.fields.linked_id'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.uploaded'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isAdmin() && blank($record->fileable_type) && blank($record->fileable_id);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaFiles::route('/'),
            'view' => ViewMediaFile::route('/{record}'),
        ];
    }
}
