<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Product::CATEGORY_OPTIONS[$state] ?? $state),
                TextColumn::make('model')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Product::MODEL_OPTIONS[$state] ?? $state),
                TextColumn::make('price_usd')
                    ->label('Price USD')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2)),
                IconColumn::make('in_stock')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(Product::CATEGORY_OPTIONS),
                SelectFilter::make('model')
                    ->options(Product::MODEL_OPTIONS),
                SelectFilter::make('color')
                    ->options(Product::COLOR_OPTIONS),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
