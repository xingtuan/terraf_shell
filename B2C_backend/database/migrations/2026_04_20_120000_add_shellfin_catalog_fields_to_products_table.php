<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->enum('category', ['tableware', 'planters', 'wellness_interior', 'architectural'])
                ->default('tableware')
                ->after('slug');
            $table->enum('model', ['lite_15', 'heritage_16'])
                ->default('lite_15')
                ->after('category');
            $table->enum('finish', ['glossy', 'matte'])
                ->default('glossy')
                ->after('model');
            $table->enum('color', ['ocean_bone', 'forged_ash'])
                ->default('ocean_bone')
                ->after('finish');
            $table->enum('technique', ['original_pure', 'precision_inlay', 'driftwood_blend'])
                ->default('original_pure')
                ->after('color');
            $table->decimal('price_usd', 8, 2)
                ->nullable()
                ->after('price_from');
            $table->boolean('in_stock')
                ->default(true)
                ->after('price_usd');
            $table->string('image_url')
                ->nullable()
                ->after('media_url');
            $table->boolean('is_active')
                ->default(true)
                ->after('sample_request_enabled')
                ->index();
        });

        DB::table('products')
            ->whereNull('price_usd')
            ->update([
                'price_usd' => DB::raw('COALESCE(price_from, 0.00)'),
                'image_url' => DB::raw('COALESCE(media_url, image_url)'),
                'is_active' => DB::raw("CASE WHEN status = 'published' THEN 1 ELSE 0 END"),
            ]);

        $categoryMap = [
            'tableware' => 'tableware',
            'planters' => 'planters',
            'wellness-interior' => 'wellness_interior',
            'wellness_interior' => 'wellness_interior',
            'architectural' => 'architectural',
            'home-objects' => 'wellness_interior',
            'gift-sets' => 'wellness_interior',
        ];

        $products = DB::table('products')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->select('products.id', 'product_categories.slug as category_slug')
            ->get();

        foreach ($products as $product) {
            $category = $categoryMap[$product->category_slug] ?? 'tableware';

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'category' => $category,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'category',
                'model',
                'finish',
                'color',
                'technique',
                'price_usd',
                'in_stock',
                'image_url',
                'is_active',
            ]);
        });
    }
};
