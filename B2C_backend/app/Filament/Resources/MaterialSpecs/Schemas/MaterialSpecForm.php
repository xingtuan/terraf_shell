<?php

namespace App\Filament\Resources\MaterialSpecs\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\AdminUploadStorage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                                    ->label(__('admin.resources.materials'))
                                    ->relationship('material', 'title')
                                    ->required(),
                                TextInput::make('key'),
                                TextInput::make('unit')
                                    ->label(__('admin.ui.unit')),
                                TextInput::make('icon')
                                    ->label(__('admin.ui.icon')),
                                Select::make('status')
                                    ->label(__('admin.fields.status'))
                                    ->options(PublishStatus::options())
                                    ->required()
                                    ->default(PublishStatus::Draft->value),
                                Toggle::make('is_seeded')
                                    ->label(__('admin.ui.seeded_demo_content'))
                                    ->helperText(__('admin.ui.seeded_demo_content_help'))
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label(__('admin.ui.uploaded_media'))
                                    ->image()
                                    ->disk(fn (): string => AdminUploadStorage::disk())
                                    ->directory('cms/material-specs')
                                    ->visibility(fn (): string => AdminUploadStorage::visibility()),
                                TextInput::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->url(),
                                DateTimePicker::make('published_at')
                                    ->label(__('admin.ui.published_at')),
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
