<?php

namespace App\Filament\Resources\FundingCampaigns;

use App\Filament\Resources\FundingCampaigns\Pages\CreateFundingCampaign;
use App\Filament\Resources\FundingCampaigns\Pages\EditFundingCampaign;
use App\Filament\Resources\FundingCampaigns\Pages\ListFundingCampaigns;
use App\Filament\Resources\FundingCampaigns\Pages\ViewFundingCampaign;
use App\Filament\Resources\FundingCampaigns\Schemas\FundingCampaignForm;
use App\Filament\Resources\FundingCampaigns\Schemas\FundingCampaignInfolist;
use App\Filament\Resources\FundingCampaigns\Tables\FundingCampaignsTable;
use App\Filament\Support\PanelAccess;
use App\Models\FundingCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FundingCampaignResource extends Resource
{
    protected static ?string $model = FundingCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Funding Campaigns';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return FundingCampaignForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FundingCampaignInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FundingCampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['post.user.profile', 'post.category']);
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
            'index' => ListFundingCampaigns::route('/'),
            'create' => CreateFundingCampaign::route('/create'),
            'view' => ViewFundingCampaign::route('/{record}'),
            'edit' => EditFundingCampaign::route('/{record}/edit'),
        ];
    }
}
