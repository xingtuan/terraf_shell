<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.article_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('status')
                                    ->options(PublishStatus::options())
                                    ->required()
                                    ->default(PublishStatus::Draft->value),
                                Toggle::make('is_seeded')
                                    ->label(__('admin.ui.seeded_demo_content'))
                                    ->helperText(__('admin.ui.seeded_demo_content_help'))
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->url(),
                                FileUpload::make('media_path')
                                    ->label(__('admin.ui.uploaded_media'))
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/articles')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                    ->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->label(__('admin.ui.published_at')),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label(__('admin.ui.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('category_translations.en')
                            ->label(__('admin.ui.category')),
                        Textarea::make('excerpt_translations.en')
                            ->label(__('admin.ui.excerpt'))
                            ->columnSpanFull(),
                        RichEditor::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label(__('admin.ui.title')),
                        TextInput::make('category_translations.ko')
                            ->label(__('admin.ui.category')),
                        Textarea::make('excerpt_translations.ko')
                            ->label(__('admin.ui.excerpt'))
                            ->columnSpanFull(),
                        RichEditor::make('content_translations.ko')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label(__('admin.ui.title')),
                        TextInput::make('category_translations.zh')
                            ->label(__('admin.ui.category')),
                        Textarea::make('excerpt_translations.zh')
                            ->label(__('admin.ui.excerpt'))
                            ->columnSpanFull(),
                        RichEditor::make('content_translations.zh')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
