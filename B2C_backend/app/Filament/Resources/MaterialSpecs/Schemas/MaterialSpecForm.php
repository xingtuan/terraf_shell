<?php

namespace App\Filament\Resources\MaterialSpecs\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaterialSpecForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('material_id')
                    ->relationship('material', 'title')
                    ->required(),
                TextInput::make('key'),
                TextInput::make('label')
                    ->required(),
                TextInput::make('value')
                    ->required(),
                TextInput::make('unit'),
                Textarea::make('detail')
                    ->columnSpanFull(),
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
                    ->visibility('public'),
                TextInput::make('media_url')
                    ->label('External media URL')
                    ->url(),
                DateTimePicker::make('published_at')
                    ->disabled(),
            ]);
    }
}
