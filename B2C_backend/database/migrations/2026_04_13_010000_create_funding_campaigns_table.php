<?php

use App\Enums\FundingCampaignStatus;
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
        Schema::create('funding_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('support_enabled')->default(false)->index();
            $table->string('support_button_text', 120)->default('Support this concept');
            $table->string('external_crowdfunding_url', 2048)->nullable();
            $table->string('campaign_status', 40)->default(FundingCampaignStatus::Draft->value)->index();
            $table->decimal('target_amount', 12, 2)->nullable();
            $table->decimal('pledged_amount', 12, 2)->nullable();
            $table->unsignedInteger('backer_count')->nullable();
            $table->text('reward_description')->nullable();
            $table->timestamp('campaign_start_at')->nullable();
            $table->timestamp('campaign_end_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_campaigns');
    }
};
