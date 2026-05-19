<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title($this->faker->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(100, 9999),
            'headline' => $this->faker->sentence(),
            'summary' => $this->faker->paragraph(),
            'story_overview' => $this->faker->paragraphs(2, true),
            'science_overview' => $this->faker->paragraphs(2, true),
            'status' => PublishStatus::Draft->value,
            'is_featured' => false,
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
