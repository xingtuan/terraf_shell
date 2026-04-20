<?php

namespace App\Filament\Resources\MaterialStorySections\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialStorySectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Story Section Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('material_id')
                                    ->relationship('material', 'title')
                                    ->required(),
                                TextInput::make('highlight')
                                    ->label('Default highlight'),
                                Select::make('status')
                                    ->options(PublishStatus::options())
                                    ->required()
                                    ->default(PublishStatus::Draft->value),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label('Uploaded media')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/material-story-sections')
                                    ->visibility('public'),
                                TextInput::make('media_url')
                                    ->label('External media URL')
                                    ->url(),
                                DateTimePicker::make('published_at')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label('Title')
                            ->required(),
                        TextInput::make('subtitle_translations.en')
                            ->label('Subtitle'),
                        Textarea::make('content_translations.en')
                            ->label('Content')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.en')
                            ->label('Highlight'),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label('Title'),
                        TextInput::make('subtitle_translations.ko')
                            ->label('Subtitle'),
                        Textarea::make('content_translations.ko')
                            ->label('Content')
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.ko')
                            ->label('Highlight'),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label('Title'),
                        TextInput::make('subtitle_translations.zh')
                            ->label('Subtitle'),
                        Textarea::make('content_translations.zh')
                            ->label('Content')
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.zh')
                            ->label('Highlight'),
                    ]),
            ]);
    }
}
