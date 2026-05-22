<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        if (
            DB::table('home_sections')
                ->where('key', 'footer')
                ->exists()
        ) {
            return;
        }

        $now = now();

        DB::table('home_sections')->insert([
            'key' => 'footer',
            'title' => 'Footer',
            'title_translations' => $this->json([
                'en' => 'Footer',
            ]),
            'subtitle' => 'Site footer',
            'subtitle_translations' => $this->json([
                'en' => 'Site footer',
            ]),
            'content' => 'Oyster shell pellets and finished objects from South Korea, shaped for premium retail, hospitality, and material partnerships.',
            'content_translations' => $this->json([
                'en' => 'Oyster shell pellets and finished objects from South Korea, shaped for premium retail, hospitality, and material partnerships.',
            ]),
            'cta_label' => null,
            'cta_label_translations' => null,
            'cta_url' => null,
            'payload' => $this->json([
                'variant' => 'footer',
                'home_translations' => ['en' => 'Home'],
                'material_translations' => ['en' => 'Material'],
                'store_translations' => ['en' => 'Store'],
                'b2b_translations' => ['en' => 'B2B'],
                'community_translations' => ['en' => 'Community'],
                'contact_translations' => ['en' => 'Contact'],
                'explore_translations' => ['en' => 'Explore'],
                'business_translations' => ['en' => 'Business'],
                'community_label_translations' => ['en' => 'Community'],
                'material_sheet_translations' => ['en' => 'Material Sheet'],
                'sample_request_translations' => ['en' => 'Material Request'],
                'product_development_translations' => ['en' => 'Product Development'],
                'idea_support_translations' => ['en' => 'Idea Support'],
                'concept_fund_translations' => ['en' => 'Concept Fund'],
                'email_label_translations' => ['en' => 'Email'],
                'phone_label_translations' => ['en' => 'Phone'],
                'location_label_translations' => ['en' => 'Location'],
                'location_value_translations' => ['en' => 'South Korea'],
                'copyright_translations' => ['en' => '(c) 2026 OXP. All rights reserved.'],
                'privacy_translations' => ['en' => 'Privacy Policy'],
                'terms_translations' => ['en' => 'Terms of Use'],
                'phone_value' => '+82 51-555-0188',
                'phone_href' => 'tel:+82515550188',
                'privacy_href' => '/privacy',
                'terms_href' => '/terms',
            ]),
            'is_seeded' => true,
            'status' => PublishStatus::Published->value,
            'sort_order' => 99,
            'media_path' => null,
            'media_url' => null,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        DB::table('home_sections')
            ->where('key', 'footer')
            ->where('is_seeded', true)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
};
