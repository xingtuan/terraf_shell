<?php

namespace Tests\Feature\Api;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Mail\B2BLeadSubmittedMail;
use App\Models\B2BLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class B2BLeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_inquiry_endpoint_still_creates_a_business_contact_lead(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'Shellfin Studio',
            'organization_type' => 'design_studio',
            'email' => 'jane@example.com',
            'phone' => '+82-10-5555-0101',
            'country' => 'South Korea',
            'region' => 'Seoul',
            'company_website' => 'https://shellfin.example.com',
            'job_title' => 'Founder',
            'inquiry_type' => 'Business Contact',
            'message' => 'We want to discuss a hospitality collaboration.',
            'source_page' => 'b2b:landing',
            'metadata' => [
                'campaign' => 'launch',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'INQ-000001')
            ->assertJsonPath('data.lead_type', B2BLeadType::BusinessContact->value)
            ->assertJsonPath('data.company_name', 'Shellfin Studio')
            ->assertJsonMissingPath('data.internal_notes');

        $this->assertDatabaseHas('inquiries', [
            'id' => 1,
            'reference' => 'INQ-000001',
            'lead_type' => B2BLeadType::BusinessContact->value,
            'organization_type' => 'design_studio',
            'region' => 'Seoul',
            'company_website' => 'https://shellfin.example.com',
            'job_title' => 'Founder',
            'status' => B2BLeadStatus::New->value,
        ]);
    }

    public function test_guests_can_submit_business_partnership_sample_and_collaboration_forms(): void
    {
        $this->postJson('/api/business-contacts', [
            'name' => 'Ariana Kim',
            'company_name' => 'Blue Current',
            'organization_type' => 'brand',
            'email' => 'ariana@example.com',
            'message' => 'We are exploring a premium packaging collaboration.',
            'source_page' => 'materials:hero',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lead_type', B2BLeadType::BusinessContact->value);

        $this->postJson('/api/partnership-inquiries', [
            'name' => 'Leo Park',
            'company_name' => 'Helix Atelier',
            'organization_type' => 'company',
            'email' => 'leo@example.com',
            'message' => 'We want to co-develop a premium furniture capsule.',
            'collaboration_type' => B2BLeadType::PartnershipInquiry->value,
            'collaboration_goal' => 'Pilot a limited-edition material application.',
            'project_stage' => 'prototype',
            'timeline' => 'Q3 2026',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lead_type', B2BLeadType::PartnershipInquiry->value)
            ->assertJsonPath('data.partnership_inquiry.collaboration_type', B2BLeadType::PartnershipInquiry->value);

        $this->postJson('/api/sample-requests', [
            'name' => 'Mika Tan',
            'company_name' => 'Carbon Form',
            'organization_type' => 'manufacturer',
            'email' => 'mika@example.com',
            'country' => 'Japan',
            'region' => 'Osaka',
            'message' => 'We need evaluation samples for an interior pilot.',
            'material_interest' => 'Pressed oyster-shell panel',
            'quantity_estimate' => '10 sheets',
            'shipping_country' => 'Japan',
            'shipping_region' => 'Osaka',
            'shipping_address' => '1-2-3 Minami, Osaka',
            'intended_use' => 'Interior wall system prototyping.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lead_type', B2BLeadType::SampleRequest->value)
            ->assertJsonPath('data.sample_request.material_interest', 'Pressed oyster-shell panel');

        $this->postJson('/api/university-collaborations', [
            'name' => 'Dr. Hana Lee',
            'company_name' => 'Pacific Design University',
            'organization_type' => 'university',
            'email' => 'hana@example.edu',
            'message' => 'We want a joint studio around circular marine materials.',
            'collaboration_goal' => 'Run a semester-long materials research studio.',
            'project_stage' => 'curriculum-planning',
            'timeline' => 'Semester 1 2027',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lead_type', B2BLeadType::UniversityCollaboration->value)
            ->assertJsonPath('data.partnership_inquiry.collaboration_type', B2BLeadType::UniversityCollaboration->value);

        $this->postJson('/api/product-development-collaborations', [
            'name' => 'Jun Park',
            'company_name' => 'Northline Labs',
            'organization_type' => 'company',
            'email' => 'jun@example.com',
            'message' => 'We want to explore a co-developed shell composite product.',
            'collaboration_goal' => 'Validate a countertop accessory line.',
            'project_stage' => 'discovery',
            'timeline' => 'Q4 2026',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lead_type', B2BLeadType::ProductDevelopmentCollaboration->value)
            ->assertJsonPath('data.partnership_inquiry.collaboration_type', B2BLeadType::ProductDevelopmentCollaboration->value);

        $this->assertDatabaseCount('inquiries', 5);
        $this->assertDatabaseHas('partnership_inquiries', [
            'collaboration_type' => B2BLeadType::UniversityCollaboration->value,
        ]);
        $this->assertDatabaseHas('partnership_inquiries', [
            'collaboration_type' => B2BLeadType::ProductDevelopmentCollaboration->value,
        ]);
        $this->assertDatabaseHas('sample_requests', [
            'material_interest' => 'Pressed oyster-shell panel',
        ]);
    }

    public function test_admins_can_be_notified_when_new_leads_are_submitted(): void
    {
        config()->set('community.b2b_leads.notify_admins', true);
        config()->set('community.b2b_leads.notification_recipients', []);
        Mail::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'ops@example.com',
        ]);

        $this->postJson('/api/business-contacts', [
            'name' => 'Theo Marsh',
            'company_name' => 'Current Works',
            'organization_type' => 'agency',
            'email' => 'theo@example.com',
            'message' => 'We need a material showcase briefing.',
        ])->assertCreated();

        Mail::assertSent(B2BLeadSubmittedMail::class, function (B2BLeadSubmittedMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email)
                && $mail->lead->lead_type === B2BLeadType::BusinessContact->value;
        });
    }

    public function test_admin_can_list_filter_update_and_export_b2b_leads(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();

        $targetLead = B2BLead::factory()->create([
            'reference' => 'INQ-000101',
            'lead_type' => B2BLeadType::PartnershipInquiry->value,
            'inquiry_type' => B2BLeadType::PartnershipInquiry->label(),
            'company_name' => 'Helix Atelier',
            'status' => B2BLeadStatus::InReview->value,
            'source_page' => 'materials:science',
        ]);
        $targetLead->partnershipInquiry()->create([
            'collaboration_type' => B2BLeadType::PartnershipInquiry->value,
            'collaboration_goal' => 'Pilot a limited-edition furniture line.',
            'project_stage' => 'prototype',
            'timeline' => 'Q3 2026',
        ]);

        B2BLead::factory()->create([
            'reference' => 'INQ-000102',
            'lead_type' => B2BLeadType::SampleRequest->value,
            'inquiry_type' => B2BLeadType::SampleRequest->label(),
            'company_name' => 'Other Co',
            'status' => B2BLeadStatus::Closed->value,
        ]);

        Sanctum::actingAs($moderator);
        $this->getJson('/api/admin/b2b-leads')
            ->assertForbidden();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/b2b-leads?search=Helix&lead_type=partnership_inquiry&status=in_review')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reference', 'INQ-000101')
            ->assertJsonPath('data.0.partnership_inquiry.collaboration_goal', 'Pilot a limited-edition furniture line.');

        $this->getJson("/api/admin/b2b-leads/{$targetLead->id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'INQ-000101');

        $this->patchJson("/api/admin/b2b-leads/{$targetLead->id}", [
            'status' => B2BLeadStatus::Qualified->value,
            'internal_notes' => 'Strong partnership fit. Schedule a follow-up call.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', B2BLeadStatus::Qualified->value)
            ->assertJsonPath('data.internal_notes', 'Strong partnership fit. Schedule a follow-up call.')
            ->assertJsonPath('data.reviewed_by.id', $admin->id);

        $this->assertDatabaseHas('inquiries', [
            'id' => $targetLead->id,
            'status' => B2BLeadStatus::Qualified->value,
            'reviewed_by' => $admin->id,
            'internal_notes' => 'Strong partnership fit. Schedule a follow-up call.',
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'b2b_lead.updated',
            'subject_type' => 'b2b_lead',
            'subject_id' => $targetLead->id,
        ]);

        $exportResponse = $this->get('/api/admin/b2b-leads/export?search=Helix&status=qualified');

        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $exportResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $exportResponse->headers->get('content-disposition'));

        $csv = $exportResponse->streamedContent();

        $this->assertStringContainsString('INQ-000101', $csv);
        $this->assertStringContainsString('Helix Atelier', $csv);
        $this->assertStringNotContainsString('INQ-000102', $csv);
    }
}
