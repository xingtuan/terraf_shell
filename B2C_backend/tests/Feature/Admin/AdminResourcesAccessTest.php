<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourcesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_only_resource_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/enquiries')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/materials')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/materials/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/product-categories')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/product-images')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/b2-b-leads')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/funding-campaigns')
            ->assertOk();
    }

    public function test_moderator_can_access_staff_resources_but_not_admin_only_pages(): void
    {
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->actingAs($moderator)
            ->get('/admin/enquiries')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/posts')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/comments')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/reports')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/user-violations')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/idea-media')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/user-notifications')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/materials')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/product-categories')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/products')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/product-images')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/b2-b-leads')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get('/admin/funding-campaigns')
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get("/admin/users/{$user->getKey()}/edit")
            ->assertForbidden();
    }

    public function test_non_staff_roles_cannot_access_admin_resource_pages(): void
    {
        $creator = User::factory()->create();
        $smePartner = User::factory()->smePartner()->create();

        $this->actingAs($creator)
            ->get('/admin/posts')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/materials')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/products')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/admin/enquiries')
            ->assertForbidden();

        $this->actingAs($smePartner)
            ->get('/admin/comments')
            ->assertForbidden();
    }
}
