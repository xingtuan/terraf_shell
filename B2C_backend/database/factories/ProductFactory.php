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

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'category' => $category,
            'model' => $model,
            'finish' => $finish,
            'color' => $color,
            'technique' => $technique,
            'status' => ProductStatus::Draft->value,
            'sort_order' => 0,
            'media_path' => null,
            'media_url' => $imageUrl,
            'image_url' => $imageUrl,
            'price_from' => fake()->randomFloat(2, 28, 180),
            'price_usd' => fake()->randomFloat(2, 28, 180),
            'currency' => 'USD',
            'in_stock' => true,
            'is_active' => true,
            'featured' => false,
            'inquiry_only' => false,
            'sample_request_enabled' => true,
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
            'published_at' => null,
        ]);
    }
}
