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
            'name' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'organization_type' => 'company',
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'country' => $this->faker->country(),
            'region' => $this->faker->state(),
            'company_website' => $this->faker->url(),
            'job_title' => $this->faker->jobTitle(),
            'inquiry_type' => B2BLeadType::BusinessContact->label(),
            'message' => $this->faker->paragraph(),
            'source_page' => 'b2b:landing',
            'status' => B2BLeadStatus::New->value,
            'internal_notes' => null,
            'assigned_to' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'metadata' => null,
        ];
    }
}
