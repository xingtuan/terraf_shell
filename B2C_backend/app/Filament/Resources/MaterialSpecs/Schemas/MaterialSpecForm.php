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
                Section::make(__('admin.ui.specification_settings'))
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
                                    ->label(__('admin.ui.uploaded_media'))
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/material-specs')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
                                TextInput::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->url(),
                                DateTimePicker::make('published_at')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('label_translations.en')
                            ->label(__('admin.ui.label'))
                            ->required(),
                        TextInput::make('value_translations.en')
                            ->label(__('admin.ui.value'))
                            ->required(),
                        Textarea::make('detail_translations.en')
                            ->label(__('admin.ui.detail'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('label_translations.ko')
                            ->label(__('admin.ui.label')),
                        TextInput::make('value_translations.ko')
                            ->label(__('admin.ui.value')),
                        Textarea::make('detail_translations.ko')
                            ->label(__('admin.ui.detail'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('label_translations.zh')
                            ->label(__('admin.ui.label')),
                        TextInput::make('value_translations.zh')
                            ->label(__('admin.ui.value')),
                        Textarea::make('detail_translations.zh')
                            ->label(__('admin.ui.detail'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
