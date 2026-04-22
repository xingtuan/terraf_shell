<?php

namespace App\Filament\Widgets;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Support\PanelAccess;
use App\Models\B2BLead;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLeads extends TableWidget
{
    protected static ?string $heading = 'Lead Queue';

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => B2BLead::query()
                ->with(['assignee.profile'])
                ->whereNotIn('status', [
                    B2BLeadStatus::Archived->value,
                    B2BLeadStatus::Closed->value,
                ])
                ->latest())
            ->description('Open enquiries and business-development submissions that still need owner attention.')
            ->headerActions([
                Action::make('manageLeads')
                    ->label('Open lead ops')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(EnquiryResource::getUrl()),
            ])
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('lead_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (B2BLead $record): string => $record->company_name ?: 'No company provided'),
                TextColumn::make('source_page')
                    ->label('Source')
                    ->placeholder('Not tracked')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('assignee.name')
                    ->label('Owner')
                    ->placeholder('Unassigned'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (B2BLead $record): string => $record->lead_type === B2BLeadType::BusinessContact->value
                        ? EnquiryResource::getUrl('view', ['record' => $record->getKey()])
                        : B2BLeadResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([6])
            ->emptyStateHeading('No open leads in the queue.')
            ->emptyStateDescription('New enquiries and business-development submissions will appear here.');
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
