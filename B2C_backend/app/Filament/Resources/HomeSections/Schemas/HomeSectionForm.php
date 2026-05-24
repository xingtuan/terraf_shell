<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\AdminUploadStorage;
use App\Models\HomeSection;
use App\Support\HomeSectionPayloadNormalizer;
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
        'product_grid',
        'store_faq',
        'open_concepts',
        'inquiry_form',
        'pilot_projects',
        'details',
        'process',
        'after_submit',
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
        'details',
        'process',
        'after_submit',
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
                                    ->label(self::field('page'))
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
                                Toggle::make('show_on_frontend')
                                    ->label(self::field('show_on_frontend'))
                                    ->helperText(self::helpText('show_on_frontend'))
                                    ->default(false)
                                    ->formatStateUsing(fn (?HomeSection $record): bool => PublishStatus::normalize($record?->status) === PublishStatus::Published)
                                    ->dehydrated(false)
                                    ->validatedWhenNotDehydrated(false),
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
                                    ->disk(fn (): string => AdminUploadStorage::disk())
                                    ->directory('cms/home-sections')
                                    ->visibility(fn (): string => AdminUploadStorage::visibility()),
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
                self::productGridLabelsSection(),
                self::credibilityBenefitsSection(),
                self::storeFaqSection(),
                self::openConceptsSection(),
                self::contactDetailsSection(),
                self::contactInquiryFormSection(),
                self::collaborationStepsSection(),
                self::materialFamilyExtrasSection(),
                self::comparisonSection(),
                self::trustDisclaimerSection(),
                self::downloadsSection(),
                self::downloadLabelsSection(),
                self::certificationLabelsSection(),
                self::certificationRecordsSection(),
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
                            ->dehydrated(fn (Get $get): bool => $get('key') === 'pilot_projects')
                            ->schema([
                                TextInput::make('title_translations.en')
                                    ->label(__('admin.ui.title_en'))
                                    ->maxLength(180),
                                TextInput::make('status_translations.en')
                                    ->label(self::localizedField('status', 'en'))
                                    ->maxLength(120),
                                Textarea::make('description_translations.en')
                                    ->label(__('admin.ui.description_en'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.ko')
                                    ->label(__('admin.ui.title_ko'))
                                    ->maxLength(180),
                                TextInput::make('status_translations.ko')
                                    ->label(self::localizedField('status', 'ko'))
                                    ->maxLength(120),
                                Textarea::make('description_translations.ko')
                                    ->label(__('admin.ui.description_ko'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('title_translations.zh')
                                    ->label(__('admin.ui.title_zh'))
                                    ->maxLength(180),
                                TextInput::make('status_translations.zh')
                                    ->label(self::localizedField('status', 'zh'))
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
        return Section::make(self::sectionTitle('cards_items'))
            ->description(self::helpText('cards_items'))
            ->schema([
                Repeater::make('payload.items')
                    ->label(self::field('items'))
                    ->addActionLabel(self::actionLabel('add_item'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->dehydrated(fn (Get $get): bool => in_array($get('key'), self::ITEM_PAYLOAD_KEYS, true))
                    ->schema([
                        TextInput::make('key')
                            ->label(self::field('key'))
                            ->maxLength(120),
                        TextInput::make('icon')
                            ->label(self::field('icon'))
                            ->maxLength(80),
                        TextInput::make('status')
                            ->label(__('admin.fields.status'))
                            ->maxLength(120),
                        TextInput::make('status_translations.en')
                            ->label(self::localizedField('status', 'en'))
                            ->maxLength(120),
                        TextInput::make('status_translations.zh')
                            ->label(self::localizedField('status', 'zh'))
                            ->maxLength(120),
                        TextInput::make('status_translations.ko')
                            ->label(self::localizedField('status', 'ko'))
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
                            ->label(__('admin.ui.label_en'))
                            ->maxLength(180),
                        TextInput::make('label_translations.zh')
                            ->label(__('admin.ui.label_zh'))
                            ->maxLength(180),
                        TextInput::make('label_translations.ko')
                            ->label(__('admin.ui.label_ko'))
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
                            ->label(__('admin.ui.subtitle_en'))
                            ->maxLength(180),
                        TextInput::make('subtitle_translations.zh')
                            ->label(__('admin.ui.subtitle_zh'))
                            ->maxLength(180),
                        TextInput::make('subtitle_translations.ko')
                            ->label(__('admin.ui.subtitle_ko'))
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
                            ->label(__('admin.ui.cta_label_en'))
                            ->maxLength(120),
                        TextInput::make('cta_label_translations.zh')
                            ->label(__('admin.ui.cta_label_zh'))
                            ->maxLength(120),
                        TextInput::make('cta_label_translations.ko')
                            ->label(__('admin.ui.cta_label_ko'))
                            ->maxLength(120),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => in_array($get('key'), self::ITEM_PAYLOAD_KEYS, true));
    }

    private static function metricsSection(): Section
    {
        return Section::make(self::sectionTitle('stats_metrics'))
            ->description(self::helpText('stats_metrics'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.sheet_title_translations.en')
                            ->label(self::localizedField('sheet_title', 'en')),
                        TextInput::make('payload.sheet_title_translations.zh')
                            ->label(self::localizedField('sheet_title', 'zh')),
                        TextInput::make('payload.sheet_title_translations.ko')
                            ->label(self::localizedField('sheet_title', 'ko')),
                        Textarea::make('payload.note_translations.en')
                            ->label(self::localizedField('note', 'en'))
                            ->rows(2),
                        Textarea::make('payload.note_translations.zh')
                            ->label(self::localizedField('note', 'zh'))
                            ->rows(2),
                        Textarea::make('payload.note_translations.ko')
                            ->label(self::localizedField('note', 'ko'))
                            ->rows(2),
                    ])
                    ->visible(fn (Get $get): bool => in_array($get('key'), ['science_block', 'material_facts'], true)),
                Repeater::make('payload.metrics')
                    ->label(self::field('metrics'))
                    ->addActionLabel(self::actionLabel('add_metric'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')
                            ->label(self::field('key'))
                            ->maxLength(120),
                        TextInput::make('icon')
                            ->label(self::field('icon'))
                            ->maxLength(80),
                        TextInput::make('label_translations.en')
                            ->label(__('admin.ui.label_en'))
                            ->maxLength(180),
                        TextInput::make('label_translations.zh')
                            ->label(__('admin.ui.label_zh'))
                            ->maxLength(180),
                        TextInput::make('label_translations.ko')
                            ->label(__('admin.ui.label_ko'))
                            ->maxLength(180),
                        TextInput::make('value_translations.en')
                            ->label(__('admin.ui.value_en'))
                            ->maxLength(180),
                        TextInput::make('value_translations.zh')
                            ->label(__('admin.ui.value_zh'))
                            ->maxLength(180),
                        TextInput::make('value_translations.ko')
                            ->label(__('admin.ui.value_ko'))
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
        return Section::make(self::sectionTitle('secondary_cta'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.secondary_cta_url')
                            ->label(self::field('secondary_cta_url'))
                            ->maxLength(255),
                        TextInput::make('payload.secondary_cta_label_translations.en')
                            ->label(self::localizedField('secondary_cta_label', 'en')),
                        TextInput::make('payload.secondary_cta_label_translations.zh')
                            ->label(self::localizedField('secondary_cta_label', 'zh')),
                        TextInput::make('payload.secondary_cta_label_translations.ko')
                            ->label(self::localizedField('secondary_cta_label', 'ko')),
                    ]),
            ])
            ->visible(fn (Get $get): bool => in_array($get('key'), ['hero', 'intro'], true));
    }

    private static function productGridLabelsSection(): Section
    {
        return Section::make(self::sectionTitle('store_product_grid_labels'))
            ->description(self::helpText('store_product_grid_labels'))
            ->schema([
                Grid::make(3)
                    ->schema(self::localizedPayloadComponents([
                        'price_prefix',
                        'availability_label',
                        'category_quick_filter_label',
                        'filters_title',
                        'search_label',
                        'search_placeholder',
                        'all_option',
                        'filter_hint',
                        'category_hint',
                        'active_filters_label',
                        'remove_filter_label',
                        'sort_label',
                        'stock_label',
                        'price_label',
                        'min_price',
                        'max_price',
                        'apply_filters',
                        'clear_all',
                        'result_label',
                        'search_result_title',
                        'filtered_products_title',
                        'all_products_title',
                        'showing_label',
                        'empty_title',
                        'empty_description',
                        'empty_action',
                        'error_title',
                        'error_description',
                        'retry_action',
                        'attribute_label',
                    ], [
                        'filter_hint',
                        'category_hint',
                        'empty_description',
                        'error_description',
                    ])),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'product_grid');
    }

    private static function credibilityBenefitsSection(): Section
    {
        return Section::make(self::sectionTitle('credibility_benefits'))
            ->description(self::helpText('credibility_benefits'))
            ->schema([
                Repeater::make('payload.benefits')
                    ->label(self::field('benefits'))
                    ->addActionLabel(self::actionLabel('add_benefit'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        Textarea::make('description_translations.en')->label(__('admin.ui.description_en'))->rows(2),
                        Textarea::make('description_translations.zh')->label(__('admin.ui.description_zh'))->rows(2),
                        Textarea::make('description_translations.ko')->label(__('admin.ui.description_ko'))->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'credibility');
    }

    private static function storeFaqSection(): Section
    {
        return Section::make(self::sectionTitle('store_faq_items'))
            ->schema([
                Repeater::make('payload.items')
                    ->label(self::field('faq_items'))
                    ->addActionLabel(self::actionLabel('add_faq_item'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->dehydrated(fn (Get $get): bool => $get('key') === 'store_faq')
                    ->schema([
                        TextInput::make('question_translations.en')->label(self::localizedField('question', 'en'))->maxLength(240),
                        TextInput::make('question_translations.zh')->label(self::localizedField('question', 'zh'))->maxLength(240),
                        TextInput::make('question_translations.ko')->label(self::localizedField('question', 'ko'))->maxLength(240),
                        Textarea::make('answer_translations.en')->label(self::localizedField('answer', 'en'))->rows(3),
                        Textarea::make('answer_translations.zh')->label(self::localizedField('answer', 'zh'))->rows(3),
                        Textarea::make('answer_translations.ko')->label(self::localizedField('answer', 'ko'))->rows(3),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'store_faq');
    }

    private static function openConceptsSection(): Section
    {
        return Section::make(self::sectionTitle('community_open_concepts_labels'))
            ->schema([
                Grid::make(3)
                    ->schema(self::localizedPayloadComponents([
                        'focus_label',
                        'stage_label',
                        'support_label',
                        'cta_primary_label',
                        'cta_secondary_label',
                    ])),
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.cta_primary_url')
                            ->label(self::field('primary_cta_url'))
                            ->maxLength(255),
                        TextInput::make('payload.cta_secondary_url')
                            ->label(self::field('secondary_cta_url'))
                            ->maxLength(255),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'open_concepts');
    }

    private static function contactDetailsSection(): Section
    {
        return Section::make(self::sectionTitle('contact_detail_cards'))
            ->schema([
                Repeater::make('payload.cards')
                    ->label(self::field('cards'))
                    ->addActionLabel(self::actionLabel('add_card'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(self::localizedField('label', 'en'))->maxLength(120),
                        TextInput::make('label_translations.zh')->label(self::localizedField('label', 'zh'))->maxLength(120),
                        TextInput::make('label_translations.ko')->label(self::localizedField('label', 'ko'))->maxLength(120),
                        TextInput::make('value_translations.en')->label(self::localizedField('value', 'en'))->maxLength(180),
                        TextInput::make('value_translations.zh')->label(self::localizedField('value', 'zh'))->maxLength(180),
                        TextInput::make('value_translations.ko')->label(self::localizedField('value', 'ko'))->maxLength(180),
                        Textarea::make('detail_translations.en')->label(self::localizedField('detail', 'en'))->rows(2),
                        Textarea::make('detail_translations.zh')->label(self::localizedField('detail', 'zh'))->rows(2),
                        Textarea::make('detail_translations.ko')->label(self::localizedField('detail', 'ko'))->rows(2),
                        Select::make('href_type')
                            ->label(self::field('href_type'))
                            ->options([
                                'email' => 'Email',
                                'phone' => 'Phone',
                                'text' => 'Text',
                            ])
                            ->default('text'),
                        TextInput::make('href')
                            ->label(self::field('href'))
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema(self::localizedPayloadComponents(['response'], ['response'])),
            ])
            ->visible(fn (Get $get): bool => $get('page_key') === 'contact' && $get('key') === 'details');
    }

    private static function contactInquiryFormSection(): Section
    {
        return Section::make(self::sectionTitle('contact_inquiry_form'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.form_anchor_id')
                            ->label(self::field('form_anchor_id'))
                            ->maxLength(120),
                    ]),
                Grid::make(3)
                    ->schema(self::localizedPayloadComponents([
                        'submit_button_label',
                        'submit_success_message',
                        'privacy_note',
                    ], [
                        'submit_success_message',
                        'privacy_note',
                    ])),
                Repeater::make('payload.topic_options')
                    ->label(self::field('topic_options'))
                    ->addActionLabel(self::actionLabel('add_topic_option'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(self::localizedField('label', 'en'))->maxLength(160),
                        TextInput::make('label_translations.zh')->label(self::localizedField('label', 'zh'))->maxLength(160),
                        TextInput::make('label_translations.ko')->label(self::localizedField('label', 'ko'))->maxLength(160),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('page_key') === 'contact' && $get('key') === 'inquiry_form');
    }

    private static function collaborationStepsSection(): Section
    {
        return Section::make(self::sectionTitle('collaboration_process'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.process_title_translations.en')
                            ->label(self::localizedField('process_title', 'en')),
                        TextInput::make('payload.process_title_translations.zh')
                            ->label(self::localizedField('process_title', 'zh')),
                        TextInput::make('payload.process_title_translations.ko')
                            ->label(self::localizedField('process_title', 'ko')),
                    ]),
                Repeater::make('payload.steps')
                    ->label(self::field('process_steps'))
                    ->addActionLabel(self::actionLabel('add_step'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(self::localizedField('step', 'en')),
                        TextInput::make('label_translations.zh')->label(self::localizedField('step', 'zh')),
                        TextInput::make('label_translations.ko')->label(self::localizedField('step', 'ko')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'collaboration');
    }

    private static function materialFamilyExtrasSection(): Section
    {
        return Section::make(self::sectionTitle('material_family_details'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.diagram.title_translations.en')->label(self::localizedField('diagram_title', 'en')),
                        TextInput::make('payload.diagram.title_translations.zh')->label(self::localizedField('diagram_title', 'zh')),
                        TextInput::make('payload.diagram.title_translations.ko')->label(self::localizedField('diagram_title', 'ko')),
                        Textarea::make('payload.diagram.alt_translations.en')->label(self::localizedField('diagram_alt', 'en'))->rows(2),
                        Textarea::make('payload.diagram.alt_translations.zh')->label(self::localizedField('diagram_alt', 'zh'))->rows(2),
                        Textarea::make('payload.diagram.alt_translations.ko')->label(self::localizedField('diagram_alt', 'ko'))->rows(2),
                        Textarea::make('payload.diagram.caption_translations.en')->label(self::localizedField('caption', 'en'))->rows(2),
                        Textarea::make('payload.diagram.caption_translations.zh')->label(self::localizedField('caption', 'zh'))->rows(2),
                        Textarea::make('payload.diagram.caption_translations.ko')->label(self::localizedField('caption', 'ko'))->rows(2),
                        TextInput::make('payload.diagram.media_url')->label(self::field('diagram_media_url')),
                        TextInput::make('payload.diagram.media_url_ko')->label(self::field('korean_diagram_media_url')),
                    ]),
                Repeater::make('payload.legend')
                    ->label(self::field('legend'))
                    ->addActionLabel(self::actionLabel('add_legend_item'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(__('admin.ui.label_en')),
                        TextInput::make('label_translations.zh')->label(__('admin.ui.label_zh')),
                        TextInput::make('label_translations.ko')->label(__('admin.ui.label_ko')),
                        Textarea::make('description_translations.en')->label(__('admin.ui.description_en'))->rows(2),
                        Textarea::make('description_translations.zh')->label(__('admin.ui.description_zh'))->rows(2),
                        Textarea::make('description_translations.ko')->label(__('admin.ui.description_ko'))->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.badges.current_translations.en')->label(self::localizedField('current_badge', 'en')),
                        TextInput::make('payload.badges.current_translations.zh')->label(self::localizedField('current_badge', 'zh')),
                        TextInput::make('payload.badges.current_translations.ko')->label(self::localizedField('current_badge', 'ko')),
                        TextInput::make('payload.badges.sibling_translations.en')->label(self::localizedField('sibling_badge', 'en')),
                        TextInput::make('payload.badges.sibling_translations.zh')->label(self::localizedField('sibling_badge', 'zh')),
                        TextInput::make('payload.badges.sibling_translations.ko')->label(self::localizedField('sibling_badge', 'ko')),
                        TextInput::make('payload.badges.inactive_translations.en')->label(self::localizedField('inactive_badge', 'en')),
                        TextInput::make('payload.badges.inactive_translations.zh')->label(self::localizedField('inactive_badge', 'zh')),
                        TextInput::make('payload.badges.inactive_translations.ko')->label(self::localizedField('inactive_badge', 'ko')),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'material_family');
    }

    private static function comparisonSection(): Section
    {
        return Section::make(self::sectionTitle('comparison_content'))
            ->schema([
                Repeater::make('payload.columns')
                    ->label(self::field('columns'))
                    ->addActionLabel(self::actionLabel('add_column'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(self::localizedField('column', 'en')),
                        TextInput::make('label_translations.zh')->label(self::localizedField('column', 'zh')),
                        TextInput::make('label_translations.ko')->label(self::localizedField('column', 'ko')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Repeater::make('payload.rows')
                    ->label(self::field('rows'))
                    ->addActionLabel(self::actionLabel('add_comparison_row'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(self::localizedField('criteria', 'en')),
                        TextInput::make('label_translations.zh')->label(self::localizedField('criteria', 'zh')),
                        TextInput::make('label_translations.ko')->label(self::localizedField('criteria', 'ko')),
                        Textarea::make('oxp_translations.en')->label(self::localizedField('oxp', 'en'))->rows(2),
                        Textarea::make('oxp_translations.zh')->label(self::localizedField('oxp', 'zh'))->rows(2),
                        Textarea::make('oxp_translations.ko')->label(self::localizedField('oxp', 'ko'))->rows(2),
                        Textarea::make('plastic_translations.en')->label(self::localizedField('plastic', 'en'))->rows(2),
                        Textarea::make('plastic_translations.zh')->label(self::localizedField('plastic', 'zh'))->rows(2),
                        Textarea::make('plastic_translations.ko')->label(self::localizedField('plastic', 'ko'))->rows(2),
                        Textarea::make('ceramic_translations.en')->label(self::localizedField('ceramic', 'en'))->rows(2),
                        Textarea::make('ceramic_translations.zh')->label(self::localizedField('ceramic', 'zh'))->rows(2),
                        Textarea::make('ceramic_translations.ko')->label(self::localizedField('ceramic', 'ko'))->rows(2),
                        Textarea::make('paper_translations.en')->label(self::localizedField('paper', 'en'))->rows(2),
                        Textarea::make('paper_translations.zh')->label(self::localizedField('paper', 'zh'))->rows(2),
                        Textarea::make('paper_translations.ko')->label(self::localizedField('paper', 'ko'))->rows(2),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        Textarea::make('payload.disclaimer_translations.en')->label(self::localizedField('disclaimer', 'en'))->rows(2),
                        Textarea::make('payload.disclaimer_translations.zh')->label(self::localizedField('disclaimer', 'zh'))->rows(2),
                        Textarea::make('payload.disclaimer_translations.ko')->label(self::localizedField('disclaimer', 'ko'))->rows(2),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'comparison');
    }

    private static function trustDisclaimerSection(): Section
    {
        return Section::make(self::sectionTitle('trust_disclaimer'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        Textarea::make('payload.disclaimer_translations.en')->label(self::localizedField('disclaimer', 'en'))->rows(2),
                        Textarea::make('payload.disclaimer_translations.zh')->label(self::localizedField('disclaimer', 'zh'))->rows(2),
                        Textarea::make('payload.disclaimer_translations.ko')->label(self::localizedField('disclaimer', 'ko'))->rows(2),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'trust_and_credibility');
    }

    private static function downloadsSection(): Section
    {
        return Section::make(self::sectionTitle('technical_downloads'))
            ->schema([
                Repeater::make('payload.downloads')
                    ->label(self::field('downloads'))
                    ->addActionLabel(self::actionLabel('add_download'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('type')
                            ->label(__('admin.fields.type'))
                            ->maxLength(120),
                        TextInput::make('url')
                            ->label(__('admin.fields.url'))
                            ->maxLength(255),
                        TextInput::make('document_url')
                            ->label(__('admin.ui.document_url'))
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
        return Section::make(self::sectionTitle('download_empty_state_labels'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.empty_title_translations.en')->label(self::localizedField('empty_title', 'en')),
                        TextInput::make('payload.empty_title_translations.zh')->label(self::localizedField('empty_title', 'zh')),
                        TextInput::make('payload.empty_title_translations.ko')->label(self::localizedField('empty_title', 'ko')),
                        Textarea::make('payload.empty_description_translations.en')->label(self::localizedField('empty_description', 'en'))->rows(2),
                        Textarea::make('payload.empty_description_translations.zh')->label(self::localizedField('empty_description', 'zh'))->rows(2),
                        Textarea::make('payload.empty_description_translations.ko')->label(self::localizedField('empty_description', 'ko'))->rows(2),
                        TextInput::make('payload.file_label_translations.en')->label(self::localizedField('file_label', 'en')),
                        TextInput::make('payload.file_label_translations.zh')->label(self::localizedField('file_label', 'zh')),
                        TextInput::make('payload.file_label_translations.ko')->label(self::localizedField('file_label', 'ko')),
                        TextInput::make('payload.on_request_label_translations.en')->label(self::localizedField('on_request_label', 'en')),
                        TextInput::make('payload.on_request_label_translations.zh')->label(self::localizedField('on_request_label', 'zh')),
                        TextInput::make('payload.on_request_label_translations.ko')->label(self::localizedField('on_request_label', 'ko')),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'technical_downloads');
    }

    private static function certificationLabelsSection(): Section
    {
        return Section::make(self::sectionTitle('certification_labels'))
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('payload.verified_label_translations.en')->label(self::localizedField('verified_label', 'en')),
                        TextInput::make('payload.verified_label_translations.zh')->label(self::localizedField('verified_label', 'zh')),
                        TextInput::make('payload.verified_label_translations.ko')->label(self::localizedField('verified_label', 'ko')),
                        TextInput::make('payload.empty_message_translations.en')->label(self::localizedField('empty_message', 'en')),
                        TextInput::make('payload.empty_message_translations.zh')->label(self::localizedField('empty_message', 'zh')),
                        TextInput::make('payload.empty_message_translations.ko')->label(self::localizedField('empty_message', 'ko')),
                        TextInput::make('payload.issuer_label_translations.en')->label(self::localizedField('issuer_label', 'en')),
                        TextInput::make('payload.issuer_label_translations.zh')->label(self::localizedField('issuer_label', 'zh')),
                        TextInput::make('payload.issuer_label_translations.ko')->label(self::localizedField('issuer_label', 'ko')),
                        TextInput::make('payload.tested_at_label_translations.en')->label(self::localizedField('test_date_label', 'en')),
                        TextInput::make('payload.tested_at_label_translations.zh')->label(self::localizedField('test_date_label', 'zh')),
                        TextInput::make('payload.tested_at_label_translations.ko')->label(self::localizedField('test_date_label', 'ko')),
                        TextInput::make('payload.download_label_translations.en')->label(self::localizedField('download_label', 'en')),
                        TextInput::make('payload.download_label_translations.zh')->label(self::localizedField('download_label', 'zh')),
                        TextInput::make('payload.download_label_translations.ko')->label(self::localizedField('download_label', 'ko')),
                        TextInput::make('payload.status_labels.certified_translations.en')->label(self::localizedField('certified', 'en')),
                        TextInput::make('payload.status_labels.certified_translations.zh')->label(self::localizedField('certified', 'zh')),
                        TextInput::make('payload.status_labels.certified_translations.ko')->label(self::localizedField('certified', 'ko')),
                        TextInput::make('payload.status_labels.tested_translations.en')->label(self::localizedField('tested', 'en')),
                        TextInput::make('payload.status_labels.tested_translations.zh')->label(self::localizedField('tested', 'zh')),
                        TextInput::make('payload.status_labels.tested_translations.ko')->label(self::localizedField('tested', 'ko')),
                        TextInput::make('payload.status_labels.in_testing_translations.en')->label(self::localizedField('in_testing', 'en')),
                        TextInput::make('payload.status_labels.in_testing_translations.zh')->label(self::localizedField('in_testing', 'zh')),
                        TextInput::make('payload.status_labels.in_testing_translations.ko')->label(self::localizedField('in_testing', 'ko')),
                        TextInput::make('payload.status_labels.pending_translations.en')->label(self::localizedField('pending', 'en')),
                        TextInput::make('payload.status_labels.pending_translations.zh')->label(self::localizedField('pending', 'zh')),
                        TextInput::make('payload.status_labels.pending_translations.ko')->label(self::localizedField('pending', 'ko')),
                        TextInput::make('payload.status_labels.not_applicable_translations.en')->label(self::localizedField('not_applicable', 'en')),
                        TextInput::make('payload.status_labels.not_applicable_translations.zh')->label(self::localizedField('not_applicable', 'zh')),
                        TextInput::make('payload.status_labels.not_applicable_translations.ko')->label(self::localizedField('not_applicable', 'ko')),
                    ]),
            ])
            ->visible(fn (Get $get): bool => $get('key') === 'certifications');
    }

    private static function certificationRecordsSection(): Section
    {
        return Section::make(self::sectionTitle('certification_records'))
            ->description(self::helpText('certification_records'))
            ->schema([
                Repeater::make('payload.certifications')
                    ->label(self::field('certifications'))
                    ->addActionLabel(self::actionLabel('add_certification'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin.ui.internal_key'))
                            ->maxLength(120),
                        Select::make('status')
                            ->label(__('admin.fields.status'))
                            ->options([
                                'certified' => 'Certified',
                                'tested' => 'Tested',
                                'in_testing' => 'In testing',
                                'pending' => 'Pending',
                                'not_applicable' => 'Not applicable',
                            ])
                            ->default('pending'),
                        Toggle::make('verified')
                            ->label(__('admin.ui.verified')),
                        TextInput::make('unit')
                            ->label(__('admin.ui.unit'))
                            ->maxLength(40),
                        TextInput::make('tested_at')
                            ->label(__('admin.ui.test_date'))
                            ->maxLength(20)
                            ->placeholder('YYYY-MM-DD'),
                        TextInput::make('document_url')
                            ->label(__('admin.ui.document_url'))
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('name_translations.en')
                            ->label(self::localizedField('name', 'en'))
                            ->maxLength(180),
                        TextInput::make('name_translations.zh')
                            ->label(self::localizedField('name', 'zh'))
                            ->maxLength(180),
                        TextInput::make('name_translations.ko')
                            ->label(self::localizedField('name', 'ko'))
                            ->maxLength(180),
                        TextInput::make('label_translations.en')
                            ->label(self::localizedField('label', 'en'))
                            ->maxLength(180),
                        TextInput::make('label_translations.zh')
                            ->label(self::localizedField('label', 'zh'))
                            ->maxLength(180),
                        TextInput::make('label_translations.ko')
                            ->label(self::localizedField('label', 'ko'))
                            ->maxLength(180),
                        TextInput::make('value_translations.en')
                            ->label(self::localizedField('value', 'en'))
                            ->maxLength(120),
                        TextInput::make('value_translations.zh')
                            ->label(self::localizedField('value', 'zh'))
                            ->maxLength(120),
                        TextInput::make('value_translations.ko')
                            ->label(self::localizedField('value', 'ko'))
                            ->maxLength(120),
                        TextInput::make('result')
                            ->label(self::field('result'))
                            ->maxLength(120),
                        TextInput::make('issuer')
                            ->label(self::field('issuer'))
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
            ->visible(fn (Get $get): bool => $get('key') === 'certifications');
    }

    private static function finalCtaSection(): Section
    {
        return Section::make(self::sectionTitle('final_cta_actions'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('payload.primary_cta_url')
                            ->label(self::field('primary_cta_url'))
                            ->maxLength(255),
                        TextInput::make('payload.secondary_cta_url')
                            ->label(self::field('secondary_cta_url'))
                            ->maxLength(255),
                        TextInput::make('payload.primary_cta_label_translations.en')
                            ->label(self::localizedField('primary_cta_label', 'en')),
                        TextInput::make('payload.primary_cta_label_translations.zh')
                            ->label(self::localizedField('primary_cta_label', 'zh')),
                        TextInput::make('payload.primary_cta_label_translations.ko')
                            ->label(self::localizedField('primary_cta_label', 'ko')),
                        TextInput::make('payload.secondary_cta_label_translations.en')
                            ->label(self::localizedField('secondary_cta_label', 'en')),
                        TextInput::make('payload.secondary_cta_label_translations.zh')
                            ->label(self::localizedField('secondary_cta_label', 'zh')),
                        TextInput::make('payload.secondary_cta_label_translations.ko')
                            ->label(self::localizedField('secondary_cta_label', 'ko')),
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
        return Section::make(self::sectionTitle('footer_link_groups'))
            ->schema([
                Repeater::make('payload.social_links')
                    ->label(self::field('social_links'))
                    ->addActionLabel(self::actionLabel('add_social_link'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(__('admin.ui.label_en'))->maxLength(120),
                        TextInput::make('label_translations.zh')->label(__('admin.ui.label_zh'))->maxLength(120),
                        TextInput::make('label_translations.ko')->label(__('admin.ui.label_ko'))->maxLength(120),
                        TextInput::make('href')->label(__('admin.fields.url'))->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Repeater::make('payload.legal_links')
                    ->label(self::field('legal_links'))
                    ->addActionLabel(self::actionLabel('add_legal_link'))
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->defaultItems(0)
                    ->schema([
                        TextInput::make('label_translations.en')->label(__('admin.ui.label_en'))->maxLength(120),
                        TextInput::make('label_translations.zh')->label(__('admin.ui.label_zh'))->maxLength(120),
                        TextInput::make('label_translations.ko')->label(__('admin.ui.label_ko'))->maxLength(120),
                        TextInput::make('href')->label(__('admin.fields.url'))->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->hidden(fn (Get $get): bool => ! self::isFooterSection($get));
    }

    private static function sectionTitle(string $key): string
    {
        return __("admin.home_sections.sections.{$key}");
    }

    private static function helpText(string $key): string
    {
        return __("admin.home_sections.help.{$key}");
    }

    private static function actionLabel(string $key): string
    {
        return __("admin.home_sections.actions.{$key}");
    }

    private static function field(string $key): string
    {
        return __("admin.home_sections.fields.{$key}");
    }

    private static function localizedField(string $key, string $locale): string
    {
        return __('admin.labels.localized_field', [
            'field' => self::field($key),
            'locale' => strtoupper($locale),
        ]);
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<int, string>  $textareaFields
     * @return array<int, TextInput|Textarea>
     */
    private static function localizedPayloadComponents(array $fields, array $textareaFields = []): array
    {
        $components = [];

        foreach ($fields as $field) {
            foreach (LocalizedContent::supportedLocales() as $locale) {
                $path = "payload.{$field}_translations.{$locale}";
                $label = self::payloadFieldLabel($field, $locale);
                $components[] = in_array($field, $textareaFields, true)
                    ? Textarea::make($path)->label($label)->rows(2)
                    : TextInput::make($path)->label($label)->maxLength(255);
            }
        }

        return $components;
    }

    private static function payloadFieldLabel(string $field, string $locale): string
    {
        $translationKey = "admin.home_sections.fields.{$field}";
        $fieldLabel = __($translationKey);

        if ($fieldLabel === $translationKey) {
            $fieldLabel = str_replace('_', ' ', ucfirst($field));
        }

        return __('admin.labels.localized_field', [
            'field' => $fieldLabel,
            'locale' => strtoupper($locale),
        ]);
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
            // Prefer $data['payload'] (Filament-dehydrated, integer-indexed arrays) over
            // $state['payload'] (raw Livewire state, which uses UUID keys for Repeater items).
            // Using UUID-keyed state causes items to be appended instead of replaced on every save.
            $incomingPayload = isset($data['payload']) && is_array($data['payload'])
                ? $data['payload']
                : (isset($state['payload']) && is_array($state['payload']) ? $state['payload'] : null);

            if ($incomingPayload !== null) {
                $data['payload'] = HomeSectionPayloadNormalizer::normalize(
                    self::normalizeLocalizedArrays(self::mergePayloadState(
                        is_array($record?->payload) ? $record->payload : [],
                        self::normalizeLocalizedArrays($incomingPayload)
                    ))
                );
            }

            unset($data['payload_json']);

            return $data;
        }

        if (array_key_exists('payload_json', $state)) {
            $payload = self::decodePayloadJson($state['payload_json'] ?? null);
            $data['payload'] = is_array($payload)
                ? HomeSectionPayloadNormalizer::normalize(self::normalizeLocalizedArrays($payload))
                : $payload;
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
        // Re-index UUID-keyed arrays that originate from Filament's Livewire Repeater state.
        // UUID keys appear when $state (not $data) is passed as the incoming payload source.
        if (! array_is_list($incoming) && self::isUuidKeyedArray($incoming)) {
            $incoming = array_values($incoming);
        }

        // Guard: PHP's empty array `[]` is always array_is_list() === true, which would cause
        // an empty incoming (e.g. a translation set left blank by the form) to REPLACE an
        // existing associative map with an empty list. Instead, treat empty-incoming +
        // non-empty-associative-existing as "no data supplied" and return existing unchanged.
        // For list-typed existing (payload.items) this guard does NOT fire, so clearing all
        // Repeater items (user intentionally removed them) still works correctly.
        if (empty($incoming) && ! empty($existing) && ! array_is_list($existing)) {
            return $existing;
        }

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
                // Use recursive merge for all array values, including _translations.
                // Previously _translations used direct assignment, which wiped all existing
                // locale entries whenever the form submitted a partial or empty set.
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

    /**
     * Detect if every key in the array is a UUID string (Filament Repeater internal key format).
     * Filament stores repeater items with UUID keys in Livewire state; during dehydration it
     * re-indexes them to integers. This guard handles edge cases where UUID keys slip through.
     *
     * @param  array<mixed>  $array
     */
    private static function isUuidKeyedArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach (array_keys($array) as $key) {
            if (! is_string($key) || ! preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $key
            )) {
                return false;
            }
        }

        return true;
    }
}
