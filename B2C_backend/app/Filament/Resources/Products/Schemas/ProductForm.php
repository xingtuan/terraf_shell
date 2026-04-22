<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                    ->default('USD'),
                Section::make('Catalogue Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('sku')
                                    ->maxLength(255)
                                    ->helperText('Leave blank to generate a SKU from the slug.'),
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
                                Select::make('model')
                                    ->options(Product::MODEL_OPTIONS)
                                    ->required(),
                                Select::make('finish')
                                    ->options(Product::FINISH_OPTIONS)
                                    ->required(),
                                Select::make('color')
                                    ->options(Product::COLOR_OPTIONS)
                                    ->required(),
                                Select::make('technique')
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
                            ]),
                    ]),
                Section::make('Storefront Merchandising')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('price_usd')
                                    ->label('Price (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                TextInput::make('compare_at_price_usd')
                                    ->label('Compare-at price (USD)')
                                    ->numeric()
                                    ->prefix('$'),
                                Select::make('stock_status')
                                    ->options(Product::STOCK_STATUS_OPTIONS)
                                    ->required()
                                    ->default('in_stock'),
                                TextInput::make('stock_quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Leave blank for preorder or made-to-order items.'),
                                Select::make('use_cases')
                                    ->multiple()
                                    ->options(Product::USE_CASE_OPTIONS)
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                                Toggle::make('inquiry_only')
                                    ->label('Inquiry only')
                                    ->helperText('Hide add-to-cart and route conversion to the lead forms.'),
                                Toggle::make('sample_request_enabled')
                                    ->label('Sample request enabled')
                                    ->helperText('Expose this product in sample-led B2B workflows.'),
                                TextInput::make('weight_grams')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                    ]),
                self::localeSection('en', 'English', true),
                self::localeSection('ko', 'Korean'),
                self::localeSection('zh', 'Chinese'),
                Section::make('Specifications')
                    ->schema([
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
                Section::make('Media & Related Products')
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
                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name.' ('.$record->sku.')')
                                    ->columnSpanFull()
                                    ->helperText('Optional products to surface on the detail page.'),
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
            ]);
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
                TagsInput::make("certifications_translations.{$locale}")
                    ->label('Certifications')
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
