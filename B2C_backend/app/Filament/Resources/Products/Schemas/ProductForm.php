<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Models\ProductCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                Section::make('Product Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (ProductCategory $record): string => $record->name)
                                    ->required(),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('status')
                                    ->options(ProductStatus::options())
                                    ->required()
                                    ->default(ProductStatus::Draft->value),
                                Toggle::make('featured')
                                    ->label('Featured')
                                    ->default(false),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                TextInput::make('price_from')
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('currency')
                                    ->required()
                                    ->maxLength(3)
                                    ->default('USD'),
                                Toggle::make('inquiry_only')
                                    ->label('Inquiry only')
                                    ->default(false),
                                Toggle::make('sample_request_enabled')
                                    ->label('Sample request enabled')
                                    ->default(true),
                                FileUpload::make('media_path')
                                    ->label('Cover image')
                                    ->image()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/products')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('name_translations.en')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        Textarea::make('short_description_translations.en')
                            ->label('Short description')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('full_description_translations.en')
                            ->label('Full description')
                            ->rows(8)
                            ->columnSpanFull(),
                        TextInput::make('availability_text_translations.en')
                            ->label('Availability text')
                            ->maxLength(255),
                        Repeater::make('features_translations.en')
                            ->label('Features')
                            ->simple(
                                TextInput::make('value')
                                    ->required()
                                    ->maxLength(255)
                            )
                            ->defaultItems(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('name_translations.ko')
                            ->label('Name')
                            ->maxLength(255),
                        Textarea::make('short_description_translations.ko')
                            ->label('Short description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('full_description_translations.ko')
                            ->label('Full description')
                            ->rows(8)
                            ->columnSpanFull(),
                        TextInput::make('availability_text_translations.ko')
                            ->label('Availability text')
                            ->maxLength(255),
                        Repeater::make('features_translations.ko')
                            ->label('Features')
                            ->simple(
                                TextInput::make('value')
                                    ->maxLength(255)
                            )
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('name_translations.zh')
                            ->label('Name')
                            ->maxLength(255),
                        Textarea::make('short_description_translations.zh')
                            ->label('Short description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('full_description_translations.zh')
                            ->label('Full description')
                            ->rows(8)
                            ->columnSpanFull(),
                        TextInput::make('availability_text_translations.zh')
                            ->label('Availability text')
                            ->maxLength(255),
                        Repeater::make('features_translations.zh')
                            ->label('Features')
                            ->simple(
                                TextInput::make('value')
                                    ->maxLength(255)
                            )
                            ->columnSpanFull(),
                    ]),
                Section::make('Gallery Images')
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->label('Gallery')
                            ->addActionLabel('Add image')
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->schema([
                                FileUpload::make('media_path')
                                    ->label('Image')
                                    ->image()
                                    ->required()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/products/gallery')
                                    ->visibility('public')
                                    ->imagePreviewHeight('140'),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                TextInput::make('alt_text_translations.en')
                                    ->label('Alt text (EN)')
                                    ->maxLength(255),
                                TextInput::make('alt_text_translations.ko')
                                    ->label('Alt text (KO)')
                                    ->maxLength(255),
                                TextInput::make('alt_text_translations.zh')
                                    ->label('Alt text (ZH)')
                                    ->maxLength(255),
                                TextInput::make('caption_translations.en')
                                    ->label('Caption (EN)')
                                    ->maxLength(255),
                                TextInput::make('caption_translations.ko')
                                    ->label('Caption (KO)')
                                    ->maxLength(255),
                                TextInput::make('caption_translations.zh')
                                    ->label('Caption (ZH)')
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
