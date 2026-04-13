<?php

namespace App\Filament\Resources\IdeaMedia\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IdeaMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->maxLength(255),
                                Select::make('kind')
                                    ->options([
                                        'sketch' => 'Sketch',
                                        'concept_image' => 'Concept Image',
                                        'render_image' => 'Render Image',
                                        'presentation_pdf' => 'PDF Presentation',
                                        'spec_sheet' => 'Spec Sheet',
                                        'model_3d' => '3D Model',
                                    ]),
                                TextInput::make('alt_text')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('original_name')
                                    ->label('Original filename')
                                    ->disabled(),
                                TextInput::make('mime_type')
                                    ->label('MIME type')
                                    ->disabled(),
                                TextInput::make('size_bytes')
                                    ->label('Size (bytes)')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('external_url')
                                    ->label('External URL')
                                    ->url()
                                    ->maxLength(2048),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
