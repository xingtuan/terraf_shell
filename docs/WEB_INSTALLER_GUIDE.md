# Web Installer Guide

Route: `/install`

The installer is available only before installation. It is disabled when any installed marker is present:

- `storage/app/installed.lock`
- `app_settings.system.installed_at`
- an admin user plus installation flag

Steps shown by the installer:

1. Requirements check: PHP version, PDO, writable storage, writable `bootstrap/cache`, `.env` writable or creatable, public storage link.
2. Application settings: app name, app URL, frontend URL, timezone, locale.
3. Database settings: connection, host, port, database, username, password.
4. Storage settings: local or Azure, with Azure account/container/URL fields.
5. Mail settings: log, array, or SMTP, sender identity.
6. Admin account.
7. Install.

During install, the app writes or updates only minimal bootstrap `.env` keys:

- `APP_KEY`
- `APP_ENV`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_STORE`
- `SESSION_DRIVER`

After migrations run, operational settings are saved into `app_settings`. The installer creates the admin user, creates a storage link for local storage, clears caches, creates `installed.lock`, and redirects to `/admin`.

Recovery:

- Remove `storage/app/installed.lock` only if this is truly a failed first install and no real production data exists.
- Fix bootstrap DB settings in `.env` if the app cannot reach the database.
- Use `php artisan optimize:clear` after manual recovery changes.

