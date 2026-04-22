<?php

use App\Enums\CommunitySubmissionPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_moderation_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('submission_policy')
                ->default(CommunitySubmissionPolicy::AllRequireApproval->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_moderation_settings');
    }
};
