<?php

namespace Database\Factories;

use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    public function definition(): array
    {
        $label = Str::title(fake()->unique()->word());

        return [
            'attribute_definition_id' => ProductAttributeDefinition::factory(),
            'value' => Str::slug($label, '_'),
            'label' => $label,
            'label_translations' => ['en' => $label],
            'color_hex' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
