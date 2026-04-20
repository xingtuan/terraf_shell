<?php

namespace App\Filament\Resources\MaterialApplications\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('material_id')
                                    ->relationship('material', 'title')
                                    ->required(),
                                TextInput::make('cta_url')
                                    ->url(),
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
                                    ->directory('cms/material-applications')
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
                        TextInput::make('audience_translations.en')
                            ->label('Audience'),
                        TextInput::make('cta_label_translations.en')
                            ->label('CTA label'),
                        Textarea::make('description_translations.en')
                            ->label('Description')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label('Title'),
                        TextInput::make('subtitle_translations.ko')
                            ->label('Subtitle'),
                        TextInput::make('audience_translations.ko')
                            ->label('Audience'),
                        TextInput::make('cta_label_translations.ko')
                            ->label('CTA label'),
                        Textarea::make('description_translations.ko')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label('Title'),
                        TextInput::make('subtitle_translations.zh')
                            ->label('Subtitle'),
                        TextInput::make('audience_translations.zh')
                            ->label('Audience'),
                        TextInput::make('cta_label_translations.zh')
                            ->label('CTA label'),
                        Textarea::make('description_translations.zh')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
