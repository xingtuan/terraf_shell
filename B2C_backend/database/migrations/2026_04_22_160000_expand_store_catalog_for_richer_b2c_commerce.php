<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku')->nullable()->unique();
            $table->string('subtitle')->nullable();
            $table->json('subtitle_translations')->nullable();
            $table->decimal('compare_at_price_usd', 10, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->string('stock_status')->default('in_stock')->index();
            $table->string('lead_time')->nullable();
            $table->json('lead_time_translations')->nullable();
            $table->boolean('is_bestseller')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->string('dimensions')->nullable();
            $table->json('dimensions_translations')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->json('specifications')->nullable();
            $table->json('certifications')->nullable();
            $table->json('certifications_translations')->nullable();
            $table->json('care_instructions')->nullable();
            $table->json('care_instructions_translations')->nullable();
            $table->json('material_benefits')->nullable();
            $table->json('material_benefits_translations')->nullable();
            $table->json('use_cases')->nullable();
            $table->json('seo_title_translations')->nullable();
            $table->string('seo_title')->nullable();
            $table->json('seo_description_translations')->nullable();
            $table->text('seo_description')->nullable();
        });

        Schema::create('product_related_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id']);
        });

        $canonicalCategories = [
            'tableware' => [
                'name' => 'Tableware',
                'description' => 'Dining pieces shaped for hospitality service and calm daily rituals.',
                'sort_order' => 1,
            ],
            'planters' => [
                'name' => 'Planters',
                'description' => 'Planters and sculptural vessels for botanical styling and premium interiors.',
                'sort_order' => 2,
            ],
            'wellness_interior' => [
                'name' => 'Wellness & Interior',
                'description' => 'Interior accents and wellness objects designed around quieter material rituals.',
                'sort_order' => 3,
            ],
            'architectural' => [
                'name' => 'Architectural',
                'description' => 'Material samples and surface objects for design studios and hospitality projects.',
                'sort_order' => 4,
            ],
        ];

        $categoryIds = [];

        foreach ($canonicalCategories as $slug => $category) {
            $existingId = DB::table('product_categories')->where('slug', $slug)->value('id');

            if ($existingId !== null) {
                DB::table('product_categories')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);

                $categoryIds[$slug] = (int) $existingId;

                continue;
            }

            $categoryIds[$slug] = (int) DB::table('product_categories')->insertGetId([
                'name' => $category['name'],
                'name_translations' => $this->encodeStringTranslations($category['name']),
                'description' => $category['description'],
                'description_translations' => $this->encodeStringTranslations($category['description']),
                'slug' => $slug,
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_categories')
            ->whereNotIn('slug', array_keys($canonicalCategories))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $products = DB::table('products')
            ->select([
                'id',
                'name',
                'name_translations',
                'slug',
                'category',
                'short_description',
                'short_description_translations',
                'availability_text',
                'availability_text_translations',
                'price_usd',
                'in_stock',
                'featured',
            ])
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            $categorySlug = array_key_exists((string) $product->category, $canonicalCategories)
                ? (string) $product->category
                : 'tableware';
            $name = is_string($product->name) ? trim($product->name) : '';
            $subtitle = is_string($product->short_description) ? trim($product->short_description) : null;
            $leadTime = is_string($product->availability_text) ? trim($product->availability_text) : null;
            $price = $product->price_usd !== null ? (float) $product->price_usd : null;
            $inStock = (bool) $product->in_stock;

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'category_id' => $categoryIds[$categorySlug],
                    'sku' => $this->normalizeSku((string) $product->slug),
                    'subtitle' => $subtitle,
                    'subtitle_translations' => $this->mergeStringTranslations(
                        $product->short_description_translations,
                        $subtitle,
                    ),
                    'stock_quantity' => $inStock ? 24 : 0,
                    'stock_status' => $inStock ? 'in_stock' : 'sold_out',
                    'lead_time' => $leadTime,
                    'lead_time_translations' => $this->mergeStringTranslations(
                        $product->availability_text_translations,
                        $leadTime,
                    ),
                    'is_bestseller' => (bool) $product->featured,
                    'is_new' => false,
                    'seo_title' => $name !== '' ? $name : null,
                    'seo_title_translations' => $this->mergeStringTranslations(
                        $product->name_translations,
                        $name !== '' ? $name : null,
                    ),
                    'seo_description' => $subtitle,
                    'seo_description_translations' => $this->mergeStringTranslations(
                        $product->short_description_translations,
                        $subtitle,
                    ),
                    'updated_at' => now(),
                ]);

            if ($price !== null && $price > 0.0) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'compare_at_price_usd' => round($price * 1.15, 2),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related_products');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'sku',
                'subtitle',
                'subtitle_translations',
                'compare_at_price_usd',
                'stock_quantity',
                'stock_status',
                'lead_time',
                'lead_time_translations',
                'is_bestseller',
                'is_new',
                'dimensions',
                'dimensions_translations',
                'weight_grams',
                'specifications',
                'certifications',
                'certifications_translations',
                'care_instructions',
                'care_instructions_translations',
                'material_benefits',
                'material_benefits_translations',
                'use_cases',
                'seo_title_translations',
                'seo_title',
                'seo_description_translations',
                'seo_description',
            ]);
        });
    }

    private function normalizeSku(string $slug): string
    {
        $normalized = Str::upper(str_replace('-', '_', trim($slug)));

        return $normalized !== '' ? $normalized : 'SHF_PRODUCT';
    }

    private function encodeStringTranslations(?string $fallback): ?string
    {
        $normalized = $fallback !== null && trim($fallback) !== ''
            ? ['en' => trim($fallback)]
            : [];

        return $normalized === [] ? null : json_encode($normalized, JSON_THROW_ON_ERROR);
    }

    private function mergeStringTranslations(mixed $translations, ?string $fallback): ?string
    {
        $normalized = [];

        if (is_array($translations)) {
            foreach (['en', 'ko', 'zh'] as $locale) {
                $value = $translations[$locale] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    $normalized[$locale] = trim($value);
                }
            }
        } elseif (is_string($translations) && trim($translations) !== '') {
            $decoded = json_decode($translations, true);

            if (is_array($decoded)) {
                foreach (['en', 'ko', 'zh'] as $locale) {
                    $value = $decoded[$locale] ?? null;

                    if (is_string($value) && trim($value) !== '') {
                        $normalized[$locale] = trim($value);
                    }
                }
            }
        }

        if ($normalized === [] && $fallback !== null && trim($fallback) !== '') {
            $normalized['en'] = trim($fallback);
        }

        return $normalized === [] ? null : json_encode($normalized, JSON_THROW_ON_ERROR);
    }
};
