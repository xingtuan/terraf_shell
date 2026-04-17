<?php

namespace Tests\Feature\Admin;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Models\B2BLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_filter_and_sort_enquiries_from_admin_list(): void
    {
        $moderator = User::factory()->moderator()->create();
        $assignee = User::factory()->admin()->create([
            'name' => 'Casey Ops',
        ]);

        $oldestInquiry = B2BLead::factory()->create([
            'lead_type' => B2BLeadType::BusinessContact->value,
            'company_name' => 'Anchor Studio',
            'organization_type' => 'brand',
            'email' => 'anchor@example.com',
            'inquiry_type' => 'Retail Display',
            'message' => 'Requesting a retail display material deck.',
            'status' => B2BLeadStatus::New->value,
            'created_at' => now()->subDays(3),
        ]);

        $matchingInquiry = B2BLead::factory()->create([
            'lead_type' => B2BLeadType::BusinessContact->value,
            'company_name' => 'Harbor Works',
            'organization_type' => 'brand',
            'email' => 'ops@harbor.example.com',
            'inquiry_type' => 'Packaging Supply',
            'message' => 'Need an oyster-shell pilot supply proposal for packaging.',
            'status' => B2BLeadStatus::InReview->value,
            'assigned_to' => $assignee->id,
            'created_at' => now()->subDay(),
        ]);

        $otherInquiry = B2BLead::factory()->create([
            'lead_type' => B2BLeadType::BusinessContact->value,
            'company_name' => 'Northline Lab',
            'organization_type' => 'manufacturer',
            'email' => 'hello@northline.example.com',
            'inquiry_type' => 'Technical Review',
            'message' => 'Need a technical review for molded sample pieces.',
            'status' => B2BLeadStatus::Closed->value,
            'created_at' => now(),
        ]);

        $nonEnquiryLead = B2BLead::factory()->create([
            'lead_type' => B2BLeadType::SampleRequest->value,
            'company_name' => 'Sample Only Co',
            'inquiry_type' => B2BLeadType::SampleRequest->label(),
            'message' => 'Sample request record should not appear in enquiries.',
        ]);

        $this->actingAs($moderator);

        Livewire::test(ListEnquiries::class)
            ->searchTable('oyster-shell')
            ->assertCanSeeTableRecords([$matchingInquiry])
            ->assertCanNotSeeTableRecords([$oldestInquiry, $otherInquiry, $nonEnquiryLead])
            ->assertCountTableRecords(1);

        Livewire::test(ListEnquiries::class)
            ->filterTable('status', B2BLeadStatus::InReview->value)
            ->filterTable('assigned_to', $assignee)
            ->filterTable('inquiry_type', 'Packaging Supply')
            ->filterTable('company', [
                'company_name' => 'Harbor',
                'organization_type' => 'brand',
            ])
            ->filterTable('email', [
                'email' => 'harbor.example.com',
            ])
            ->filterTable('created_at', [
                'created_from' => now()->subDays(2)->toDateString(),
                'created_until' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$matchingInquiry])
            ->assertCanNotSeeTableRecords([$oldestInquiry, $otherInquiry]);

        Livewire::test(ListEnquiries::class)
            ->sortTable('created_at', 'asc')
            ->assertCanSeeTableRecords([$oldestInquiry, $matchingInquiry, $otherInquiry], true)
            ->assertCanNotSeeTableRecords([$nonEnquiryLead]);
    }

    public function test_staff_can_view_and_update_enquiry_from_admin(): void
    {
        $moderator = User::factory()->moderator()->create();
        $assignee = User::factory()->admin()->create([
            'name' => 'Morgan Admin',
        ]);

        $inquiry = B2BLead::factory()->create([
            'lead_type' => B2BLeadType::BusinessContact->value,
            'company_name' => 'Meridian House',
            'organization_type' => 'brand',
            'email' => 'team@meridian.example.com',
            'inquiry_type' => 'Hospitality Packaging',
            'message' => 'Need a hospitality packaging discussion for a pilot rollout.',
            'status' => B2BLeadStatus::New->value,
        ]);

        $this->actingAs($moderator)
            ->get('/admin/enquiries')
            ->assertOk();

        $this->actingAs($moderator)
            ->get("/admin/enquiries/{$inquiry->getKey()}")
            ->assertOk();

        $this->actingAs($moderator);

        Livewire::test(EditEnquiry::class, ['record' => $inquiry->getKey()])
            ->fillForm([
                'status' => B2BLeadStatus::Archived->value,
                'assigned_to' => $assignee->getKey(),
                'internal_notes' => 'Reviewed, replied, and archived for record keeping.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => B2BLeadStatus::Archived->value,
            'assigned_to' => $assignee->id,
            'reviewed_by' => $moderator->id,
            'internal_notes' => 'Reviewed, replied, and archived for record keeping.',
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'inquiry.updated',
            'subject_type' => 'inquiry',
            'subject_id' => $inquiry->id,
            'actor_user_id' => $moderator->id,
        ]);
    }
}
