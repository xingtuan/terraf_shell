<?php

namespace App\Providers;

use App\Filesystem\AzureFilesystemAdapter as LaravelAzureFilesystemAdapter;
use App\Models\B2BLead;
use App\Models\Comment;
use App\Models\Inquiry;
use App\Models\PartnershipInquiry;
use App\Models\Post;
use App\Models\Report;
use App\Models\SampleRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! extension_loaded('intl')) {
            require_once app_path('Support/IntlFallback.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $buildAzureConnectionString = static function (array $config): string {
            $accountName = trim((string) ($config['name'] ?? ''));
            $accountKey = trim((string) ($config['key'] ?? ''));
            $serviceUrl = trim((string) ($config['storage_url'] ?? ''));
            $protocol = parse_url($serviceUrl, PHP_URL_SCHEME) ?: 'https';

            $connectionString = sprintf(
                'DefaultEndpointsProtocol=%s;AccountName=%s;AccountKey=%s',
                $protocol,
                $accountName,
                $accountKey
            );

            if ($serviceUrl !== '') {
                $connectionString .= ';BlobEndpoint='.rtrim($serviceUrl, '/');
            }

            return $connectionString.';';
        };

        Storage::extend('azure', function ($app, array $config) use ($buildAzureConnectionString) {
            $connectionString = $buildAzureConnectionString($config);
            $client = BlobRestProxy::createBlobService($connectionString);
            $serviceSettings = StorageServiceSettings::createFromConnectionString($connectionString);

            $adapter = new AzureBlobStorageAdapter(
                $client,
                (string) $config['container'],
                (string) ($config['prefix'] ?? ''),
                null,
                5000,
                AzureBlobStorageAdapter::ON_VISIBILITY_IGNORE,
                $serviceSettings
            );

            return new LaravelAzureFilesystemAdapter(
                $this->createFlysystem($adapter, $config),
                $adapter,
                $config
            );
        });

        Relation::enforceMorphMap([
            'post' => Post::class,
            'comment' => Comment::class,
            'user' => User::class,
            'report' => Report::class,
            'inquiry' => Inquiry::class,
            'b2b_lead' => B2BLead::class,
            'partnership_inquiry' => PartnershipInquiry::class,
            'sample_request' => SampleRequest::class,
        ]);

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());

        RateLimiter::for('auth', fn ($request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for(
            'password-reset',
            fn ($request): Limit => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())
        );
        RateLimiter::for(
            'verification',
            fn ($request): Limit => Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip()))
        );
        RateLimiter::for(
            'leads',
            fn ($request): Limit => Limit::perMinute(15)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            )
        );
    }
}
