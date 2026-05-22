<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Filament\Support\AdminUploadStorage;
use App\Filament\Support\PanelAccess;
use App\Models\IdeaMedia;
use App\Models\Post;
use App\Models\User;
use App\Rules\ExternalSafeUrl;
use App\Support\StorageUrl;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.post'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('admin.ui.title'))
                                    ->required()
                                    ->maxLength(200)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('slug', Str::slug((string) $state));
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('user_id')
                                    ->label(__('admin.ui.author'))
                                    ->relationship('user', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name.' (@'.$record->username.')')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('category_id')
                                    ->label(__('admin.ui.category'))
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->label(__('admin.fields.status'))
                                    ->options(ContentStatus::options())
                                    ->default(ContentStatus::Pending->value)
                                    ->required(),
                                Select::make('tags')
                                    ->label(__('admin.ui.tags'))
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_pinned')
                                    ->label(__('admin.ui.pinned'))
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Toggle::make('is_featured')
                                    ->label(__('admin.ui.featured'))
                                    ->visible(fn (): bool => PanelAccess::isAdmin()),
                                Textarea::make('excerpt')
                                    ->label(__('admin.ui.excerpt'))
                                    ->rows(4)
                                    ->helperText(__('admin.ui.leave_blank_to_generate_an_excerpt_from_the_content'))
                                    ->columnSpanFull(),
                                TextInput::make('funding_url')
                                    ->label(__('admin.ui.funding_url'))
                                    ->url()
                                    ->rule(new ExternalSafeUrl)
                                    ->maxLength(2048)
                                    ->helperText(__('admin.ui.external_http_https_urls_only_javascript_and_data_urls_are_rejected'))
                                    ->placeholder('https://www.gofundme.com/... or https://www.kickstarter.com/...')
                                    ->columnSpanFull(),
                                Textarea::make('content')
                                    ->label(__('admin.ui.content'))
                                    ->required()
                                    ->rows(12)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.images'))
                    ->schema([
                        Hidden::make('cover_image_disk'),
                        FileUpload::make('cover_image_path')
                            ->label(__('admin.ui.cover_image'))
                            ->image()
                            ->disk(fn (): string => AdminUploadStorage::disk())
                            ->directory('posts/covers')
                            ->visibility(fn (): string => AdminUploadStorage::visibility())
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing(fn (string $file, ?Post $record = null, string|array|null $storedFileNames = null): ?array => self::uploadedFileInfo(
                                $file,
                                $record?->coverImageDisk(),
                                $storedFileNames,
                            ))
                            ->afterStateUpdated(function (Set $set): void {
                                $set('cover_image_disk', AdminUploadStorage::disk());
                            })
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                        ViewField::make('content_images_preview')
                            ->label(__('admin.ui.content_images'))
                            ->view('filament.components.media-image-grid', fn (?Post $record): array => [
                                'urls' => $record?->contentImageUrls() ?? [],
                            ])
                            ->dehydrated(false)
                            ->visible(fn (?Post $record): bool => count($record?->contentImageUrls() ?? []) > 0)
                            ->columnSpanFull(),
                        Repeater::make('images')
                            ->relationship()
                            ->label(__('admin.ui.post_images'))
                            ->addActionLabel(__('admin.ui.add_image'))
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->grid(2)
                            ->schema([
                                Hidden::make('disk')
                                    ->default(fn (): string => AdminUploadStorage::disk()),
                                FileUpload::make('path')
                                    ->label(__('admin.ui.image'))
                                    ->image()
                                    ->required()
                                    ->disk(fn (): string => AdminUploadStorage::disk())
                                    ->directory('posts')
                                    ->visibility(fn (): string => AdminUploadStorage::visibility())
                                    ->fetchFileInformation(false)
                                    ->getUploadedFileUsing(fn (string $file, ?IdeaMedia $record = null, string|array|null $storedFileNames = null): ?array => self::uploadedFileInfo(
                                        $file,
                                        $record?->storageDisk(),
                                        $storedFileNames,
                                    ))
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('disk', AdminUploadStorage::disk());
                                    })
                                    ->imagePreviewHeight('140'),
                                TextInput::make('alt_text')
                                    ->label(__('admin.ui.alt_text'))
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array{name: string, size: int, type: ?string, url: string}|null
     */
    private static function uploadedFileInfo(string $file, ?string $disk, string|array|null $storedFileNames = null): ?array
    {
        $resolvedDisk = StorageUrl::normalizeDisk($disk ?: AdminUploadStorage::disk());
        $storage = Storage::disk($resolvedDisk);
        $size = 0;
        $type = null;

        try {
            if ($storage->exists($file)) {
                $size = (int) $storage->size($file);
                $type = $storage->mimeType($file) ?: null;
            }
        } catch (\Throwable) {
            //
        }

        return [
            'name' => is_array($storedFileNames) ? ($storedFileNames[$file] ?? basename($file)) : ($storedFileNames ?: basename($file)),
            'size' => $size,
            'type' => $type,
            'url' => (string) StorageUrl::resolve($file, $resolvedDisk),
        ];
    }
}
