<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
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
                Tabs::make('Shop product management')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Basic Info')
                            ->schema([
                                Section::make('Catalogue settings')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Fallback name')
                                                    ->maxLength(255)
                                                    ->helperText('Used only when localized names are empty.'),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true),
                                                TextInput::make('sku')
                                                    ->label('Legacy default SKU')
                                                    ->maxLength(255)
                                                    ->helperText('Compatibility field. Variant SKU is the storefront source of truth.'),
                                                Select::make('category_id')
                                                    ->label('Product category')
                                                    ->options(fn (): array => ProductCategory::query()
                                                        ->ordered()
                                                        ->pluck('name', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('status')
                                                    ->options(ProductStatus::options())
                                                    ->required()
                                                    ->default(ProductStatus::Draft->value),
                                                Select::make('category')
                                                    ->label('Legacy category')
                                                    ->options(Product::CATEGORY_OPTIONS)
                                                    ->required(),
                                                Select::make('model')
                                                    ->label('Legacy model')
                                                    ->options(Product::MODEL_OPTIONS)
                                                    ->required(),
                                                Select::make('finish')
                                                    ->label('Legacy finish')
                                                    ->options(Product::FINISH_OPTIONS)
                                                    ->required(),
                                                Select::make('color')
                                                    ->label('Legacy color')
                                                    ->options(Product::COLOR_OPTIONS)
                                                    ->required(),
                                                Select::make('technique')
                                                    ->label('Legacy technique')
                                                    ->options(Product::TECHNIQUE_OPTIONS)
                                                    ->required(),
                                                TextInput::make('sort_order')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(0),
                                                DateTimePicker::make('published_at')
                                                    ->disabled(),
                                                Toggle::make('is_active')
                                                    ->label('Visible in store')
                                                    ->default(true),
                                                Toggle::make('featured')
                                                    ->label('Featured in store'),
                                                Toggle::make('is_bestseller')
                                                    ->label('Best seller'),
                                                Toggle::make('is_new')
                                                    ->label('New arrival'),
                                                Toggle::make('inquiry_only')
                                                    ->label('Inquiry only'),
                                                Toggle::make('sample_request_enabled')
                                                    ->label('Sample request enabled'),
                                            ]),
                                    ]),
                                Section::make('Publish checklist')
                                    ->schema([
                                        Placeholder::make('checklist')
                                            ->hiddenLabel()
                                            ->content(fn (?Product $record): string => self::publishChecklist($record)),
                                    ]),
                            ]),
                        Tab::make('Pricing & Inventory')
                            ->schema([
                                Section::make('Variant management')
                                    ->description('Variant SKU, NZD price, inventory, and option values drive storefront purchasing.')
                                    ->schema([
                                        Repeater::make('variants')
                                            ->relationship()
                                            ->label('Product variants')
                                            ->addActionLabel('Add variant')
                                            ->orderColumn('sort_order')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->defaultItems(1)
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('sku')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique(ignoreRecord: true),
                                                        TextInput::make('title')
                                                            ->maxLength(255),
                                                        KeyValue::make('option_values')
                                                            ->label('Option values')
                                                            ->keyLabel('Attribute key')
                                                            ->valueLabel('Value')
                                                            ->columnSpanFull(),
                                                        TextInput::make('price_amount')
                                                            ->label(__('admin.fields.price').' (NZD)')
                                                            ->numeric()
                                                            ->prefix('$')
                                                            ->required(),
                                                        TextInput::make('compare_at_price_amount')
                                                            ->label('Compare-at price (NZD)')
                                                            ->numeric()
                                                            ->prefix('$'),
                                                        Hidden::make('currency')
                                                            ->default('NZD'),
                                                        TextInput::make('stock_quantity')
                                                            ->numeric()
                                                            ->minValue(0),
                                                        Select::make('stock_status')
                                                            ->options(ProductVariant::STOCK_STATUS_OPTIONS)
                                                            ->default('in_stock')
                                                            ->required(),
                                                        Select::make('inventory_policy')
                                                            ->options(ProductVariant::INVENTORY_POLICY_OPTIONS)
                                                            ->default('deny')
                                                            ->required(),
                                                        TextInput::make('low_stock_threshold')
                                                            ->numeric()
                                                            ->default(5)
                                                            ->minValue(0),
                                                        TextInput::make('weight_grams')
                                                            ->numeric()
                                                            ->minValue(0),
                                                        KeyValue::make('dimensions')
                                                            ->columnSpanFull(),
                                                        TextInput::make('image_url')
                                                            ->url()
                                                            ->maxLength(2048),
                                                        FileUpload::make('media_path')
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
                                                            ->numeric()
                                                            ->default(0),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Stock Fallback')
                            ->schema([
                                Section::make('Legacy inventory fallback')
                                    ->description('Used only when a product has no active variant.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('price_usd')
                                                    ->label('Legacy price fallback (NZD)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required(),
                                                TextInput::make('compare_at_price_usd')
                                                    ->label('Legacy compare-at fallback (NZD)')
                                                    ->numeric()
                                                    ->prefix('$'),
                                                Select::make('stock_status')
                                                    ->options(Product::STOCK_STATUS_OPTIONS)
                                                    ->required()
                                                    ->default('in_stock'),
                                                TextInput::make('stock_quantity')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->helperText('Leave blank for preorder or made-to-order fallback items.'),
                                                TextInput::make('weight_grams')
                                                    ->numeric()
                                                    ->minValue(0),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Storefront Content')
                            ->schema([
                                self::localeSection('en', 'English', true),
                                self::localeSection('ko', 'Korean'),
                                self::localeSection('zh', 'Chinese'),
                                Section::make('Selling content')
                                    ->schema([
                                        TagsInput::make('selling_points')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        TagsInput::make('shipping_notes')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        TagsInput::make('return_notes')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        Repeater::make('product_faqs')
                                            ->label('Product FAQs')
                                            ->addActionLabel('Add FAQ')
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('question')
                                                    ->required()
                                                    ->maxLength(180),
                                                Textarea::make('answer')
                                                    ->required()
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Specifications & Attributes')
                            ->schema([
                                Section::make('Dynamic attributes')
                                    ->schema([
                                        Repeater::make('attributeAssignments')
                                            ->relationship()
                                            ->label('Product attributes')
                                            ->addActionLabel('Assign attribute')
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->schema([
                                                Select::make('attribute_definition_id')
                                                    ->label('Attribute')
                                                    ->options(fn (): array => ProductAttributeDefinition::query()
                                                        ->active()
                                                        ->ordered()
                                                        ->pluck('label', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('product_attribute_value_id')
                                                    ->label('Predefined value')
                                                    ->relationship('attributeValue', 'label')
                                                    ->searchable()
                                                    ->preload(),
                                                TextInput::make('value_text')
                                                    ->label('Text / rich text value')
                                                    ->columnSpanFull(),
                                                TextInput::make('value_number')
                                                    ->numeric(),
                                                Toggle::make('value_boolean')
                                                    ->label('Boolean value'),
                                                KeyValue::make('value_json')
                                                    ->label('JSON value')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Legacy specifications')
                                    ->schema([
                                        Select::make('use_cases')
                                            ->multiple()
                                            ->options(Product::USE_CASE_OPTIONS)
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),
                                        TextInput::make('dimensions')
                                            ->maxLength(255),
                                        Repeater::make('specifications')
                                            ->label('Technical specifications')
                                            ->addActionLabel('Add specification')
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('key')
                                                    ->maxLength(80),
                                                TextInput::make('label')
                                                    ->required()
                                                    ->maxLength(120),
                                                TextInput::make('value')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('unit')
                                                    ->maxLength(40),
                                                TextInput::make('group')
                                                    ->maxLength(80),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Material Proof')
                            ->schema([
                                Section::make('Material proof & downloads')
                                    ->description('Use cautious, evidence-backed wording. Leave uncertain documents pending or available on request.')
                                    ->schema([
                                        Repeater::make('certifications')
                                            ->label('Certifications and tests')
                                            ->addActionLabel('Add certification or test')
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Certification / test name')
                                                    ->required()
                                                    ->maxLength(180),
                                                Select::make('status')
                                                    ->options([
                                                        'certified' => 'Certified',
                                                        'tested' => 'Tested',
                                                        'in_testing' => 'In testing',
                                                        'pending' => 'Pending',
                                                        'not_applicable' => 'Not applicable',
                                                    ])
                                                    ->required()
                                                    ->default('pending'),
                                                TextInput::make('result')
                                                    ->label('Value / result')
                                                    ->maxLength(120),
                                                TextInput::make('unit')
                                                    ->maxLength(40),
                                                TextInput::make('issuer')
                                                    ->label('Issuing body / lab')
                                                    ->maxLength(180)
                                                    ->helperText('Use "Client confirmation pending" when a lab is not approved for publication.'),
                                                DatePicker::make('tested_at')
                                                    ->label('Test date'),
                                                TextInput::make('document_url')
                                                    ->label('Document URL')
                                                    ->url()
                                                    ->maxLength(2048),
                                                Textarea::make('description')
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        Repeater::make('technical_downloads')
                                            ->label('Technical downloads')
                                            ->addActionLabel('Add download')
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(180),
                                                Select::make('type')
                                                    ->options([
                                                        'material_data_sheet' => 'Material data sheet',
                                                        'product_specification_sheet' => 'Product specification sheet',
                                                        'certification_document' => 'Certification document',
                                                        'safety_food_contact_document' => 'Safety / food-contact document',
                                                        'catalogue' => 'Catalogue',
                                                    ])
                                                    ->required(),
                                                Select::make('status')
                                                    ->options([
                                                        'available' => 'Available',
                                                        'on_request' => 'Available on request',
                                                        'pending' => 'Pending upload',
                                                    ])
                                                    ->default('on_request')
                                                    ->required(),
                                                TextInput::make('url')
                                                    ->label('File URL')
                                                    ->url()
                                                    ->maxLength(2048),
                                                Textarea::make('description')
                                                    ->rows(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        TagsInput::make('material_benefits')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Images')
                            ->schema([
                                Section::make('Media & related products')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('media_path')
                                                    ->label('Primary image')
                                                    ->image()
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->imagePreviewHeight('180'),
                                                TextInput::make('image_url')
                                                    ->label('External primary image URL')
                                                    ->url()
                                                    ->maxLength(2048),
                                                Select::make('relatedProducts')
                                                    ->label('Related products')
                                                    ->relationship('relatedProducts', 'name')
                                                    ->multiple()
                                                    ->searchable()
                                                    ->preload()
                                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name.' ('.$record->effectiveSku().')')
                                                    ->columnSpanFull(),
                                            ]),
                                        Repeater::make('images')
                                            ->relationship()
                                            ->label('Gallery images')
                                            ->addActionLabel('Add gallery image')
                                            ->orderColumn('sort_order')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->grid(2)
                                            ->schema([
                                                FileUpload::make('media_path')
                                                    ->label('Image')
                                                    ->image()
                                                    ->required()
                                                    ->disk((string) config('community.uploads.disk'))
                                                    ->directory('cms/products/gallery')
                                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                                    ->imagePreviewHeight('140'),
                                                TextInput::make('alt_text_translations.en')
                                                    ->label('Alt text (EN)')
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.en')
                                                    ->label('Caption (EN)')
                                                    ->maxLength(255),
                                                TextInput::make('alt_text_translations.ko')
                                                    ->label('Alt text (KO)')
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.ko')
                                                    ->label('Caption (KO)')
                                                    ->maxLength(255),
                                                TextInput::make('alt_text_translations.zh')
                                                    ->label('Alt text (ZH)')
                                                    ->maxLength(255),
                                                TextInput::make('caption_translations.zh')
                                                    ->label('Caption (ZH)')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('B2B / Sample')
                            ->schema([
                                Section::make('B2B conversion')
                                    ->schema([
                                        Placeholder::make('b2b_flags')
                                            ->label('Conversion flags')
                                            ->content('Sample request and inquiry-only flags are managed in Overview.'),
                                        TextInput::make('lead_time')
                                            ->maxLength(255),
                                        TagsInput::make('care_instructions')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('SEO')
                            ->schema([
                                Section::make('Search metadata')
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->maxLength(255),
                                        Textarea::make('seo_description')
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
            return 'Save the product, then review image, category, price, default variant, and English title before publishing.';
        }

        $warnings = collect([
            $record->primaryImageUrl() ? null : 'No image',
            $record->defaultVariant() ? null : 'No active default variant',
            ($record->effectivePrice() ?? 0) > 0 ? null : 'No price',
            filled(data_get($record->name_translations, 'en') ?: $record->name) ? null : 'Missing English title/name',
            $record->category_id ? null : 'No category',
        ])->filter()->values();

        return $warnings->isEmpty()
            ? 'Ready to publish.'
            : 'Warnings: '.$warnings->implode(', ');
    }

    private static function localeSection(string $locale, string $label, bool $isRequired = false): Section
    {
        $isEnglish = $locale === 'en';

        return Section::make($label)
            ->schema([
                TextInput::make("name_translations.{$locale}")
                    ->label('Name')
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
                    ->label('Subtitle')
                    ->maxLength(255),
                Textarea::make("short_description_translations.{$locale}")
                    ->label('Short description')
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make("full_description_translations.{$locale}")
                    ->label('Full description')
                    ->rows(8)
                    ->columnSpanFull(),
                TextInput::make("availability_text_translations.{$locale}")
                    ->label('Availability text')
                    ->maxLength(255),
                TextInput::make("lead_time_translations.{$locale}")
                    ->label('Lead time')
                    ->maxLength(255),
                TextInput::make("dimensions_translations.{$locale}")
                    ->label('Dimensions')
                    ->maxLength(255),
                TagsInput::make("features_translations.{$locale}")
                    ->label('Features')
                    ->separator(',')
                    ->columnSpanFull(),
                TagsInput::make("care_instructions_translations.{$locale}")
                    ->label('Care instructions')
                    ->separator(',')
                    ->columnSpanFull(),
                TagsInput::make("material_benefits_translations.{$locale}")
                    ->label('Material benefits')
                    ->separator(',')
                    ->columnSpanFull(),
                TextInput::make("seo_title_translations.{$locale}")
                    ->label('SEO title')
                    ->maxLength(255),
                Textarea::make("seo_description_translations.{$locale}")
                    ->label('SEO description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
