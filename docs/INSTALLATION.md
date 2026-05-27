# Installation

This document describes the current installation paths for the project. It is based on the actual repository, especially `auto_deploy.sh`.

## Scope

`auto_deploy.sh` is intended for Ubuntu / Debian test or production servers using an apt-based single-server deployment:

- Nginx listens on `80` and proxies `/` to Next.js.
- Nginx proxies `/api/` and `/storage/` to Laravel.
- Laravel / Filament is available at `http://SERVER_NAME:8000/admin`.
- Next.js runs on `127.0.0.1:3000`.
- The Laravel Nginx site listens on `127.0.0.1:8000` / `SERVER_NAME:8000`, with PHP requests handled by PHP-FPM.
- MySQL, PHP-FPM, Supervisor, and Cron run on the same server.

The script is not a Windows local development installer and is not a container or Kubernetes deployment.

## Prerequisites

- OS: Ubuntu or Debian with apt available.
- Permission: root or `sudo`.
- Network: access to apt repositories, Git, Composer, NodeSource, npm, and pnpm registries.
- Domain / IP: the first script argument is used as Nginx `server_name`, Laravel `APP_URL`, and frontend `NEXT_PUBLIC_SITE_URL`.
- Git access: the default repository is `https://github.com/xingtuan/terraf_shell.git`; private repositories require credentials.
- Database: local MySQL root access through the server account.
- Ports: open `80`; the script also opens `8000` for the admin panel when UFW is available.
- Storage: the script defaults to local public storage and creates `public/storage`.
- SSL: not installed by the script. Configure HTTPS separately.

## Automated Installation

Minimal command:

```bash
sudo bash auto_deploy.sh example.com
```

Common parameterized command:

```bash
sudo env \
  APP_DIR=/var/www/terraf_shell \
  REPO_URL=https://github.com/xingtuan/terraf_shell.git \
  BRANCH=main \
  DB_NAME=oxp_local \
  DB_USER=oxp_user \
  DB_PASS='change-me-long-password' \
  RUN_SEED=1 \
  bash auto_deploy.sh example.com
```

For IP-only servers:

```bash
sudo bash auto_deploy.sh 203.0.113.10
```

The script writes credentials and useful commands to:

```text
/root/terraf-install/credentials.txt
```

## What The Script Does

`auto_deploy.sh` performs these operations:

1. Checks root permission and sets non-interactive apt mode.
2. Installs system packages: `git`, `curl`, `unzip`, `zip`, `ca-certificates`, `gnupg`, `lsb-release`, `software-properties-common`, `nginx`, `mysql-server`, `supervisor`, `openssl`, `cron`, `python3`, `acl`.
3. Installs PHP 8.3 and extensions: FPM, CLI, MySQL, mbstring, xml, curl, zip, bcmath, intl, gd.
4. Installs Composer.
5. Installs Node.js 20 through NodeSource when needed.
6. Installs pnpm.
7. Starts MySQL and creates the configured database and user.
8. Clones or updates the Git repository.
9. Runs preflight checks for `composer.lock`, frontend server API support, legal page build safety, and cart session length.
10. Creates or updates backend `.env`.
11. Sets permissions and ACLs for `.env`, `storage/`, and `bootstrap/cache/`.
12. Runs `composer install --no-dev --optimize-autoloader --no-interaction`.
13. Creates the Laravel `public/storage` symlink.
14. Runs `php artisan migrate --force`.
15. Runs `php artisan db:seed --force` when `RUN_SEED=1`.
16. Clears and rebuilds Laravel caches.
17. Writes Nginx sites for frontend/API and Laravel admin.
18. Restarts Nginx and PHP-FPM.
19. Writes Supervisor queue worker config.
20. Writes Cron scheduler config.
21. Creates or updates frontend `.env.local`.
22. Runs `pnpm install --frozen-lockfile=false` and `pnpm build`.
23. Writes and restarts `terraf-frontend.service`.
24. Checks frontend, `/api/cart`, and backend `/up`.

## Script Parameters

| Name | Default | Purpose |
| --- | --- | --- |
| `SERVER_NAME` | first argument, or first `hostname -I` address | Nginx `server_name`, backend URL, frontend URL |
| `APP_DIR` | `/var/www/terraf_shell` | Deployment directory |
| `REPO_URL` | `https://github.com/xingtuan/terraf_shell.git` | Git repository |
| `BRANCH` | `main` | Branch to deploy |
| `PHP_VERSION` | `8.3` | PHP version |
| `NODE_MAJOR` | `20` | Node.js major version |
| `DB_NAME` | `oxp_local` | MySQL database |
| `DB_USER` | `oxp_user` | MySQL application user |
| `DB_PASS` | random 24-character value | MySQL application password |
| `RUN_SEED` | `1` | Run `php artisan db:seed --force` |
| `RESET_WORKTREE` | `0` | Force-reset an existing Git worktree |
| `STRICT_PREFLIGHT` | `1` | Stop on preflight failures |

Internal values:

- `BACKEND_DIR=$APP_DIR/B2C_backend`
- `FRONTEND_DIR=$APP_DIR/B2C_frontend`
- `DEPLOY_USER=${SUDO_USER:-victor}` for storage/cache ACLs
- `FRONTEND_URL=http://SERVER_NAME`
- `BACKEND_URL=http://SERVER_NAME:8000`
- `BACKEND_LOCAL_URL=http://127.0.0.1:8000`
- `COMPOSER_ALLOW_SUPERUSER=1`
- `DEBIAN_FRONTEND=noninteractive`

The script does not use these as deployment overrides: `APP_ENV`, `APP_URL`, `ADMIN_*`, `STORAGE_*`, `AZURE_*`, `MAIL_*`, `STORE_*`, `SHIPPING_*`. Configure those after installation through `.env` or the admin panel.

## Re-running The Script

Normal rerun:

```bash
sudo bash auto_deploy.sh example.com
```

If the repository contains local changes, the script stops with:

```text
Existing repo has local modifications. Commit/stash them, or rerun with RESET_WORKTREE=1.
```

Use the reset mode only when local server changes may be discarded:

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh example.com
```

Risks:

- Runs `git reset --hard origin/$BRANCH`.
- Runs `git clean -fd`.
- Does not delete the database.
- Still runs seeders if `RUN_SEED=1`.
- Must not be used to discard customer uploads or operational edits.

For normal production updates:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh example.com
```

## Initial Data And Admin User

When `RUN_SEED=1`, `DatabaseSeeder` creates starter users, product catalog, material content, page sections, email center data, and default settings.

Default seeded admin:

- Email: `admin@example.com`
- Password: `password`

Change the password before handover or production use.

## Local Development

Backend:

```bash
cd B2C_backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend:

```bash
cd B2C_frontend
pnpm install
cp .env.example .env.local
pnpm dev
```

Recommended local frontend environment:

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
API_PROXY_TARGET=http://127.0.0.1:8000
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

## Manual Server Installation

Manual deployment must install and configure PHP 8.3, Composer, Node.js 20, pnpm, MySQL, Nginx, PHP-FPM, Supervisor, Cron, backend `.env`, migrations, queue workers, scheduler, and frontend systemd service.

Basic backend commands:

```bash
git clone --branch main https://github.com/xingtuan/terraf_shell.git /var/www/terraf_shell
cd /var/www/terraf_shell/B2C_backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Frontend:

```bash
cd /var/www/terraf_shell/B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
pnpm start --hostname 127.0.0.1 --port 3000
```

Use `auto_deploy.sh` as the canonical service configuration reference.

## Web Installer

The backend provides `/install` for manual setup environments. It can create a minimal `.env`, run migrations, initialize default settings, create an admin user, and check storage links before the install lock exists.

For standard server delivery, prefer `auto_deploy.sh`.

## Common Installation Errors

### Existing Repo Has Local Modifications

Check changes:

```bash
git -C /var/www/terraf_shell status --short
```

Commit, back up, or stash anything that must be preserved. Use `RESET_WORKTREE=1` only when changes can be discarded.

### Composer Install Fails

Confirm PHP 8.3, required extensions, and a valid `composer.lock`. Do not run `composer update` on production servers.

### Node / pnpm Version Issues

The script expects Node.js 20. Check:

```bash
node -v
pnpm -v
```

### MySQL Connection Fails

```bash
systemctl status mysql
cd B2C_backend
php artisan migrate:status
```

### Migration Fails

Check `B2C_backend/storage/logs/laravel.log` and the terminal error. Do not manually alter tables to bypass migrations.

### Storage Permission Fails

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo -u www-data php B2C_backend/artisan storage:link
```

### Nginx Site Does Not Work

```bash
sudo nginx -t
sudo systemctl status nginx
ls -l /etc/nginx/sites-enabled/
```

### PHP-FPM Is Not Running

```bash
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

### Frontend Build Fails

Check `B2C_frontend/.env.local`, especially `NEXT_PUBLIC_API_BASE_URL`, `NEXT_SERVER_API_BASE_URL`, and `API_PROXY_TARGET`.

### Uploads Do Not Display

For local storage, check `php artisan storage:link` and the `/storage/` route. For Azure, run the admin Storage Settings connection and upload tests.

### Queue Or Scheduler Does Not Run

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
cat /etc/cron.d/terraf-scheduler
```
