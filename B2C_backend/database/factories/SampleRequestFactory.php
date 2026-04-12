<?php

namespace Database\Factories;

use App\Enums\B2BLeadType;
use App\Models\B2BLead;
use App\Models\SampleRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SampleRequest>
 */
class SampleRequestFactory extends Factory
{
    protected $model = SampleRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => B2BLead::factory()->state([
                'lead_type' => B2BLeadType::SampleRequest->value,
                'inquiry_type' => B2BLeadType::SampleRequest->label(),
            ]),
            'material_interest' => 'Premium shell composite pellets',
            'quantity_estimate' => '5kg',
            'shipping_country' => fake()->country(),
            'shipping_region' => fake()->state(),
            'shipping_address' => fake()->address(),
            'intended_use' => fake()->sentence(10),
            'metadata' => null,
        ];
    }
}
