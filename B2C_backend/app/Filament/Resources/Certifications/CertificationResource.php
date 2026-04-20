<?php

namespace App\Filament\Resources\Certifications;

use App\Filament\Resources\Certifications\Pages\CreateCertification;
use App\Filament\Resources\Certifications\Pages\EditCertification;
use App\Filament\Resources\Certifications\Pages\ListCertifications;
use App\Filament\Support\PanelAccess;
use App\Models\Certification;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CertificationResource extends Resource
{
    protected static ?string $model = Certification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Certifications';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255),
                Select::make('locale')
                    ->options(self::localeOptions())
                    ->required(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                TextInput::make('badge_color')
                    ->maxLength(255)
                    ->placeholder('#2D6A4F'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->required()
                    ->default(0),
                Textarea::make('description')
                    ->rows(5)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('value')
                    ->limit(40),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('locale')
                    ->options(self::localeOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canCreate(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertifications::route('/'),
            'create' => CreateCertification::route('/create'),
            'edit' => EditCertification::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function localeOptions(): array
    {
        return [
            'en' => 'en',
            'ko' => 'ko',
            'zh' => 'zh',
        ];
    }
}
