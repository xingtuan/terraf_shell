# Troubleshooting

Use this checklist for installation, deployment, database, permissions, storage, store, community, i18n, HTTP errors, queue, and scheduler issues.

## Quick Checks

Logs:

```bash
tail -f B2C_backend/storage/logs/laravel.log
tail -f B2C_backend/storage/logs/queue-worker.log
sudo journalctl -u terraf-frontend -f
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

Health checks:

```bash
curl -I http://127.0.0.1:8000/up
curl -I http://127.0.0.1:3000/
curl -I http://your-domain/api/cart
```

## Installation Issues

### Existing Repo Has Local Modifications

```bash
git -C /var/www/terraf_shell status --short
```

Preserve required changes before rerunning. Only discard when safe:

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

### Composer Fails

```bash
php -v
php -m
cd B2C_backend
composer validate
composer install --no-dev --optimize-autoloader --no-interaction
```

Do not run `composer update` on production.

### Node / pnpm Issues

```bash
node -v
pnpm -v
cd B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
```

If build-time API access fails, check `NEXT_SERVER_API_BASE_URL`.

### MySQL Connection Fails

```bash
sudo systemctl status mysql
mysql -u oxp_user -p oxp_local
cd B2C_backend
php artisan migrate:status
```

Confirm `.env` database values match the actual database.

### Migration Fails

Check the full error and `laravel.log`. Common causes are wrong branch, partial previous migration, wrong database, or insufficient MySQL permissions.

```bash
cd B2C_backend
php artisan migrate --force
```

### Seeder Concerns

Automated deployment defaults to `RUN_SEED=1`. Use this for normal production updates:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

## Deployment Issues

### Nginx Does Not Work

```bash
sudo nginx -t
ls -l /etc/nginx/sites-enabled/
sudo systemctl status nginx
sudo tail -f /var/log/nginx/error.log
```

The automated script should enable `front` and `laravel`.

### PHP-FPM Is Down

```bash
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

### Frontend Service Is Down

```bash
sudo systemctl status terraf-frontend
sudo journalctl -u terraf-frontend -f
cd B2C_frontend
pnpm build
```

Expected `.env.local` values:

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_SITE_URL=http://your-domain-or-ip
```

### Frontend Build Fails

Check Node.js 20, pnpm, i18n keys, build-time API address, and TypeScript errors.

```bash
cd B2C_frontend
node scripts/check-i18n-keys.mjs
node scripts/i18n-diff.mjs
pnpm build
```

## Permissions

Laravel must write to:

```text
B2C_backend/storage
B2C_backend/bootstrap/cache
```

Repair:

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo chmod -R ug+rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -R -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -dR -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
```

## Storage

Local:

```bash
cd B2C_backend
php artisan storage:link
ls -l public/storage
```

Azure:

- Run Storage Settings connection test.
- Run upload test.
- Check container, key, public URL, and SAS TTL.

Switching drivers does not migrate historical files.

## Store

Add-to-cart or checkout failures usually involve product status, variant status, stock, inventory policy, Guest Checkout feature flag, shipping options, Tax Settings, or Laravel logs.

For GST and shipping setting changes:

```bash
cd B2C_backend
php artisan optimize:clear
```

## Admin

If admin saves fail, check permissions, validation errors, storage permissions, `.env`, runtime settings, and `laravel.log`.

If settings do not apply, clear cache:

```bash
cd B2C_backend
php artisan optimize:clear
```

## I18N

Frontend:

```bash
cd B2C_frontend
node scripts/check-i18n-keys.mjs
node scripts/i18n-diff.mjs
```

Backend:

```bash
cd B2C_backend
php artisan admin:check-translations
```

## HTTP Errors

### 502

Check upstream services:

```bash
sudo systemctl status terraf-frontend
curl -I http://127.0.0.1:3000/
curl -I http://127.0.0.1:8000/up
sudo systemctl status php8.3-fpm
```

### 404

Check Nginx `server_name`, frontend routes, API routes, and route cache.

```bash
cd B2C_backend
php artisan route:list | grep cart
php artisan route:clear
php artisan route:cache
```

### 500

Check:

- `APP_KEY`
- database connection
- permissions
- storage driver
- runtime secrets
- `B2C_backend/storage/logs/laravel.log`

## Queue And Scheduler

Queue:

```bash
sudo supervisorctl status terraf-queue:*
sudo supervisorctl restart terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

Scheduler:

```bash
cat /etc/cron.d/terraf-scheduler
sudo systemctl status cron
cd B2C_backend
php artisan schedule:run
```

Cache:

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
```
