<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Terraf OXP</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f8fafc; color: #111827; }
        main { max-width: 960px; margin: 0 auto; padding: 32px 20px; }
        section { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        h1, h2 { margin-top: 0; }
        label { display: block; font-weight: 600; margin-top: 12px; }
        input, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
        button { padding: 12px 18px; border: 0; border-radius: 6px; background: #111827; color: white; font-weight: 700; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .status { display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
        .ok { color: #047857; }
        .fail { color: #b91c1c; }
        .errors { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <h1>Install Terraf OXP</h1>

    @if ($errors->any())
        <section class="errors">
            <strong>Installation cannot continue yet.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <h2>Step 1 - Requirements Check</h2>
        @foreach ($requirements as $requirement)
            <div class="status">
                <span>{{ $requirement['label'] }}</span>
                <span class="{{ $requirement['ok'] ? 'ok' : 'fail' }}">{{ $requirement['ok'] ? 'OK' : 'Check' }} - {{ $requirement['detail'] }}</span>
            </div>
        @endforeach
    </section>

    <form method="post" action="/install">
        @csrf
        <section>
            <h2>Step 2 - Application Settings</h2>
            <div class="grid">
                <label>App name <input name="app_name" value="{{ old('app_name', 'Terraf OXP') }}" required></label>
                <label>App URL <input name="app_url" value="{{ old('app_url', url('/')) }}" required></label>
                <label>Frontend URL <input name="frontend_url" value="{{ old('frontend_url') }}"></label>
                <label>Timezone <input name="timezone" value="{{ old('timezone', 'Pacific/Auckland') }}" required></label>
                <label>Locale <input name="locale" value="{{ old('locale', 'en') }}" required></label>
            </div>
        </section>

        <section>
            <h2>Step 3 - Database Settings</h2>
            <div class="grid">
                <label>Connection
                    <select name="db_connection">
                        <option value="mysql" @selected(old('db_connection', 'mysql') === 'mysql')>mysql</option>
                        <option value="pgsql" @selected(old('db_connection') === 'pgsql')>pgsql</option>
                        <option value="sqlite" @selected(old('db_connection') === 'sqlite')>sqlite</option>
                        <option value="sqlsrv" @selected(old('db_connection') === 'sqlsrv')>sqlsrv</option>
                    </select>
                </label>
                <label>Host <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}"></label>
                <label>Port <input name="db_port" value="{{ old('db_port', '3306') }}"></label>
                <label>Database <input name="db_database" value="{{ old('db_database') }}" required></label>
                <label>Username <input name="db_username" value="{{ old('db_username') }}"></label>
                <label>Password <input type="password" name="db_password"></label>
            </div>
        </section>

        <section>
            <h2>Step 4 - Storage Settings</h2>
            <div class="grid">
                <label>Driver
                    <select name="storage_driver">
                        <option value="local" @selected(old('storage_driver', 'local') === 'local')>local</option>
                        <option value="azure" @selected(old('storage_driver') === 'azure')>azure</option>
                    </select>
                </label>
                <label>Azure account name <input name="azure_account_name" value="{{ old('azure_account_name') }}"></label>
                <label>Azure account key <input type="password" name="azure_account_key"></label>
                <label>Azure container <input name="azure_container" value="{{ old('azure_container', 'uploads') }}"></label>
                <label>Azure storage URL <input name="azure_url" value="{{ old('azure_url') }}"></label>
            </div>
        </section>

        <section>
            <h2>Step 5 - Mail Settings</h2>
            <div class="grid">
                <label>Mailer
                    <select name="mail_mailer">
                        <option value="log" @selected(old('mail_mailer', 'log') === 'log')>log</option>
                        <option value="array" @selected(old('mail_mailer') === 'array')>array</option>
                        <option value="smtp" @selected(old('mail_mailer') === 'smtp')>smtp</option>
                    </select>
                </label>
                <label>SMTP host <input name="mail_host" value="{{ old('mail_host') }}"></label>
                <label>SMTP port <input name="mail_port" value="{{ old('mail_port', '587') }}"></label>
                <label>SMTP username <input name="mail_username" value="{{ old('mail_username') }}"></label>
                <label>SMTP password <input type="password" name="mail_password"></label>
                <label>Encryption <input name="mail_encryption" value="{{ old('mail_encryption', 'tls') }}"></label>
                <label>From email <input name="mail_from_address" value="{{ old('mail_from_address', 'hello@example.com') }}" required></label>
                <label>From name <input name="mail_from_name" value="{{ old('mail_from_name', 'Terraf OXP') }}" required></label>
            </div>
        </section>

        <section>
            <h2>Step 6 - Admin Account</h2>
            <div class="grid">
                <label>Name <input name="admin_name" value="{{ old('admin_name') }}" required></label>
                <label>Email <input name="admin_email" value="{{ old('admin_email') }}" required></label>
                <label>Password <input type="password" name="admin_password" required></label>
                <label>Confirm password <input type="password" name="admin_password_confirmation" required></label>
            </div>
        </section>

        <section>
            <h2>Step 7 - Install</h2>
            <button type="submit">Install and open admin</button>
        </section>
    </form>
</main>
</body>
</html>
