<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('media_files', 'disk')) {
            Schema::table('media_files', function (Blueprint $table): void {
                $table->string('disk')->nullable()->after('user_id')->index();
            });
        }

        DB::table('media_files')
            ->whereNull('disk')
            ->update([
                'disk' => (string) config('community.uploads.disk', config('filesystems.default', 'public')),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('media_files', 'disk')) {
            Schema::table('media_files', function (Blueprint $table): void {
                $table->dropIndex(['disk']);
                $table->dropColumn('disk');
            });
        }
    }
};
