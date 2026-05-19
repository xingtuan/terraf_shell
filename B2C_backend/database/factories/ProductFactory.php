<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(3, true));
        $imageUrl = $this->faker->imageUrl(1200, 800, 'business', true);
        $priceAmount = $this->faker->randomFloat(2, 28, 180);

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'subtitle' => $this->faker->sentence(),
            'short_description' => $this->faker->sentence(),
            'full_description' => $this->faker->paragraphs(2, true),
            'features' => $this->faker->randomElements([
                'Compression-moulded shell composite',
                'Refined mineral finish',
                'Premium service durability',
                'Lightweight handling',
            ], 3),
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(100, 9999),
            'weight_grams' => $this->faker->numberBetween(240, 1200),
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
            'selling_points' => [
                'Made with recovered oyster shell OXP material.',
                'Premium surface feel for considered spaces.',
            ],
            'shipping_notes' => [
                'New Zealand delivery only.',
                'Shipping calculated at checkout.',
            ],
            'return_notes' => [
                'Order confirmation and next steps are sent by email.',
            ],
            'product_faqs' => [
                [
                    'question' => 'Is this made from oyster shell?',
                    'answer' => 'This product uses Terrafin OXP material, a material line made with recovered oyster shell.',
                ],
            ],
            'status' => ProductStatus::Draft->value,
            'sort_order' => 0,
            'media_path' => null,
            'media_url' => $imageUrl,
            'image_url' => $imageUrl,
            'price_from' => $priceAmount,
            'currency' => 'NZD',
            'is_active' => true,
            'featured' => false,
            'is_bestseller' => false,
            'is_new' => false,
            'inquiry_only' => false,
            'sample_request_enabled' => true,
            'lead_time' => 'Ships in 3-5 business days',
            'seo_title' => $name,
            'seo_description' => $this->faker->sentence(),
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

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if ($product->variants()->exists()) {
                return;
            }

            $price = $product->price_from ?? 0;
            $stockQuantity = 24;
            $stockStatus = 'in_stock';
            $baseSku = Product::normalizeSku($product->slug)
                ?: 'OXP_'.$product->id;
            $sku = (string) $baseSku;
            $suffix = 2;

            while (ProductVariant::query()->where('sku', $sku)->exists()) {
                $sku = $baseSku.'_'.$suffix;
                $suffix++;
            }

            ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $sku,
                'title' => 'Default',
                'price_amount' => $price,
                'compare_at_price_amount' => null,
                'currency' => 'NZD',
                'stock_quantity' => $stockQuantity,
                'stock_status' => $stockStatus,
                'inventory_policy' => 'deny',
                'low_stock_threshold' => 5,
                'weight_grams' => $product->weight_grams,
                'image_url' => $product->primaryImageUrl(),
                'media_path' => $product->media_path,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]);
        });
    }
}
