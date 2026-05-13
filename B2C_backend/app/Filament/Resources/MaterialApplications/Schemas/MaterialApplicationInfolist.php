<?php

namespace App\Filament\Resources\MaterialApplications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.application_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('material.title')
                                    ->label(__('admin.ui.material')),
                                TextEntry::make('status'),
                                TextEntry::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->numeric(),
                                TextEntry::make('cta_url')
                                    ->label(__('admin.ui.cta_url'))
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
                        TextEntry::make('audience_translations.en')
                            ->label(__('admin.ui.audience'))
                            ->placeholder('-'),
                        TextEntry::make('cta_label_translations.en')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.en')
                            ->label(__('admin.ui.description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('title_translations.ko')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('subtitle_translations.ko')
                            ->label(__('admin.ui.subtitle'))
                            ->placeholder('-'),
                        TextEntry::make('audience_translations.ko')
                            ->label(__('admin.ui.audience'))
                            ->placeholder('-'),
                        TextEntry::make('cta_label_translations.ko')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.ko')
                            ->label(__('admin.ui.description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('title_translations.zh')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('subtitle_translations.zh')
                            ->label(__('admin.ui.subtitle'))
                            ->placeholder('-'),
                        TextEntry::make('audience_translations.zh')
                            ->label(__('admin.ui.audience'))
                            ->placeholder('-'),
                        TextEntry::make('cta_label_translations.zh')
                            ->label(__('admin.ui.cta_label'))
                            ->placeholder('-'),
                        TextEntry::make('description_translations.zh')
                            ->label(__('admin.ui.description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
