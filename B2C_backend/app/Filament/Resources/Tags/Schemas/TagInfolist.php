<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Models\Tag;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tag')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('slug'),
                                TextEntry::make('posts_count')
                                    ->label('Posts')
                                    ->state(fn (Tag $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
