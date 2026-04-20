<?php

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->restrictOnDelete();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('short_description')->nullable();
            $table->json('short_description_translations')->nullable();
            $table->longText('full_description')->nullable();
            $table->json('full_description_translations')->nullable();
            $table->json('features')->nullable();
            $table->json('features_translations')->nullable();
            $table->string('availability_text')->nullable();
            $table->json('availability_text_translations')->nullable();
            $table->string('slug')->unique();
            $table->string('status')->default(ProductStatus::Draft->value)->index();
            $table->boolean('featured')->default(false)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->decimal('price_from', 12, 2)->nullable();
            $table->string('currency', 3)->default('KRW');
            $table->boolean('inquiry_only')->default(false);
            $table->boolean('sample_request_enabled')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('alt_text')->nullable();
            $table->json('alt_text_translations')->nullable();
            $table->string('caption')->nullable();
            $table->json('caption_translations')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
