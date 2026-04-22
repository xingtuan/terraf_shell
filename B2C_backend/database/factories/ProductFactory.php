<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));
        $category = fake()->randomElement(array_keys(Product::CATEGORY_OPTIONS));
        $model = fake()->randomElement(array_keys(Product::MODEL_OPTIONS));
        $finish = fake()->randomElement(array_keys(Product::FINISH_OPTIONS));
        $color = fake()->randomElement(array_keys(Product::COLOR_OPTIONS));
        $technique = fake()->randomElement(array_keys(Product::TECHNIQUE_OPTIONS));
        $imageUrl = fake()->imageUrl(1200, 800, 'business', true);
        $priceUsd = fake()->randomFloat(2, 28, 180);
        $stockQuantity = fake()->numberBetween(6, 60);
        $sku = Product::normalizeSku(Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999));

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'subtitle' => fake()->sentence(),
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraphs(2, true),
            'features' => fake()->randomElements([
                'Compression-moulded shell composite',
                'Refined mineral finish',
                'Premium service durability',
                'Lightweight handling',
            ], 3),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'sku' => $sku,
            'category' => $category,
            'model' => $model,
            'finish' => $finish,
            'color' => $color,
            'technique' => $technique,
            'dimensions' => fake()->randomElement(['Dia 27 cm x H 2.4 cm', 'W 18 cm x D 18 cm x H 8 cm']),
            'weight_grams' => fake()->numberBetween(240, 1200),
            'specifications' => [
                [
                    'key' => 'pack_size',
                    'label' => 'Pack Size',
                    'value' => fake()->randomElement(['6 pcs', '12 pcs']),
                    'group' => 'Program',
                ],
            ],
            'certifications' => [
                '0% water absorption',
                'Food-contact reviewed',
                'Natural antibacterial mineral base',
            ],
            'care_instructions' => [
                'Wash with non-abrasive detergent.',
                'Avoid open flame and direct stovetop heat.',
            ],
            'material_benefits' => [
                'Oyster-shell composite narrative for premium storytelling.',
                'Lighter handling than traditional heavy ceramic programs.',
            ],
            'use_cases' => fake()->randomElements(array_keys(Product::USE_CASE_OPTIONS), fake()->numberBetween(1, 3)),
            'status' => ProductStatus::Draft->value,
            'sort_order' => 0,
            'media_path' => null,
            'media_url' => $imageUrl,
            'image_url' => $imageUrl,
            'price_from' => $priceUsd,
            'price_usd' => $priceUsd,
            'compare_at_price_usd' => round($priceUsd * 1.15, 2),
            'currency' => 'USD',
            'stock_quantity' => $stockQuantity,
            'stock_status' => 'in_stock',
            'in_stock' => true,
            'is_active' => true,
            'featured' => false,
            'is_bestseller' => false,
            'is_new' => false,
            'inquiry_only' => false,
            'sample_request_enabled' => true,
            'lead_time' => 'Ships in 3-5 business days',
            'seo_title' => $name,
            'seo_description' => fake()->sentence(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'featured' => true,
        ]);
    }

    public function inquiryOnly(): static
    {
        return $this->state(fn (): array => [
            'inquiry_only' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Archived->value,
            'is_active' => false,
            'in_stock' => false,
            'stock_quantity' => 0,
            'stock_status' => 'sold_out',
            'published_at' => null,
        ]);
    }
}
