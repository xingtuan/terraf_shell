<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.installer.title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f8fafc; color: #111827; }
        main { max-width: 960px; margin: 0 auto; padding: 32px 20px; }
        section { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        h1, h2 { margin-top: 0; }
        label { display: block; font-weight: 600; margin-top: 12px; }
        input, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        button { padding: 12px 18px; border: 0; border-radius: 6px; background: #111827; color: white; font-weight: 700; }
        button:disabled { cursor: not-allowed; opacity: .55; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .stepper { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin: 20px 0; }
        .step { border: 1px solid #d1d5db; border-radius: 999px; padding: 8px 10px; background: white; color: #374151; font-size: 13px; text-align: center; }
        .status { display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
        .ok { color: #047857; }
        .fail { color: #b91c1c; }
        .errors { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .notice { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .muted { color: #6b7280; font-size: 14px; }
        @media (max-width: 720px) { .grid, .stepper { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <h1>{{ __('admin.installer.title') }}</h1>
    <p class="muted">{{ __('admin.installer.intro') }}</p>

    <div class="stepper" aria-label="{{ __('admin.installer.progress_label') }}">
        <div class="step">1 {{ __('admin.installer.steps.requirements') }}</div>
        <div class="step">2 {{ __('admin.installer.steps.application') }}</div>
        <div class="step">3 {{ __('admin.installer.steps.database') }}</div>
        <div class="step">4 {{ __('admin.installer.steps.storage') }}</div>
        <div class="step">5 {{ __('admin.installer.steps.mail') }}</div>
        <div class="step">6 {{ __('admin.installer.steps.admin') }}</div>
        <div class="step">7 {{ __('admin.installer.steps.summary') }}</div>
        <div class="step">8 {{ __('admin.installer.steps.complete') }}</div>
    </div>

    @if ($isInstalling)
        <section class="notice">
            <strong>{{ __('admin.installer.messages.lock_title') }}</strong>
            <p>{{ __('admin.installer.messages.lock_body') }}</p>
        </section>
    @endif

    @if ($errors->any())
        <section class="errors">
            <strong>{{ __('admin.installer.messages.errors_title') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <p>{{ __('admin.installer.messages.secrets_hidden') }}</p>
        </section>
    @endif

    <section>
        <h2>{{ __('admin.installer.sections.requirements') }}</h2>
        @foreach ($requirements as $requirement)
            <div class="status">
                <span>{{ $requirement['label'] }}</span>
                <span class="{{ $requirement['ok'] ? 'ok' : 'fail' }}">{{ $requirement['ok'] ? __('admin.installer.messages.requirement_ok') : __('admin.installer.messages.requirement_check') }} - {{ $requirement['detail'] }}</span>
            </div>
        @endforeach
    </section>

    <form method="post" action="/install" autocomplete="off">
        @csrf
        <section>
            <h2>{{ __('admin.installer.sections.application') }}</h2>
            <div class="grid">
                <label>{{ __('admin.installer.fields.app_name') }} <input name="app_name" value="{{ old('app_name', 'Terraf OXP') }}" required></label>
                <label>{{ __('admin.installer.fields.app_url') }} <input name="app_url" value="{{ old('app_url', url('/')) }}" required></label>
                <label>{{ __('admin.installer.fields.frontend_url') }} <input name="frontend_url" value="{{ old('frontend_url') }}"></label>
                <label>{{ __('admin.installer.fields.timezone') }} <input name="timezone" value="{{ old('timezone', 'Pacific/Auckland') }}" required></label>
                <label>{{ __('admin.installer.fields.locale') }} <input name="locale" value="{{ old('locale', 'en') }}" required></label>
            </div>
        </section>

        <section>
            <h2>{{ __('admin.installer.sections.database') }}</h2>
            <div class="grid">
                <label>{{ __('admin.installer.fields.connection') }}
                    <select name="db_connection">
                        <option value="mysql" @selected(old('db_connection', 'mysql') === 'mysql')>mysql</option>
                        <option value="pgsql" @selected(old('db_connection') === 'pgsql')>pgsql</option>
                        <option value="sqlite" @selected(old('db_connection') === 'sqlite')>sqlite</option>
                        <option value="sqlsrv" @selected(old('db_connection') === 'sqlsrv')>sqlsrv</option>
                    </select>
                </label>
                <label>{{ __('admin.installer.fields.host') }} <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}"></label>
                <label>{{ __('admin.installer.fields.port') }} <input name="db_port" value="{{ old('db_port', '3306') }}"></label>
                <label>{{ __('admin.installer.fields.database') }} <input name="db_database" value="{{ old('db_database') }}" required></label>
                <label>{{ __('admin.installer.fields.username') }} <input name="db_username" value="{{ old('db_username') }}"></label>
                <label>{{ __('admin.installer.fields.password') }} <input type="password" name="db_password" autocomplete="new-password"></label>
            </div>
        </section>

        <section>
            <h2>{{ __('admin.installer.sections.storage') }}</h2>
            <div class="grid">
                <label>{{ __('admin.installer.fields.driver') }}
                    <select name="storage_driver">
                        <option value="local" @selected(old('storage_driver', 'local') === 'local')>local</option>
                        <option value="azure" @selected(old('storage_driver') === 'azure')>azure</option>
                    </select>
                </label>
                <label>{{ __('admin.installer.fields.azure_account_name') }} <input name="azure_account_name" value="{{ old('azure_account_name') }}"></label>
                <label>{{ __('admin.installer.fields.azure_account_key') }} <input type="password" name="azure_account_key" autocomplete="new-password"></label>
                <label>{{ __('admin.installer.fields.azure_container') }} <input name="azure_container" value="{{ old('azure_container', 'uploads') }}"></label>
                <label>{{ __('admin.installer.fields.azure_url') }} <input name="azure_url" value="{{ old('azure_url') }}"></label>
            </div>
        </section>

        <section>
            <h2>{{ __('admin.installer.sections.mail') }}</h2>
            <div class="grid">
                <label>{{ __('admin.installer.fields.mailer') }}
                    <select name="mail_mailer">
                        <option value="log" @selected(old('mail_mailer', 'log') === 'log')>log</option>
                        <option value="array" @selected(old('mail_mailer') === 'array')>array</option>
                        <option value="smtp" @selected(old('mail_mailer') === 'smtp')>smtp</option>
                    </select>
                </label>
                <label>{{ __('admin.installer.fields.smtp_host') }} <input name="mail_host" value="{{ old('mail_host') }}"></label>
                <label>{{ __('admin.installer.fields.smtp_port') }} <input name="mail_port" value="{{ old('mail_port', '587') }}"></label>
                <label>{{ __('admin.installer.fields.smtp_username') }} <input name="mail_username" value="{{ old('mail_username') }}"></label>
                <label>{{ __('admin.installer.fields.smtp_password') }} <input type="password" name="mail_password" autocomplete="new-password"></label>
                <label>{{ __('admin.installer.fields.encryption') }} <input name="mail_encryption" value="{{ old('mail_encryption', 'tls') }}"></label>
                <label>{{ __('admin.installer.fields.from_email') }} <input name="mail_from_address" value="{{ old('mail_from_address', 'hello@example.com') }}" required></label>
                <label>{{ __('admin.installer.fields.from_name') }} <input name="mail_from_name" value="{{ old('mail_from_name', 'Terraf OXP') }}" required></label>
            </div>
        </section>

        <section>
            <h2>{{ __('admin.installer.sections.admin') }}</h2>
            <div class="grid">
                <label>{{ __('admin.installer.fields.name') }} <input name="admin_name" value="{{ old('admin_name') }}" required></label>
                <label>{{ __('admin.installer.fields.email') }} <input name="admin_email" value="{{ old('admin_email') }}" required></label>
                <label>{{ __('admin.installer.fields.password') }} <input type="password" name="admin_password" autocomplete="new-password" required></label>
                <label>{{ __('admin.installer.fields.confirm_password') }} <input type="password" name="admin_password_confirmation" autocomplete="new-password" required></label>
            </div>
        </section>

        <section>
            <h2>{{ __('admin.installer.sections.summary') }}</h2>
            <p class="muted">{{ __('admin.installer.messages.summary') }}</p>
            <button type="submit" @disabled($isInstalling)>{{ __('admin.installer.messages.submit') }}</button>
        </section>
    </form>
</main>
</body>
</html>
