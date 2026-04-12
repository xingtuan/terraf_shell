<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ContentStatus;
use App\Models\Post;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('slug'),
                                TextEntry::make('title')
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label('Author'),
                                TextEntry::make('category.name')
                                    ->label('Category')
                                    ->placeholder('No category assigned.'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('published_at')
                                    ->dateTime()
                                    ->placeholder('Not published.'),
                                TextEntry::make('is_pinned')
                                    ->label('Pinned')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                                TextEntry::make('is_featured')
                                    ->label('Featured')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('tags_list')
                                    ->label('Tags')
                                    ->state(fn (Post $record): string => $record->tags->pluck('name')->implode(', ') ?: 'No tags assigned.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Content')
                    ->schema([
                        TextEntry::make('excerpt')
                            ->placeholder('No excerpt available.')
                            ->columnSpanFull(),
                        TextEntry::make('content')
                            ->columnSpanFull(),
                    ]),
                Section::make('Images')
                    ->schema([
                        RepeatableEntry::make('images')
                            ->label('Post images')
                            ->schema([
                                ImageEntry::make('url')
                                    ->label('Image')
                                    ->height(140),
                                TextEntry::make('alt_text')
                                    ->label('Alt text')
                                    ->placeholder('No alt text provided.'),
                            ]),
                    ]),
            ]);
    }
}
