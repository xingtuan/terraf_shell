<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Install\InstallationService;
use App\Services\Settings\SettingsService;
use Database\Seeders\DefaultAppSettingsSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class InstallController extends Controller
{
    public function index(InstallationService $installation): View
    {
        abort_if($installation->isInstalled(), 404);

        return view('install.index', [
            'requirements' => $installation->requirements(),
        ]);
    }

    public function store(Request $request, InstallationService $installation, SettingsService $settings): RedirectResponse
    {
        abort_if($installation->isInstalled(), 404);

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'frontend_url' => ['nullable', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'max:80'],
            'locale' => ['required', 'string', 'max:8'],
            'db_connection' => ['required', 'string', 'in:mysql,pgsql,sqlite,sqlsrv'],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'storage_driver' => ['required', 'string', 'in:local,azure'],
            'azure_account_name' => ['nullable', 'string', 'max:255'],
            'azure_account_key' => ['nullable', 'string', 'max:2000'],
            'azure_container' => ['nullable', 'string', 'max:255'],
            'azure_url' => ['nullable', 'url', 'max:255'],
            'mail_mailer' => ['required', 'string', 'in:log,array,smtp'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::min(10)],
        ]);

        if (! $installation->databaseIsReachable($validated)) {
            return back()
                ->withInput($request->except(['db_password', 'azure_account_key', 'mail_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['db_database' => 'Database connection failed.']);
        }

        try {
            $appKey = $this->ensureAppKey();
            $this->writeMinimalEnv($validated, $appKey);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => DefaultAppSettingsSeeder::class, '--force' => true]);

            $settings->setMany($this->runtimeSettingsPayload($validated));

            $admin = User::query()->firstOrNew([
                'email' => $validated['admin_email'],
            ]);

            $admin->forceFill([
                'name' => $validated['admin_name'],
                'username' => Str::slug(Str::before($validated['admin_email'], '@')).'-admin',
                'password' => $validated['admin_password'],
                'role' => 'admin',
                'account_status' => 'active',
                'is_banned' => false,
                'email_verified_at' => now(),
            ])->save();

            if ($validated['storage_driver'] === 'local') {
                Artisan::call('storage:link');
            }

            $settings->set('system.installed_at', now()->toISOString(), ['type' => 'string']);
            $installation->createLock();
            Artisan::call('optimize:clear');

            return redirect('/admin');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput($request->except(['db_password', 'azure_account_key', 'mail_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['install' => app()->environment('production') ? 'Installation failed.' : $throwable->getMessage()]);
        }
    }

    private function ensureAppKey(): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            $key = 'base64:'.base64_encode(random_bytes(32));
        }

        config(['app.key' => $key]);
        app()->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');

        return $key;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeMinimalEnv(array $data, string $appKey): void
    {
        $values = [
            'APP_KEY' => $appKey,
            'APP_ENV' => 'production',
            'APP_URL' => $data['app_url'],
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'] ?? '127.0.0.1',
            'DB_PORT' => (string) ($data['db_port'] ?? '3306'),
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'CACHE_STORE' => 'database',
            'SESSION_DRIVER' => 'database',
        ];

        $path = base_path('.env');
        $contents = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->envValue((string) $value);

            if (preg_match('/^'.$key.'=.*$/m', $contents) === 1) {
                $contents = preg_replace('/^'.$key.'=.*$/m', $line, $contents) ?? $contents;
            } else {
                $contents .= (str_ends_with($contents, PHP_EOL) || $contents === '' ? '' : PHP_EOL).$line.PHP_EOL;
            }
        }

        File::put($path, $contents);
    }

    private function envValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"|\'/', $value) === 1) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function runtimeSettingsPayload(array $data): array
    {
        return [
            'app.site_name' => ['value' => $data['app_name'], 'type' => 'string'],
            'app.url' => ['value' => $data['app_url'], 'type' => 'string'],
            'app.frontend_url' => ['value' => $data['frontend_url'] ?? null, 'type' => 'string'],
            'app.timezone' => ['value' => $data['timezone'], 'type' => 'string'],
            'app.default_locale' => ['value' => $data['locale'], 'type' => 'string'],
            'storage.default_driver' => ['value' => $data['storage_driver'], 'type' => 'string'],
            'storage.local.disk' => ['value' => 'public', 'type' => 'string'],
            'storage.azure.account_name' => ['value' => $data['azure_account_name'] ?? null, 'type' => 'string'],
            'storage.azure.account_key' => ['value' => $data['azure_account_key'] ?? null, 'type' => 'string', 'is_secret' => true],
            'storage.azure.container' => ['value' => $data['azure_container'] ?? 'uploads', 'type' => 'string'],
            'storage.azure.url' => ['value' => $data['azure_url'] ?? null, 'type' => 'string'],
            'mail.mailer' => ['value' => $data['mail_mailer'], 'type' => 'string'],
            'mail.host' => ['value' => $data['mail_host'] ?? null, 'type' => 'string'],
            'mail.port' => ['value' => $data['mail_port'] ?? null, 'type' => 'integer'],
            'mail.username' => ['value' => $data['mail_username'] ?? null, 'type' => 'string'],
            'mail.password' => ['value' => $data['mail_password'] ?? null, 'type' => 'string', 'is_secret' => true],
            'mail.encryption' => ['value' => $data['mail_encryption'] ?? null, 'type' => 'string'],
            'mail.from_address' => ['value' => $data['mail_from_address'], 'type' => 'string'],
            'mail.from_name' => ['value' => $data['mail_from_name'], 'type' => 'string'],
        ];
    }
}
