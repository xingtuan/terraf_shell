<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->string('priority', 20)
                ->default('normal')
                ->after('status')
                ->index();
            $table->timestamp('follow_up_at')
                ->nullable()
                ->after('assigned_to')
                ->index();
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->boolean('is_demo_content')
                ->default(false)
                ->after('is_featured')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('is_demo_content');
        });

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropColumn(['priority', 'follow_up_at']);
        });
    }
};
