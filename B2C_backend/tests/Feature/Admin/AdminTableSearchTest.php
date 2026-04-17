<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTableSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_posts_table_by_creator_name_or_username(): void
    {
        $moderator = User::factory()->moderator()->create();
        $matchingAuthor = User::factory()->create([
            'name' => 'Rem Carter',
            'username' => 'remix_creator',
        ]);
        $nonMatchingAuthor = User::factory()->create([
            'name' => 'Alice Stone',
            'username' => 'alice_stone',
        ]);

        $matchingPost = Post::factory()->for($matchingAuthor)->create([
            'title' => 'Ocean concept',
        ]);
        $nonMatchingPost = Post::factory()->for($nonMatchingAuthor)->create([
            'title' => 'Forest concept',
        ]);

        $this->actingAs($moderator);

        Livewire::test(ListPosts::class)
            ->searchTable('Rem')
            ->assertCanSeeTableRecords([$matchingPost])
            ->assertCanNotSeeTableRecords([$nonMatchingPost])
            ->assertCountTableRecords(1);
    }

    public function test_staff_can_search_comments_table_by_author_name_or_username(): void
    {
        $moderator = User::factory()->moderator()->create();
        $matchingAuthor = User::factory()->create([
            'name' => 'Rem Carter',
            'username' => 'remix_creator',
        ]);
        $nonMatchingAuthor = User::factory()->create([
            'name' => 'Alice Stone',
            'username' => 'alice_stone',
        ]);

        $matchingComment = Comment::factory()->for($matchingAuthor)->create([
            'content' => 'Thoughtful feedback on the roadmap.',
        ]);
        $nonMatchingComment = Comment::factory()->for($nonMatchingAuthor)->create([
            'content' => 'Another note for review.',
        ]);

        $this->actingAs($moderator);

        Livewire::test(ListComments::class)
            ->searchTable('Rem')
            ->assertCanSeeTableRecords([$matchingComment])
            ->assertCanNotSeeTableRecords([$nonMatchingComment])
            ->assertCountTableRecords(1);
    }

    public function test_staff_can_search_reports_table_by_reporter_name_or_username(): void
    {
        $moderator = User::factory()->moderator()->create();
        $matchingReporter = User::factory()->create([
            'name' => 'Rem Carter',
            'username' => 'remix_creator',
        ]);
        $nonMatchingReporter = User::factory()->create([
            'name' => 'Alice Stone',
            'username' => 'alice_stone',
        ]);

        $matchingReport = Report::factory()->for($matchingReporter, 'reporter')->create([
            'reason' => 'Escalated for moderation review',
        ]);
        $nonMatchingReport = Report::factory()->for($nonMatchingReporter, 'reporter')->create([
            'reason' => 'Escalated for moderation review',
        ]);

        $this->actingAs($moderator);

        Livewire::test(ListReports::class)
            ->searchTable('Rem')
            ->assertCanSeeTableRecords([$matchingReport])
            ->assertCanNotSeeTableRecords([$nonMatchingReport])
            ->assertCountTableRecords(1);
    }
}
