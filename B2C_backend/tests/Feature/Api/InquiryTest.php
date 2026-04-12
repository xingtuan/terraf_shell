<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_an_inquiry(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'Shellfin Studio',
            'email' => 'jane@example.com',
            'phone' => '+82-10-5555-0101',
            'country' => 'South Korea',
            'inquiry_type' => 'Sample Request',
            'message' => 'We need pellets for a pilot hospitality project.',
            'source_page' => 'b2b:en',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'INQ-000001')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.company_name', 'Shellfin Studio');

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Jane Doe',
            'company_name' => 'Shellfin Studio',
            'email' => 'jane@example.com',
            'status' => 'new',
            'source_page' => 'b2b:en',
        ]);
    }

    public function test_inquiry_submission_requires_required_fields(): void
    {
        $this->postJson('/api/inquiries', [
            'name' => '',
            'company_name' => '',
            'email' => 'not-an-email',
            'inquiry_type' => '',
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'name',
                'company_name',
                'email',
                'inquiry_type',
                'message',
            ]);
    }
}
