<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.article_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('slug'),
                                TextEntry::make('status'),
                                TextEntry::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->numeric(),
                                TextEntry::make('media_path')
                                    ->label(__('admin.ui.uploaded_media'))
                                    ->placeholder('-'),
                                TextEntry::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->placeholder('-'),
                                TextEntry::make('published_at')
                                    ->label(__('admin.ui.published_at'))
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->label(__('admin.fields.updated_at'))
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextEntry::make('title_translations.en')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('category_translations.en')
                            ->label(__('admin.ui.category'))
                            ->placeholder('-'),
                        TextEntry::make('excerpt_translations.en')
                            ->label(__('admin.ui.excerpt'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('title_translations.ko')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('category_translations.ko')
                            ->label(__('admin.ui.category'))
                            ->placeholder('-'),
                        TextEntry::make('excerpt_translations.ko')
                            ->label(__('admin.ui.excerpt'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('content_translations.ko')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('title_translations.zh')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('category_translations.zh')
                            ->label(__('admin.ui.category'))
                            ->placeholder('-'),
                        TextEntry::make('excerpt_translations.zh')
                            ->label(__('admin.ui.excerpt'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('content_translations.zh')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
