<?php

namespace Tests\Feature\Api;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use App\Enums\ContentStatus;
use App\Enums\FundingCampaignStatus;
use App\Models\B2BLead;
use App\Models\Category;
use App\Models\FundingCampaign;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retrieve_analytics_overview_metrics(): void
    {
        $admin = User::factory()->admin()->create();

        $materials = Category::factory()->create([
            'name' => 'Materials',
            'slug' => 'materials',
        ]);
        $furniture = Category::factory()->create([
            'name' => 'Furniture',
            'slug' => 'furniture',
        ]);

        $creator = User::factory()->create([
            'name' => 'Hana Lee',
            'role' => 'creator',
        ]);
        $creator->profile()->update([
            'school_or_company' => 'Pacific Materials Lab',
            'region' => 'Auckland',
            'open_to_collab' => true,
        ]);

        $secondCreator = User::factory()->create([
            'name' => 'Mina Park',
            'role' => 'creator',
        ]);
        $secondCreator->profile()->update([
            'school_or_company' => 'Coastal Design Studio',
            'region' => 'Busan',
            'open_to_collab' => false,
        ]);

        $mostViewedCandidate = Post::factory()->create([
            'user_id' => $creator->id,
            'category_id' => $materials->id,
            'title' => 'Premium shell chair',
            'slug' => 'premium-shell-chair',
            'status' => ContentStatus::Approved->value,
            'likes_count' => 12,
            'comments_count' => 6,
            'favorites_count' => 4,
            'engagement_score' => 68,
            'views_count' => 20,
            'is_featured' => true,
        ]);

        $fundingReady = Post::factory()->create([
            'user_id' => $creator->id,
            'category_id' => $materials->id,
            'title' => 'Crowdfunding-ready shell stool',
            'slug' => 'crowdfunding-ready-shell-stool',
            'status' => ContentStatus::Approved->value,
            'likes_count' => 8,
            'comments_count' => 5,
            'favorites_count' => 6,
            'engagement_score' => 58,
            'views_count' => 14,
            'is_featured' => false,
        ]);

        FundingCampaign::factory()->create([
            'post_id' => $fundingReady->id,
            'support_enabled' => true,
            'campaign_status' => FundingCampaignStatus::Scheduled->value,
        ]);

        Post::factory()->create([
            'user_id' => $secondCreator->id,
            'category_id' => $furniture->id,
            'title' => 'Furniture system concept',
            'status' => ContentStatus::Approved->value,
            'likes_count' => 4,
            'comments_count' => 1,
            'favorites_count' => 1,
            'engagement_score' => 21,
            'views_count' => 18,
        ]);

        Post::factory()->pending()->create([
            'user_id' => $secondCreator->id,
            'category_id' => $furniture->id,
            'title' => 'Pending shell bench concept',
            'engagement_score' => 0,
            'views_count' => 0,
        ]);

        B2BLead::factory()->create([
            'lead_type' => B2BLeadType::SampleRequest->value,
            'inquiry_type' => B2BLeadType::SampleRequest->label(),
            'status' => B2BLeadStatus::Qualified->value,
            'source_page' => 'materials:hero',
        ]);
        B2BLead::factory()->create([
            'lead_type' => B2BLeadType::PartnershipInquiry->value,
            'inquiry_type' => B2BLeadType::PartnershipInquiry->label(),
            'status' => B2BLeadStatus::Closed->value,
            'source_page' => 'materials:hero',
        ]);
        B2BLead::factory()->create([
            'lead_type' => B2BLeadType::BusinessContact->value,
            'inquiry_type' => B2BLeadType::BusinessContact->label(),
            'status' => B2BLeadStatus::New->value,
            'source_page' => 'homepage:hero',
        ]);

        $this->getJson("/api/posts/{$mostViewedCandidate->slug}")->assertOk();
        $this->getJson("/api/posts/{$mostViewedCandidate->slug}")->assertOk();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/analytics/overview?limit=3');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_concepts', 4)
            ->assertJsonPath('data.summary.approved_concepts', 3)
            ->assertJsonPath('data.attention.tracking.concept_views_available', true)
            ->assertJsonPath('data.categories.most_common.0.slug', 'materials')
            ->assertJsonPath('data.categories.highest_engagement.0.slug', 'materials')
            ->assertJsonPath('data.attention.most_viewed_concepts.0.id', $mostViewedCandidate->id)
            ->assertJsonPath('data.attention.best_performing_cta_sources.0.source_page', 'materials:hero');

        $response->assertJsonFragment([
            'school_or_company' => 'Pacific Materials Lab',
        ]);
        $response->assertJsonFragment([
            'id' => $fundingReady->id,
            'campaign_status' => FundingCampaignStatus::Scheduled->value,
        ]);
        $response->assertJsonFragment([
            'role' => 'creator',
        ]);
        $response->assertJsonFragment([
            'formula' => '(engagement_score) + (favorites_count * 3) + (views_count / 10) + featured_bonus + collab_bonus + support_bonus',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $mostViewedCandidate->id,
            'views_count' => 22,
        ]);
    }

    public function test_only_admin_can_access_analytics_overview(): void
    {
        $moderator = User::factory()->moderator()->create();

        Sanctum::actingAs($moderator);

        $this->getJson('/api/admin/analytics/overview')
            ->assertForbidden();
    }
}
