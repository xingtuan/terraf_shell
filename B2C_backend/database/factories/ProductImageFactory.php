<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        $altText = fake()->sentence(4);
        $caption = fake()->sentence();

        return [
            'product_id' => Product::factory(),
            'alt_text' => $altText,
            'alt_text_translations' => ['en' => $altText],
            'caption' => $caption,
            'caption_translations' => ['en' => $caption],
            'media_path' => null,
            'media_url' => fake()->imageUrl(1200, 800, 'business', true),
            'sort_order' => 0,
        ];
    }
}
