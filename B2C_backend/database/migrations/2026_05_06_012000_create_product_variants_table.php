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
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('title')->nullable();
            $table->json('option_values')->nullable();
            $table->decimal('price_amount', 10, 2);
            $table->decimal('compare_at_price_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('NZD');
            $table->integer('stock_quantity')->nullable();
            $table->string('stock_status')->default('in_stock')->index();
            $table->string('inventory_policy')->default('deny')->index();
            $table->integer('low_stock_threshold')->default(5);
            $table->integer('weight_grams')->nullable();
            $table->json('dimensions')->nullable();
            $table->string('image_url')->nullable();
            $table->string('media_path')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'is_default'], 'product_variants_product_active_default_index');
        });

        $seenSkus = [];

        DB::table('products')
            ->orderBy('id')
            ->select([
                'id',
                'name',
                'slug',
                'sku',
                'price_usd',
                'price_from',
                'compare_at_price_usd',
                'stock_quantity',
                'stock_status',
                'weight_grams',
                'dimensions',
                'image_url',
                'media_path',
                'media_url',
                'created_at',
                'updated_at',
            ])
            ->chunkById(100, function ($products) use (&$seenSkus): void {
                foreach ($products as $product) {
                    $primaryImage = DB::table('product_images')
                        ->where('product_id', $product->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->first(['media_url', 'media_path']);

                    $price = $product->price_usd
                        ?? $product->price_from
                        ?? 0;
                    $baseSku = $product->sku
                        ?: ($product->slug ? Str::upper(Str::slug((string) $product->slug, '_')) : null)
                        ?: 'OXP-'.$product->id;

                    DB::table('product_variants')->insert([
                        'product_id' => $product->id,
                        'sku' => $this->uniqueSku((string) $baseSku, (int) $product->id, $seenSkus),
                        'title' => 'Default',
                        'option_values' => null,
                        'price_amount' => number_format((float) $price, 2, '.', ''),
                        'compare_at_price_amount' => $product->compare_at_price_usd !== null
                            ? number_format((float) $product->compare_at_price_usd, 2, '.', '')
                            : null,
                        'currency' => 'NZD',
                        'stock_quantity' => $product->stock_quantity,
                        'stock_status' => $product->stock_status ?: 'in_stock',
                        'inventory_policy' => 'deny',
                        'low_stock_threshold' => 5,
                        'weight_grams' => $product->weight_grams,
                        'dimensions' => $this->jsonOrNull($product->dimensions),
                        'image_url' => $primaryImage?->media_url ?: ($product->image_url ?: $product->media_url),
                        'media_path' => $primaryImage?->media_path ?: $product->media_path,
                        'is_default' => true,
                        'is_active' => true,
                        'sort_order' => 0,
                        'created_at' => $product->created_at ?? now(),
                        'updated_at' => $product->updated_at ?? now(),
                    ]);
                }
            });

        DB::table('products')->update(['currency' => 'NZD']);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }

    /**
     * @param  array<string, true>  $seenSkus
     */
    private function uniqueSku(string $baseSku, int $productId, array &$seenSkus): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', '_', trim($baseSku));
        $candidate = Str::upper(trim($normalized ?: 'OXP_'.$productId, '_'));
        $candidate = $candidate !== '' ? $candidate : 'OXP_'.$productId;
        $original = $candidate;
        $suffix = 2;

        while (isset($seenSkus[$candidate]) || DB::table('product_variants')->where('sku', $candidate)->exists()) {
            $candidate = $original.'_'.$suffix;
            $suffix++;
        }

        $seenSkus[$candidate] = true;

        return $candidate;
    }

    private function jsonOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_THROW_ON_ERROR);
            }

            return json_encode(['label' => $value], JSON_THROW_ON_ERROR);
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
};
