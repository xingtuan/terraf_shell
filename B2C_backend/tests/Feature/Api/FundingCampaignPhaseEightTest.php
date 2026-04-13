<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\FundingCampaignStatus;
use App\Models\FundingCampaign;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FundingCampaignPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_post_responses_expose_visible_funding_campaign_data(): void
    {
        $post = Post::factory()->create([
            'status' => ContentStatus::Approved->value,
        ]);

        FundingCampaign::factory()->create([
            'post_id' => $post->id,
            'support_enabled' => true,
            'support_button_text' => 'Back this concept',
            'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/oyster-shell-chair',
            'campaign_status' => FundingCampaignStatus::Live->value,
            'target_amount' => 10000,
            'pledged_amount' => 2500,
            'backer_count' => 42,
            'reward_description' => 'Backers receive a sample tile and campaign updates.',
        ]);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.support_enabled', true)
            ->assertJsonPath('data.support_button_text', 'Back this concept')
            ->assertJsonPath('data.external_crowdfunding_url', 'https://crowdfund.example.com/projects/oyster-shell-chair')
            ->assertJsonPath('data.campaign_status', FundingCampaignStatus::Live->value)
            ->assertJsonPath('data.backer_count', 42)
            ->assertJsonPath('data.funding_campaign.progress_percentage', 25);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonFragment([
                'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/oyster-shell-chair',
            ]);
    }

    public function test_draft_funding_campaigns_are_hidden_from_public_post_responses(): void
    {
        $post = Post::factory()->create([
            'status' => ContentStatus::Approved->value,
        ]);

        FundingCampaign::factory()->create([
            'post_id' => $post->id,
            'support_enabled' => true,
            'support_button_text' => 'Support privately',
            'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/draft-shell-chair',
            'campaign_status' => FundingCampaignStatus::Draft->value,
        ]);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.support_enabled', false)
            ->assertJsonPath('data.support_button_text', null)
            ->assertJsonPath('data.external_crowdfunding_url', null)
            ->assertJsonPath('data.campaign_status', null)
            ->assertJsonMissingPath('data.funding_campaign');
    }

    public function test_admin_can_attach_update_and_remove_funding_campaigns_for_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($moderator);
        $this->patchJson("/api/admin/posts/{$post->id}/funding-campaign", [
            'support_enabled' => true,
            'support_button_text' => 'Back this concept',
            'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/moderator',
            'campaign_status' => FundingCampaignStatus::Live->value,
        ])->assertForbidden();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/posts/{$post->id}/funding-campaign", [
            'support_enabled' => true,
            'support_button_text' => 'Back this concept',
            'external_crowdfunding_url' => 'https://crowdfund.example.com/projects/oyster-shell-chair',
            'campaign_status' => FundingCampaignStatus::Scheduled->value,
            'target_amount' => 15000,
            'pledged_amount' => 3000,
            'backer_count' => 58,
            'reward_description' => 'Backers receive sample tiles and early updates.',
            'campaign_start_at' => '2026-05-01 00:00:00',
            'campaign_end_at' => '2026-06-01 00:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.support_enabled', true)
            ->assertJsonPath('data.campaign_status', FundingCampaignStatus::Scheduled->value)
            ->assertJsonPath('data.target_amount', 15000)
            ->assertJsonPath('data.pledged_amount', 3000);

        $this->assertDatabaseHas('funding_campaigns', [
            'post_id' => $post->id,
            'support_enabled' => true,
            'campaign_status' => FundingCampaignStatus::Scheduled->value,
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'post.funding_campaign_created',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ]);

        $this->getJson("/api/admin/posts/{$post->id}/funding-campaign")
            ->assertOk()
            ->assertJsonPath('data.external_crowdfunding_url', 'https://crowdfund.example.com/projects/oyster-shell-chair');

        $this->patchJson("/api/admin/posts/{$post->id}/funding-campaign", [
            'support_enabled' => false,
            'support_button_text' => 'Support coming soon',
            'external_crowdfunding_url' => null,
            'campaign_status' => FundingCampaignStatus::Paused->value,
            'target_amount' => 15000,
            'pledged_amount' => 4500,
            'backer_count' => 61,
            'reward_description' => 'Campaign paused while materials are refined.',
            'campaign_start_at' => '2026-05-01 00:00:00',
            'campaign_end_at' => '2026-06-15 00:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.support_enabled', false)
            ->assertJsonPath('data.campaign_status', FundingCampaignStatus::Paused->value)
            ->assertJsonPath('data.external_crowdfunding_url', null);

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'post.funding_campaign_updated',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ]);

        $this->deleteJson("/api/admin/posts/{$post->id}/funding-campaign")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('funding_campaigns', [
            'post_id' => $post->id,
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'action' => 'post.funding_campaign_deleted',
            'subject_type' => 'post',
            'subject_id' => $post->id,
        ]);
    }
}
