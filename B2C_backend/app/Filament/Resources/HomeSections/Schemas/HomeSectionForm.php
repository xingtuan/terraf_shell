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
    private const STRUCTURED_PAYLOAD_KEYS = [
        'pilot_projects',
        'footer',
    ];

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
                                    ->hidden(fn (Get $get): bool => self::hasStructuredPayload($get))
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
                    ->visible(fn (Get $get): bool => self::isFooterSection($get)),
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
                            ->columnSpanFull(),
                        TextInput::make('cta_label_translations.en')
                            ->label(__('admin.ui.cta_label')),
                    ])
                    ->visible(fn (Get $get): bool => ! self::isFooterSection($get)),
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
                    ])
                    ->visible(fn (Get $get): bool => ! self::isFooterSection($get)),
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
                    ])
                    ->visible(fn (Get $get): bool => ! self::isFooterSection($get)),
            ]);
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
            ->visible(fn (Get $get): bool => self::isFooterSection($get));
    }

    private static function isFooterSection(Get $get): bool
    {
        return $get('key') === 'footer';
    }

    private static function hasStructuredPayload(Get $get): bool
    {
        return in_array($get('key'), self::STRUCTURED_PAYLOAD_KEYS, true);
    }
}
