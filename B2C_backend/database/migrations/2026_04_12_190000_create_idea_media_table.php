<?php

use App\Enums\IdeaMediaKind;
use App\Enums\IdeaMediaSourceType;
use App\Enums\IdeaMediaType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('idea_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->default(IdeaMediaSourceType::Upload->value)->index();
            $table->string('media_type')->default(IdeaMediaType::Image->value)->index();
            $table->string('kind')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('disk')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('preview_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('external_url')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'sort_order']);
        });

        $legacyImages = DB::table('post_images')->orderBy('id')->get();

        foreach ($legacyImages as $image) {
            $fileName = basename((string) $image->path);
            $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));

            DB::table('idea_media')->insert([
                'id' => $image->id,
                'post_id' => $image->post_id,
                'source_type' => IdeaMediaSourceType::Upload->value,
                'media_type' => IdeaMediaType::Image->value,
                'kind' => IdeaMediaKind::ConceptImage->value,
                'title' => null,
                'alt_text' => $image->alt_text,
                'disk' => null,
                'original_name' => $fileName,
                'file_name' => $fileName,
                'extension' => $extension !== '' ? $extension : null,
                'mime_type' => null,
                'size_bytes' => null,
                'path' => $image->path,
                'url' => $image->url,
                'preview_url' => $image->url,
                'thumbnail_url' => $image->url,
                'external_url' => null,
                'metadata' => null,
                'sort_order' => $image->sort_order,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_media');
    }
};
