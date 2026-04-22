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
                'description' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                'description_translations' => [
                    'en' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                    'ko' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                    'zh' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'planters',
                'name' => 'Planters',
                'name_translations' => [
                    'en' => 'Planters',
                    'ko' => 'Planters',
                    'zh' => 'Planters',
                ],
                'description' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                'description_translations' => [
                    'en' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                    'ko' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                    'zh' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => 'wellness_interior',
                'name' => 'Wellness & Interior',
                'name_translations' => [
                    'en' => 'Wellness & Interior',
                    'ko' => 'Wellness & Interior',
                    'zh' => 'Wellness & Interior',
                ],
                'description' => 'Interior accents and wellness objects designed around quieter material rituals.',
                'description_translations' => [
                    'en' => 'Interior accents and wellness objects designed around quieter material rituals.',
                    'ko' => 'Interior accents and wellness objects designed around quieter material rituals.',
                    'zh' => 'Interior accents and wellness objects designed around quieter material rituals.',
                ],
                'sort_order' => 3,
            ],
            [
                'slug' => 'architectural',
                'name' => 'Architectural',
                'name_translations' => [
                    'en' => 'Architectural',
                    'ko' => 'Architectural',
                    'zh' => 'Architectural',
                ],
                'description' => 'Material samples and surface objects for design studios and hospitality projects.',
                'description_translations' => [
                    'en' => 'Material samples and surface objects for design studios and hospitality projects.',
                    'ko' => 'Material samples and surface objects for design studios and hospitality projects.',
                    'zh' => 'Material samples and surface objects for design studios and hospitality projects.',
                ],
                'sort_order' => 4,
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

        ProductCategory::query()
            ->whereNotIn('slug', $categories->keys()->all())
            ->update(['is_active' => false]);

        $products = [
            [
                'slug' => 'tidal-dinner-plate',
                'sku' => 'TIDAL_DINNER_PLATE',
                'category' => 'tableware',
                'name' => 'Tidal Dinner Plate',
                'name_translations' => [
                    'en' => 'Tidal Dinner Plate',
                    'ko' => 'Tidal Dinner Plate',
                    'zh' => 'Tidal Dinner Plate',
                ],
                'subtitle' => 'Mineral-soft dinner plate designed for premium daily service.',
                'short_description' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                'short_description_translations' => [
                    'en' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                    'ko' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                    'zh' => 'A refined dinner plate with a mineral-soft edge, quieter weight profile, and strong shell-led tactility.',
                ],
                'full_description' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                'full_description_translations' => [
                    'en' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                    'ko' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                    'zh' => 'Developed for chef-led dining rooms and design-conscious homes that want a premium plate with lighter handling, low absorption, and a clear reclaimed-material narrative. The broad face supports plated courses while the mineral edge keeps the presentation crisp without feeling overly formal.',
                ],
                'features' => [
                    'Compression-moulded shell composite body',
                    'Balanced weight for long service shifts',
                    'Low-absorption surface for premium dining use',
                ],
                'features_translations' => [
                    'en' => [
                        'Compression-moulded shell composite body',
                        'Balanced weight for long service shifts',
                        'Low-absorption surface for premium dining use',
                    ],
                    'ko' => [
                        'Compression-moulded shell composite body',
                        'Balanced weight for long service shifts',
                        'Low-absorption surface for premium dining use',
                    ],
                    'zh' => [
                        'Compression-moulded shell composite body',
                        'Balanced weight for long service shifts',
                        'Low-absorption surface for premium dining use',
                    ],
                ],
                'availability_text' => 'Ready from the current production batch',
                'availability_text_translations' => [
                    'en' => 'Ready from the current production batch',
                    'ko' => 'Ready from the current production batch',
                    'zh' => 'Ready from the current production batch',
                ],
                'lead_time' => 'Ships in 2-4 business days',
                'image_url' => '/images/application-tableware.jpg',
                'gallery' => [
                    '/images/application-tableware.jpg',
                    '/images/hero-material.jpg',
                    '/images/material-texture.jpg',
                ],
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'Dia 27 cm x H 2.4 cm',
                'weight_grams' => 590,
                'specifications' => [
                    ['key' => 'rim_profile', 'label' => 'Rim Profile', 'value' => 'Soft coupe edge', 'group' => 'Product'],
                    ['key' => 'service_pack', 'label' => 'Service Pack', 'value' => '12 pcs', 'group' => 'Program'],
                ],
                'care_instructions' => [
                    'Dishwasher safe on a gentle cycle.',
                    'Avoid direct stovetop flame or oven use.',
                    'Use soft separators for high-turn hospitality stacking.',
                ],
                'material_benefits' => [
                    'Mineral tactility shaped from reclaimed oyster shell feedstock.',
                    'Lighter handling than many heavy ceramic service plates.',
                    'Premium traceability story for dining programs and gifting.',
                ],
                'certifications' => [
                    'Food-contact reviewed',
                    '0% water absorption',
                    'Natural antibacterial mineral base',
                ],
                'use_cases' => ['home_dining', 'hospitality_service', 'retail_gifting'],
                'price_from' => 76,
                'currency' => 'USD',
                'featured' => true,
                'is_bestseller' => true,
                'is_new' => false,
                'sort_order' => 1,
                'compare_at_price_usd' => 92,
                'price_usd' => 76,
                'stock_quantity' => 42,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'seo_title' => 'Tidal Dinner Plate | Shellfin premium oyster-shell tableware',
                'seo_description' => 'A premium oyster-shell dinner plate with refined tactility, lighter handling, and hospitality-ready durability.',
                'related' => [
                    'harbor-serving-bowl',
                    'salt-air-espresso-set',
                    'studio-sample-kit',
                ],
            ],
            [
                'slug' => 'harbor-serving-bowl',
                'sku' => 'HARBOR_SERVING_BOWL',
                'category' => 'tableware',
                'name' => 'Harbor Serving Bowl',
                'name_translations' => [
                    'en' => 'Harbor Serving Bowl',
                    'ko' => 'Harbor Serving Bowl',
                    'zh' => 'Harbor Serving Bowl',
                ],
                'subtitle' => 'Generous serving bowl tuned for chef-led tables and boutique stays.',
                'short_description' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                'short_description_translations' => [
                    'en' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                    'ko' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                    'zh' => 'A hospitality-scaled serving bowl with a warm mineral profile and a durable rim built for repeat service.',
                ],
                'full_description' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the Shellfin material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                'full_description_translations' => [
                    'en' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the Shellfin material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                    'ko' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the Shellfin material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                    'zh' => 'Sized for shared courses, breakfast service, and premium residential tables, Harbor brings a heavier visual presence without losing the lighter handling benefits of the Shellfin material system. It is especially suited to boutique hotel breakfast, shared appetizers, and curated chef service packs.',
                ],
                'features' => [
                    'Durable hospitality-ready rim',
                    'Warm matte finish',
                    'Shared-course proportions',
                ],
                'features_translations' => [
                    'en' => [
                        'Durable hospitality-ready rim',
                        'Warm matte finish',
                        'Shared-course proportions',
                    ],
                    'ko' => [
                        'Durable hospitality-ready rim',
                        'Warm matte finish',
                        'Shared-course proportions',
                    ],
                    'zh' => [
                        'Durable hospitality-ready rim',
                        'Warm matte finish',
                        'Shared-course proportions',
                    ],
                ],
                'availability_text' => 'Limited batch in stock',
                'availability_text_translations' => [
                    'en' => 'Limited batch in stock',
                    'ko' => 'Limited batch in stock',
                    'zh' => 'Limited batch in stock',
                ],
                'lead_time' => 'Ships in 5-7 business days',
                'image_url' => '/images/application-interior.jpg',
                'gallery' => [
                    '/images/application-interior.jpg',
                    '/images/material-texture.jpg',
                    '/images/application-tableware.jpg',
                ],
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'precision_inlay',
                'dimensions' => 'Dia 21 cm x H 7.5 cm',
                'weight_grams' => 720,
                'specifications' => [
                    ['key' => 'capacity', 'label' => 'Capacity', 'value' => '1.3', 'unit' => 'L', 'group' => 'Dimensions'],
                    ['key' => 'service_pack', 'label' => 'Service Pack', 'value' => '8 pcs', 'group' => 'Program'],
                ],
                'care_instructions' => [
                    'Rinse quickly after acidic sauces or dressings.',
                    'Warm dishwasher cycle recommended.',
                    'Avoid metal scouring pads on the finish.',
                ],
                'material_benefits' => [
                    'Dense mineral feel with lower water absorption.',
                    'Durable edge performance for repeated service.',
                    'Refined shell story for boutique hospitality programs.',
                ],
                'certifications' => [
                    'Food-contact reviewed',
                    'Impact-resistant composite body',
                    'Low-absorption hospitality finish',
                ],
                'use_cases' => ['hospitality_service', 'home_dining'],
                'price_from' => 96,
                'currency' => 'USD',
                'featured' => true,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 2,
                'compare_at_price_usd' => 118,
                'price_usd' => 96,
                'stock_quantity' => 5,
                'stock_status' => 'low_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'seo_title' => 'Harbor Serving Bowl | Shellfin hospitality-ready servingware',
                'seo_description' => 'A generous Shellfin serving bowl for chef-led hospitality service and premium residential tables.',
                'related' => [
                    'tidal-dinner-plate',
                    'salt-air-espresso-set',
                    'cove-display-tile',
                ],
            ],
            [
                'slug' => 'salt-air-espresso-set',
                'sku' => 'SALT_AIR_ESPRESSO_SET',
                'category' => 'tableware',
                'name' => 'Salt Air Espresso Set',
                'name_translations' => [
                    'en' => 'Salt Air Espresso Set',
                    'ko' => 'Salt Air Espresso Set',
                    'zh' => 'Salt Air Espresso Set',
                ],
                'subtitle' => 'Compact cup and saucer pairing built for gifting and premium coffee rituals.',
                'short_description' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                'short_description_translations' => [
                    'en' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                    'ko' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                    'zh' => 'A smaller-shell tableware set for morning service, gifting programs, and boutique retail moments.',
                ],
                'full_description' => 'Salt Air was designed as an approachable entry into the Shellfin tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                'full_description_translations' => [
                    'en' => 'Salt Air was designed as an approachable entry into the Shellfin tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                    'ko' => 'Salt Air was designed as an approachable entry into the Shellfin tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                    'zh' => 'Salt Air was designed as an approachable entry into the Shellfin tableware world: compact enough for retail gifting, refined enough for boutique cafe counters, and distinctive enough to carry the oyster-shell material story into a smaller ritual object.',
                ],
                'features' => [
                    'Cup and saucer pairing',
                    'Retail-ready gifting proposition',
                    'Cafe counter presentation appeal',
                ],
                'features_translations' => [
                    'en' => [
                        'Cup and saucer pairing',
                        'Retail-ready gifting proposition',
                        'Cafe counter presentation appeal',
                    ],
                    'ko' => [
                        'Cup and saucer pairing',
                        'Retail-ready gifting proposition',
                        'Cafe counter presentation appeal',
                    ],
                    'zh' => [
                        'Cup and saucer pairing',
                        'Retail-ready gifting proposition',
                        'Cafe counter presentation appeal',
                    ],
                ],
                'availability_text' => 'Pre-order now for the next micro batch',
                'availability_text_translations' => [
                    'en' => 'Pre-order now for the next micro batch',
                    'ko' => 'Pre-order now for the next micro batch',
                    'zh' => 'Pre-order now for the next micro batch',
                ],
                'lead_time' => 'Dispatches in 3-4 weeks',
                'image_url' => '/images/application-retail.jpg',
                'gallery' => [
                    '/images/application-retail.jpg',
                    '/images/material-texture.jpg',
                    '/images/hero-material.jpg',
                ],
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'driftwood_blend',
                'dimensions' => 'Cup Dia 7 cm x H 6 cm / Saucer Dia 12 cm',
                'weight_grams' => 310,
                'specifications' => [
                    ['key' => 'set_contents', 'label' => 'Set Contents', 'value' => 'Cup + Saucer', 'group' => 'Product'],
                    ['key' => 'capacity', 'label' => 'Cup Capacity', 'value' => '150', 'unit' => 'ml', 'group' => 'Dimensions'],
                ],
                'care_instructions' => [
                    'Hand wash recommended for glossy surface retention.',
                    'Do not microwave until production certification is finalized.',
                ],
                'material_benefits' => [
                    'Compact gifting format with a clear shell-origin material story.',
                    'Low-absorption surface for coffee service and boutique retail display.',
                ],
                'certifications' => [
                    'Retail gifting format',
                    'Low-absorption shell composite',
                    'Pilot batch traceability',
                ],
                'use_cases' => ['home_dining', 'retail_gifting', 'hospitality_service'],
                'price_from' => 58,
                'currency' => 'USD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 3,
                'compare_at_price_usd' => 68,
                'price_usd' => 58,
                'stock_quantity' => null,
                'stock_status' => 'preorder',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'seo_title' => 'Salt Air Espresso Set | Shellfin oyster-shell giftable tableware',
                'seo_description' => 'A compact Shellfin espresso set designed for premium rituals, gifting, and boutique hospitality.',
                'related' => [
                    'tidal-dinner-plate',
                    'harbor-serving-bowl',
                    'shoreline-wellness-tray',
                ],
            ],
            [
                'slug' => 'drift-planter-no-2',
                'sku' => 'DRIFT_PLANTER_NO_2',
                'category' => 'planters',
                'name' => 'Drift Planter No. 2',
                'name_translations' => [
                    'en' => 'Drift Planter No. 2',
                    'ko' => 'Drift Planter No. 2',
                    'zh' => 'Drift Planter No. 2',
                ],
                'subtitle' => 'Mineral planter with a soft silhouette for residential and hospitality styling.',
                'short_description' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                'short_description_translations' => [
                    'en' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                    'ko' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                    'zh' => 'A sculptural planter built for design-led homes, boutique hospitality, and visual merchandising.',
                ],
                'full_description' => 'Drift Planter translates Shellfin materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                'full_description_translations' => [
                    'en' => 'Drift Planter translates Shellfin materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                    'ko' => 'Drift Planter translates Shellfin materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                    'zh' => 'Drift Planter translates Shellfin materiality into the interior category, giving the oyster-shell story a calmer architectural presence. The piece works equally well for boutique hotel room styling, elevated plant gifting, and retail display capsules.',
                ],
                'features' => [
                    'Drainage-ready interior shape',
                    'Styling-led sculptural silhouette',
                    'Boutique hospitality placement',
                ],
                'features_translations' => [
                    'en' => [
                        'Drainage-ready interior shape',
                        'Styling-led sculptural silhouette',
                        'Boutique hospitality placement',
                    ],
                    'ko' => [
                        'Drainage-ready interior shape',
                        'Styling-led sculptural silhouette',
                        'Boutique hospitality placement',
                    ],
                    'zh' => [
                        'Drainage-ready interior shape',
                        'Styling-led sculptural silhouette',
                        'Boutique hospitality placement',
                    ],
                ],
                'availability_text' => 'Available for immediate dispatch',
                'availability_text_translations' => [
                    'en' => 'Available for immediate dispatch',
                    'ko' => 'Available for immediate dispatch',
                    'zh' => 'Available for immediate dispatch',
                ],
                'lead_time' => 'Ships in 3-5 business days',
                'image_url' => '/images/application-interior.jpg',
                'gallery' => [
                    '/images/application-interior.jpg',
                    '/images/application-packaging.jpg',
                    '/images/material-texture.jpg',
                ],
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'original_pure',
                'dimensions' => 'Dia 18 cm x H 16 cm',
                'weight_grams' => 860,
                'specifications' => [
                    ['key' => 'opening', 'label' => 'Opening', 'value' => '14', 'unit' => 'cm', 'group' => 'Dimensions'],
                    ['key' => 'liner', 'label' => 'Recommended Liner', 'value' => 'Soft nursery pot insert', 'group' => 'Care'],
                ],
                'care_instructions' => [
                    'Use an insert or drainage layer for live plants.',
                    'Wipe exterior with a soft cloth after watering.',
                    'Suitable for indoor use and covered styling zones.',
                ],
                'material_benefits' => [
                    'Extends the Shellfin material story beyond the table into interior rituals.',
                    'Lower-absorption surface than many porous indoor decorative materials.',
                ],
                'certifications' => [
                    'Interior styling ready',
                    'Low-absorption composite body',
                    'Retail display friendly',
                ],
                'use_cases' => ['interior_styling', 'retail_gifting', 'design_projects'],
                'price_from' => 88,
                'currency' => 'USD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 4,
                'compare_at_price_usd' => 104,
                'price_usd' => 88,
                'stock_quantity' => 18,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => false,
                'seo_title' => 'Drift Planter No. 2 | Shellfin premium interior planter',
                'seo_description' => 'A sculptural Shellfin planter for premium interiors, boutique hospitality, and gifting programs.',
                'related' => [
                    'shoreline-wellness-tray',
                    'reef-candle-vessel',
                    'cove-display-tile',
                ],
            ],
            [
                'slug' => 'shoreline-wellness-tray',
                'sku' => 'SHORELINE_WELLNESS_TRAY',
                'category' => 'wellness_interior',
                'name' => 'Shoreline Wellness Tray',
                'name_translations' => [
                    'en' => 'Shoreline Wellness Tray',
                    'ko' => 'Shoreline Wellness Tray',
                    'zh' => 'Shoreline Wellness Tray',
                ],
                'subtitle' => 'Slim tray for scent rituals, bathroom styling, and calm hospitality moments.',
                'short_description' => 'A low-profile tray that brings Shellfin tactility into quieter bathroom, spa, and bedside routines.',
                'short_description_translations' => [
                    'en' => 'A low-profile tray that brings Shellfin tactility into quieter bathroom, spa, and bedside routines.',
                    'ko' => 'A low-profile tray that brings Shellfin tactility into quieter bathroom, spa, and bedside routines.',
                    'zh' => 'A low-profile tray that brings Shellfin tactility into quieter bathroom, spa, and bedside routines.',
                ],
                'full_description' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                'full_description_translations' => [
                    'en' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                    'ko' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                    'zh' => 'Shoreline is shaped for settings where objects are seen and handled at close range: boutique bathrooms, spa amenities, bedside styling, and premium gifting. The compact footprint makes it easy to pair with candles, soap, jewelry, or curated guest-room items.',
                ],
                'features' => [
                    'Quiet mineral surface',
                    'Spa and bathroom styling appeal',
                    'Gift-ready proportion',
                ],
                'features_translations' => [
                    'en' => [
                        'Quiet mineral surface',
                        'Spa and bathroom styling appeal',
                        'Gift-ready proportion',
                    ],
                    'ko' => [
                        'Quiet mineral surface',
                        'Spa and bathroom styling appeal',
                        'Gift-ready proportion',
                    ],
                    'zh' => [
                        'Quiet mineral surface',
                        'Spa and bathroom styling appeal',
                        'Gift-ready proportion',
                    ],
                ],
                'availability_text' => 'Available now for direct B2C purchase',
                'availability_text_translations' => [
                    'en' => 'Available now for direct B2C purchase',
                    'ko' => 'Available now for direct B2C purchase',
                    'zh' => 'Available now for direct B2C purchase',
                ],
                'lead_time' => 'Ships in 2-4 business days',
                'image_url' => '/images/material-texture.jpg',
                'gallery' => [
                    '/images/material-texture.jpg',
                    '/images/application-packaging.jpg',
                    '/images/application-interior.jpg',
                ],
                'model' => 'lite_15',
                'finish' => 'glossy',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'W 23 cm x D 12 cm x H 1.8 cm',
                'weight_grams' => 340,
                'specifications' => [
                    ['key' => 'edge', 'label' => 'Edge Detail', 'value' => 'Soft chamfer', 'group' => 'Product'],
                    ['key' => 'placement', 'label' => 'Suggested Placement', 'value' => 'Bath, vanity, bedside', 'group' => 'Application'],
                ],
                'care_instructions' => [
                    'Wipe dry after oils or fragrance spills.',
                    'Use a soft cloth to preserve finish clarity.',
                ],
                'material_benefits' => [
                    'Brings the oyster-shell story into intimate styling rituals.',
                    'Low-absorption surface works well for soaps, candles, and amenity objects.',
                ],
                'certifications' => [
                    'Low-absorption tray surface',
                    'Hospitality amenity friendly',
                    'Retail gifting suitable',
                ],
                'use_cases' => ['interior_styling', 'retail_gifting', 'home_dining'],
                'price_from' => 64,
                'currency' => 'USD',
                'featured' => false,
                'is_bestseller' => true,
                'is_new' => false,
                'sort_order' => 5,
                'compare_at_price_usd' => 79,
                'price_usd' => 64,
                'stock_quantity' => 16,
                'stock_status' => 'in_stock',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => false,
                'seo_title' => 'Shoreline Wellness Tray | Shellfin premium interior tray',
                'seo_description' => 'A compact Shellfin tray for spa rituals, gifting, and calm interior styling.',
                'related' => [
                    'drift-planter-no-2',
                    'reef-candle-vessel',
                    'salt-air-espresso-set',
                ],
            ],
            [
                'slug' => 'cove-display-tile',
                'sku' => 'COVE_DISPLAY_TILE',
                'category' => 'architectural',
                'name' => 'Cove Display Tile',
                'name_translations' => [
                    'en' => 'Cove Display Tile',
                    'ko' => 'Cove Display Tile',
                    'zh' => 'Cove Display Tile',
                ],
                'subtitle' => 'Architectural shell composite sample tile for design libraries and hospitality fit-outs.',
                'short_description' => 'A specification-oriented sample piece for interior teams evaluating Shellfin for hospitality and retail surfaces.',
                'short_description_translations' => [
                    'en' => 'A specification-oriented sample piece for interior teams evaluating Shellfin for hospitality and retail surfaces.',
                    'ko' => 'A specification-oriented sample piece for interior teams evaluating Shellfin for hospitality and retail surfaces.',
                    'zh' => 'A specification-oriented sample piece for interior teams evaluating Shellfin for hospitality and retail surfaces.',
                ],
                'full_description' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                'full_description_translations' => [
                    'en' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                    'ko' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                    'zh' => 'Cove Display Tile is less a conventional B2C object and more a bridge into future B2B surface programs. It gives designers and hospitality buyers a clear way to review finish, tone, density, and care requirements before moving into larger material conversations.',
                ],
                'features' => [
                    'Architectural sample format',
                    'Finish and density review tool',
                    'Hospitality specification bridge',
                ],
                'features_translations' => [
                    'en' => [
                        'Architectural sample format',
                        'Finish and density review tool',
                        'Hospitality specification bridge',
                    ],
                    'ko' => [
                        'Architectural sample format',
                        'Finish and density review tool',
                        'Hospitality specification bridge',
                    ],
                    'zh' => [
                        'Architectural sample format',
                        'Finish and density review tool',
                        'Hospitality specification bridge',
                    ],
                ],
                'availability_text' => 'Bulk and project enquiry recommended',
                'availability_text_translations' => [
                    'en' => 'Bulk and project enquiry recommended',
                    'ko' => 'Bulk and project enquiry recommended',
                    'zh' => 'Bulk and project enquiry recommended',
                ],
                'lead_time' => 'Sample review packs ship in 2-3 weeks',
                'image_url' => '/images/application-packaging.jpg',
                'gallery' => [
                    '/images/application-packaging.jpg',
                    '/images/hero-material.jpg',
                    '/images/process-refined.jpg',
                ],
                'model' => 'heritage_16',
                'finish' => 'matte',
                'color' => 'forged_ash',
                'technique' => 'precision_inlay',
                'dimensions' => 'W 10 cm x D 10 cm x H 0.8 cm',
                'weight_grams' => 180,
                'specifications' => [
                    ['key' => 'sample_format', 'label' => 'Sample Format', 'value' => 'Architectural swatch tile', 'group' => 'Program'],
                    ['key' => 'moq', 'label' => 'Project MOQ', 'value' => 'Discuss with Shellfin team', 'group' => 'Commercial'],
                ],
                'care_instructions' => [
                    'Use as a review sample for finish and tone comparison.',
                    'Request project guidance before specifying in wet areas.',
                ],
                'material_benefits' => [
                    'Carries the reclaimed shell narrative into material library conversations.',
                    'Useful for hospitality teams reviewing premium sustainable finish directions.',
                ],
                'certifications' => [
                    'Design-library sample format',
                    'Project review support',
                    'Future B2B surface pathway',
                ],
                'use_cases' => ['design_projects', 'hospitality_service', 'interior_styling'],
                'price_from' => 24,
                'currency' => 'USD',
                'featured' => true,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 6,
                'compare_at_price_usd' => null,
                'price_usd' => 24,
                'stock_quantity' => null,
                'stock_status' => 'made_to_order',
                'in_stock' => true,
                'inquiry_only' => true,
                'sample_request_enabled' => true,
                'seo_title' => 'Cove Display Tile | Shellfin architectural material sample',
                'seo_description' => 'A Shellfin architectural sample tile for hospitality, retail, and interior design project review.',
                'related' => [
                    'studio-sample-kit',
                    'drift-planter-no-2',
                    'harbor-serving-bowl',
                ],
            ],
            [
                'slug' => 'studio-sample-kit',
                'sku' => 'STUDIO_SAMPLE_KIT',
                'category' => 'architectural',
                'name' => 'Studio Sample Kit',
                'name_translations' => [
                    'en' => 'Studio Sample Kit',
                    'ko' => 'Studio Sample Kit',
                    'zh' => 'Studio Sample Kit',
                ],
                'subtitle' => 'Entry sample kit for designers, specifiers, and hospitality buyers.',
                'short_description' => 'A compact pack of Shellfin finish chips and object references for teams reviewing the material story.',
                'short_description_translations' => [
                    'en' => 'A compact pack of Shellfin finish chips and object references for teams reviewing the material story.',
                    'ko' => 'A compact pack of Shellfin finish chips and object references for teams reviewing the material story.',
                    'zh' => 'A compact pack of Shellfin finish chips and object references for teams reviewing the material story.',
                ],
                'full_description' => 'The Studio Sample Kit is intended for early project conversations. It combines finish references, a small-format object sample, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                'full_description_translations' => [
                    'en' => 'The Studio Sample Kit is intended for early project conversations. It combines finish references, a small-format object sample, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                    'ko' => 'The Studio Sample Kit is intended for early project conversations. It combines finish references, a small-format object sample, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                    'zh' => 'The Studio Sample Kit is intended for early project conversations. It combines finish references, a small-format object sample, and care notes so design teams can move from inspiration to a more grounded materials review without committing to a large order.',
                ],
                'features' => [
                    'Finish chips and sample references',
                    'Early-stage hospitality and design review',
                    'Bridges B2C discovery into future B2B supply',
                ],
                'features_translations' => [
                    'en' => [
                        'Finish chips and sample references',
                        'Early-stage hospitality and design review',
                        'Bridges B2C discovery into future B2B supply',
                    ],
                    'ko' => [
                        'Finish chips and sample references',
                        'Early-stage hospitality and design review',
                        'Bridges B2C discovery into future B2B supply',
                    ],
                    'zh' => [
                        'Finish chips and sample references',
                        'Early-stage hospitality and design review',
                        'Bridges B2C discovery into future B2B supply',
                    ],
                ],
                'availability_text' => 'Open for sample-kit orders',
                'availability_text_translations' => [
                    'en' => 'Open for sample-kit orders',
                    'ko' => 'Open for sample-kit orders',
                    'zh' => 'Open for sample-kit orders',
                ],
                'lead_time' => 'Dispatches in 7-10 business days',
                'image_url' => '/images/process-collected.jpg',
                'gallery' => [
                    '/images/process-collected.jpg',
                    '/images/process-refined.jpg',
                    '/images/process-recrafted.jpg',
                ],
                'model' => 'lite_15',
                'finish' => 'matte',
                'color' => 'ocean_bone',
                'technique' => 'original_pure',
                'dimensions' => 'A5 kit box',
                'weight_grams' => 240,
                'specifications' => [
                    ['key' => 'contents', 'label' => 'Kit Contents', 'value' => 'Finish chips, object sample, care notes', 'group' => 'Product'],
                    ['key' => 'audience', 'label' => 'Audience', 'value' => 'Design studios and hospitality buyers', 'group' => 'Application'],
                ],
                'care_instructions' => [
                    'Store samples flat and dry for finish comparison.',
                    'Contact Shellfin for project-specific care guidance.',
                ],
                'material_benefits' => [
                    'Low-friction way to evaluate the shell-led material story.',
                    'Connects premium B2C discovery with future B2B program planning.',
                ],
                'certifications' => [
                    'Sample review format',
                    'Design team friendly',
                    'Project pathway support',
                ],
                'use_cases' => ['design_projects', 'hospitality_service', 'retail_gifting'],
                'price_from' => 36,
                'currency' => 'USD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'sort_order' => 7,
                'compare_at_price_usd' => null,
                'price_usd' => 36,
                'stock_quantity' => null,
                'stock_status' => 'preorder',
                'in_stock' => true,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'seo_title' => 'Studio Sample Kit | Shellfin designer review kit',
                'seo_description' => 'A compact Shellfin sample kit for design teams, hospitality buyers, and project evaluation.',
                'related' => [
                    'cove-display-tile',
                    'tidal-dinner-plate',
                    'shoreline-wellness-tray',
                ],
            ],
            [
                'slug' => 'reef-candle-vessel',
                'sku' => 'REEF_CANDLE_VESSEL',
                'category' => 'wellness_interior',
                'name' => 'Reef Candle Vessel',
                'name_translations' => [
                    'en' => 'Reef Candle Vessel',
                    'ko' => 'Reef Candle Vessel',
                    'zh' => 'Reef Candle Vessel',
                ],
                'subtitle' => 'Candle-ready vessel for premium interiors and quiet gifting.',
                'short_description' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                'short_description_translations' => [
                    'en' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                    'ko' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                    'zh' => 'A shell composite vessel designed for candle programs, guest-room styling, and seasonal gifting capsules.',
                ],
                'full_description' => 'Reef is currently between production runs and is useful as a test case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                'full_description_translations' => [
                    'en' => 'Reef is currently between production runs and is useful as a test case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                    'ko' => 'Reef is currently between production runs and is useful as a test case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                    'zh' => 'Reef is currently between production runs and is useful as a test case for the out-of-stock journey. The form is intended for boutique candle collaborations, guest-room amenities, and smaller branded gifting programs where the container itself adds material value.',
                ],
                'features' => [
                    'Candle-ready interior form',
                    'Premium gifting silhouette',
                    'Hospitality amenity potential',
                ],
                'features_translations' => [
                    'en' => [
                        'Candle-ready interior form',
                        'Premium gifting silhouette',
                        'Hospitality amenity potential',
                    ],
                    'ko' => [
                        'Candle-ready interior form',
                        'Premium gifting silhouette',
                        'Hospitality amenity potential',
                    ],
                    'zh' => [
                        'Candle-ready interior form',
                        'Premium gifting silhouette',
                        'Hospitality amenity potential',
                    ],
                ],
                'availability_text' => 'Next production batch not yet scheduled',
                'availability_text_translations' => [
                    'en' => 'Next production batch not yet scheduled',
                    'ko' => 'Next production batch not yet scheduled',
                    'zh' => 'Next production batch not yet scheduled',
                ],
                'lead_time' => 'Join the waitlist or request a hospitality update',
                'image_url' => '/images/application-retail.jpg',
                'gallery' => [
                    '/images/application-retail.jpg',
                    '/images/application-packaging.jpg',
                    '/images/material-texture.jpg',
                ],
                'model' => 'heritage_16',
                'finish' => 'glossy',
                'color' => 'forged_ash',
                'technique' => 'driftwood_blend',
                'dimensions' => 'Dia 8.5 cm x H 9 cm',
                'weight_grams' => 420,
                'specifications' => [
                    ['key' => 'wax_fill', 'label' => 'Wax Fill Guideline', 'value' => 'Discuss with Shellfin team', 'group' => 'Program'],
                    ['key' => 'batch_state', 'label' => 'Batch Status', 'value' => 'Sold out', 'group' => 'Availability'],
                ],
                'care_instructions' => [
                    'Keep away from open flame until a certified fill program is approved.',
                    'Request a sample or restock update for collaboration planning.',
                ],
                'material_benefits' => [
                    'Strong premium storytelling for fragrance and wellness collaborations.',
                    'Extends the shell-origin narrative into intimate interior rituals.',
                ],
                'certifications' => [
                    'Waitlist available',
                    'Collaboration-ready form factor',
                    'Retail gifting potential',
                ],
                'use_cases' => ['interior_styling', 'retail_gifting', 'hospitality_service'],
                'price_from' => 72,
                'currency' => 'USD',
                'featured' => false,
                'is_bestseller' => false,
                'is_new' => false,
                'sort_order' => 8,
                'compare_at_price_usd' => 84,
                'price_usd' => 72,
                'stock_quantity' => 0,
                'stock_status' => 'sold_out',
                'in_stock' => false,
                'inquiry_only' => false,
                'sample_request_enabled' => true,
                'seo_title' => 'Reef Candle Vessel | Shellfin premium candle collaboration vessel',
                'seo_description' => 'A currently sold-out Shellfin vessel designed for premium candle and hospitality amenity concepts.',
                'related' => [
                    'shoreline-wellness-tray',
                    'drift-planter-no-2',
                    'studio-sample-kit',
                ],
            ],
        ];

        Product::query()
            ->whereNotIn('slug', collect($products)->pluck('slug')->all())
            ->update([
                'is_active' => false,
                'status' => ProductStatus::Archived->value,
                'published_at' => null,
            ]);

        $records = [];

        foreach ($products as $productData) {
            /** @var ProductCategory $category */
            $category = $categories[$productData['category']];

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'subtitle' => $productData['subtitle'],
                    'name_translations' => $productData['name_translations'],
                    'short_description' => $productData['short_description'],
                    'short_description_translations' => $productData['short_description_translations'],
                    'full_description' => $productData['full_description'],
                    'full_description_translations' => $productData['full_description_translations'],
                    'features' => $productData['features'],
                    'features_translations' => $productData['features_translations'],
                    'availability_text' => $productData['availability_text'],
                    'availability_text_translations' => $productData['availability_text_translations'],
                    'lead_time' => $productData['lead_time'],
                    'sku' => $productData['sku'],
                    'category' => $productData['category'],
                    'model' => $productData['model'],
                    'finish' => $productData['finish'],
                    'color' => $productData['color'],
                    'technique' => $productData['technique'],
                    'dimensions' => $productData['dimensions'],
                    'weight_grams' => $productData['weight_grams'],
                    'specifications' => $productData['specifications'],
                    'care_instructions' => $productData['care_instructions'],
                    'material_benefits' => $productData['material_benefits'],
                    'certifications' => $productData['certifications'],
                    'use_cases' => $productData['use_cases'],
                    'seo_title' => $productData['seo_title'],
                    'seo_description' => $productData['seo_description'],
                    'status' => ProductStatus::Published->value,
                    'featured' => $productData['featured'],
                    'is_bestseller' => $productData['is_bestseller'],
                    'is_new' => $productData['is_new'],
                    'sort_order' => $productData['sort_order'],
                    'image_url' => $productData['image_url'],
                    'price_from' => $productData['price_from'],
                    'price_usd' => $productData['price_usd'],
                    'compare_at_price_usd' => $productData['compare_at_price_usd'],
                    'currency' => $productData['currency'],
                    'stock_quantity' => $productData['stock_quantity'],
                    'stock_status' => $productData['stock_status'],
                    'in_stock' => $productData['in_stock'],
                    'inquiry_only' => $productData['inquiry_only'],
                    'sample_request_enabled' => $productData['sample_request_enabled'],
                    'is_active' => true,
                    'published_at' => now(),
                ],
            );

            foreach ($productData['gallery'] as $index => $mediaUrl) {
                ProductImage::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sort_order' => $index + 1,
                    ],
                    [
                        'alt_text' => $product->name,
                        'alt_text_translations' => $product->name_translations,
                        'caption' => $index === 0 ? $product->short_description : $product->subtitle,
                        'caption_translations' => $product->short_description_translations,
                        'media_url' => $mediaUrl,
                    ],
                );
            }

            ProductImage::query()
                ->where('product_id', $product->id)
                ->whereNotIn('sort_order', range(1, count($productData['gallery'])))
                ->delete();

            $records[$productData['slug']] = $product;
        }

        foreach ($products as $productData) {
            $relatedIds = collect($productData['related'] ?? [])
                ->map(fn (string $slug): ?int => $records[$slug]->id ?? null)
                ->filter(fn (?int $id): bool => $id !== null && $id !== $records[$productData['slug']]->id)
                ->values()
                ->all();

            $records[$productData['slug']]->relatedProducts()->sync($relatedIds);
        }
    }
}
