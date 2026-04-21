<?php

namespace App\Filament\Resources\ProductImages\Schemas;

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
                Section::make('Media')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name)
                                    ->required(),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                FileUpload::make('media_path')
                                    ->label('Image')
                                    ->image()
                                    ->required()
                                    ->disk((string) config('community.uploads.disk'))
                                    ->directory('cms/products/gallery')
                                    ->visibility((string) config('community.uploads.disk') === 'azure' ? 'private' : 'public')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('alt_text_translations.en')
                            ->label('Alt text')
                            ->maxLength(255),
                        TextInput::make('caption_translations.en')
                            ->label('Caption')
                            ->maxLength(255),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('alt_text_translations.ko')
                            ->label('Alt text')
                            ->maxLength(255),
                        TextInput::make('caption_translations.ko')
                            ->label('Caption')
                            ->maxLength(255),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('alt_text_translations.zh')
                            ->label('Alt text')
                            ->maxLength(255),
                        TextInput::make('caption_translations.zh')
                            ->label('Caption')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
