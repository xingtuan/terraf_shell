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
            'bio' => $this->faker->sentence(12),
            'school_or_company' => $this->faker->company(),
            'region' => $this->faker->city().', '.$this->faker->country(),
            'location' => $this->faker->city().', '.$this->faker->country(),
            'portfolio_url' => $this->faker->url(),
            'website' => $this->faker->url(),
            'open_to_collab' => $this->faker->boolean(),
            'avatar_path' => null,
            'avatar_url' => null,
        ];
    }
}
