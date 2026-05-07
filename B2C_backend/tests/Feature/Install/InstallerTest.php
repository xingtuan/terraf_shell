<?php

namespace Tests\Feature\Install;

use App\Services\Install\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(app(InstallationService::class)->lockPath());

        parent::tearDown();
    }

    public function test_requirements_page_loads_when_not_installed(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('Requirements Check')
            ->assertSee('Admin Account');
    }

    public function test_installer_is_unavailable_when_installed_lock_exists(): void
    {
        File::ensureDirectoryExists(dirname(app(InstallationService::class)->lockPath()));
        File::put(app(InstallationService::class)->lockPath(), now()->toISOString());

        $this->get('/install')->assertNotFound();
    }

    public function test_install_flow_validates_required_fields(): void
    {
        $this->post('/install', [])
            ->assertSessionHasErrors([
                'app_name',
                'app_url',
                'db_connection',
                'db_database',
                'storage_driver',
                'mail_mailer',
                'mail_from_address',
                'mail_from_name',
                'admin_name',
                'admin_email',
                'admin_password',
            ]);
    }
}
