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
                Section::make(__('admin.ui.context'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('post_context')
                                    ->label(__('admin.ui.post'))
                                    ->content(fn (?Comment $record): string => $record?->post?->title ?? __('admin.ui.unknown_post')),
                                Placeholder::make('author_context')
                                    ->label(__('admin.ui.user'))
                                    ->content(fn (?Comment $record): string => $record?->user?->name ? $record->user->name.' (@'.$record->user->username.')' : __('admin.ui.unknown_user')),
                                Placeholder::make('parent_context')
                                    ->label(__('admin.ui.parent_comment'))
                                    ->content(fn (?Comment $record): string => filled($record?->parent?->content) ? str($record->parent->content)->limit(100)->toString() : __('admin.ui.top_level_comment'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.moderation'))
                    ->schema([
                        Select::make('status')
                            ->label(__('admin.fields.status'))
                            ->options(ContentStatus::options())
                            ->required(),
                        Textarea::make('content')
                            ->label(__('admin.ui.content'))
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
