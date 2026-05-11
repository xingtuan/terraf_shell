<?php

namespace App\Filament\Resources\ProductImages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('media_url')
                    ->label(__('admin.ui.image'))
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x64?text=Media'),
                TextColumn::make('product.name')
                    ->label(__('admin.ui.product'))
                    ->searchable(),
                TextColumn::make('alt_text')
                    ->label(__('admin.ui.alt_text'))
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label(__('admin.ui.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
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
