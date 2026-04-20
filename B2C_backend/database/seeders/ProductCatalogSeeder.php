<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            [
                'slug' => 'tableware',
                'name' => 'Tableware',
                'name_translations' => [
                    'en' => 'Tableware',
                    'ko' => 'Tableware',
                    'zh' => 'Tableware',
                ],
                'description' => 'Dining pieces shaped for hospitality programs and calm daily rituals.',
                'description_translations' => [
                    'en' => 'Dining pieces shaped for hospitality programs and calm daily rituals.',
                    'ko' => 'Dining pieces shaped for hospitality programs and calm daily rituals.',
                    'zh' => 'Dining pieces shaped for hospitality programs and calm daily rituals.',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'home-objects',
                'name' => 'Home Objects',
                'name_translations' => [
                    'en' => 'Home Objects',
                    'ko' => 'Home Objects',
                    'zh' => 'Home Objects',
                ],
                'description' => 'Quiet accents and trays that bring the shell story into the home.',
                'description_translations' => [
                    'en' => 'Quiet accents and trays that bring the shell story into the home.',
                    'ko' => 'Quiet accents and trays that bring the shell story into the home.',
                    'zh' => 'Quiet accents and trays that bring the shell story into the home.',
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => 'gift-sets',
                'name' => 'Gift Sets',
                'name_translations' => [
                    'en' => 'Gift Sets',
                    'ko' => 'Gift Sets',
                    'zh' => 'Gift Sets',
                ],
                'description' => 'Curated bundles for concept gifting, launches, and premium retail moments.',
                'description_translations' => [
                    'en' => 'Curated bundles for concept gifting, launches, and premium retail moments.',
                    'ko' => 'Curated bundles for concept gifting, launches, and premium retail moments.',
                    'zh' => 'Curated bundles for concept gifting, launches, and premium retail moments.',
                ],
                'sort_order' => 3,
            ],
        ])->mapWithKeys(function (array $category): array {
            $record = ProductCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    ...$category,
                    'is_active' => true,
                ],
            );

            return [$category['slug'] => $record];
        });

        $products = [
            [
                'slug' => 'tidal-dinner-plate',
                'category_slug' => 'tableware',
                'name' => 'Tidal Dinner Plate',
                'name_translations' => [
                    'en' => 'Tidal Dinner Plate',
                    'ko' => 'Tidal Dinner Plate',
                    'zh' => 'Tidal Dinner Plate',
                ],
                'short_description' => 'A refined dinner plate with a mineral-soft edge and shell-led tactility.',
                'short_description_translations' => [
                    'en' => 'A refined dinner plate with a mineral-soft edge and shell-led tactility.',
                    'ko' => 'A refined dinner plate with a mineral-soft edge and shell-led tactility.',
                    'zh' => 'A refined dinner plate with a mineral-soft edge and shell-led tactility.',
                ],
                'full_description' => 'Developed for premium tabletop programs that need a lighter handling experience, a calm finish, and a strong material narrative.',
                'full_description_translations' => [
                    'en' => 'Developed for premium tabletop programs that need a lighter handling experience, a calm finish, and a strong material narrative.',
                    'ko' => 'Developed for premium tabletop programs that need a lighter handling experience, a calm finish, and a strong material narrative.',
                    'zh' => 'Developed for premium tabletop programs that need a lighter handling experience, a calm finish, and a strong material narrative.',
                ],
                'features' => [
                    'Compress-moulded shell composite',
                    'Lighter carry weight',
                    'Small-batch production',
                ],
                'features_translations' => [
                    'en' => [
                        'Compress-moulded shell composite',
                        'Lighter carry weight',
                        'Small-batch production',
                    ],
                    'ko' => [
                        'Compress-moulded shell composite',
                        'Lighter carry weight',
                        'Small-batch production',
                    ],
                    'zh' => [
                        'Compress-moulded shell composite',
                        'Lighter carry weight',
                        'Small-batch production',
                    ],
                ],
                'availability_text' => 'Made in small runs',
                'availability_text_translations' => [
                    'en' => 'Made in small runs',
                    'ko' => 'Made in small runs',
                    'zh' => 'Made in small runs',
                ],
                'media_url' => '/images/application-tableware.jpg',
                'price_from' => 68,
                'currency' => 'USD',
                'featured' => true,
                'sort_order' => 1,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
            ],
            [
                'slug' => 'harbor-serving-bowl',
                'category_slug' => 'tableware',
                'name' => 'Harbor Serving Bowl',
                'name_translations' => [
                    'en' => 'Harbor Serving Bowl',
                    'ko' => 'Harbor Serving Bowl',
                    'zh' => 'Harbor Serving Bowl',
                ],
                'short_description' => 'A generous serving bowl tuned for chef-led dining rooms and boutique stays.',
                'short_description_translations' => [
                    'en' => 'A generous serving bowl tuned for chef-led dining rooms and boutique stays.',
                    'ko' => 'A generous serving bowl tuned for chef-led dining rooms and boutique stays.',
                    'zh' => 'A generous serving bowl tuned for chef-led dining rooms and boutique stays.',
                ],
                'full_description' => 'Designed for premium dining service with a warmer matte presence, durable rim, and a size that works in hospitality packs.',
                'full_description_translations' => [
                    'en' => 'Designed for premium dining service with a warmer matte presence, durable rim, and a size that works in hospitality packs.',
                    'ko' => 'Designed for premium dining service with a warmer matte presence, durable rim, and a size that works in hospitality packs.',
                    'zh' => 'Designed for premium dining service with a warmer matte presence, durable rim, and a size that works in hospitality packs.',
                ],
                'features' => [
                    'Durable rim',
                    'Warm matte finish',
                    'Hospitality-ready sizing',
                ],
                'features_translations' => [
                    'en' => [
                        'Durable rim',
                        'Warm matte finish',
                        'Hospitality-ready sizing',
                    ],
                    'ko' => [
                        'Durable rim',
                        'Warm matte finish',
                        'Hospitality-ready sizing',
                    ],
                    'zh' => [
                        'Durable rim',
                        'Warm matte finish',
                        'Hospitality-ready sizing',
                    ],
                ],
                'availability_text' => 'Available for hospitality packs',
                'availability_text_translations' => [
                    'en' => 'Available for hospitality packs',
                    'ko' => 'Available for hospitality packs',
                    'zh' => 'Available for hospitality packs',
                ],
                'media_url' => '/images/application-interior.jpg',
                'price_from' => null,
                'currency' => 'USD',
                'featured' => true,
                'sort_order' => 2,
                'inquiry_only' => true,
                'sample_request_enabled' => true,
            ],
            [
                'slug' => 'shore-catchall',
                'category_slug' => 'home-objects',
                'name' => 'Shore Catchall',
                'name_translations' => [
                    'en' => 'Shore Catchall',
                    'ko' => 'Shore Catchall',
                    'zh' => 'Shore Catchall',
                ],
                'short_description' => 'A compact tray for jewelry, keys, and quieter daily rituals.',
                'short_description_translations' => [
                    'en' => 'A compact tray for jewelry, keys, and quieter daily rituals.',
                    'ko' => 'A compact tray for jewelry, keys, and quieter daily rituals.',
                    'zh' => 'A compact tray for jewelry, keys, and quieter daily rituals.',
                ],
                'full_description' => 'Built as an entry-point home object that carries the material narrative into calmer interior moments and styled retail displays.',
                'full_description_translations' => [
                    'en' => 'Built as an entry-point home object that carries the material narrative into calmer interior moments and styled retail displays.',
                    'ko' => 'Built as an entry-point home object that carries the material narrative into calmer interior moments and styled retail displays.',
                    'zh' => 'Built as an entry-point home object that carries the material narrative into calmer interior moments and styled retail displays.',
                ],
                'features' => [
                    'Dense mineral touch',
                    'Home styling accent',
                    'Natural shell speckle',
                ],
                'features_translations' => [
                    'en' => [
                        'Dense mineral touch',
                        'Home styling accent',
                        'Natural shell speckle',
                    ],
                    'ko' => [
                        'Dense mineral touch',
                        'Home styling accent',
                        'Natural shell speckle',
                    ],
                    'zh' => [
                        'Dense mineral touch',
                        'Home styling accent',
                        'Natural shell speckle',
                    ],
                ],
                'availability_text' => 'Ready for online pre-order',
                'availability_text_translations' => [
                    'en' => 'Ready for online pre-order',
                    'ko' => 'Ready for online pre-order',
                    'zh' => 'Ready for online pre-order',
                ],
                'media_url' => '/images/material-texture.jpg',
                'price_from' => 54,
                'currency' => 'USD',
                'featured' => false,
                'sort_order' => 3,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
            ],
            [
                'slug' => 'atelier-gift-set',
                'category_slug' => 'gift-sets',
                'name' => 'Atelier Gift Set',
                'name_translations' => [
                    'en' => 'Atelier Gift Set',
                    'ko' => 'Atelier Gift Set',
                    'zh' => 'Atelier Gift Set',
                ],
                'short_description' => 'A composed pair of signature objects built for concept gifting and limited launches.',
                'short_description_translations' => [
                    'en' => 'A composed pair of signature objects built for concept gifting and limited launches.',
                    'ko' => 'A composed pair of signature objects built for concept gifting and limited launches.',
                    'zh' => 'A composed pair of signature objects built for concept gifting and limited launches.',
                ],
                'full_description' => 'Suited to boutique retail displays, premium gifting programs, and smaller collaborations that need a ready-made presentation format.',
                'full_description_translations' => [
                    'en' => 'Suited to boutique retail displays, premium gifting programs, and smaller collaborations that need a ready-made presentation format.',
                    'ko' => 'Suited to boutique retail displays, premium gifting programs, and smaller collaborations that need a ready-made presentation format.',
                    'zh' => 'Suited to boutique retail displays, premium gifting programs, and smaller collaborations that need a ready-made presentation format.',
                ],
                'features' => [
                    'Curated pairings',
                    'Brand-ready packaging',
                    'Limited seasonal release',
                ],
                'features_translations' => [
                    'en' => [
                        'Curated pairings',
                        'Brand-ready packaging',
                        'Limited seasonal release',
                    ],
                    'ko' => [
                        'Curated pairings',
                        'Brand-ready packaging',
                        'Limited seasonal release',
                    ],
                    'zh' => [
                        'Curated pairings',
                        'Brand-ready packaging',
                        'Limited seasonal release',
                    ],
                ],
                'availability_text' => 'Concept launch edition',
                'availability_text_translations' => [
                    'en' => 'Concept launch edition',
                    'ko' => 'Concept launch edition',
                    'zh' => 'Concept launch edition',
                ],
                'media_url' => '/images/application-retail.jpg',
                'price_from' => 148,
                'currency' => 'USD',
                'featured' => false,
                'sort_order' => 4,
                'inquiry_only' => false,
                'sample_request_enabled' => false,
            ],
        ];

        foreach ($products as $productData) {
            /** @var ProductCategory $category */
            $category = $categories[$productData['category_slug']];

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'name_translations' => $productData['name_translations'],
                    'short_description' => $productData['short_description'],
                    'short_description_translations' => $productData['short_description_translations'],
                    'full_description' => $productData['full_description'],
                    'full_description_translations' => $productData['full_description_translations'],
                    'features' => $productData['features'],
                    'features_translations' => $productData['features_translations'],
                    'availability_text' => $productData['availability_text'],
                    'availability_text_translations' => $productData['availability_text_translations'],
                    'status' => ProductStatus::Published->value,
                    'featured' => $productData['featured'],
                    'sort_order' => $productData['sort_order'],
                    'media_url' => $productData['media_url'],
                    'price_from' => $productData['price_from'],
                    'currency' => $productData['currency'],
                    'inquiry_only' => $productData['inquiry_only'],
                    'sample_request_enabled' => $productData['sample_request_enabled'],
                    'published_at' => now(),
                ],
            );

            ProductImage::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sort_order' => 1,
                ],
                [
                    'alt_text' => $product->name,
                    'alt_text_translations' => $product->name_translations,
                    'caption' => $product->short_description,
                    'caption_translations' => $product->short_description_translations,
                    'media_url' => $productData['media_url'],
                ],
            );
        }
    }
}
