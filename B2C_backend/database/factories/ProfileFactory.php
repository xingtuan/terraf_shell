<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bio' => fake()->sentence(12),
            'school_or_company' => fake()->company(),
            'region' => fake()->city().', '.fake()->country(),
            'location' => fake()->city().', '.fake()->country(),
            'portfolio_url' => fake()->url(),
            'website' => fake()->url(),
            'open_to_collab' => fake()->boolean(),
            'avatar_path' => null,
            'avatar_url' => null,
        ];
    }
}
