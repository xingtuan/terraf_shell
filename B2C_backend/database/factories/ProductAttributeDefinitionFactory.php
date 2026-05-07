<?php

namespace Database\Factories;

use App\Models\ProductAttributeDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductAttributeDefinition>
 */
class ProductAttributeDefinitionFactory extends Factory
{
    public function definition(): array
    {
        $label = Str::title(fake()->unique()->words(2, true));

        return [
            'key' => Str::slug($label, '_'),
            'label' => $label,
            'label_translations' => ['en' => $label],
            'type' => fake()->randomElement(array_keys(ProductAttributeDefinition::TYPE_OPTIONS)),
            'unit' => null,
            'is_variant_option' => false,
            'is_filterable' => true,
            'is_searchable' => false,
            'is_specification' => true,
            'is_required' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
