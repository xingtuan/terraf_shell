<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_and_moderator_can_access_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin')
            ->assertOk();
    }

    public function test_regular_users_and_banned_staff_cannot_access_the_admin_dashboard(): void
    {
        $regularUser = User::factory()->create();
        $bannedModerator = User::factory()->moderator()->banned()->create();

        $this->actingAs($regularUser)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($bannedModerator)
            ->get('/admin')
            ->assertForbidden();
    }
}
