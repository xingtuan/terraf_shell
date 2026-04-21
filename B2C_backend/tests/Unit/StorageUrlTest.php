<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\MediaFile;
use App\Models\Post;
use App\Support\StorageUrl;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StorageUrlTest extends TestCase
{
    public function test_azure_storage_urls_are_signed_with_sas_tokens(): void
    {
        Config::set('community.uploads.disk', 'azure');
        Config::set('community.uploads.azure.use_sas_urls', true);
        Config::set('community.uploads.azure.signed_url_ttl_minutes', 120);

        $disk = Mockery::mock();
        $disk->shouldReceive('temporaryUrl')
            ->once()
            ->withArgs(function (string $path, $expiration): bool {
                return $path === 'images/community/2026/04/blob.png'
                    && now()->diffInMinutes($expiration, false) >= 119;
            })
            ->andReturn('https://example.blob.core.windows.net/uploads/images/community/2026/04/blob.png?sig=test');

        Storage::shouldReceive('disk')
            ->once()
            ->with('azure')
            ->andReturn($disk);

        $this->assertSame(
            'https://example.blob.core.windows.net/uploads/images/community/2026/04/blob.png?sig=test',
            StorageUrl::resolve('images/community/2026/04/blob.png', 'azure')
        );
    }

    public function test_public_resolve_returns_stable_azure_blob_url(): void
    {
        Config::set('filesystems.disks.azure.storage_url', 'https://example.blob.core.windows.net');
        Config::set('filesystems.disks.azure.container', 'uploads');

        $this->assertSame(
            'https://example.blob.core.windows.net/uploads/images/community/2026/04/blob.png',
            StorageUrl::publicResolve('images/community/2026/04/blob.png', 'azure')
        );
    }

    public function test_models_with_optional_media_paths_resolve_signed_urls_at_read_time(): void
    {
        Config::set('community.uploads.disk', 'azure');
        Config::set('community.uploads.azure.use_sas_urls', true);

        $disk = Mockery::mock();
        $disk->shouldReceive('temporaryUrl')
            ->once()
            ->withArgs(fn (string $path, $expiration): bool => $path === 'images/general/2026/04/material.jpg')
            ->andReturn('https://example.blob.core.windows.net/uploads/images/general/2026/04/material.jpg?sig=test');

        Storage::shouldReceive('disk')
            ->once()
            ->with('azure')
            ->andReturn($disk);

        $article = new Article([
            'media_path' => 'images/general/2026/04/material.jpg',
            'media_url' => 'https://stale.example.com/material.jpg',
        ]);

        $this->assertSame(
            'https://example.blob.core.windows.net/uploads/images/general/2026/04/material.jpg?sig=test',
            $article->media_url
        );
    }

    public function test_media_file_and_post_cover_image_resolve_signed_urls_from_paths(): void
    {
        Config::set('community.uploads.disk', 'azure');
        Config::set('community.uploads.azure.use_sas_urls', true);

        $disk = Mockery::mock();
        $disk->shouldReceive('temporaryUrl')
            ->once()
            ->withArgs(fn (string $path, $expiration): bool => $path === 'images/community/2026/04/upload.png')
            ->andReturn('https://example.blob.core.windows.net/uploads/images/community/2026/04/upload.png?sig=media');
        $disk->shouldReceive('temporaryUrl')
            ->once()
            ->withArgs(fn (string $path, $expiration): bool => $path === 'images/community/2026/04/cover.png')
            ->andReturn('https://example.blob.core.windows.net/uploads/images/community/2026/04/cover.png?sig=cover');

        Storage::shouldReceive('disk')
            ->twice()
            ->with('azure')
            ->andReturn($disk);

        $mediaFile = new MediaFile([
            'path' => 'images/community/2026/04/upload.png',
            'url' => 'https://stale.example.com/upload.png',
        ]);

        $post = new Post([
            'cover_image_path' => 'images/community/2026/04/cover.png',
            'cover_image_url' => 'https://stale.example.com/cover.png',
        ]);

        $this->assertSame(
            'https://example.blob.core.windows.net/uploads/images/community/2026/04/upload.png?sig=media',
            $mediaFile->url
        );
        $this->assertSame(
            'https://example.blob.core.windows.net/uploads/images/community/2026/04/cover.png?sig=cover',
            $post->coverImageUrl()
        );
    }
}
