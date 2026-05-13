<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\NotificationType;
use App\Enums\ReportResolutionAction;
use App\Enums\ReportStatus;
use App\Models\Post;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_submit_report_and_receives_received_notification(): void
    {
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);

        Sanctum::actingAs($reporter);

        $response = $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Spam or misleading information',
            'description' => 'This looks promotional and off-topic.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.target_id', $post->id)
            ->assertJsonMissingPath('data.moderator_note');

        $this->assertDatabaseHas('reports', [
            'id' => $response->json('data.id'),
            'reporter_id' => $reporter->id,
            'status' => ReportStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_user_id' => $reporter->id,
            'type' => NotificationType::ReportReceived->value,
            'body' => 'We received your report.',
        ]);
    }

    public function test_reporter_can_list_only_their_own_reports(): void
    {
        [$reporter, $otherReporter] = User::factory()->count(2)->create();
        $owner = User::factory()->create();
        $firstPost = Post::factory()->create(['user_id' => $owner->id]);
        $secondPost = Post::factory()->create(['user_id' => $owner->id]);

        $ownReportId = $this->submitReport($reporter, $firstPost);
        $otherReportId = $this->submitReport($otherReporter, $secondPost);

        Sanctum::actingAs($reporter);

        $this->getJson('/api/reports')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ownReportId)
            ->assertJsonMissing(['id' => $otherReportId]);
    }

    public function test_reporter_cannot_view_other_users_report(): void
    {
        [$reporter, $otherReporter] = User::factory()->count(2)->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($otherReporter, $post);

        Sanctum::actingAs($reporter);

        $this->getJson("/api/reports/{$reportId}")
            ->assertForbidden();
    }

    public function test_reporter_cannot_see_moderator_note_but_admin_can(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/reports/{$reportId}/review", [
            'internal_note' => 'Internal staff-only note.',
            'public_note' => 'We are reviewing this report.',
        ])->assertOk();

        Sanctum::actingAs($reporter);
        $this->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonMissingPath('data.moderator_note')
            ->assertJsonPath('data.public_note', 'We are reviewing this report.');

        Sanctum::actingAs($admin);
        $this->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonPath('data.moderator_note', 'Internal staff-only note.');
    }

    public function test_mark_reviewed_sends_safe_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/reports/{$reportId}/review", [
            'internal_note' => 'Do not show this internal review note.',
            'public_note' => 'Your report is now under review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Reviewed->value);

        $notification = $this->notification($reporter, NotificationType::ReportReviewed);

        $this->assertSame('Your report is now under review.', $notification->body);
        $this->assertStringNotContainsString('internal review note', $notification->body);
    }

    public function test_dismiss_sends_safe_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/reports/{$reportId}/dismiss", [
            'internal_note' => 'Internal dismissal note.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Dismissed->value);

        $notification = $this->notification($reporter, NotificationType::ReportDismissed);

        $this->assertSame('We reviewed your report and did not find a violation.', $notification->body);
        $this->assertStringNotContainsString('Internal dismissal note', $notification->body);
    }

    public function test_resolve_sends_safe_notification_without_raw_punishment_details(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/reports/{$reportId}/resolve", [
            'resolution_action' => ReportResolutionAction::UserBanned->value,
            'internal_note' => 'Ban details are private.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Resolved->value);

        $notification = $this->notification($reporter, NotificationType::ReportResolved);

        $this->assertSame('We reviewed your report and took appropriate action.', $notification->body);
        $this->assertStringNotContainsString('Ban details', $notification->body);
        $this->assertStringNotContainsString(ReportResolutionAction::UserBanned->value, json_encode($notification->data));

        Sanctum::actingAs($reporter);
        $this->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonPath('data.resolution_action', 'action_taken');
    }

    public function test_hide_target_and_resolve_updates_target_and_report_status(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $owner->id,
            'status' => ContentStatus::Approved->value,
        ]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/reports/{$reportId}/hide-target", [
            'internal_note' => 'Content hidden after report.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Resolved->value)
            ->assertJsonPath('data.resolution_action', ReportResolutionAction::ContentHidden->value);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => ContentStatus::Hidden->value,
        ]);

        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'status' => ReportStatus::Resolved->value,
            'resolution_action' => ReportResolutionAction::ContentHidden->value,
        ]);
    }

    public function test_reviewed_report_can_be_resolved_or_dismissed_and_is_completed(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $firstPost = Post::factory()->create(['user_id' => $owner->id]);
        $secondPost = Post::factory()->create(['user_id' => $owner->id]);
        $resolveReportId = $this->submitReport($reporter, $firstPost);
        $dismissReportId = $this->submitReport($reporter, $secondPost);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/reports/{$resolveReportId}/review", [
            'internal_note' => 'Reviewed before resolution.',
        ])->assertOk();

        $this->patchJson("/api/admin/reports/{$resolveReportId}/resolve", [
            'resolution_action' => ReportResolutionAction::Other->value,
            'internal_note' => 'Resolved after review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Resolved->value)
            ->assertJsonPath('data.resolution_action', ReportResolutionAction::Other->value);

        $this->assertDatabaseHas('reports', [
            'id' => $resolveReportId,
            'status' => ReportStatus::Resolved->value,
        ]);

        $this->assertNotNull($this->reportTimestamp($resolveReportId, 'completed_at'));
        $this->assertNotNull($this->reportTimestamp($resolveReportId, 'resolved_at'));

        $this->patchJson("/api/admin/reports/{$dismissReportId}/review", [
            'internal_note' => 'Reviewed before dismissal.',
        ])->assertOk();

        $this->patchJson("/api/admin/reports/{$dismissReportId}/dismiss", [
            'internal_note' => 'Dismissed after review.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Dismissed->value);

        $this->assertNotNull($this->reportTimestamp($dismissReportId, 'completed_at'));
        $this->assertNotNull($this->reportTimestamp($dismissReportId, 'dismissed_at'));
    }

    public function test_finalized_report_cannot_return_to_reviewed_or_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        $reportId = $this->submitReport($reporter, $post);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/reports/{$reportId}/resolve", [
            'resolution_action' => ReportResolutionAction::Other->value,
            'internal_note' => 'Final resolution.',
        ])->assertOk();

        $this->patchJson("/api/admin/reports/{$reportId}/review", [
            'internal_note' => 'Trying to review again.',
        ])->assertUnprocessable();

        $this->patchJson("/api/admin/reports/{$reportId}/status", [
            'status' => ReportStatus::Pending->value,
            'reason' => 'Trying to reopen through generic status.',
        ])->assertUnprocessable();

        $this->patchJson("/api/admin/reports/{$reportId}/status", [
            'status' => ReportStatus::Reviewed->value,
            'reason' => 'Trying to move back to reviewed.',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'status' => ReportStatus::Resolved->value,
        ]);
    }

    public function test_report_table_workflow_actions_are_limited_to_open_reports(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/Reports/Tables/ReportsTable.php'));

        $this->assertStringContainsString('isOpenForModeration', $source);
        $this->assertStringNotContainsString("status !== ReportStatus::Resolved", $source);
        $this->assertStringNotContainsString("status !== ReportStatus::Dismissed", $source);
    }

    public function test_duplicate_report_is_blocked(): void
    {
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->submitReport($reporter, $post);

        Sanctum::actingAs($reporter);
        $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Spam or misleading information',
        ])->assertUnprocessable();
    }

    public function test_self_report_is_blocked(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $reporter->id]);

        Sanctum::actingAs($reporter);
        $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Spam or misleading information',
        ])->assertUnprocessable();
    }

    private function submitReport(User $reporter, Post $post): int
    {
        Sanctum::actingAs($reporter);

        return (int) $this->postJson('/api/reports', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'Spam or misleading information',
            'description' => 'This content should be reviewed.',
        ])
            ->assertCreated()
            ->json('data.id');
    }

    private function notification(User $recipient, NotificationType $type): UserNotification
    {
        return UserNotification::query()
            ->where('recipient_user_id', $recipient->id)
            ->where('type', $type->value)
            ->latest('id')
            ->firstOrFail();
    }

    private function reportTimestamp(int $reportId, string $column): ?string
    {
        return \App\Models\Report::query()
            ->whereKey($reportId)
            ->value($column);
    }
}
