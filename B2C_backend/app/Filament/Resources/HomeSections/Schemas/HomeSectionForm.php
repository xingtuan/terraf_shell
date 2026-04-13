<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required(),
                TextInput::make('title'),
                TextInput::make('subtitle'),
                Textarea::make('content')
                    ->columnSpanFull(),
                TextInput::make('cta_label'),
                TextInput::make('cta_url')
                    ->url(),
                KeyValue::make('payload')
                    ->keyLabel('Setting')
                    ->valueLabel('Value')
                    ->columnSpanFull(),
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
                    ->directory('cms/home-sections')
                    ->visibility('public'),
                TextInput::make('media_url')
                    ->label('External media URL')
                    ->url(),
                DateTimePicker::make('published_at')
                    ->disabled(),
            ]);
    }
}
