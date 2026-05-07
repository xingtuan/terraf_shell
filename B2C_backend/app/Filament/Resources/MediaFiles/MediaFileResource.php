<?php

namespace App\Filament\Resources\MediaFiles;

use App\Filament\Resources\MediaFiles\Pages\ListMediaFiles;
use App\Filament\Resources\MediaFiles\Pages\ViewMediaFile;
use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\HasAdminResourceTranslations;
use App\Filament\Support\PanelAccess;
use App\Models\MediaFile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mime_type')
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
                    ->options([
                        'image' => __('admin.media.type.image'),
                        'document' => __('admin.media.type.document'),
                        'video' => __('admin.media.type.video'),
                        'other' => __('admin.media.type.other'),
                    ]),
                SelectFilter::make('category')
                    ->options(fn (): array => MediaFile::query()
                        ->whereNotNull('category')
                        ->select('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download')
                    ->label(__('admin.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MediaFile $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (MediaFile $record): bool => filled($record->url)),
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
                        TextEntry::make('original_name'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('mime_type')
                            ->placeholder('-'),
                        TextEntry::make('size')
                            ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state / 1024, 1).' KB'),
                        TextEntry::make('path')
                            ->copyable()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('url')
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
