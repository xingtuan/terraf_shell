<?php

namespace App\Filament\Resources\Materials\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.material_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('slug'),
                                TextEntry::make('status'),
                                IconEntry::make('is_featured')
                                    ->boolean(),
                                TextEntry::make('sort_order')
                                    ->numeric(),
                                TextEntry::make('specs_count')
                                    ->label(__('admin.ui.specifications'))
                                    ->numeric(),
                                TextEntry::make('story_sections_count')
                                    ->label(__('admin.ui.story_sections'))
                                    ->numeric(),
                                TextEntry::make('applications_count')
                                    ->label(__('admin.ui.applications'))
                                    ->numeric(),
                                TextEntry::make('certifications')
                                    ->label(__('admin.ui.certifications_tests'))
                                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state).' records' : '-'),
                                TextEntry::make('technical_downloads')
                                    ->label(__('admin.ui.technical_downloads'))
                                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state).' records' : '-'),
                                TextEntry::make('media_url')
                                    ->label(__('admin.ui.external_media_url'))
                                    ->placeholder('-')
                                    ->url(fn (?string $state): ?string => $state)
                                    ->openUrlInNewTab(),
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
                        TextEntry::make('headline_translations.en')
                            ->label(__('admin.ui.headline'))
                            ->placeholder('-'),
                        TextEntry::make('summary_translations.en')
                            ->label(__('admin.ui.summary'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('story_overview_translations.en')
                            ->label(__('admin.ui.story_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('science_overview_translations.en')
                            ->label(__('admin.ui.science_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextEntry::make('title_translations.ko')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('headline_translations.ko')
                            ->label(__('admin.ui.headline'))
                            ->placeholder('-'),
                        TextEntry::make('summary_translations.ko')
                            ->label(__('admin.ui.summary'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('story_overview_translations.ko')
                            ->label(__('admin.ui.story_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('science_overview_translations.ko')
                            ->label(__('admin.ui.science_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextEntry::make('title_translations.zh')
                            ->label(__('admin.ui.title'))
                            ->placeholder('-'),
                        TextEntry::make('headline_translations.zh')
                            ->label(__('admin.ui.headline'))
                            ->placeholder('-'),
                        TextEntry::make('summary_translations.zh')
                            ->label(__('admin.ui.summary'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('story_overview_translations.zh')
                            ->label(__('admin.ui.story_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('science_overview_translations.zh')
                            ->label(__('admin.ui.science_overview'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
