<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\PublishStatus;
use App\Models\HomeSection;
use App\Support\LocalizedContent;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class HomeSectionForm
{
    private const STRUCTURED_PAYLOAD_KEYS = [
        'audience_paths',
        'business_pillars',
        'why_it_matters',
        'open_source_legacy',
        'material_story',
        'applications',
        'collaboration',
        'credibility',
        'trust_and_credibility',
        'proof_points',
        'material_family',
        'certifications',
        'hero',
        'intro',
        'science_block',
        'material_facts',
        'comparison',
        'technical_downloads',
        'pilot_projects',
        'final_cta',
        'footer',
    ];

    private const ITEM_PAYLOAD_KEYS = [
        'audience_paths',
        'business_pillars',
        'why_it_matters',
        'open_source_legacy',
        'material_story',
        'applications',
        'collaboration',
        'credibility',
        'trust_and_credibility',
        'proof_points',
        'material_family',
    ];

    private const METRIC_PAYLOAD_KEYS = [
        'hero',
        'science_block',
        'material_facts',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.section_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('page_key')
                                    ->label('Page')
                                    ->options(HomeSection::pageKeyOptions())
                                    ->required()
                                    ->default('home'),
                                TextInput::make('key')
                                    ->label(__('admin.ui.section_key'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(120)
                                    ->scopedUnique(
                                        HomeSection::class,
                                        'key',
                                        ignoreRecord: true,
                                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                            ->where('page_key', $get('page_key') ?: 'home'),
                                    )
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
                                    ->maxLength(255),
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
                                    ->maxLength(255),
                                CodeEditor::make('payload_json')
                                    ->label(__('admin.ui.payload').' JSON')
                                    ->language(Language::Json)
                                    ->formatStateUsing(fn (?HomeSection $record): string => self::encodePayloadJson($record?->payload ?? []))
                                    ->json(fn (Get $get): bool => ! self::hasStructuredPayload($get))
                                    ->hidden(fn (Get $get): bool => self::hasStructuredPayload($get))
                                    ->dehydrated(fn (Get $get): bool => ! self::hasStructuredPayload($get))
                                    ->dehydratedWhenHidden(false)
                                    ->validatedWhenNotDehydrated(false)
                                    ->columnSpanFull(),
                                DateTimePicker::make('published_at')
                                    ->label(__('admin.ui.published_at')),
                            ]),
                    ]),
                self::itemsSection(),
                self::metricsSection(),
                self::secondaryCtaSection(),
                self::collaborationStepsSection(),
                self::materialFamilyExtrasSection(),
                self::comparisonSection(),
                self::trustDisclaimerSection(),
                self::downloadsSection(),
                self::downloadLabelsSection(),
                self::certificationLabelsSection(),
                self::finalCtaSection(),
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
                                    ->maxLength(180),
                                TextInput::make('status_translations.en')
                                    ->label(__('admin.fields.status').' (EN)')
                                    ->maxLength(120),
                                Textarea::make('description_translations.en')
                                    ->label(__('admin.ui.description_en'))
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
                Section::make(__('admin.ui.footer_content'))
                    ->description(__('admin.ui.footer_payload_help'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('payload.email_value')
                                    ->label(__('admin.ui.footer_email_value'))
                                    ->maxLength(180),
                                TextInput::make('payload.email_href')
                                    ->label(__('admin.ui.footer_email_href'))
                                    ->maxLength(255),
                                TextInput::make('payload.phone_value')
                                    ->label(__('admin.ui.footer_phone_value'))
                                    ->maxLength(80),
                                TextInput::make('payload.phone_href')
                                    ->label(__('admin.ui.footer_phone_href'))
                                    ->maxLength(255),
                                TextInput::make('payload.location_href')
                                    ->label(__('admin.ui.footer_location_href'))
                                    ->maxLength(255),
                                TextInput::make('payload.privacy_href')
                                    ->label(__('admin.ui.footer_privacy_href'))
                                    ->maxLength(255),
                                TextInput::make('payload.terms_href')
                                    ->label(__('admin.ui.footer_terms_href'))
                                    ->maxLength(255),
                            ]),
                    ])
                    ->hidden(fn (Get $get): bool => ! self::isFooterSection($get)),
                self::footerLinksSection(),
                self::footerLocaleSection('en', __('admin.ui.english')),
                self::footerLocaleSection('ko', __('admin.ui.korean')),
                self::footerLocaleSection('zh', __('admin.ui.chinese')),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('title_translations.en')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.en')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull()
                            ->dehydrated(fn (Get $get): bool => ! self::isFooterSection($get)),
                        TextInput::make('cta_label_translations.en')
                            ->label(__('admin.ui.cta_label')),
                    ])
                    ->hidden(fn (Get $get): bool => self::isFooterSection($get)),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('title_translations.ko')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.ko')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.ko')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull()
                            ->dehydrated(fn (Get $get): bool => ! self::isFooterSection($get)),
                        TextInput::make('cta_label_translations.ko')
                            ->label(__('admin.ui.cta_label')),
                    ])
                    ->hidden(fn (Get $get): bool => self::isFooterSection($get)),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('title_translations.zh')
                            ->label(__('admin.ui.title')),
                        TextInput::make('subtitle_translations.zh')
                            ->label(__('admin.ui.subtitle')),
                        Textarea::make('content_translations.zh')
                            ->label(__('admin.ui.content'))
                            ->columnSpanFull()
                            ->dehydrated(fn (Get $get): bool => ! self::isFooterSection($get)),
                        TextInput::make('cta_label_translations.zh')
                            ->label(__('admin.ui.cta_label')),
                    ])
                    ->hidden(fn (Get $get): bool => self::isFooterSection($get)),
            ]);
    }

    private static function itemsSection(): Section
    {
        return Section::make('Cards / items')
            ->description('Structured cards shown by page sections. Leave optional fields blank when unused by the frontend component.')
            ->schema([
                Repeater::make('payload.items')
                    ->label('Items')
                    ->addActionLabel('Add item')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')
                            ->label('Key')
                            ->maxLength(120),
                        TextInput::make('icon')
                            ->label('Icon')
                            ->maxLength(80),
                        TextInput::make('status')
                            ->label(__('admin.fields.status'))
                            ->maxLength(120),
                        TextInput::make('status_translations.en')
                            ->label(__('admin.fields.status').' (EN)')
                            ->maxLength(120),
                        TextInput::make('status_translations.zh')
                            ->label(__('admin.fields.status').' (ZH)')
                            ->maxLength(120),
                        TextInput::make('status_translations.ko')
                            ->label(__('admin.fields.status').' (KO)')
                            ->maxLength(120),
                        TextInput::make('cta_url')
                            ->label(__('admin.ui.cta_url'))
                            ->maxLength(255),
                        TextInput::make('media_path')
                            ->label(__('admin.ui.uploaded_media'))
                            ->maxLength(255),
                        TextInput::make('media_url')
                            ->label(__('admin.ui.external_media_url'))
                            ->maxLength(255),
                        TextInput::make('label_translations.en')
                            ->label('Label (EN)')
                            ->maxLength(180),
                        TextInput::make('label_translations.zh')
                            ->label('Label (ZH)')
                            ->maxLength(180),
                        TextInput::make('label_translations.ko')
                            ->label('Label (KO)')
                            ->maxLength(180),
                        TextInput::make('title_translations.en')
                            ->label(__('admin.ui.title_en'))
                            ->maxLength(180),
                        TextInput::make('title_translations.zh')
                            ->label(__('admin.ui.title_zh'))
                            ->maxLength(180),
                        TextInput::make('title_translations.ko')
                            ->label(__('admin.ui.title_ko'))
                            ->maxLength(180),
                        TextInput::make('subtitle_translations.en')
                            ->label('Subtitle (EN)')
                            ->maxLength(180),
                        TextInput::make('subtitle_translations.zh')
                            ->label('Subtitle (ZH)')
                            ->maxLength(180),
                        TextInput::make('subtitle_translations.ko')
                            ->label('Subtitle (KO)')
                            ->maxLength(180),
                        Textarea::make('description_translations.en')
                            ->label(__('admin.ui.description_en'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description_translations.zh')
                            ->label(__('admin.ui.description_zh'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description_translations.ko')
                            ->label(__('admin.ui.description_ko'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('cta_label_translations.en')
                            ->label('CTA label (EN)')
                            ->maxLength(120),
                        TextInput::make('cta_label_translations.zh')
                            ->label('CTA label (ZH)')
                            ->maxLength(120),
                        TextInput::make('cta_label_translations.ko')
                            ->label('CTA label (KO)')
                            ->maxLength(120),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => in_array($get('key'), self::ITEM_PAYLOAD_KEYS, true));
    }

    private static function metricsSection(): Section
    {
        return Section::make('Stats / metrics')
            ->description('Use for hero indicators, material fact cards, and science metrics.')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.sheet_title_translations.en')
                            ->label('Sheet title (EN)'),
                        TextInput::make('payload.sheet_title_translations.zh')
                            ->label('Sheet title (ZH)'),
                        TextInput::make('payload.sheet_title_translations.ko')
                            ->label('Sheet title (KO)'),
                        Textarea::make('payload.note_translations.en')
                            ->label('Note (EN)')
                            ->rows(2),
                        Textarea::make('payload.note_translations.zh')
                            ->label('Note (ZH)')
                            ->rows(2),
                        Textarea::make('payload.note_translations.ko')
                            ->label('Note (KO)')
                            ->rows(2),
                    ])
                    ->visible(fn (Get $get): bool => in_array($get('key'), ['science_block', 'material_facts'], true)),
                Repeater::make('payload.metrics')
                    ->label('Metrics')
                    ->addActionLabel('Add metric')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')
                            ->label('Key')
                            ->maxLength(120),
                        TextInput::make('icon')
                            ->label('Icon')
                            ->maxLength(80),
                        TextInput::make('label_translations.en')
                            ->label('Label (EN)')
                            ->maxLength(180),
                        TextInput::make('label_translations.zh')
                            ->label('Label (ZH)')
                            ->maxLength(180),
                        TextInput::make('label_translations.ko')
                            ->label('Label (KO)')
                            ->maxLength(180),
                        TextInput::make('value_translations.en')
                            ->label('Value (EN)')
                            ->maxLength(180),
                        TextInput::make('value_translations.zh')
                            ->label('Value (ZH)')
                            ->maxLength(180),
                        TextInput::make('value_translations.ko')
                            ->label('Value (KO)')
                            ->maxLength(180),
                        Textarea::make('description_translations.en')
                            ->label(__('admin.ui.description_en'))
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('description_translations.zh')
                            ->label(__('admin.ui.description_zh'))
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('description_translations.ko')
                            ->label(__('admin.ui.description_ko'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => in_array($get('key'), self::METRIC_PAYLOAD_KEYS, true));
    }

    private static function secondaryCtaSection(): Section
    {
        return Section::make('Secondary CTA')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.secondary_cta_url')
                            ->label('Secondary CTA URL')
                            ->maxLength(255),
                        TextInput::make('payload.secondary_cta_label_translations.en')
                            ->label('Secondary CTA label (EN)'),
                        TextInput::make('payload.secondary_cta_label_translations.zh')
                            ->label('Secondary CTA label (ZH)'),
                        TextInput::make('payload.secondary_cta_label_translations.ko')
                            ->label('Secondary CTA label (KO)'),
                    ]),
            ])
            ->visible(fn (Get $get): bool => in_array($get('key'), ['hero', 'intro'], true));
    }

    private static function collaborationStepsSection(): Section
    {
        return Section::make('Collaboration process')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.process_title_translations.en')
                            ->label('Process title (EN)'),
                        TextInput::make('payload.process_title_translations.zh')
                            ->label('Process title (ZH)'),
                        TextInput::make('payload.process_title_translations.ko')
                            ->label('Process title (KO)'),
                    ]),
                Repeater::make('payload.steps')
                    ->label('Process steps')
                    ->addActionLabel('Add step')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Step (EN)'),
                        TextInput::make('label_translations.zh')->label('Step (ZH)'),
                        TextInput::make('label_translations.ko')->label('Step (KO)'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'collaboration');
    }

    private static function materialFamilyExtrasSection(): Section
    {
        return Section::make('Material family details')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.diagram.title_translations.en')->label('Diagram title (EN)'),
                        TextInput::make('payload.diagram.title_translations.zh')->label('Diagram title (ZH)'),
                        TextInput::make('payload.diagram.title_translations.ko')->label('Diagram title (KO)'),
                        Textarea::make('payload.diagram.alt_translations.en')->label('Diagram alt (EN)')->rows(2),
                        Textarea::make('payload.diagram.alt_translations.zh')->label('Diagram alt (ZH)')->rows(2),
                        Textarea::make('payload.diagram.alt_translations.ko')->label('Diagram alt (KO)')->rows(2),
                        Textarea::make('payload.diagram.caption_translations.en')->label('Caption (EN)')->rows(2),
                        Textarea::make('payload.diagram.caption_translations.zh')->label('Caption (ZH)')->rows(2),
                        Textarea::make('payload.diagram.caption_translations.ko')->label('Caption (KO)')->rows(2),
                        TextInput::make('payload.diagram.media_url')->label('Diagram media URL'),
                        TextInput::make('payload.diagram.media_url_ko')->label('Korean diagram media URL'),
                    ]),
                Repeater::make('payload.legend')
                    ->label('Legend')
                    ->addActionLabel('Add legend item')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Label (EN)'),
                        TextInput::make('label_translations.zh')->label('Label (ZH)'),
                        TextInput::make('label_translations.ko')->label('Label (KO)'),
                        Textarea::make('description_translations.en')->label('Description (EN)')->rows(2),
                        Textarea::make('description_translations.zh')->label('Description (ZH)')->rows(2),
                        Textarea::make('description_translations.ko')->label('Description (KO)')->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.badges.current_translations.en')->label('Current badge (EN)'),
                        TextInput::make('payload.badges.current_translations.zh')->label('Current badge (ZH)'),
                        TextInput::make('payload.badges.current_translations.ko')->label('Current badge (KO)'),
                        TextInput::make('payload.badges.sibling_translations.en')->label('Sibling badge (EN)'),
                        TextInput::make('payload.badges.sibling_translations.zh')->label('Sibling badge (ZH)'),
                        TextInput::make('payload.badges.sibling_translations.ko')->label('Sibling badge (KO)'),
                        TextInput::make('payload.badges.inactive_translations.en')->label('Inactive badge (EN)'),
                        TextInput::make('payload.badges.inactive_translations.zh')->label('Inactive badge (ZH)'),
                        TextInput::make('payload.badges.inactive_translations.ko')->label('Inactive badge (KO)'),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'material_family');
    }

    private static function comparisonSection(): Section
    {
        return Section::make('Comparison content')
            ->schema([
                Repeater::make('payload.columns')
                    ->label('Columns')
                    ->addActionLabel('Add column')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Column (EN)'),
                        TextInput::make('label_translations.zh')->label('Column (ZH)'),
                        TextInput::make('label_translations.ko')->label('Column (KO)'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Repeater::make('payload.rows')
                    ->label('Rows')
                    ->addActionLabel('Add comparison row')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Criteria (EN)'),
                        TextInput::make('label_translations.zh')->label('Criteria (ZH)'),
                        TextInput::make('label_translations.ko')->label('Criteria (KO)'),
                        Textarea::make('oxp_translations.en')->label('OXP (EN)')->rows(2),
                        Textarea::make('oxp_translations.zh')->label('OXP (ZH)')->rows(2),
                        Textarea::make('oxp_translations.ko')->label('OXP (KO)')->rows(2),
                        Textarea::make('plastic_translations.en')->label('Plastic (EN)')->rows(2),
                        Textarea::make('plastic_translations.zh')->label('Plastic (ZH)')->rows(2),
                        Textarea::make('plastic_translations.ko')->label('Plastic (KO)')->rows(2),
                        Textarea::make('ceramic_translations.en')->label('Ceramic (EN)')->rows(2),
                        Textarea::make('ceramic_translations.zh')->label('Ceramic (ZH)')->rows(2),
                        Textarea::make('ceramic_translations.ko')->label('Ceramic (KO)')->rows(2),
                        Textarea::make('paper_translations.en')->label('Paper (EN)')->rows(2),
                        Textarea::make('paper_translations.zh')->label('Paper (ZH)')->rows(2),
                        Textarea::make('paper_translations.ko')->label('Paper (KO)')->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        Textarea::make('payload.disclaimer_translations.en')->label('Disclaimer (EN)')->rows(2),
                        Textarea::make('payload.disclaimer_translations.zh')->label('Disclaimer (ZH)')->rows(2),
                        Textarea::make('payload.disclaimer_translations.ko')->label('Disclaimer (KO)')->rows(2),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'comparison');
    }

    private static function trustDisclaimerSection(): Section
    {
        return Section::make('Trust disclaimer')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Textarea::make('payload.disclaimer_translations.en')->label('Disclaimer (EN)')->rows(2),
                        Textarea::make('payload.disclaimer_translations.zh')->label('Disclaimer (ZH)')->rows(2),
                        Textarea::make('payload.disclaimer_translations.ko')->label('Disclaimer (KO)')->rows(2),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'trust_and_credibility');
    }

    private static function downloadsSection(): Section
    {
        return Section::make('Technical downloads')
            ->schema([
                Repeater::make('payload.downloads')
                    ->label('Downloads')
                    ->addActionLabel('Add download')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('type')
                            ->label('Type')
                            ->maxLength(120),
                        TextInput::make('url')
                            ->label('URL')
                            ->maxLength(255),
                        TextInput::make('document_url')
                            ->label('Document URL')
                            ->maxLength(255),
                        TextInput::make('title_translations.en')->label(__('admin.ui.title_en')),
                        TextInput::make('title_translations.zh')->label(__('admin.ui.title_zh')),
                        TextInput::make('title_translations.ko')->label(__('admin.ui.title_ko')),
                        Textarea::make('description_translations.en')->label(__('admin.ui.description_en'))->rows(2),
                        Textarea::make('description_translations.zh')->label(__('admin.ui.description_zh'))->rows(2),
                        Textarea::make('description_translations.ko')->label(__('admin.ui.description_ko'))->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'technical_downloads');
    }

    private static function downloadLabelsSection(): Section
    {
        return Section::make('Download empty-state labels')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.empty_title_translations.en')->label('Empty title (EN)'),
                        TextInput::make('payload.empty_title_translations.zh')->label('Empty title (ZH)'),
                        TextInput::make('payload.empty_title_translations.ko')->label('Empty title (KO)'),
                        Textarea::make('payload.empty_description_translations.en')->label('Empty description (EN)')->rows(2),
                        Textarea::make('payload.empty_description_translations.zh')->label('Empty description (ZH)')->rows(2),
                        Textarea::make('payload.empty_description_translations.ko')->label('Empty description (KO)')->rows(2),
                        TextInput::make('payload.file_label_translations.en')->label('File label (EN)'),
                        TextInput::make('payload.file_label_translations.zh')->label('File label (ZH)'),
                        TextInput::make('payload.file_label_translations.ko')->label('File label (KO)'),
                        TextInput::make('payload.on_request_label_translations.en')->label('On request label (EN)'),
                        TextInput::make('payload.on_request_label_translations.zh')->label('On request label (ZH)'),
                        TextInput::make('payload.on_request_label_translations.ko')->label('On request label (KO)'),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'technical_downloads');
    }

    private static function certificationLabelsSection(): Section
    {
        return Section::make('Certification labels')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.verified_label_translations.en')->label('Verified label (EN)'),
                        TextInput::make('payload.verified_label_translations.zh')->label('Verified label (ZH)'),
                        TextInput::make('payload.verified_label_translations.ko')->label('Verified label (KO)'),
                        TextInput::make('payload.empty_message_translations.en')->label('Empty message (EN)'),
                        TextInput::make('payload.empty_message_translations.zh')->label('Empty message (ZH)'),
                        TextInput::make('payload.empty_message_translations.ko')->label('Empty message (KO)'),
                        TextInput::make('payload.issuer_label_translations.en')->label('Issuer label (EN)'),
                        TextInput::make('payload.issuer_label_translations.zh')->label('Issuer label (ZH)'),
                        TextInput::make('payload.issuer_label_translations.ko')->label('Issuer label (KO)'),
                        TextInput::make('payload.tested_at_label_translations.en')->label('Test date label (EN)'),
                        TextInput::make('payload.tested_at_label_translations.zh')->label('Test date label (ZH)'),
                        TextInput::make('payload.tested_at_label_translations.ko')->label('Test date label (KO)'),
                        TextInput::make('payload.download_label_translations.en')->label('Download label (EN)'),
                        TextInput::make('payload.download_label_translations.zh')->label('Download label (ZH)'),
                        TextInput::make('payload.download_label_translations.ko')->label('Download label (KO)'),
                        TextInput::make('payload.status_labels.certified_translations.en')->label('Certified (EN)'),
                        TextInput::make('payload.status_labels.certified_translations.zh')->label('Certified (ZH)'),
                        TextInput::make('payload.status_labels.certified_translations.ko')->label('Certified (KO)'),
                        TextInput::make('payload.status_labels.tested_translations.en')->label('Tested (EN)'),
                        TextInput::make('payload.status_labels.tested_translations.zh')->label('Tested (ZH)'),
                        TextInput::make('payload.status_labels.tested_translations.ko')->label('Tested (KO)'),
                        TextInput::make('payload.status_labels.in_testing_translations.en')->label('In testing (EN)'),
                        TextInput::make('payload.status_labels.in_testing_translations.zh')->label('In testing (ZH)'),
                        TextInput::make('payload.status_labels.in_testing_translations.ko')->label('In testing (KO)'),
                        TextInput::make('payload.status_labels.pending_translations.en')->label('Pending (EN)'),
                        TextInput::make('payload.status_labels.pending_translations.zh')->label('Pending (ZH)'),
                        TextInput::make('payload.status_labels.pending_translations.ko')->label('Pending (KO)'),
                        TextInput::make('payload.status_labels.demo_translations.en')->label('Demo (EN)'),
                        TextInput::make('payload.status_labels.demo_translations.zh')->label('Demo (ZH)'),
                        TextInput::make('payload.status_labels.demo_translations.ko')->label('Demo (KO)'),
                        TextInput::make('payload.status_labels.not_applicable_translations.en')->label('Not applicable (EN)'),
                        TextInput::make('payload.status_labels.not_applicable_translations.zh')->label('Not applicable (ZH)'),
                        TextInput::make('payload.status_labels.not_applicable_translations.ko')->label('Not applicable (KO)'),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'certifications');
    }

    private static function finalCtaSection(): Section
    {
        return Section::make('Final CTA actions')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.primary_cta_url')
                            ->label('Primary CTA URL')
                            ->maxLength(255),
                        TextInput::make('payload.secondary_cta_url')
                            ->label('Secondary CTA URL')
                            ->maxLength(255),
                        TextInput::make('payload.primary_cta_label_translations.en')
                            ->label('Primary CTA label (EN)'),
                        TextInput::make('payload.primary_cta_label_translations.zh')
                            ->label('Primary CTA label (ZH)'),
                        TextInput::make('payload.primary_cta_label_translations.ko')
                            ->label('Primary CTA label (KO)'),
                        TextInput::make('payload.secondary_cta_label_translations.en')
                            ->label('Secondary CTA label (EN)'),
                        TextInput::make('payload.secondary_cta_label_translations.zh')
                            ->label('Secondary CTA label (ZH)'),
                        TextInput::make('payload.secondary_cta_label_translations.ko')
                            ->label('Secondary CTA label (KO)'),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'final_cta');
    }

    private static function footerLocaleSection(string $locale, string $label): Section
    {
        return Section::make($label)
            ->schema([
                Textarea::make("content_translations.{$locale}")
                    ->label(__('admin.ui.footer_description'))
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make("payload.home_translations.{$locale}")
                    ->label(__('admin.ui.footer_home_label')),
                TextInput::make("payload.material_translations.{$locale}")
                    ->label(__('admin.ui.footer_material_label')),
                TextInput::make("payload.store_translations.{$locale}")
                    ->label(__('admin.ui.footer_store_label')),
                TextInput::make("payload.b2b_translations.{$locale}")
                    ->label(__('admin.ui.footer_b2b_label')),
                TextInput::make("payload.community_translations.{$locale}")
                    ->label(__('admin.ui.footer_community_link_label')),
                TextInput::make("payload.contact_translations.{$locale}")
                    ->label(__('admin.ui.footer_contact_label')),
                TextInput::make("payload.explore_translations.{$locale}")
                    ->label(__('admin.ui.footer_explore_heading')),
                TextInput::make("payload.business_translations.{$locale}")
                    ->label(__('admin.ui.footer_business_heading')),
                TextInput::make("payload.community_label_translations.{$locale}")
                    ->label(__('admin.ui.footer_community_heading')),
                TextInput::make("payload.material_sheet_translations.{$locale}")
                    ->label(__('admin.ui.footer_material_sheet_label')),
                TextInput::make("payload.sample_request_translations.{$locale}")
                    ->label(__('admin.ui.footer_sample_request_label')),
                TextInput::make("payload.product_development_translations.{$locale}")
                    ->label(__('admin.ui.footer_product_development_label')),
                TextInput::make("payload.idea_support_translations.{$locale}")
                    ->label(__('admin.ui.footer_idea_support_label')),
                TextInput::make("payload.concept_fund_translations.{$locale}")
                    ->label(__('admin.ui.footer_concept_fund_label')),
                TextInput::make("payload.email_label_translations.{$locale}")
                    ->label(__('admin.ui.footer_email_label')),
                TextInput::make("payload.phone_label_translations.{$locale}")
                    ->label(__('admin.ui.footer_phone_label')),
                TextInput::make("payload.location_label_translations.{$locale}")
                    ->label(__('admin.ui.footer_location_label')),
                TextInput::make("payload.location_value_translations.{$locale}")
                    ->label(__('admin.ui.footer_location_value')),
                TextInput::make("payload.copyright_translations.{$locale}")
                    ->label(__('admin.ui.footer_copyright')),
                TextInput::make("payload.privacy_translations.{$locale}")
                    ->label(__('admin.ui.footer_privacy_label')),
                TextInput::make("payload.terms_translations.{$locale}")
                    ->label(__('admin.ui.footer_terms_label')),
            ])
            ->columns(2)
            ->hidden(fn (Get $get): bool => ! self::isFooterSection($get));
    }

    private static function footerLinksSection(): Section
    {
        return Section::make('Footer link groups')
            ->schema([
                Repeater::make('payload.social_links')
                    ->label('Social links')
                    ->addActionLabel('Add social link')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Label (EN)')->maxLength(120),
                        TextInput::make('label_translations.zh')->label('Label (ZH)')->maxLength(120),
                        TextInput::make('label_translations.ko')->label('Label (KO)')->maxLength(120),
                        TextInput::make('href')->label('URL')->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Repeater::make('payload.legal_links')
                    ->label('Legal links')
                    ->addActionLabel('Add legal link')
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label('Label (EN)')->maxLength(120),
                        TextInput::make('label_translations.zh')->label('Label (ZH)')->maxLength(120),
                        TextInput::make('label_translations.ko')->label('Label (KO)')->maxLength(120),
                        TextInput::make('href')->label('URL')->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->hidden(fn (Get $get): bool => ! self::isFooterSection($get));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function applyPayloadState(array $data, array $state, ?HomeSection $record = null): array
    {
        $data = self::normalizeLocalizedArrays($data);
        $key = $data['key'] ?? $record?->key;

        if (is_string($key) && self::hasStructuredPayloadKey($key)) {
            if (isset($state['payload']) && is_array($state['payload'])) {
                $data['payload'] = self::mergePayloadState(
                    is_array($record?->payload) ? $record->payload : [],
                    self::normalizeLocalizedArrays($state['payload'])
                );
            }

            unset($data['payload_json']);

            return $data;
        }

        if (array_key_exists('payload_json', $state)) {
            $payload = self::decodePayloadJson($state['payload_json'] ?? null);
            $data['payload'] = is_array($payload) ? self::normalizeLocalizedArrays($payload) : $payload;
        }

        unset($data['payload_json']);

        return $data;
    }

    public static function hasStructuredPayloadKey(?string $key): bool
    {
        return in_array($key, self::STRUCTURED_PAYLOAD_KEYS, true);
    }

    private static function encodePayloadJson(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '{}';
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodePayloadJson(mixed $payload): ?array
    {
        if (! is_string($payload) || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeLocalizedArrays(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $data[$key] = is_string($key) && str_ends_with($key, '_translations')
                ? self::normalizeTranslationSet($value)
                : self::normalizeLocalizedArrays($value);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    private static function normalizeTranslationSet(array $translations): array
    {
        $normalized = [];

        foreach (LocalizedContent::supportedLocales() as $locale) {
            $value = $translations[$locale] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value !== '') {
                $normalized[$locale] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergePayloadState(array $existing, array $incoming): array
    {
        if (array_is_list($incoming)) {
            return array_values(array_map(
                fn (mixed $value, int $index): mixed => is_array($value)
                    ? self::mergePayloadState(
                        is_array($existing[$index] ?? null) ? $existing[$index] : [],
                        $value
                    )
                    : $value,
                $incoming,
                array_keys($incoming)
            ));
        }

        $merged = $existing;

        foreach ($incoming as $key => $value) {
            if (is_array($value)) {
                if (is_string($key) && str_ends_with($key, '_translations')) {
                    $merged[$key] = $value;

                    continue;
                }

                $merged[$key] = self::mergePayloadState(
                    is_array($existing[$key] ?? null) ? $existing[$key] : [],
                    $value
                );

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private static function isFooterSection(Get $get): bool
    {
        return $get('key') === 'footer';
    }

    private static function hasStructuredPayload(Get $get): bool
    {
        return self::hasStructuredPayloadKey($get('key'));
    }
}
