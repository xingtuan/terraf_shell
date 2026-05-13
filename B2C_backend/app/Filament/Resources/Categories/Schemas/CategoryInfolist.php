<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.category_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('slug'),
                                TextEntry::make('is_active')
                                    ->label(__('admin.ui.status'))
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.ui.active') : __('admin.ui.inactive'))
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('sort_order'),
                                TextEntry::make('posts_count')
                                    ->label(__('admin.ui.posts'))
                                    ->state(fn (Category $record): int => (int) ($record->posts_count ?? $record->posts()->count())),
                                TextEntry::make('updated_at')
                                    ->label(__('admin.ui.updated'))
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextEntry::make('name_translations.en')
                            ->label(__('admin.ui.name'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.en')
                            ->label(__('admin.ui.description'))
                            ->placeholder(__('admin.ui.no_description_provided'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('name_translations.ko')
                            ->label(__('admin.ui.name'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.ko')
                            ->label(__('admin.ui.description'))
                            ->placeholder(__('admin.ui.no_description_provided'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('name_translations.zh')
                            ->label(__('admin.ui.name'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.zh')
                            ->label(__('admin.ui.description'))
                            ->placeholder(__('admin.ui.no_description_provided'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
