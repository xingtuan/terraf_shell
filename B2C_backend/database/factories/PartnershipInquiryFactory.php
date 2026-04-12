<?php

namespace Database\Factories;

use App\Enums\B2BLeadType;
use App\Models\B2BLead;
use App\Models\PartnershipInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnershipInquiry>
 */
class PartnershipInquiryFactory extends Factory
{
    protected $model = PartnershipInquiry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => B2BLead::factory()->state([
                'lead_type' => B2BLeadType::PartnershipInquiry->value,
                'inquiry_type' => B2BLeadType::PartnershipInquiry->label(),
            ]),
            'collaboration_type' => B2BLeadType::PartnershipInquiry->value,
            'collaboration_goal' => fake()->sentence(8),
            'project_stage' => 'pilot',
            'timeline' => 'Q3 2026',
            'metadata' => null,
        ];
    }
}
