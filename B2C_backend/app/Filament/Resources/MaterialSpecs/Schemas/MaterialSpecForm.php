<?php

namespace App\Filament\Resources\MaterialSpecs\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialSpecForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Specification Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('material_id')
                                    ->relationship('material', 'title')
                                    ->required(),
                                TextInput::make('key'),
                                TextInput::make('unit'),
                                TextInput::make('icon'),
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
                                    ->directory('cms/material-specs')
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
                        TextInput::make('label_translations.en')
                            ->label('Label')
                            ->required(),
                        TextInput::make('value_translations.en')
                            ->label('Value')
                            ->required(),
                        Textarea::make('detail_translations.en')
                            ->label('Detail')
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('label_translations.ko')
                            ->label('Label'),
                        TextInput::make('value_translations.ko')
                            ->label('Value'),
                        Textarea::make('detail_translations.ko')
                            ->label('Detail')
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('label_translations.zh')
                            ->label('Label'),
                        TextInput::make('value_translations.zh')
                            ->label('Value'),
                        Textarea::make('detail_translations.zh')
                            ->label('Detail')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
