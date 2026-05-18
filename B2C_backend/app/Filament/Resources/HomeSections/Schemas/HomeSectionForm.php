<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.section_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('admin.ui.section_key'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(120)
                                    ->unique(ignoreRecord: true)
                                    ->helperText(__('admin.ui.section_key_helper')),
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
                                TextInput::make('cta_url')
                                    ->label(__('admin.ui.cta_url'))
                                    ->url(),
                                TextInput::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label(__('admin.ui.uploaded_media'))
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/home-sections')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
                                TextInput::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->url(),
                                KeyValue::make('payload')
                                    ->label(__('admin.ui.payload'))
                                    ->keyLabel(__('admin.ui.setting'))
                                    ->valueLabel(__('admin.ui.value'))
                                    ->hidden(fn (Get $get): bool => $get('key') === 'pilot_projects')
                                    ->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->label(__('admin.ui.published_at')),
                            ]),
                    ]),
                Section::make(__('admin.ui.pilot_projects'))
                    ->description(__('admin.ui.pilot_projects_payload_help'))
                    ->schema([
                        Repeater::make('payload.items')
                            ->label(__('admin.ui.pilot_project_cards'))
                            ->addActionLabel(__('admin.ui.add_pilot_project_card'))
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('title_translations.en')
                                    ->label(__('admin.ui.title_en'))
                                    ->required()
                                    ->maxLength(180),
                                TextInput::make('status_translations.en')
                                    ->label(__('admin.fields.status').' (EN)')
                                    ->required()
                                    ->maxLength(120),
                                Textarea::make('description_translations.en')
                                    ->label(__('admin.ui.description_en'))
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.ko')
                                    ->label(__('admin.ui.title_ko'))
                                    ->maxLength(180),
                                TextInput::make('status_translations.ko')
                                    ->label(__('admin.fields.status').' (KO)')
                                    ->maxLength(120),
                                Textarea::make('description_translations.ko')
                                    ->label(__('admin.ui.description_ko'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.zh')
                                    ->label(__('admin.ui.title_zh'))
                                    ->maxLength(180),
                                TextInput::make('status_translations.zh')
                                    ->label(__('admin.fields.status').' (ZH)')
                                    ->maxLength(120),
                                Textarea::make('description_translations.zh')
                                    ->label(__('admin.ui.description_zh'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get): bool => $get('key') === 'pilot_projects'),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.en')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull(),
                        TextInput::make('cta_label_translations.en')
                            ->label(__('admin.ui.cta_label')),
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
                        TextInput::make('cta_label_translations.ko')
                            ->label(__('admin.ui.cta_label')),
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
                        TextInput::make('cta_label_translations.zh')
                            ->label(__('admin.ui.cta_label')),
                    ]),
            ]);
    }
}
