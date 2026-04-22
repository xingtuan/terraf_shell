<?php

namespace App\Filament\Resources\Materials\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MaterialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('headline')
                    ->placeholder('-'),
                TextEntry::make('summary')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('story_overview')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('science_overview')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                IconEntry::make('is_featured')
                    ->boolean(),
                TextEntry::make('specs_count')
                    ->label('Specifications')
                    ->numeric(),
                TextEntry::make('story_sections_count')
                    ->label('Story sections')
                    ->numeric(),
                TextEntry::make('applications_count')
                    ->label('Applications')
                    ->numeric(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('media_url')
                    ->placeholder('-')
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab(),
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
