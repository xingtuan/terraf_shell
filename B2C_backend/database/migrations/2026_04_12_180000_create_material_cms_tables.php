<?php

use App\Enums\PublishStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->longText('story_overview')->nullable();
            $table->longText('science_overview')->nullable();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('key')->nullable()->index();
            $table->string('label');
            $table->string('value');
            $table->string('unit')->nullable();
            $table->text('detail')->nullable();
            $table->string('icon')->nullable();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_story_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('content');
            $table->string('highlight')->nullable();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('description');
            $table->string('audience')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('category')->nullable()->index();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default(PublishStatus::Draft->value)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sections');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('material_applications');
        Schema::dropIfExists('material_story_sections');
        Schema::dropIfExists('material_specs');
        Schema::dropIfExists('materials');
    }
};
