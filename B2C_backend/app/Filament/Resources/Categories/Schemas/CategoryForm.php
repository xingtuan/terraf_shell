<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.category_settings'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->label(__('admin.ui.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Toggle::make('is_active')
                                    ->label(__('admin.ui.active'))
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label(__('admin.ui.sort_order'))
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('name_translations.en')
                            ->label(__('admin.ui.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        Textarea::make('description_translations.en')
                            ->label(__('admin.ui.description'))
                            ->rows(4),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('name_translations.ko')
                            ->label(__('admin.ui.name'))
                            ->maxLength(255),
                        Textarea::make('description_translations.ko')
                            ->label(__('admin.ui.description'))
                            ->rows(4),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('name_translations.zh')
                            ->label(__('admin.ui.name'))
                            ->maxLength(255),
                        Textarea::make('description_translations.zh')
                            ->label(__('admin.ui.description'))
                            ->rows(4),
                    ]),
            ]);
    }
}
