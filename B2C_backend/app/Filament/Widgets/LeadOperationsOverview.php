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
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = 'Inbound enquiry, partnership, and sample-request workload.';

    public function getHeading(): ?string
    {
        return __('admin.widgets.lead_operations');
    }

    protected function getStats(): array
    {
        $openEnquiriesQuery = Inquiry::query()->whereNotIn('status', [
            B2BLeadStatus::Archived->value,
            B2BLeadStatus::Closed->value,
            B2BLeadStatus::Resolved->value,
        ]);

        $openOpportunityQuery = B2BLead::query()
            ->where('lead_type', '!=', B2BLeadType::BusinessContact->value)
            ->whereNotIn('status', [
                B2BLeadStatus::Archived->value,
                B2BLeadStatus::Closed->value,
                B2BLeadStatus::Resolved->value,
            ]);

        return [
            Stat::make(
                'New leads this week',
                number_format(
                    B2BLead::query()->where('created_at', '>=', now()->startOfWeek())->count(),
                ),
            )
                ->description(__('admin.ui.all_lead_types_submitted_since_monday'))
                ->color('info')
                ->icon('heroicon-o-sparkles')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Overdue follow-ups',
                number_format(
                    B2BLead::query()
                        ->whereNotNull('follow_up_at')
                        ->where('follow_up_at', '<', now())
                        ->whereNotIn('status', [
                            B2BLeadStatus::Archived->value,
                            B2BLeadStatus::Closed->value,
                            B2BLeadStatus::Resolved->value,
                        ])
                        ->count(),
                ),
            )
                ->description(__('admin.ui.open_leads_past_their_follow_up_date'))
                ->color('danger')
                ->icon('heroicon-o-calendar-days')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'New enquiries',
                number_format(
                    Inquiry::query()->where('status', B2BLeadStatus::New->value)->count(),
                ),
            )
                ->description(__('admin.ui.fresh_contact_form_submissions_awaiting_triage'))
                ->color('warning')
                ->icon('heroicon-o-inbox-stack')
                ->url(EnquiryResource::getUrl()),
            Stat::make(
                'Unassigned enquiries',
                number_format(
                    (clone $openEnquiriesQuery)->whereNull('assigned_to')->count(),
                ),
            )
                ->description(__('admin.ui.open_enquiries_without_an_owner'))
                ->color('danger')
                ->icon('heroicon-o-user-plus')
                ->url(EnquiryResource::getUrl()),
            Stat::make(
                'Active B2B opportunities',
                number_format((clone $openOpportunityQuery)->count()),
            )
                ->description(__('admin.ui.sample_and_collaboration_leads_still_in_progress'))
                ->color('warning')
                ->icon('heroicon-o-briefcase')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Qualified leads',
                number_format(
                    B2BLead::query()->where('status', B2BLeadStatus::Qualified->value)->count(),
                ),
            )
                ->description(__('admin.ui.high_intent_leads_ready_for_conversion_follow_up'))
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->url(B2BLeadResource::getUrl()),
            Stat::make(
                'Sample requests',
                number_format(
                    B2BLead::query()->where('lead_type', B2BLeadType::SampleRequest->value)->count(),
                ),
            )
                ->description(__('admin.ui.material_sample_submissions_recorded_to_date'))
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
                ->description(__('admin.ui.partnership_university_and_product_development_flows'))
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
