<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                            ]),
                    ]),
                Section::make('English')
                    ->schema([
                        TextInput::make('name_translations.en')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        Textarea::make('description_translations.en')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Korean')
                    ->schema([
                        TextInput::make('name_translations.ko')
                            ->label('Name')
                            ->maxLength(255),
                        Textarea::make('description_translations.ko')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Chinese')
                    ->schema([
                        TextInput::make('name_translations.zh')
                            ->label('Name')
                            ->maxLength(255),
                        Textarea::make('description_translations.zh')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
