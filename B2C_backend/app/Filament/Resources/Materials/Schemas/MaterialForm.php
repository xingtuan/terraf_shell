<?php

namespace App\Filament\Resources\Materials\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material Settings')
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
                                Toggle::make('is_featured')
                                    ->required(),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label('Uploaded media')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/materials')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
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
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('headline_translations.en')
                            ->label('Headline'),
                        Textarea::make('summary_translations.en')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.en')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.en')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label('Title'),
                        TextInput::make('headline_translations.ko')
                            ->label('Headline'),
                        Textarea::make('summary_translations.ko')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.ko')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.ko')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label('Title'),
                        TextInput::make('headline_translations.zh')
                            ->label('Headline'),
                        Textarea::make('summary_translations.zh')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.zh')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.zh')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
