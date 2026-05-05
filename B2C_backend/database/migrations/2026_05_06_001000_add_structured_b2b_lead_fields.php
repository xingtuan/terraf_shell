<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->string('interest_type', 80)
                ->nullable()
                ->after('lead_type')
                ->index();
            $table->string('application_type', 150)
                ->nullable()
                ->after('interest_type')
                ->index();
            $table->text('expected_use_case')
                ->nullable()
                ->after('application_type');
            $table->string('estimated_quantity', 120)
                ->nullable()
                ->after('expected_use_case');
            $table->string('timeline', 120)
                ->nullable()
                ->after('estimated_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropColumn([
                'interest_type',
                'application_type',
                'expected_use_case',
                'estimated_quantity',
                'timeline',
            ]);
        });
    }
};
