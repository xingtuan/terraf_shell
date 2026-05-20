<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        if (! Schema::hasColumn('home_sections', 'page_key')) {
            Schema::table('home_sections', function (Blueprint $table): void {
                $table->string('page_key')->default('home')->after('id')->index();
            });
        }

        DB::table('home_sections')
            ->where(function ($query): void {
                $query->whereNull('page_key')->orWhere('page_key', '');
            })
            ->update(['page_key' => 'home']);

        Schema::table('home_sections', function (Blueprint $table): void {
            try {
                $table->dropUnique('home_sections_key_unique');
            } catch (Throwable) {
                // Older or partially migrated databases may not have the legacy index.
            }
        });

        Schema::table('home_sections', function (Blueprint $table): void {
            try {
                $table->unique(['page_key', 'key'], 'home_sections_page_key_key_unique');
            } catch (Throwable) {
                // Index may already exist on repeated migration runs.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        Schema::table('home_sections', function (Blueprint $table): void {
            try {
                $table->dropUnique('home_sections_page_key_key_unique');
            } catch (Throwable) {
                //
            }

            $table->unique('key', 'home_sections_key_unique');
        });

        if (Schema::hasColumn('home_sections', 'page_key')) {
            Schema::table('home_sections', function (Blueprint $table): void {
                $table->dropColumn('page_key');
            });
        }
    }
};
