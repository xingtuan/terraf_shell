<?php

namespace App\Filament\Resources\ProductImages\Schemas;

use App\Filament\Support\AdminUploadStorage;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.media'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('admin.ui.product'))
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name)
                                    ->required(),
                                TextInput::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label(__('admin.ui.image'))
                                    ->image()
                                    ->required()
                                    ->disk(fn (): string => AdminUploadStorage::disk())
                                    ->directory('cms/products/gallery')
                                    ->visibility(fn (): string => AdminUploadStorage::visibility())
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('alt_text_translations.en')
                            ->label(__('admin.ui.alt_text'))
                            ->maxLength(255),
                        TextInput::make('caption_translations.en')
                            ->label(__('admin.ui.caption'))
                            ->maxLength(255),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('alt_text_translations.ko')
                            ->label(__('admin.ui.alt_text'))
                            ->maxLength(255),
                        TextInput::make('caption_translations.ko')
                            ->label(__('admin.ui.caption'))
                            ->maxLength(255),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('alt_text_translations.zh')
                            ->label(__('admin.ui.alt_text'))
                            ->maxLength(255),
                        TextInput::make('caption_translations.zh')
                            ->label(__('admin.ui.caption'))
                            ->maxLength(255),
                    ]),
            ]);
    }
}
