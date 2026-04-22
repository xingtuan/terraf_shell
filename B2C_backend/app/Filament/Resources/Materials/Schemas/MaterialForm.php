<?php

namespace App\Filament\Resources\Materials\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
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
                                self::mediaUpload('cms/materials'),
                                TextInput::make('media_url')
                                    ->label('External media URL')
                                    ->url(),
                                DateTimePicker::make('published_at')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label('Title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('headline_translations.en')
                            ->label('Headline'),
                        Textarea::make('summary_translations.en')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.en')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.en')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label('Title'),
                        TextInput::make('headline_translations.ko')
                            ->label('Headline'),
                        Textarea::make('summary_translations.ko')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.ko')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.ko')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label('Title'),
                        TextInput::make('headline_translations.zh')
                            ->label('Headline'),
                        Textarea::make('summary_translations.zh')
                            ->label('Summary')
                            ->columnSpanFull(),
                        Textarea::make('story_overview_translations.zh')
                            ->label('Story overview')
                            ->columnSpanFull(),
                        Textarea::make('science_overview_translations.zh')
                            ->label('Science overview')
                            ->columnSpanFull(),
                    ]),
                Section::make('Specifications')
                    ->description('Structured attributes shown on material detail pages.')
                    ->schema([
                        Repeater::make('specs')
                            ->relationship()
                            ->label('Specifications')
                            ->addActionLabel('Add specification')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->defaultItems(0)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('key')
                                            ->label('Internal key')
                                            ->maxLength(120),
                                        TextInput::make('unit')
                                            ->maxLength(40),
                                        TextInput::make('icon')
                                            ->maxLength(120),
                                        Select::make('status')
                                            ->options(PublishStatus::options())
                                            ->required()
                                            ->default(PublishStatus::Draft->value),
                                        self::mediaUpload('cms/material-specs'),
                                        TextInput::make('media_url')
                                            ->label('External media URL')
                                            ->url(),
                                    ]),
                                TextInput::make('label_translations.en')
                                    ->label('Label (EN)')
                                    ->required(),
                                TextInput::make('value_translations.en')
                                    ->label('Value (EN)')
                                    ->required(),
                                Textarea::make('detail_translations.en')
                                    ->label('Detail (EN)')
                                    ->columnSpanFull(),
                                TextInput::make('label_translations.ko')
                                    ->label('Label (KO)'),
                                TextInput::make('value_translations.ko')
                                    ->label('Value (KO)'),
                                Textarea::make('detail_translations.ko')
                                    ->label('Detail (KO)')
                                    ->columnSpanFull(),
                                TextInput::make('label_translations.zh')
                                    ->label('Label (ZH)'),
                                TextInput::make('value_translations.zh')
                                    ->label('Value (ZH)'),
                                Textarea::make('detail_translations.zh')
                                    ->label('Detail (ZH)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Story Sections')
                    ->description('Narrative sections used across the material storytelling page.')
                    ->schema([
                        Repeater::make('storySections')
                            ->relationship()
                            ->label('Story sections')
                            ->addActionLabel('Add story section')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->defaultItems(0)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('highlight')
                                            ->label('Default highlight')
                                            ->maxLength(255),
                                        Select::make('status')
                                            ->options(PublishStatus::options())
                                            ->required()
                                            ->default(PublishStatus::Draft->value),
                                        self::mediaUpload('cms/material-story-sections'),
                                        TextInput::make('media_url')
                                            ->label('External media URL')
                                            ->url(),
                                    ]),
                                TextInput::make('title_translations.en')
                                    ->label('Title (EN)')
                                    ->required(),
                                TextInput::make('subtitle_translations.en')
                                    ->label('Subtitle (EN)'),
                                Textarea::make('content_translations.en')
                                    ->label('Content (EN)')
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('highlight_translations.en')
                                    ->label('Highlight (EN)'),
                                TextInput::make('title_translations.ko')
                                    ->label('Title (KO)'),
                                TextInput::make('subtitle_translations.ko')
                                    ->label('Subtitle (KO)'),
                                Textarea::make('content_translations.ko')
                                    ->label('Content (KO)')
                                    ->columnSpanFull(),
                                TextInput::make('highlight_translations.ko')
                                    ->label('Highlight (KO)'),
                                TextInput::make('title_translations.zh')
                                    ->label('Title (ZH)'),
                                TextInput::make('subtitle_translations.zh')
                                    ->label('Subtitle (ZH)'),
                                Textarea::make('content_translations.zh')
                                    ->label('Content (ZH)')
                                    ->columnSpanFull(),
                                TextInput::make('highlight_translations.zh')
                                    ->label('Highlight (ZH)'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Applications')
                    ->description('Audience-specific use cases and call-to-action blocks for the material.')
                    ->schema([
                        Repeater::make('applications')
                            ->relationship()
                            ->label('Applications')
                            ->addActionLabel('Add application')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->defaultItems(0)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('cta_url')
                                            ->label('CTA URL')
                                            ->url(),
                                        Select::make('status')
                                            ->options(PublishStatus::options())
                                            ->required()
                                            ->default(PublishStatus::Draft->value),
                                        self::mediaUpload('cms/material-applications'),
                                        TextInput::make('media_url')
                                            ->label('External media URL')
                                            ->url(),
                                    ]),
                                TextInput::make('title_translations.en')
                                    ->label('Title (EN)')
                                    ->required(),
                                TextInput::make('subtitle_translations.en')
                                    ->label('Subtitle (EN)'),
                                TextInput::make('audience_translations.en')
                                    ->label('Audience (EN)'),
                                TextInput::make('cta_label_translations.en')
                                    ->label('CTA label (EN)'),
                                Textarea::make('description_translations.en')
                                    ->label('Description (EN)')
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.ko')
                                    ->label('Title (KO)'),
                                TextInput::make('subtitle_translations.ko')
                                    ->label('Subtitle (KO)'),
                                TextInput::make('audience_translations.ko')
                                    ->label('Audience (KO)'),
                                TextInput::make('cta_label_translations.ko')
                                    ->label('CTA label (KO)'),
                                Textarea::make('description_translations.ko')
                                    ->label('Description (KO)')
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.zh')
                                    ->label('Title (ZH)'),
                                TextInput::make('subtitle_translations.zh')
                                    ->label('Subtitle (ZH)'),
                                TextInput::make('audience_translations.zh')
                                    ->label('Audience (ZH)'),
                                TextInput::make('cta_label_translations.zh')
                                    ->label('CTA label (ZH)'),
                                Textarea::make('description_translations.zh')
                                    ->label('Description (ZH)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function mediaUpload(string $directory): FileUpload
    {
        return FileUpload::make('media_path')
            ->label('Uploaded media')
            ->image()
            ->disk((string) config('community.uploads.disk'))
            ->directory($directory)
            ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public');
    }
}
