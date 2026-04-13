<?php

namespace App\Filament\Resources\Materials\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('headline'),
                Textarea::make('summary')
                    ->columnSpanFull(),
                Textarea::make('story_overview')
                    ->columnSpanFull(),
                Textarea::make('science_overview')
                    ->columnSpanFull(),
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
                    ->visibility('public'),
                TextInput::make('media_url')
                    ->label('External media URL')
                    ->url(),
                DateTimePicker::make('published_at')
                    ->disabled(),
            ]);
    }
}
