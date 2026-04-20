<?php

namespace App\Filament\Resources\ProcessSteps;

use App\Filament\Resources\ProcessSteps\Pages\CreateProcessStep;
use App\Filament\Resources\ProcessSteps\Pages\EditProcessStep;
use App\Filament\Resources\ProcessSteps\Pages\ListProcessSteps;
use App\Filament\Support\PanelAccess;
use App\Models\ProcessStep;
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

class ProcessStepResource extends Resource
{
    protected static ?string $model = ProcessStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Process Steps';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('step_number')
                    ->numeric()
                    ->required(),
                Select::make('locale')
                    ->options(self::localeOptions())
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('icon')
                    ->maxLength(255),
                Textarea::make('body')
                    ->required()
                    ->rows(6)
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
                TextColumn::make('step_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
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
            'index' => ListProcessSteps::route('/'),
            'create' => CreateProcessStep::route('/create'),
            'edit' => EditProcessStep::route('/{record}/edit'),
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
