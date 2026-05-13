<?php

namespace App\Filament\Resources\MaterialSpecs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialSpecInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.specification_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('material.title')
                                    ->label(__('admin.ui.material')),
                                TextEntry::make('key')
                                    ->placeholder('-'),
                                TextEntry::make('unit')
                                    ->label(__('admin.ui.unit'))
                                    ->placeholder('-'),
                                TextEntry::make('icon')
                                    ->label(__('admin.ui.icon'))
                                    ->placeholder('-'),
                                TextEntry::make('status'),
                                TextEntry::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->numeric(),
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
                        TextEntry::make('label_translations.en')
                            ->label(__('admin.ui.label'))
                            ->placeholder('-'),
                        TextEntry::make('value_translations.en')
                            ->label(__('admin.ui.value'))
                            ->placeholder('-'),
                        TextEntry::make('detail_translations.en')
                            ->label(__('admin.ui.detail'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('label_translations.ko')
                            ->label(__('admin.ui.label'))
                            ->placeholder('-'),
                        TextEntry::make('value_translations.ko')
                            ->label(__('admin.ui.value'))
                            ->placeholder('-'),
                        TextEntry::make('detail_translations.ko')
                            ->label(__('admin.ui.detail'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('label_translations.zh')
                            ->label(__('admin.ui.label'))
                            ->placeholder('-'),
                        TextEntry::make('value_translations.zh')
                            ->label(__('admin.ui.value'))
                            ->placeholder('-'),
                        TextEntry::make('detail_translations.zh')
                            ->label(__('admin.ui.detail'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
