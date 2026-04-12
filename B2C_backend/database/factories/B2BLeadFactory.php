<?php

namespace Database\Factories;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Models\B2BLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<B2BLead>
 */
class B2BLeadFactory extends Factory
{
    protected $model = B2BLead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => null,
            'lead_type' => B2BLeadType::BusinessContact->value,
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'organization_type' => 'company',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'region' => fake()->state(),
            'company_website' => fake()->url(),
            'job_title' => fake()->jobTitle(),
            'inquiry_type' => B2BLeadType::BusinessContact->label(),
            'message' => fake()->paragraph(),
            'source_page' => 'b2b:landing',
            'status' => B2BLeadStatus::New->value,
            'internal_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'metadata' => null,
        ];
    }
}
