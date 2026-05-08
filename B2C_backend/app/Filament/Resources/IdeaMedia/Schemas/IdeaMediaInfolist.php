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
                Section::make(__('admin.ui.media'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                ImageEntry::make('thumbnail_url')
                                    ->label(__('admin.ui.preview'))
                                    ->height(180),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('post.title')
                                            ->label(__('admin.ui.concept'))
                                            ->columnSpanFull(),
                                        TextEntry::make('post.user.name')
                                            ->label(__('admin.ui.creator')),
                                        TextEntry::make('media_type')
                                            ->badge(),
                                        TextEntry::make('kind')
                                            ->badge()
                                            ->placeholder(__('admin.ui.unclassified')),
                                        TextEntry::make('original_name')
                                            ->label(__('admin.ui.filename'))
                                            ->placeholder(__('admin.ui.no_file_name')),
                                        TextEntry::make('mime_type')
                                            ->label(__('admin.ui.mime'))
                                            ->placeholder(__('admin.ui.no_mime_type')),
                                        TextEntry::make('size_bytes')
                                            ->label(__('admin.ui.size_bytes'))
                                            ->numeric()
                                            ->placeholder(__('admin.ui.unknown_size')),
                                        TextEntry::make('external_url')
                                            ->label(__('admin.ui.external_url'))
                                            ->placeholder(__('admin.ui.no_external_url'))
                                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                            ->openUrlInNewTab(),
                                        TextEntry::make('url')
                                            ->label(__('admin.ui.stored_url'))
                                            ->placeholder(__('admin.ui.no_stored_url'))
                                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                            ->openUrlInNewTab()
                                            ->columnSpanFull(),
                                        TextEntry::make('alt_text')
                                            ->placeholder(__('admin.ui.no_alt_text'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
