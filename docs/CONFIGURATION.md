# Configuration

Configuration comes from two places:

- `.env`: bootstrap, infrastructure, and sensitive secrets.
- Admin System Settings: runtime business settings stored in the database and cached by `SettingsService`.

The database must be available before runtime settings can override application behavior.

## Precedence

1. Laravel boots from `.env`.
2. After the database is available, `RuntimeSettingsServiceProvider` loads `app_settings`.
3. Application, storage, mail, store, shipping, GST, NZ Post, community, and feature flags can be overridden by admin settings.
4. Settings are cached; clear cache when changes do not appear immediately.

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
```

## App

```dotenv
APP_NAME=OXP
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://api.example.com
FRONTEND_URL=https://example.com
```

Notes:

- Keep `APP_KEY` stable after production launch.
- Use `APP_DEBUG=false` in production.
- `APP_URL` affects backend links and storage URLs.
- `FRONTEND_URL` affects CORS, emails, and frontend redirects.

Admin Application Settings controls brand name, logo, default locale, timezone, contact email, support email, and frontend URL for business display.

## Database

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oxp_local
DB_USERNAME=oxp_user
DB_PASSWORD=change-me
```

Database settings must be changed in `.env`. After changing them:

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
sudo supervisorctl restart terraf-queue:*
```

## Cache, Queue, Session

Default deployment:

```dotenv
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Database drivers are appropriate for single-server delivery. Redis can be configured through standard Laravel settings if needed. Restart queue workers after changing queue configuration.

```bash
sudo supervisorctl restart terraf-queue:*
```

## Mail

The default automated deployment uses:

```dotenv
MAIL_MAILER=log
```

Production should configure SMTP from admin Email Settings or `.env`.

```bash
cd B2C_backend
php artisan email:center:preview order.created --locale=en
php artisan email:center:test admin@example.com
```

## Storage

Local defaults:

```dotenv
STORAGE_DISK=public
FILESYSTEM_DISK=public
MEDIA_DRIVER=public
COMMUNITY_UPLOAD_DISK=public
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public
```

Azure:

```dotenv
AZURE_STORAGE_NAME=
AZURE_STORAGE_KEY=
AZURE_STORAGE_CONTAINER=
AZURE_STORAGE_URL=
```

The admin Storage Settings page can switch local / Azure drivers and run connection and upload tests. See [STORAGE.md](STORAGE.md).

## Frontend

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
API_PROXY_TARGET=http://127.0.0.1:8000
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_BRAND_CONTACT_EMAIL=
NEXT_PUBLIC_SITE_URL=https://example.com
```

- `NEXT_PUBLIC_API_BASE_URL=/api` sends browser requests through the proxy.
- `API_PROXY_TARGET` is used by Next.js rewrites.
- `NEXT_SERVER_API_BASE_URL` is used by server-side rendering and production builds.
- `NEXT_PUBLIC_MEDIA_BASE_URL` is optional; normally the backend returns media URLs.
- Rebuild the frontend after changing frontend environment variables.

## Admin Users

`auto_deploy.sh` does not create administrators from `ADMIN_*` variables. Admin users are created by:

- `RUN_SEED=1` and `UserSeeder`.
- Web Installer.
- Existing admin users in the admin panel.

Change the seeded admin password before handover.

## Shop, Tax, Shipping

Runtime settings include:

- Tax Settings: GST enabled, rate, tax-inclusive pricing, tax label.
- Shipping Settings: NZ-only, origin, free-shipping threshold, standard / express / rural rates, quote source.
- NZ Post Settings: customer number, API key, API secret, and service codes.

Fallback `.env` values include:

```dotenv
STORE_GST_RATE=0.15
STORE_PRICES_INCLUDE_GST=false
STORE_TAX_LABEL=GST
NZPOST_ENABLED=false
```

Admin settings take precedence after they are saved.

## Community

Community settings cover pagination, uploads, attachment rules, funding links, moderation policies, sensitive words, guest uploads, and notification recipients.

After upload-related changes, test post creation, cover images, and attachments.

## Security

Production checklist:

- `APP_DEBUG=false`
- `.env` is not publicly accessible.
- Default admin password changed.
- Database user has only application database permissions.
- SMTP, Azure, database, and admin credentials are rotated when needed.
- HTTPS, CORS, Sanctum, and secure-cookie settings match the public domain.

## Cache Refresh

After `.env` changes:

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
sudo supervisorctl restart terraf-queue:*
sudo systemctl restart terraf-frontend
```

After runtime setting changes:

```bash
cd B2C_backend
php artisan optimize:clear
```

After frontend environment changes:

```bash
cd B2C_frontend
pnpm build
sudo systemctl restart terraf-frontend
```
