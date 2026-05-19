<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('posts', 'cover_image_disk')) {
                $table->string('cover_image_disk')->nullable()->after('cover_image_path')->index();
            }
        });

        if (! Schema::hasColumn('posts', 'cover_image_disk')) {
            return;
        }

        DB::table('posts')
            ->whereNotNull('cover_image_path')
            ->whereNull('cover_image_disk')
            ->orderBy('id')
            ->select(['id', 'cover_image_path', 'cover_image_url'])
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    $disk = null;

                    if (Schema::hasTable('media_files') && Schema::hasColumn('media_files', 'disk')) {
                        $disk = DB::table('media_files')
                            ->where('path', $post->cover_image_path)
                            ->value('disk');
                    }

                    $url = (string) ($post->cover_image_url ?? '');

                    if (! $disk && (str_contains($url, '/storage/') || str_contains($url, '/media/files/public/'))) {
                        $disk = 'public';
                    }

                    if (! $disk && str_contains($url, '.blob.core.windows.net/')) {
                        $disk = 'azure';
                    }

                    $disk ??= config('community.uploads.disk', config('filesystems.default', 'public'));
                    $disk = $disk === 'local' ? 'public' : $disk;

                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update(['cover_image_disk' => $disk]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            if (Schema::hasColumn('posts', 'cover_image_disk')) {
                $table->dropIndex(['cover_image_disk']);
                $table->dropColumn('cover_image_disk');
            }
        });
    }
};
