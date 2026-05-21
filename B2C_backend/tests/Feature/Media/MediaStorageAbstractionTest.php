<?php

namespace Tests\Feature\Media;

use App\Filament\Support\AdminUploadStorage;
use App\Models\User;
use App\Services\MediaFileService;
use App\Services\MediaService;
use App\Services\Settings\SettingsService;
use App\Support\StorageUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageAbstractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_service_uploads_to_local_driver(): void
    {
        Storage::fake('public');
        $settings = app(SettingsService::class);
        $settings->set('storage.default_driver', 'local', ['type' => 'string']);
        $settings->set('storage.local.disk', 'public', ['type' => 'string']);

        $stored = app(MediaService::class)->upload(
            UploadedFile::fake()->image('local.png'),
            'community',
        );

        $this->assertSame('public', $stored['disk']);
        Storage::disk('public')->assertExists($stored['path']);
    }

    public function test_media_service_uploads_to_azure_driver(): void
    {
        Storage::fake('azure');
        Config::set('community.uploads.azure.use_sas_urls', false);
        Config::set('filesystems.disks.azure.storage_url', 'https://example.blob.core.windows.net');
        Config::set('filesystems.disks.azure.container', 'uploads');

        app(SettingsService::class)->set('storage.default_driver', 'azure', ['type' => 'string']);

        $stored = app(MediaService::class)->upload(
            UploadedFile::fake()->create('deck.pdf', 50, 'application/pdf'),
            'community',
        );

        $this->assertSame('azure', $stored['disk']);
        Storage::disk('azure')->assertExists($stored['path']);
        $this->assertStringStartsWith('https://example.blob.core.windows.net/uploads/', $stored['url']);
    }

    public function test_media_file_delete_uses_recorded_disk_after_driver_switch(): void
    {
        Storage::fake('public');
        Storage::fake('azure');

        $settings = app(SettingsService::class);
        $settings->set('storage.default_driver', 'local', ['type' => 'string']);
        $settings->set('storage.local.disk', 'public', ['type' => 'string']);

        $user = User::factory()->create();
        $media = app(MediaFileService::class)->upload(
            $user,
            UploadedFile::fake()->image('avatar.png'),
            'avatars',
        );

        $settings->set('storage.default_driver', 'azure', ['type' => 'string']);

        app(MediaFileService::class)->delete($user, $media->path);

        Storage::disk('public')->assertMissing($media->path);
        Storage::disk('azure')->assertMissing($media->path);
        $this->assertDatabaseMissing('media_files', ['id' => $media->id]);
    }

    public function test_admin_upload_storage_uses_runtime_local_public_disk(): void
    {
        Storage::fake('public');
        Config::set('community.uploads.disk', 'azure');

        $settings = app(SettingsService::class);
        $settings->set('storage.default_driver', 'local', ['type' => 'string']);
        $settings->set('storage.local.disk', 'public', ['type' => 'string']);

        $path = 'cms/products/admin-upload-test.txt';

        $this->assertSame('public', AdminUploadStorage::disk());
        $this->assertSame('public', AdminUploadStorage::visibility());
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
        $this->assertSame(url('/storage/'.$path), StorageUrl::publicResolve($path, AdminUploadStorage::disk()));

        Storage::disk(AdminUploadStorage::disk())->put($path, 'ok', [
            'visibility' => AdminUploadStorage::visibility(),
        ]);

        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_upload_storage_uses_private_visibility_for_azure(): void
    {
        Config::set('community.uploads.disk', 'public');

        app(SettingsService::class)->set('storage.default_driver', 'azure', ['type' => 'string']);

        $this->assertSame('azure', AdminUploadStorage::disk());
        $this->assertSame('private', AdminUploadStorage::visibility());
    }

    public function test_existing_method_signatures_remain_compatible(): void
    {
        $reflection = new \ReflectionClass(MediaService::class);

        foreach (['storeIdeaAttachment', 'storeCmsAsset', 'storeAvatar', 'upload', 'delete', 'move', 'deletePath', 'disk', 'url', 'publicUrl'] as $method) {
            $this->assertTrue($reflection->hasMethod($method));
        }
    }
}
