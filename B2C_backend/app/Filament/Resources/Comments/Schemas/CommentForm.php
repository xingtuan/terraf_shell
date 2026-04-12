<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Enums\ContentStatus;
use App\Models\Comment;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Context')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('post_context')
                                    ->label('Post')
                                    ->content(fn (?Comment $record): string => $record?->post?->title ?? 'Unknown post'),
                                Placeholder::make('author_context')
                                    ->label('User')
                                    ->content(fn (?Comment $record): string => $record?->user?->name ? $record->user->name.' (@'.$record->user->username.')' : 'Unknown user'),
                                Placeholder::make('parent_context')
                                    ->label('Parent comment')
                                    ->content(fn (?Comment $record): string => filled($record?->parent?->content) ? str($record->parent->content)->limit(100)->toString() : 'Top-level comment')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Moderation')
                    ->schema([
                        Select::make('status')
                            ->options(ContentStatus::options())
                            ->required(),
                        Textarea::make('content')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
