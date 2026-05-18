<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Support\AdminOptions;
use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
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
use Filament\Schemas\Components\Utilities\Get;
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
                                                            ->keyLabel(__('admin.ui.key'))
                                                            ->valueLabel(__('admin.ui.value'))
                                                            ->columnSpanFull(),
                                                        TextInput::make('image_url')
                                                            ->label(__('admin.ui.image_url'))
                                                            ->placeholder('https://example.com/image.jpg')
                                                            ->helperText('Use only full external http/https URLs. For local/admin uploaded images, use the upload field.')
                                                            ->type('text')
                                                            ->rules(['nullable', 'url'])
                                                            ->afterStateHydrated(fn (TextInput $component, Get $get, Set $set, ?string $state): null => self::moveHydratedRelativeImageUrlToMediaPath($component, $get, $set, $state))
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
                                    ->description(__('admin.ui.dynamic_attributes_replace_legacy_product_fields'))
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
                                                    ->createOptionForm([
                                                        TextInput::make('key')
                                                            ->label(__('admin.ui.key'))
                                                            ->required()
                                                            ->maxLength(120)
                                                            ->unique(ProductAttributeDefinition::class, 'key'),
                                                        TextInput::make('label')
                                                            ->label(__('admin.ui.label'))
                                                            ->required()
                                                            ->maxLength(160),
                                                        Select::make('type')
                                                            ->label(__('admin.fields.type'))
                                                            ->options(fn (): array => AdminOptions::productAttributeTypes())
                                                            ->default('text')
                                                            ->required(),
                                                        TextInput::make('unit')
                                                            ->label(__('admin.ui.unit'))
                                                            ->maxLength(40),
                                                        TextInput::make('group')
                                                            ->label(__('admin.ui.group'))
                                                            ->maxLength(80),
                                                        Textarea::make('help_text')
                                                            ->label(__('admin.ui.help_text'))
                                                            ->rows(2)
                                                            ->columnSpanFull(),
                                                        Toggle::make('is_filterable')
                                                            ->label(__('admin.ui.filterable')),
                                                        Toggle::make('is_searchable')
                                                            ->label(__('admin.ui.searchable')),
                                                        Toggle::make('is_specification')
                                                            ->label(__('admin.ui.specification'))
                                                            ->default(true),
                                                        Toggle::make('is_variant_option')
                                                            ->label(__('admin.ui.variant')),
                                                        Toggle::make('allows_multiple')
                                                            ->label(__('admin.ui.allows_multiple')),
                                                        Toggle::make('is_active')
                                                            ->label(__('admin.ui.active'))
                                                            ->default(true),
                                                    ])
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required(),
                                                Placeholder::make('attribute_metadata')
                                                    ->label(__('admin.ui.attribute_metadata'))
                                                    ->content(fn (Get $get): string => self::attributeMetadata((int) $get('attribute_definition_id')))
                                                    ->columnSpanFull(),
                                                Select::make('product_attribute_value_id')
                                                    ->label(__('admin.ui.predefined_value'))
                                                    ->options(fn (Get $get): array => ProductAttributeValue::query()
                                                        ->where('attribute_definition_id', $get('attribute_definition_id'))
                                                        ->active()
                                                        ->ordered()
                                                        ->pluck('label', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->visible(fn (Get $get): bool => in_array(self::attributeType((int) $get('attribute_definition_id')), ['select', 'multiselect'], true)),
                                                TextInput::make('value_text')
                                                    ->label(__('admin.ui.text_rich_text_value'))
                                                    ->visible(fn (Get $get): bool => in_array(self::attributeType((int) $get('attribute_definition_id')), ['text', 'rich_text'], true))
                                                    ->columnSpanFull(),
                                                TextInput::make('value_number')
                                                    ->label(__('admin.ui.numeric_value'))
                                                    ->numeric()
                                                    ->visible(fn (Get $get): bool => self::attributeType((int) $get('attribute_definition_id')) === 'number'),
                                                Toggle::make('value_boolean')
                                                    ->label(__('admin.ui.boolean_value'))
                                                    ->visible(fn (Get $get): bool => self::attributeType((int) $get('attribute_definition_id')) === 'boolean'),
                                                KeyValue::make('value_json')
                                                    ->label(__('admin.ui.json_value'))
                                                    ->keyLabel(__('admin.ui.key'))
                                                    ->valueLabel(__('admin.ui.value'))
                                                    ->visible(fn (Get $get): bool => self::attributeType((int) $get('attribute_definition_id')) === 'json')
                                                    ->columnSpanFull(),
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
                                                FileUpload::make('document_path')
                                                    ->label(__('admin.ui.file'))
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products/certifications')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->acceptedFileTypes(self::proofDocumentFileTypes())
                                                    ->maxSize(10240),
                                                TextInput::make('document_url')
                                                    ->label(__('admin.ui.document_url'))
                                                    ->placeholder('https://example.com/document.pdf')
                                                    ->helperText('Use only full external http/https URLs. For local/admin uploaded documents, use the upload field.')
                                                    ->type('text')
                                                    ->rules(['nullable', 'url'])
                                                    ->afterStateHydrated(fn (TextInput $component, Get $get, Set $set, ?string $state): null => self::moveHydratedRelativeUrlToPath($component, $get, $set, $state, 'document_path'))
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
                                                FileUpload::make('file_path')
                                                    ->label(__('admin.ui.file'))
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products/downloads')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->acceptedFileTypes(self::proofDocumentFileTypes())
                                                    ->maxSize(10240),
                                                TextInput::make('url')
                                                    ->label(__('admin.ui.file_url'))
                                                    ->placeholder('https://example.com/file.pdf')
                                                    ->helperText('Use only full external http/https URLs. For local/admin uploaded files, use the upload field.')
                                                    ->type('text')
                                                    ->rules(['nullable', 'url'])
                                                    ->afterStateHydrated(fn (TextInput $component, Get $get, Set $set, ?string $state): null => self::moveHydratedRelativeUrlToPath($component, $get, $set, $state, 'file_path'))
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
                                                    ->placeholder('https://example.com/image.jpg')
                                                    ->helperText('Use only full external http/https URLs. For local/admin uploaded images, use the upload field.')
                                                    ->type('text')
                                                    ->rules(['nullable', 'url'])
                                                    ->afterStateHydrated(fn (TextInput $component, Get $get, Set $set, ?string $state): null => self::moveHydratedRelativeImageUrlToMediaPath($component, $get, $set, $state))
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

    private static function attributeType(int $definitionId): ?string
    {
        if ($definitionId <= 0) {
            return null;
        }

        return ProductAttributeDefinition::query()
            ->whereKey($definitionId)
            ->value('type');
    }

    private static function moveHydratedRelativeImageUrlToMediaPath(TextInput $component, Get $get, Set $set, ?string $state): null
    {
        return self::moveHydratedRelativeUrlToPath($component, $get, $set, $state, 'media_path');
    }

    private static function moveHydratedRelativeUrlToPath(TextInput $component, Get $get, Set $set, ?string $state, string $pathField): null
    {
        $value = is_string($state) ? trim($state) : '';

        if ($value === '') {
            $component->state(null);

            return null;
        }

        if (self::isExternalUrl($value)) {
            $component->state($value);

            return null;
        }

        if (blank($get($pathField))) {
            $set($pathField, ltrim($value, '/'));
        }

        $component->state(null);

        return null;
    }

    private static function isExternalUrl(string $value): bool
    {
        $value = strtolower(trim($value));

        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://');
    }

    /**
     * @return array<int, string>
     */
    private static function proofDocumentFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/csv',
            'text/plain',
        ];
    }

    private static function attributeMetadata(int $definitionId): string
    {
        if ($definitionId <= 0) {
            return __('admin.ui.select_an_attribute_to_see_metadata');
        }

        $definition = ProductAttributeDefinition::query()
            ->whereKey($definitionId)
            ->first(['type', 'unit', 'group', 'is_filterable', 'is_specification', 'is_variant_option', 'allows_multiple']);

        if ($definition === null) {
            return __('admin.ui.select_an_attribute_to_see_metadata');
        }

        $type = (string) $definition->type;
        $typeLabel = AdminOptions::productAttributeTypes()[$type] ?? $type;
        $yes = __('admin.ui.yes');
        $no = __('admin.ui.no');

        return collect([
            __('admin.fields.type').': '.$typeLabel,
            $definition->unit ? __('admin.ui.unit').': '.$definition->unit : null,
            $definition->group ? __('admin.ui.group').': '.$definition->group : null,
            __('admin.ui.filterable').': '.($definition->is_filterable ? $yes : $no),
            __('admin.ui.specification').': '.($definition->is_specification ? $yes : $no),
            __('admin.ui.variant').': '.($definition->is_variant_option ? $yes : $no),
            __('admin.ui.allows_multiple').': '.($definition->allows_multiple ? $yes : $no),
        ])->filter()->implode(' | ');
    }

    private static function publishChecklist(?Product $record): string
    {
        if ($record === null || ! $record->exists) {
            return __('admin.ui.publish_checklist_new_product');
        }

        $defaultVariant = $record->defaultVariant();
        $hasPurchasableVariant = $defaultVariant !== null
            && $defaultVariant->is_active
            && filled($defaultVariant->sku)
            && (float) $defaultVariant->price_amount > 0;
        $hasSpecificationAttribute = $record->attributeAssignments()
            ->whereHas('definition', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_specification', true))
            ->exists();

        $warnings = collect([
            $record->primaryImageUrl() ? null : __('admin.ui.publish_checklist_no_image'),
            $record->inquiry_only || $hasPurchasableVariant ? null : __('admin.ui.publish_checklist_no_active_default_variant'),
            $record->inquiry_only || ($record->effectivePrice() ?? 0) > 0 ? null : __('admin.ui.publish_checklist_no_price'),
            filled(data_get($record->name_translations, 'en') ?: $record->name) ? null : __('admin.ui.publish_checklist_missing_english_title'),
            $record->category_id ? null : __('admin.ui.publish_checklist_no_category'),
            $hasSpecificationAttribute ? null : __('admin.ui.publish_checklist_no_dynamic_specification'),
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
