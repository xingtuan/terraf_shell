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
        $shortDescription = fake()->paragraph();
        $fullDescription = fake()->paragraphs(3, true);
        $features = fake()->randomElements([
            'Compress-moulded shell composite',
            'Small-batch production',
            'Premium tabletop finish',
            'Suitable for hospitality programs',
        ], rand(2, 4));

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'name_translations' => ['en' => $name],
            'short_description' => $shortDescription,
            'short_description_translations' => ['en' => $shortDescription],
            'full_description' => $fullDescription,
            'full_description_translations' => ['en' => $fullDescription],
            'features' => $features,
            'features_translations' => ['en' => $features],
            'availability_text' => 'Made in small runs',
            'availability_text_translations' => ['en' => 'Made in small runs'],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'status' => ProductStatus::Draft->value,
            'featured' => false,
            'sort_order' => 0,
            'media_path' => null,
            'media_url' => null,
            'price_from' => fake()->randomFloat(2, 45, 280),
            'currency' => 'USD',
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
            'price_from' => null,
            'inquiry_only' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Archived->value,
            'published_at' => null,
        ]);
    }
}
