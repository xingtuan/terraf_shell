<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->json('certifications')->nullable()->after('science_overview_translations');
            $table->json('technical_downloads')->nullable()->after('certifications');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->json('technical_downloads')->nullable()->after('certifications_translations');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('technical_downloads');
        });

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn([
                'certifications',
                'technical_downloads',
            ]);
        });
    }
};
