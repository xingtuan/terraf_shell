<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAttributeAssignment;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeAssignment>
 */
class ProductAttributeAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'attribute_definition_id' => ProductAttributeDefinition::factory(),
            'product_attribute_value_id' => ProductAttributeValue::factory(),
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_json' => null,
        ];
    }
}
