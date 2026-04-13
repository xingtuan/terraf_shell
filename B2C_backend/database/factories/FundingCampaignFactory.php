<?php

namespace Database\Factories;

use App\Enums\FundingCampaignStatus;
use App\Models\FundingCampaign;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundingCampaign>
 */
class FundingCampaignFactory extends Factory
{
    protected $model = FundingCampaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'support_enabled' => true,
            'support_button_text' => 'Support this concept',
            'external_crowdfunding_url' => fake()->url(),
            'campaign_status' => FundingCampaignStatus::Live->value,
            'target_amount' => 10000,
            'pledged_amount' => 2500,
            'backer_count' => 32,
            'reward_description' => 'Early backers receive a material sample pack.',
            'campaign_start_at' => now()->subDays(3),
            'campaign_end_at' => now()->addDays(27),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'support_enabled' => false,
            'external_crowdfunding_url' => null,
            'campaign_status' => FundingCampaignStatus::Draft->value,
            'campaign_start_at' => null,
            'campaign_end_at' => null,
        ]);
    }
}
