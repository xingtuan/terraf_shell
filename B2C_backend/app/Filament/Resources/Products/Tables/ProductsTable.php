<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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
                ImageColumn::make('media_url')
                    ->label('Cover')
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x64?text=Product'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => ProductStatus::tryFrom($state)?->color() ?? 'gray'),
                IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean(),
                IconColumn::make('inquiry_only')
                    ->label('Inquiry only')
                    ->boolean(),
                TextColumn::make('price_from')
                    ->label('Price from')
                    ->formatStateUsing(fn ($state, $record): string => $state === null ? '-' : $record->currency.' '.number_format((float) $state, 2)),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatus::options()),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Category'),
                TernaryFilter::make('featured')
                    ->label('Featured'),
                TernaryFilter::make('inquiry_only')
                    ->label('Inquiry only'),
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
