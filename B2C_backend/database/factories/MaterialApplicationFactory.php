<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Material;
use App\Models\MaterialApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MaterialApplication>
 */
class MaterialApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'title' => Str::title(fake()->words(3, true)),
            'subtitle' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'audience' => fake()->company(),
            'cta_label' => 'Learn more',
            'cta_url' => fake()->url(),
            'status' => PublishStatus::Draft->value,
            'sort_order' => 0,
            'media_path' => null,
            'media_url' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PublishStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}
