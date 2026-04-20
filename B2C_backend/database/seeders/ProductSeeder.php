<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'tableware' => 'Tableware',
            'planters' => 'Planters',
            'wellness_interior' => 'Wellness & Interior',
            'architectural' => 'Architectural',
        ])->mapWithKeys(function (string $name, string $slug): array {
            $record = ProductCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $name.' category for the Shellfin product catalog.',
                    'sort_order' => array_search($slug, array_keys(Product::CATEGORY_OPTIONS), true) ?: 0,
                    'is_active' => true,
                ],
            );

            return [$slug => $record];
        });

        Product::query()->update(['is_active' => false]);

        $products = [
            [
                'name' => 'Dinner Plate - Lite / Ocean Bone',
                'category' => 'tableware',
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'price_usd' => 48.00,
                'sort_order' => 1,
            ],
            [
                'name' => 'Salad Bowl - Heritage / Forged Ash',
                'category' => 'tableware',
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'precision_inlay',
                'price_usd' => 62.00,
                'sort_order' => 2,
            ],
            [
                'name' => 'Mug - Lite / Ocean Bone',
                'category' => 'tableware',
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'driftwood_blend',
                'price_usd' => 38.00,
                'sort_order' => 3,
            ],
            [
                'name' => 'Planter S - Heritage / Forged Ash',
                'category' => 'planters',
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'original_pure',
                'price_usd' => 55.00,
                'sort_order' => 4,
            ],
            [
                'name' => 'Gua Sha - Lite / Ocean Bone',
                'category' => 'wellness_interior',
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'price_usd' => 42.00,
                'sort_order' => 5,
            ],
            [
                'name' => 'Soap Dish - Lite / Forged Ash',
                'category' => 'wellness_interior',
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'forged_ash',
                'technique' => 'original_pure',
                'price_usd' => 28.00,
                'sort_order' => 6,
            ],
            [
                'name' => 'Diffuser Holder - Heritage / Ocean Bone',
                'category' => 'wellness_interior',
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'price_usd' => 68.00,
                'sort_order' => 7,
            ],
            [
                'name' => 'Wall Panel Sample',
                'category' => 'architectural',
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'original_pure',
                'price_usd' => 120.00,
                'sort_order' => 8,
            ],
        ];

        foreach ($products as $productData) {
            /** @var ProductCategory $category */
            $category = $categories[$productData['category']];
            $slug = Str::slug($productData['name']);
            $imageUrl = 'https://placehold.co/600x400?text='.rawurlencode($productData['name']);

            Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'category' => $productData['category'],
                    'model' => $productData['model'],
                    'finish' => $productData['finish'],
                    'color' => $productData['color'],
                    'technique' => $productData['technique'],
                    'slug' => $slug,
                    'status' => ProductStatus::Published->value,
                    'featured' => false,
                    'sort_order' => $productData['sort_order'],
                    'media_url' => $imageUrl,
                    'image_url' => $imageUrl,
                    'price_from' => $productData['price_usd'],
                    'price_usd' => $productData['price_usd'],
                    'currency' => 'USD',
                    'in_stock' => true,
                    'is_active' => true,
                    'inquiry_only' => false,
                    'sample_request_enabled' => true,
                    'published_at' => now(),
                ],
            );
        }
    }
}
