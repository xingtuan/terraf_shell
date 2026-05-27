# Deployment

This document describes production deployment for the current codebase. The recommended path is the root `auto_deploy.sh` script.

## Deployment Model

| Entry | Target |
| --- | --- |
| `http://SERVER/` | Nginx to Next.js `127.0.0.1:3000` |
| `http://SERVER/api/` | Nginx to Laravel `127.0.0.1:8000/api/` |
| `http://SERVER/storage/` | Nginx to Laravel storage routes |
| `http://SERVER:8000/admin` | Laravel / Filament admin |
| `http://SERVER:8000/up` | Laravel health check |

The automated script installs and configures Nginx, PHP 8.3 FPM, MySQL, Composer, Node.js 20, pnpm, Supervisor, Cron, and `terraf-frontend.service`.

## Automated Deployment

```bash
sudo bash auto_deploy.sh example.com
```

Production update example:

```bash
sudo env \
  APP_DIR=/var/www/terraf_shell \
  REPO_URL=https://github.com/xingtuan/terraf_shell.git \
  BRANCH=main \
  DB_NAME=oxp_local \
  DB_USER=oxp_user \
  DB_PASS='change-me-long-password' \
  RUN_SEED=0 \
  bash auto_deploy.sh example.com
```

Use `RUN_SEED=1` for first delivery when starter data is required. Use `RUN_SEED=0` for routine production updates.

## Nginx

The script writes:

- `/etc/nginx/sites-available/front`: port 80, frontend plus `/api/` and `/storage/`.
- `/etc/nginx/sites-available/laravel`: port 8000, Laravel public root and admin panel.

It disables historical/default sites named `default`, `terraf`, `terraf-backend-8000`, and `terraf-frontend-80`.

Checks:

```bash
sudo nginx -t
sudo systemctl status nginx
curl -I http://example.com/
curl -I http://example.com/api/cart
curl -I http://example.com:8000/up
```

## PHP-FPM

The deployment uses `php8.3-fpm`.

```bash
php -v
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

## Frontend Service

The script writes `/etc/systemd/system/terraf-frontend.service`.

- Working directory: `$APP_DIR/B2C_frontend`
- User: `www-data`
- Command: `pnpm start --hostname 127.0.0.1 --port 3000`

```bash
sudo systemctl status terraf-frontend
sudo journalctl -u terraf-frontend -f
sudo systemctl restart terraf-frontend
```

Manual rebuild:

```bash
cd /var/www/terraf_shell/B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
sudo systemctl restart terraf-frontend
```

## Laravel Backend

```bash
cd /var/www/terraf_shell/B2C_backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Starter data:

```bash
php artisan db:seed --force
```

Do not run `composer update` on production servers.

## Queue

Supervisor config:

- File: `/etc/supervisor/conf.d/terraf-queue.conf`
- Program: `terraf-queue`
- Command: `php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90`
- User: `www-data`
- Log: `B2C_backend/storage/logs/queue-worker.log`

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status terraf-queue:*
tail -f /var/www/terraf_shell/B2C_backend/storage/logs/queue-worker.log
```

## Scheduler

The script writes `/etc/cron.d/terraf-scheduler` to run every minute:

```bash
cd /var/www/terraf_shell/B2C_backend && php artisan schedule:run
```

Checks:

```bash
cat /etc/cron.d/terraf-scheduler
sudo systemctl status cron
```

## Environment Defaults

Backend defaults written by the script:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://SERVER:8000
FRONTEND_URL=http://SERVER
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
STORAGE_DISK=public
FILESYSTEM_DISK=public
MEDIA_DRIVER=public
COMMUNITY_UPLOAD_DISK=public
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public
MAIL_MAILER=log
NZPOST_ENABLED=false
```

Frontend defaults:

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_BRAND_CONTACT_EMAIL=
NEXT_PUBLIC_SITE_URL=http://SERVER
```

See [CONFIGURATION.md](CONFIGURATION.md) for configuration precedence.

## SSL And Domains

`auto_deploy.sh` does not install SSL certificates. After configuring HTTPS, update:

- `APP_URL`
- `FRONTEND_URL`
- `NEXT_PUBLIC_SITE_URL`
- CORS allowed origins
- Sanctum stateful domains
- secure cookie settings when applicable

Then clear backend cache and rebuild frontend:

```bash
cd /var/www/terraf_shell/B2C_backend
php artisan optimize:clear
php artisan config:cache

cd ../B2C_frontend
pnpm build
sudo systemctl restart terraf-frontend
```

## Update Flow

Normal update:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh example.com
```

If local Git changes exist:

```bash
git -C /var/www/terraf_shell status --short
```

Only discard them when safe:

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh example.com
```

## Rollback Notes

There is no automatic rollback script. Before each production deployment record:

- Git commit
- database backup
- `.env` backup
- uploaded-file or Azure container state
- frontend build timestamp

Code rollback example:

```bash
cd /var/www/terraf_shell
git fetch origin
git checkout <known-good-commit>
cd B2C_backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
cd ../B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
sudo systemctl restart terraf-frontend
sudo supervisorctl restart terraf-queue:*
```

Do not blindly run database rollback commands in production.
