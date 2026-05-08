<?php

namespace App\Filament\Resources\IdeaMedia\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IdeaMediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label(__('admin.ui.preview'))
                    ->square()
                    ->defaultImageUrl('https://placehold.co/96x96?text=File'),
                TextColumn::make('post.title')
                    ->label(__('admin.ui.concept'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('post.user.name')
                    ->label(__('admin.ui.creator'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('media_type')
                    ->label(__('admin.ui.type'))
                    ->badge(),
                TextColumn::make('kind')
                    ->badge()
                    ->placeholder(__('admin.ui.unclassified')),
                TextColumn::make('original_name')
                    ->label(__('admin.ui.filename'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('mime_type')
                    ->label(__('admin.ui.mime'))
                    ->toggleable(),
                TextColumn::make('size_bytes')
                    ->label(__('admin.ui.size_bytes'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.uploaded'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('media_type')
                    ->options([
                        'image' => 'Image',
                        'document' => 'Document',
                        'external_3d' => 'External 3D',
                    ]),
                SelectFilter::make('source_type')
                    ->options([
                        'upload' => 'Upload',
                        'external_url' => 'External URL',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
