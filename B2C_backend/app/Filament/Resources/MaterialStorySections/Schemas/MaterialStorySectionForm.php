<?php

namespace App\Filament\Resources\MaterialStorySections\Schemas;

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

class MaterialStorySectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.story_section_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('material_id')
                                    ->label(__('admin.resources.materials'))
                                    ->relationship('material', 'title')
                                    ->required(),
                                TextInput::make('highlight')
                                    ->label(__('admin.ui.default_highlight')),
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
                                    ->directory('cms/material-story-sections')
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
                        TextInput::make('title_translations.en')
                            ->label(__('admin.ui.title'))
                            ->required(),
                        TextInput::make('subtitle_translations.en')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.en')
                            ->label(__('admin.ui.highlight')),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.ko')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.ko')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.ko')
                            ->label(__('admin.ui.highlight')),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.zh')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.zh')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull(),
                        TextInput::make('highlight_translations.zh')
                            ->label(__('admin.ui.highlight')),
                    ]),
            ]);
    }
}
