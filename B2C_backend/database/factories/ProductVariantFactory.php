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
        $sku = 'OXP_'.Str::upper(fake()->unique()->bothify('SKU_####'));

        return [
            'product_id' => Product::factory(),
            'sku' => $sku,
            'title' => fake()->randomElement(['Default', 'Matte / Ocean Bone', 'Warm Sand / M']),
            'option_values' => [
                'finish' => fake()->randomElement(['matte', 'glossy']),
                'color' => fake()->randomElement(['ocean_bone', 'forged_ash']),
            ],
            'price_amount' => fake()->randomFloat(2, 28, 180),
            'compare_at_price_amount' => null,
            'currency' => 'NZD',
            'stock_quantity' => fake()->numberBetween(6, 60),
            'stock_status' => 'in_stock',
            'inventory_policy' => 'deny',
            'low_stock_threshold' => 5,
            'weight_grams' => fake()->numberBetween(240, 1200),
            'dimensions' => null,
            'image_url' => fake()->imageUrl(1200, 800, 'business', true),
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
