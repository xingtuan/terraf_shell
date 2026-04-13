<?php

namespace App\Filament\Resources\MaterialSpecs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MaterialSpecInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('material.title')
                    ->label('Material'),
                TextEntry::make('key')
                    ->placeholder('-'),
                TextEntry::make('label'),
                TextEntry::make('value'),
                TextEntry::make('unit')
                    ->placeholder('-'),
                TextEntry::make('detail')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('icon')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('media_path')
                    ->placeholder('-'),
                TextEntry::make('media_url')
                    ->placeholder('-'),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
