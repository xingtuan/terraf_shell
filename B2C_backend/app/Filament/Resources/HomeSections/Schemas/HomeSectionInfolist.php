<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeSectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.section_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('page_key')
                                    ->label('Page'),
                                TextEntry::make('key')
                                    ->label(__('admin.ui.section_key')),
                                TextEntry::make('status'),
                                TextEntry::make('sort_order')
                                    ->numeric(),
                                TextEntry::make('cta_url')
                                    ->label(__('admin.ui.cta_url'))
                                    ->placeholder('-'),
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
                        TextEntry::make('subtitle_translations.en')
                            ->label(__('admin.ui.subtitle'))
                            ->placeholder('-'),
                        TextEntry::make('content_translations.en')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('cta_label_translations.en')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('title_translations.ko')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('subtitle_translations.ko')
                            ->label(__('admin.ui.subtitle'))
                            ->placeholder('-'),
                        TextEntry::make('content_translations.ko')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('cta_label_translations.ko')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('title_translations.zh')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('subtitle_translations.zh')
                            ->label(__('admin.ui.subtitle'))
                            ->placeholder('-'),
                        TextEntry::make('content_translations.zh')
                            ->label(__('admin.ui.content'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('cta_label_translations.zh')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
