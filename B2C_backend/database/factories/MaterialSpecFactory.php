<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Material;
use App\Models\MaterialSpec;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MaterialSpec>
 */
class MaterialSpecFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = Str::title($this->faker->words(2, true));

        return [
            'material_id' => Material::factory(),
            'key' => $this->faker->unique()->slug(2),
            'label' => $label,
            'value' => $this->faker->randomElement(['Low', 'Medium', 'High', '42']),
            'unit' => $this->faker->randomElement(['%', 'MPa', 'kg', null]),
            'detail' => $this->faker->sentence(12),
            'icon' => $this->faker->randomElement(['shield', 'leaf', 'feather', 'beaker']),
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
