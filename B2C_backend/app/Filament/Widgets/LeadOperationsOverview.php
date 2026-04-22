<?php

namespace App\Filament\Widgets;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Resources\B2BLeads\B2BLeadResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Support\PanelAccess;
use App\Models\B2BLead;
use App\Models\Inquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadOperationsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Lead Operations';

    protected ?string $description = 'Inbound enquiry, partnership, and sample-request workload.';

    protected function getStats(): array
    {
        $openEnquiriesQuery = Inquiry::query()->whereNotIn('status', [
            B2BLeadStatus::Archived->value,
            B2BLeadStatus::Closed->value,
        ]);

        $openOpportunityQuery = B2BLead::query()
            ->where('lead_type', '!=', B2BLeadType::BusinessContact->value)
            ->whereNotIn('status', [
                B2BLeadStatus::Archived->value,
                B2BLeadStatus::Closed->value,
            ]);

        return [
            Stat::make(
                'New enquiries',
                number_format(
                    Inquiry::query()->where('status', B2BLeadStatus::New->value)->count(),
                ),
            )
                ->description('Fresh contact form submissions awaiting triage')
                ->color('warning')
                ->icon('heroicon-o-inbox-stack')
                ->url(EnquiryResource::getUrl()),
            Stat::make(
                'Unassigned enquiries',
                number_format(
                    (clone $openEnquiriesQuery)->whereNull('assigned_to')->count(),
                ),
            )
                ->description('Open enquiries without an owner')
                ->color('danger')
                ->icon('heroicon-o-user-plus')
                ->url(EnquiryResource::getUrl()),
            Stat::make(
                'Active B2B opportunities',
                number_format((clone $openOpportunityQuery)->count()),
            )
                ->description('Sample and collaboration leads still in progress')
                ->color('warning')
                ->icon('heroicon-o-briefcase')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Qualified leads',
                number_format(
                    B2BLead::query()->where('status', B2BLeadStatus::Qualified->value)->count(),
                ),
            )
                ->description('High-intent leads ready for conversion follow-up')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Sample requests',
                number_format(
                    B2BLead::query()->where('lead_type', B2BLeadType::SampleRequest->value)->count(),
                ),
            )
                ->description('Material sample submissions recorded to date')
                ->color('info')
                ->icon('heroicon-o-beaker')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Collaboration leads',
                number_format(
                    B2BLead::query()
                        ->whereIn('lead_type', B2BLeadType::collaborationValues())
                        ->count(),
                ),
            )
                ->description('Partnership, university, and product-development flows')
                ->color('gray')
                ->icon('heroicon-o-building-office-2')
                ->url(B2BLeadResource::getUrl()),
        ];
    }

    public static function canView(): bool
    {
        return PanelAccess::isAdmin();
    }
}
