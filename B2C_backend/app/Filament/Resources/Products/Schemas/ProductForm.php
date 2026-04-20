<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
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
                Section::make('Product')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('slug', Str::slug((string) $state));
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('category')
                                    ->options(Product::CATEGORY_OPTIONS)
                                    ->required(),
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
                                TextInput::make('price_usd')
                                    ->label('Price (USD)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Toggle::make('in_stock')
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->default(true),
                                TextInput::make('image_url')
                                    ->label('Image URL')
                                    ->maxLength(2048)
                                    ->columnSpanFull(),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
