<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('slug'),
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('sort_order'),
                                TextEntry::make('posts_count')
                                    ->label('Posts')
                                    ->state(fn (Category $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime(),
                                TextEntry::make('description')
                                    ->placeholder('No description provided.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
