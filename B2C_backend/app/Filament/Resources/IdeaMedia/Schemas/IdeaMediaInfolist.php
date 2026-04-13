<?php

namespace App\Filament\Resources\IdeaMedia\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IdeaMediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('thumbnail_url')
                                    ->label('Preview')
                                    ->height(180),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('post.title')
                                            ->label('Concept')
                                            ->columnSpanFull(),
                                        TextEntry::make('post.user.name')
                                            ->label('Creator'),
                                        TextEntry::make('media_type')
                                            ->badge(),
                                        TextEntry::make('kind')
                                            ->badge()
                                            ->placeholder('Unclassified'),
                                        TextEntry::make('original_name')
                                            ->label('Filename')
                                            ->placeholder('No file name.'),
                                        TextEntry::make('mime_type')
                                            ->label('MIME')
                                            ->placeholder('No MIME type.'),
                                        TextEntry::make('size_bytes')
                                            ->label('Size (bytes)')
                                            ->numeric()
                                            ->placeholder('Unknown size.'),
                                        TextEntry::make('external_url')
                                            ->label('External URL')
                                            ->placeholder('No external URL.')
                                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                            ->openUrlInNewTab(),
                                        TextEntry::make('url')
                                            ->label('Stored URL')
                                            ->placeholder('No stored URL.')
                                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                            ->openUrlInNewTab()
                                            ->columnSpanFull(),
                                        TextEntry::make('alt_text')
                                            ->placeholder('No alt text.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
