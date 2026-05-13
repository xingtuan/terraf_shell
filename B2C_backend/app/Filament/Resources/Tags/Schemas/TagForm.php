<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.tag'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->label(__('admin.ui.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                            ]),
                    ]),
                Section::make(__('admin.ui.english'))
                    ->schema([
                        TextInput::make('name_translations.en')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                    ]),
                Section::make(__('admin.ui.korean'))
                    ->schema([
                        TextInput::make('name_translations.ko')
                            ->label(__('admin.fields.name'))
                            ->maxLength(255),
                    ]),
                Section::make(__('admin.ui.chinese'))
                    ->schema([
                        TextInput::make('name_translations.zh')
                            ->label(__('admin.fields.name'))
                            ->maxLength(255),
                    ]),
            ]);
    }
}
