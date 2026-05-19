<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $sku = 'OXP_'.Str::upper($this->faker->unique()->bothify('SKU_####'));

        return [
            'product_id' => Product::factory(),
            'sku' => $sku,
            'title' => $this->faker->randomElement(['Default', 'Matte / Ocean Bone', 'Warm Sand / M']),
            'option_values' => [
                'finish' => $this->faker->randomElement(['matte', 'glossy']),
                'color' => $this->faker->randomElement(['ocean_bone', 'forged_ash']),
            ],
            'price_amount' => $this->faker->randomFloat(2, 28, 180),
            'compare_at_price_amount' => null,
            'currency' => 'NZD',
            'stock_quantity' => $this->faker->numberBetween(6, 60),
            'stock_status' => 'in_stock',
            'inventory_policy' => 'deny',
            'low_stock_threshold' => 5,
            'weight_grams' => $this->faker->numberBetween(240, 1200),
            'dimensions' => null,
            'image_url' => $this->faker->imageUrl(1200, 800, 'business', true),
            'media_path' => null,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'title' => 'Default',
            'option_values' => null,
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
