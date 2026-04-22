<?php

namespace App\Filament\Resources\B2BLeads;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Resources\B2BLeads\Pages\EditB2BLead;
use App\Filament\Resources\B2BLeads\Pages\ListB2BLeads;
use App\Filament\Resources\B2BLeads\Pages\ViewB2BLead;
use App\Filament\Resources\B2BLeads\Schemas\B2BLeadForm;
use App\Filament\Resources\B2BLeads\Schemas\B2BLeadInfolist;
use App\Filament\Resources\B2BLeads\Tables\B2BLeadsTable;
use App\Filament\Support\PanelAccess;
use App\Models\B2BLead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class B2BLeadResource extends Resource
{
    protected static ?string $model = B2BLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Leads / Growth';

    protected static ?string $navigationLabel = 'Partnership & Sample Leads';

    protected static ?string $modelLabel = 'B2B lead';

    protected static ?string $pluralModelLabel = 'B2B leads';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return B2BLeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return B2BLeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return B2BLeadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['assignee.profile', 'reviewer.profile', 'partnershipInquiry', 'sampleRequest']);
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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('lead_type', '!=', B2BLeadType::BusinessContact->value)
            ->whereIn('status', [
                B2BLeadStatus::New->value,
                B2BLeadStatus::InReview->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListB2BLeads::route('/'),
            'view' => ViewB2BLead::route('/{record}'),
            'edit' => EditB2BLead::route('/{record}/edit'),
        ];
    }
}
