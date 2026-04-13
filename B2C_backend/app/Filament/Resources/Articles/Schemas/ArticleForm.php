<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('excerpt')
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('category'),
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
                    ->directory('cms/articles')
                    ->visibility('public'),
                TextInput::make('media_url')
                    ->label('External media URL')
                    ->url(),
                DateTimePicker::make('published_at')
                    ->disabled(),
            ]);
    }
}
