<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Enums\ContentStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentInfolist
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
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('post.title')
                                    ->label('Post')
                                    ->columnSpanFull(),
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('parent.content')
                                    ->label('Parent comment')
                                    ->placeholder('Top-level comment.')
                                    ->limit(120)
                                    ->columnSpanFull(),
                                TextEntry::make('likes_count')
                                    ->label('Likes'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make('Comment')
                    ->schema([
                        TextEntry::make('content')
                            ->columnSpanFull(),
                    ]),
                Section::make('Replies')
                    ->schema([
                        RepeatableEntry::make('replies')
                            ->label('Replies')
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ContentStatus::tryFrom($state)?->label() ?? ucfirst($state))
                                    ->color(fn (string $state): string => ContentStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('content')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
