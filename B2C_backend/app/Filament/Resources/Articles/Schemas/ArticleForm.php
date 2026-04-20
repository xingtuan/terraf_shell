<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Section::make('Article Settings')
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
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('media_url')
                                    ->label('External media URL')
                                    ->url(),
                                FileUpload::make('media_path')
                                    ->label('Uploaded media')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/articles')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label('Title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('category_translations.en')
                            ->label('Category'),
                        Textarea::make('excerpt_translations.en')
                            ->label('Excerpt')
                            ->columnSpanFull(),
                        Textarea::make('content_translations.en')
                            ->label('Content')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label('Title'),
                        TextInput::make('category_translations.ko')
                            ->label('Category'),
                        Textarea::make('excerpt_translations.ko')
                            ->label('Excerpt')
                            ->columnSpanFull(),
                        Textarea::make('content_translations.ko')
                            ->label('Content')
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label('Title'),
                        TextInput::make('category_translations.zh')
                            ->label('Category'),
                        Textarea::make('excerpt_translations.zh')
                            ->label('Excerpt')
                            ->columnSpanFull(),
                        Textarea::make('content_translations.zh')
                            ->label('Content')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
