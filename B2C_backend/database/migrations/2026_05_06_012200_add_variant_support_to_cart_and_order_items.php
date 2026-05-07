<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->decimal('unit_price_amount', 10, 2)->nullable()->after('unit_price_usd');
            $table->string('currency', 3)->default('NZD')->after('unit_price_amount');
        });

        DB::table('cart_items')
            ->orderBy('id')
            ->select(['id', 'product_id', 'unit_price_usd'])
            ->chunkById(100, function ($items): void {
                foreach ($items as $item) {
                    $variantId = DB::table('product_variants')
                        ->where('product_id', $item->product_id)
                        ->where('is_default', true)
                        ->value('id');

                    DB::table('cart_items')
                        ->where('id', $item->id)
                        ->update([
                            'product_variant_id' => $variantId,
                            'unit_price_amount' => $item->unit_price_usd,
                            'currency' => 'NZD',
                        ]);
                }
            });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->unique(
                ['cart_id', 'product_id', 'product_variant_id'],
                'cart_items_cart_product_variant_unique',
            );
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('product_title')->nullable()->after('product_sku');
            $table->string('variant_title')->nullable()->after('product_title');
            $table->string('variant_sku')->nullable()->after('variant_title');
            $table->json('option_values')->nullable()->after('variant_sku');
            $table->decimal('unit_price_amount', 10, 2)->nullable()->after('unit_price_usd');
            $table->string('currency', 3)->default('NZD')->after('unit_price_amount');
        });

        DB::table('order_items')
            ->orderBy('id')
            ->select(['id', 'product_id', 'product_name', 'product_sku', 'unit_price_usd'])
            ->chunkById(100, function ($items): void {
                foreach ($items as $item) {
                    $variant = DB::table('product_variants')
                        ->where('product_id', $item->product_id)
                        ->where('is_default', true)
                        ->first(['id', 'title', 'sku', 'option_values']);

                    DB::table('order_items')
                        ->where('id', $item->id)
                        ->update([
                            'product_variant_id' => $variant?->id,
                            'product_title' => $item->product_name,
                            'variant_title' => $variant?->title,
                            'variant_sku' => $variant?->sku ?: $item->product_sku,
                            'option_values' => $variant?->option_values,
                            'unit_price_amount' => $item->unit_price_usd,
                            'currency' => 'NZD',
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn([
                'product_variant_id',
                'product_title',
                'variant_title',
                'variant_sku',
                'option_values',
                'unit_price_amount',
                'currency',
            ]);
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique('cart_items_cart_product_variant_unique');
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn([
                'product_variant_id',
                'unit_price_amount',
                'currency',
            ]);
            $table->unique(['cart_id', 'product_id']);
        });
    }
};
