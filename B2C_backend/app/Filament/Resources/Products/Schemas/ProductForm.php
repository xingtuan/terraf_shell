<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Support\AdminOptions;
use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('currency')
                    ->default('NZD'),
                Tabs::make(__('admin.ui.shop_product_management'))
                    ->persistTab()
                    ->tabs([
                        Tab::make(__('admin.ui.basic_info'))
                            ->schema([
                                Section::make(__('admin.ui.catalogue_settings'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('admin.ui.fallback_name'))
                                                    ->maxLength(255)
                                                    ->helperText(__('admin.ui.used_only_when_localized_names_are_empty')),
                                                TextInput::make('slug')
                                                    ->label(__('admin.ui.slug'))
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true),
                                                TextInput::make('sku')
                                                    ->label(__('admin.ui.legacy_default_sku'))
                                                    ->maxLength(255)
                                                    ->helperText(__('admin.ui.compatibility_field_variant_sku_is_the_storefront_source_of_truth')),
                                                Select::make('category_id')
                                                    ->label(__('admin.ui.product_category'))
                                                    ->options(fn (): array => ProductCategory::query()
                                                        ->ordered()
                                                        ->pluck('name', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('status')
                                                    ->label(__('admin.fields.status'))
                                                    ->options(ProductStatus::options())
                                                    ->required()
                                                    ->default(ProductStatus::Draft->value),
                                                Select::make('category')
                                                    ->label(__('admin.ui.legacy_category'))
                                                    ->options(fn (): array => AdminOptions::productCategories())
                                                    ->required(),
                                                Select::make('model')
                                                    ->label(__('admin.ui.legacy_model'))
                                                    ->options(fn (): array => AdminOptions::productModels())
                                                    ->required(),
                                                Select::make('finish')
                                                    ->label(__('admin.ui.legacy_finish'))
                                                    ->options(fn (): array => AdminOptions::productFinishes())
                                                    ->required(),
                                                Select::make('color')
                                                    ->label(__('admin.ui.legacy_color'))
                                                    ->options(fn (): array => AdminOptions::productColors())
                                                    ->required(),
                                                Select::make('technique')
                                                    ->label(__('admin.ui.legacy_technique'))
                                                    ->options(fn (): array => AdminOptions::productTechniques())
                                                    ->required(),
                                                TextInput::make('sort_order')
                                                    ->label(__('admin.ui.sort_order'))
                                                    ->numeric()
                                                    ->required()
                                                    ->default(0),
                                                DateTimePicker::make('published_at')
                                                    ->label(__('admin.ui.published_at'))
                                                    ->disabled(),
                                                Toggle::make('is_active')
                                                    ->label(__('admin.ui.visible_in_store'))
                                                    ->default(true),
                                                Toggle::make('featured')
                                                    ->label(__('admin.ui.featured_in_store')),
                                                Toggle::make('is_bestseller')
                                                    ->label(__('admin.ui.best_seller')),
                                                Toggle::make('is_new')
                                                    ->label(__('admin.ui.new_arrival')),
                                                Toggle::make('inquiry_only')
                                                    ->label(__('admin.ui.inquiry_only')),
                                                Toggle::make('sample_request_enabled')
                                                    ->label(__('admin.ui.sample_request_enabled')),
                                            ]),
                                    ]),
                                Section::make(__('admin.ui.publish_checklist'))
                                    ->schema([
                                        Placeholder::make('checklist')
                                            ->hiddenLabel()
                                            ->content(fn (?Product $record): string => self::publishChecklist($record)),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.pricing_inventory'))
                            ->schema([
                                Section::make(__('admin.ui.variant_management'))
                                    ->description(__('admin.ui.variant_sku_nzd_price_inventory_and_option_values_drive_storefront_purchasing'))
                                    ->schema([
                                        Repeater::make('variants')
                                            ->relationship()
                                            ->label(__('admin.ui.product_variants'))
                                            ->addActionLabel(__('admin.ui.add_variant'))
                                            ->orderColumn('sort_order')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->defaultItems(1)
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('sku')
                                                            ->label(__('admin.fields.sku'))
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique(ignoreRecord: true),
                                                        TextInput::make('title')
                                                            ->label(__('admin.ui.title'))
                                                            ->maxLength(255),
                                                        KeyValue::make('option_values')
                                                            ->label(__('admin.ui.option_values'))
                                                            ->keyLabel(__('admin.ui.attribute_key'))
                                                            ->valueLabel(__('admin.ui.value'))
                                                            ->columnSpanFull(),
                                                        TextInput::make('price_amount')
                                                            ->label(__('admin.labels.currency_field', ['field' => __('admin.fields.price'), 'currency' => __('admin.currency.nzd')]))
                                                            ->numeric()
                                                            ->prefix('$')
                                                            ->required(),
                                                        TextInput::make('compare_at_price_amount')
                                                            ->label(__('admin.ui.compare_at_price_nzd'))
                                                            ->numeric()
                                                            ->prefix('$'),
                                                        Hidden::make('currency')
                                                            ->default('NZD'),
                                                        TextInput::make('stock_quantity')
                                                            ->label(__('admin.ui.stock_quantity'))
                                                            ->numeric()
                                                            ->minValue(0),
                                                        Select::make('stock_status')
                                                            ->label(__('admin.fields.stock_status'))
                                                            ->options(fn (): array => AdminOptions::variantStockStatuses())
                                                            ->default('in_stock')
                                                            ->required(),
                                                        Select::make('inventory_policy')
                                                            ->label(__('admin.ui.inventory_policy'))
                                                            ->options(fn (): array => AdminOptions::inventoryPolicies())
                                                            ->default('deny')
                                                            ->required(),
                                                        TextInput::make('low_stock_threshold')
                                                            ->label(__('admin.ui.low_stock_threshold'))
                                                            ->numeric()
                                                            ->default(5)
                                                            ->minValue(0),
                                                        TextInput::make('weight_grams')
                                                            ->label(__('admin.fields.weight_grams'))
                                                            ->numeric()
                                                            ->minValue(0),
                                                        KeyValue::make('dimensions')
                                                            ->label(__('admin.ui.dimensions'))
                                                            ->columnSpanFull(),
                                                        TextInput::make('image_url')
                                                            ->label(__('admin.ui.image_url'))
                                                            ->url()
                                                            ->maxLength(2048),
                                                        FileUpload::make('media_path')
                                                            ->label(__('admin.ui.image'))
                                                            ->image()
                                                            ->disk((string) config('community.uploads.disk'))
                                                            ->directory('cms/products/variants')
                                                            ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public'),
                                                        Toggle::make('is_default')
                                                            ->label(__('admin.fields.is_default')),
                                                        Toggle::make('is_active')
                                                            ->label(__('admin.account_status.active'))
                                                            ->default(true),
                                                        TextInput::make('sort_order')
                                                            ->label(__('admin.ui.sort_order'))
                                                            ->numeric()
                                                            ->default(0),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.stock_fallback'))
                            ->schema([
                                Section::make(__('admin.ui.legacy_inventory_fallback'))
                                    ->description(__('admin.ui.used_only_when_a_product_has_no_active_variant'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price_usd')
                                                    ->label(__('admin.ui.legacy_price_fallback_nzd'))
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required(),
                                                TextInput::make('compare_at_price_usd')
                                                    ->label(__('admin.ui.legacy_compare_at_fallback_nzd'))
                                                    ->numeric()
                                                    ->prefix('$'),
                                                Select::make('stock_status')
                                                    ->label(__('admin.fields.stock_status'))
                                                    ->options(fn (): array => AdminOptions::productStockStatuses())
                                                    ->required()
                                                    ->default('in_stock'),
                                                TextInput::make('stock_quantity')
                                                    ->label(__('admin.ui.stock_quantity'))
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->helperText(__('admin.ui.leave_blank_for_preorder_or_made_to_order_fallback_items')),
                                                TextInput::make('weight_grams')
                                                    ->label(__('admin.fields.weight_grams'))
                                                    ->numeric()
                                                    ->minValue(0),
                                            ]),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.storefront_content'))
                            ->schema([
                                self::localeSection('en', __('admin.locale.english'), true),
                                self::localeSection('ko', __('admin.locale.korean')),
                                self::localeSection('zh', __('admin.locale.chinese')),
                                Section::make(__('admin.ui.selling_content'))
                                    ->schema([
                                        TagsInput::make('selling_points')
                                            ->label(__('admin.ui.selling_points'))
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        TagsInput::make('shipping_notes')
                                            ->label(__('admin.ui.shipping_notes'))
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        TagsInput::make('return_notes')
                                            ->label(__('admin.ui.return_notes'))
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        Repeater::make('product_faqs')
                                            ->label(__('admin.ui.product_faqs'))
                                            ->addActionLabel(__('admin.ui.add_faq'))
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('question')
                                                    ->label(__('admin.ui.question'))
                                                    ->required()
                                                    ->maxLength(180),
                                                Textarea::make('answer')
                                                    ->label(__('admin.ui.answer'))
                                                    ->required()
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.specifications_attributes'))
                            ->schema([
                                Section::make(__('admin.ui.dynamic_attributes'))
                                    ->schema([
                                        Repeater::make('attributeAssignments')
                                            ->relationship()
                                            ->label(__('admin.ui.product_attributes'))
                                            ->addActionLabel(__('admin.ui.assign_attribute'))
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->schema([
                                                Select::make('attribute_definition_id')
                                                    ->label(__('admin.ui.attribute'))
                                                    ->options(fn (): array => ProductAttributeDefinition::query()
                                                        ->active()
                                                        ->ordered()
                                                        ->pluck('label', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('product_attribute_value_id')
                                                    ->label(__('admin.ui.predefined_value'))
                                                    ->relationship('attributeValue', 'label')
                                                    ->searchable()
                                                    ->preload(),
                                                TextInput::make('value_text')
                                                    ->label(__('admin.ui.text_rich_text_value'))
                                                    ->columnSpanFull(),
                                                TextInput::make('value_number')
                                                    ->label(__('admin.ui.numeric_value'))
                                                    ->numeric(),
                                                Toggle::make('value_boolean')
                                                    ->label(__('admin.ui.boolean_value')),
                                                KeyValue::make('value_json')
                                                    ->label(__('admin.ui.json_value'))
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.ui.legacy_specifications'))
                                    ->schema([
                                        Select::make('use_cases')
                                            ->label(__('admin.ui.use_cases'))
                                            ->multiple()
                                            ->options(fn (): array => AdminOptions::productUseCases())
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),
                                        TextInput::make('dimensions')
                                            ->label(__('admin.ui.dimensions'))
                                            ->maxLength(255),
                                        Repeater::make('specifications')
                                            ->label(__('admin.ui.technical_specifications'))
                                            ->addActionLabel(__('admin.ui.add_specification'))
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('key')
                                                    ->label(__('admin.ui.key'))
                                                    ->maxLength(80),
                                                TextInput::make('label')
                                                    ->label(__('admin.ui.label'))
                                                    ->required()
                                                    ->maxLength(120),
                                                TextInput::make('value')
                                                    ->label(__('admin.ui.value'))
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('unit')
                                                    ->label(__('admin.ui.unit'))
                                                    ->maxLength(40),
                                                TextInput::make('group')
                                                    ->label(__('admin.ui.group'))
                                                    ->maxLength(80),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.material_proof'))
                            ->schema([
                                Section::make(__('admin.ui.material_proof_downloads'))
                                    ->description(__('admin.ui.use_cautious_evidence_backed_wording_leave_uncertain_documents_pending_da0c9c6e1f'))
                                    ->schema([
                                        Repeater::make('certifications')
                                            ->label(__('admin.ui.certifications_and_tests'))
                                            ->addActionLabel(__('admin.ui.add_certification_or_test'))
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('admin.ui.certification_test_name'))
                                                    ->required()
                                                    ->maxLength(180),
                                                Select::make('status')
                                                    ->label(__('admin.fields.status'))
                                                    ->options(fn (): array => AdminOptions::certificationStatuses())
                                                    ->required()
                                                    ->default('pending'),
                                                TextInput::make('result')
                                                    ->label(__('admin.ui.value_result'))
                                                    ->maxLength(120),
                                                TextInput::make('unit')
                                                    ->label(__('admin.ui.unit'))
                                                    ->maxLength(40),
                                                TextInput::make('issuer')
                                                    ->label(__('admin.ui.issuing_body_lab'))
                                                    ->maxLength(180)
                                                    ->helperText(__('admin.ui.use_client_confirmation_pending_when_a_lab_is_not_approved_for_publication')),
                                                DatePicker::make('tested_at')
                                                    ->label(__('admin.ui.test_date')),
                                                TextInput::make('document_url')
                                                    ->label(__('admin.ui.document_url'))
                                                    ->url()
                                                    ->maxLength(2048),
                                                Textarea::make('description')
                                                    ->label(__('admin.ui.description'))
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        Repeater::make('technical_downloads')
                                            ->label(__('admin.ui.technical_downloads'))
                                            ->addActionLabel(__('admin.ui.add_download'))
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label(__('admin.ui.title'))
                                                    ->required()
                                                    ->maxLength(180),
                                                Select::make('type')
                                                    ->label(__('admin.fields.type'))
                                                    ->options(fn (): array => AdminOptions::technicalDownloadTypes())
                                                    ->required(),
                                                Select::make('status')
                                                    ->label(__('admin.fields.status'))
                                                    ->options(fn (): array => AdminOptions::technicalDownloadStatuses())
                                                    ->default('on_request')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label(__('admin.ui.file_url'))
                                                    ->url()
                                                    ->maxLength(2048),
                                                Textarea::make('description')
                                                    ->label(__('admin.ui.description'))
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        TagsInput::make('material_benefits')
                                            ->label(__('admin.ui.material_benefits'))
                                            ->separator(',')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.images'))
                            ->schema([
                                Section::make(__('admin.ui.media_related_products'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('media_path')
                                                    ->label(__('admin.ui.primary_image'))
                                                    ->image()
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->imagePreviewHeight('180'),
                                                TextInput::make('image_url')
                                                    ->label(__('admin.ui.external_primary_image_url'))
                                                    ->url()
                                                    ->maxLength(2048),
                                                Select::make('relatedProducts')
                                                    ->label(__('admin.ui.related_products'))
                                                    ->relationship('relatedProducts', 'name')
                                                    ->multiple()
                                                    ->searchable()
                                                    ->preload()
                                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name.' ('.$record->effectiveSku().')')
                                                    ->columnSpanFull(),
                                            ]),
                                        Repeater::make('images')
                                            ->relationship()
                                            ->label(__('admin.ui.gallery_images'))
                                            ->addActionLabel(__('admin.ui.add_gallery_image'))
                                            ->orderColumn('sort_order')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->grid(2)
                                            ->schema([
                                                FileUpload::make('media_path')
                                                    ->label(__('admin.ui.image'))
                                                    ->image()
                                                    ->required()
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products/gallery')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->imagePreviewHeight('140'),
                                                TextInput::make('alt_text_translations.en')
                                                    ->label(__('admin.ui.alt_text_en'))
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.en')
                                                    ->label(__('admin.ui.caption_en'))
                                                    ->maxLength(255),
                                                TextInput::make('alt_text_translations.ko')
                                                    ->label(__('admin.ui.alt_text_ko'))
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.ko')
                                                    ->label(__('admin.ui.caption_ko'))
                                                    ->maxLength(255),
                                                TextInput::make('alt_text_translations.zh')
                                                    ->label(__('admin.ui.alt_text_zh'))
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.zh')
                                                    ->label(__('admin.ui.caption_zh'))
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),
                        Tab::make(__('admin.ui.b2b_sample'))
                            ->schema([
                                Section::make(__('admin.ui.b2b_conversion'))
                                    ->schema([
                                        Placeholder::make('b2b_flags')
                                            ->label(__('admin.ui.conversion_flags'))
                                            ->content(__('admin.ui.b2b_flags_managed_in_overview')),
                                        TextInput::make('lead_time')
                                            ->label(__('admin.ui.lead_time'))
                                            ->maxLength(255),
                                        TagsInput::make('care_instructions')
                                            ->label(__('admin.ui.care_instructions'))
                                            ->separator(',')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make(__('admin.ui.seo'))
                            ->schema([
                                Section::make(__('admin.ui.search_metadata'))
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label(__('admin.ui.seo_title'))
                                            ->maxLength(255),
                                        Textarea::make('seo_description')
                                            ->label(__('admin.ui.seo_description'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function publishChecklist(?Product $record): string
    {
        if ($record === null || ! $record->exists) {
            return __('admin.ui.publish_checklist_new_product');
        }

        $warnings = collect([
            $record->primaryImageUrl() ? null : __('admin.ui.publish_checklist_no_image'),
            $record->defaultVariant() ? null : __('admin.ui.publish_checklist_no_active_default_variant'),
            ($record->effectivePrice() ?? 0) > 0 ? null : __('admin.ui.publish_checklist_no_price'),
            filled(data_get($record->name_translations, 'en') ?: $record->name) ? null : __('admin.ui.publish_checklist_missing_english_title'),
            $record->category_id ? null : __('admin.ui.publish_checklist_no_category'),
        ])->filter()->values();

        return $warnings->isEmpty()
            ? __('admin.ui.publish_checklist_ready')
            : __('admin.ui.publish_checklist_warnings', ['warnings' => $warnings->implode(', ')]);
    }

    private static function localeSection(string $locale, string $label, bool $isRequired = false): Section
    {
        $isEnglish = $locale === 'en';

        return Section::make($label)
            ->schema([
                TextInput::make("name_translations.{$locale}")
                    ->label(__('admin.ui.name'))
                    ->required($isRequired)
                    ->maxLength(255)
                    ->live(onBlur: $isEnglish)
                    ->afterStateUpdated(function (Set $set, ?string $state) use ($isEnglish): void {
                        if (! $isEnglish) {
                            return;
                        }

                        $set('slug', Str::slug((string) $state));
                    }),
                TextInput::make("subtitle_translations.{$locale}")
                    ->label(__('admin.ui.subtitle'))
                    ->maxLength(255),
                Textarea::make("short_description_translations.{$locale}")
                    ->label(__('admin.ui.short_description'))
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make("full_description_translations.{$locale}")
                    ->label(__('admin.ui.full_description'))
                    ->rows(8)
                    ->columnSpanFull(),
                TextInput::make("availability_text_translations.{$locale}")
                    ->label(__('admin.ui.availability_text'))
                    ->maxLength(255),
                TextInput::make("lead_time_translations.{$locale}")
                    ->label(__('admin.ui.lead_time'))
                    ->maxLength(255),
                TextInput::make("dimensions_translations.{$locale}")
                    ->label(__('admin.ui.dimensions'))
                    ->maxLength(255),
                TagsInput::make("features_translations.{$locale}")
                    ->label(__('admin.ui.features'))
                    ->separator(',')
                    ->columnSpanFull(),
                TagsInput::make("care_instructions_translations.{$locale}")
                    ->label(__('admin.ui.care_instructions'))
                    ->separator(',')
                    ->columnSpanFull(),
                TagsInput::make("material_benefits_translations.{$locale}")
                    ->label(__('admin.ui.material_benefits'))
                    ->separator(',')
                    ->columnSpanFull(),
                TextInput::make("seo_title_translations.{$locale}")
                    ->label(__('admin.ui.seo_title'))
                    ->maxLength(255),
                Textarea::make("seo_description_translations.{$locale}")
                    ->label(__('admin.ui.seo_description'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
