<?php

namespace Tests\Unit\Support;

use App\Support\StorageDiskResolver;
use PHPUnit\Framework\TestCase;

class StorageDiskResolverTest extends TestCase
{
    public function test_it_falls_back_to_azure_when_s3_is_selected_without_the_adapter(): void
    {
        $resolver = new StorageDiskResolver(
            [
                's3' => [
                    'driver' => 's3',
                    'bucket' => 'community-assets',
                ],
                'azure' => [
                    'driver' => 'azure',
                    'name' => 'account',
                    'key' => 'secret',
                    'container' => 'uploads',
                ],
                'public' => [
                    'driver' => 'local',
                ],
            ],
            false,
            true,
        );

        $this->assertSame('azure', $resolver->resolve('s3'));
    }

    public function test_it_falls_back_to_public_when_cloud_disks_are_not_usable(): void
    {
        $resolver = new StorageDiskResolver(
            [
                's3' => [
                    'driver' => 's3',
                    'bucket' => '',
                ],
                'azure' => [
                    'driver' => 'azure',
                    'name' => '',
                    'key' => '',
                    'container' => 'uploads',
                ],
                'public' => [
                    'driver' => 'local',
                ],
            ],
            false,
            true,
        );

        $this->assertSame('public', $resolver->resolve('s3'));
    }

    public function test_it_accepts_s3_when_the_adapter_and_bucket_are_available(): void
    {
        $resolver = new StorageDiskResolver(
            [
                's3' => [
                    'driver' => 's3',
                    'bucket' => 'community-assets',
                ],
                'public' => [
                    'driver' => 'local',
                ],
            ],
            true,
            false,
        );

        $this->assertTrue($resolver->isUsable('s3'));
        $this->assertSame('s3', $resolver->resolve('s3'));
    }
}
