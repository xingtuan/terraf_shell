<?php

namespace App\Filament\Widgets;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Support\PanelAccess;
use App\Models\B2BLead;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLeads extends TableWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('admin.widgets.lead_backlog');
    }

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
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.ui.reference'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('lead_type')
                    ->label(__('admin.ui.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (B2BLead $record): string => $record->company_name ?: 'No company provided'),
                TextColumn::make('source_page')
                    ->label(__('admin.ui.source'))
                    ->placeholder(__('admin.ui.not_tracked_2'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('assignee.name')
                    ->label(__('admin.ui.owner'))
                    ->placeholder(__('admin.ui.unassigned')),
                TextColumn::make('created_at')
                    ->label(__('admin.ui.submitted'))
                    ->dateTime()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (B2BLead $record): string => $record->lead_type === B2BLeadType::BusinessContact->value
                        ? EnquiryResource::getUrl('view', ['record' => $record->getKey()])
                        : B2BLeadResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([8])
            ->emptyStateHeading(__('admin.ui.no_open_leads_in_the_queue'))
            ->emptyStateDescription(__('admin.ui.new_enquiries_and_business_development_submissions_will_appear_here'));
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
