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
                Section::make(__('admin.ui.tag'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('slug'),
                                TextEntry::make('posts_count')
                                    ->label(__('admin.ui.posts'))
                                    ->state(fn (Tag $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('updated_at')
                                    ->label(__('admin.ui.updated'))
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextEntry::make('name_translations.en')
                            ->label(__('admin.fields.name'))
                            ->placeholder('-'),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('name_translations.ko')
                            ->label(__('admin.fields.name'))
                            ->placeholder('-'),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('name_translations.zh')
                            ->label(__('admin.fields.name'))
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
