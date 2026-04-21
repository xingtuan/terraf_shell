<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('key')
                                    ->required(),
                                Select::make('status')
                                    ->options(PublishStatus::options())
                                    ->required()
                                    ->default(PublishStatus::Draft->value),
                                TextInput::make('cta_url')
                                    ->label('CTA URL')
                                    ->url(),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label('Uploaded media')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/home-sections')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
                                TextInput::make('media_url')
                                    ->label('External media URL')
                                    ->url(),
                                KeyValue::make('payload')
                                    ->keyLabel('Setting')
                                    ->valueLabel('Value')
                                    ->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label('Title'),
                        TextInput::make('subtitle_translations.en')
                            ->label('Subtitle'),
                        Textarea::make('content_translations.en')
                            ->label('Content')
                            ->columnSpanFull(),
                        TextInput::make('cta_label_translations.en')
                            ->label('CTA label'),
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
                        TextInput::make('cta_label_translations.ko')
                            ->label('CTA label'),
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
                        TextInput::make('cta_label_translations.zh')
                            ->label('CTA label'),
                    ]),
            ]);
    }
}
