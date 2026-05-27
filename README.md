# OXP / Terraf Shell

OXP / Terraf Shell is a full-stack platform for material storytelling, content operations, B2C commerce, B2B inquiries, and community interaction.

Repository layout:

- `B2C_backend/`: Laravel API, Filament admin panel, migrations, seeders, queue jobs, and scheduler tasks.
- `B2C_frontend/`: Next.js user-facing site for homepage, material pages, store, cart, checkout, community, account pages, and multilingual UI.
- `docs/`: installation, deployment, admin, user, configuration, storage, shop, community, i18n, troubleshooting, and maintenance documentation.
- `auto_deploy.sh`: Ubuntu / Debian single-server automated installation and deployment script.
- `tests/`: Playwright end-to-end tests.

## Tech Stack

- Backend: PHP 8.3, Laravel 13, Laravel Sanctum, Filament 5.
- Frontend: Next.js 16, React 19, TypeScript, Tailwind CSS 4, Radix UI, Tiptap.
- Database: MySQL.
- Cache / queue / session: Laravel database drivers by default; Redis is optional.
- Storage: local `public` disk and Azure Blob Storage, switchable from the admin panel.
- Build tools: Composer, Vite, pnpm.
- Languages: English, Chinese, and Korean for frontend messages, backend API text, validation messages, seed content, and admin translations.
- Mail: Laravel Mail with admin-configurable SMTP, email templates, and email logs.

## Feature Overview

- Homepage and page sections: admin-managed homepage content, page sections, logo, contact details, and legal pages.
- Material and CMS: material pages, specifications, applications, story sections, articles, and CMS content.
- Store: categories, products, images, dynamic attributes, SKU / variants, stock, inventory logs, and publishing status.
- Cart and checkout: guest cart, authenticated cart, cart merge, Guest Checkout, and registered-user checkout.
- Orders: order creation, guest order lookup, account order history, order status, payment status, shipment fields, stock deduction, and pending-order stock restoration.
- GST and shipping: GST settings, tax-inclusive / tax-exclusive pricing, manual shipping rates, free-shipping threshold, rural surcharge, and NZ Post configuration.
- Account: registration, login, email verification, profile, password, addresses, orders, favorites, and community activity.
- Community: posts, rich text, cover images, attachments, external 3D links, funding links, comments, replies, likes, favorites, follows, reports, notifications, and user restrictions.
- B2B / contact: business inquiries, contact forms, sample requests, and material review requests.
- Admin: Filament `/admin` covering store, orders, community, reports, user governance, content, email center, system settings, storage settings, and handover readiness.
- Storage switching: local public storage and Azure Blob Storage with connection tests, upload tests, storage link checks, and media scan export.
- Initial content: seeders provide formal starter content and sample operational records; the product catalog seed is marked as official starter catalog data.
- Automated installation: `auto_deploy.sh` installs system dependencies and configures the full stack on Ubuntu / Debian.

## Quick Start

### Automated Server Installation

Use this on a fresh or controlled Ubuntu / Debian single-server host:

```bash
sudo bash auto_deploy.sh example.com
```

Common parameterized example:

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

After installation:

- Frontend: `http://example.com/`
- API proxy: `http://example.com/api/`
- Backend health check: `http://example.com:8000/up`
- Admin panel: `http://example.com:8000/admin`
- Deployment credentials and useful commands: `/root/terraf-install/credentials.txt`

When `RUN_SEED=1`, the initial administrator is created by `B2C_backend/database/seeders/UserSeeder.php`:

- Email: `admin@example.com`
- Password: `password`

Change this password before handover or production use.

See [docs/INSTALLATION.md](docs/INSTALLATION.md) and [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for full details.

### Local Development

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

The local frontend runs at `http://localhost:3000`. With `NEXT_PUBLIC_API_BASE_URL=/api`, Next.js proxies API requests through `API_PROXY_TARGET=http://127.0.0.1:8000`.

### Manual Production Deployment

Manual deployment must configure PHP-FPM, Nginx, MySQL, Composer, Node.js, pnpm, Supervisor, Cron, Laravel `.env`, migrations, queue workers, scheduler, frontend build, and a systemd frontend service. Use [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) unless you have explicit infrastructure requirements.

## Automated Installation

`auto_deploy.sh` is the main delivery script. It requires root / sudo access and targets apt-based Ubuntu / Debian servers.

First install:

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

Repeat install / update:

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

If the existing repository has local changes, the script stops with:

```text
Existing repo has local modifications. Commit/stash them, or rerun with RESET_WORKTREE=1.
```

Only use the reset mode after confirming server-local changes can be discarded:

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

`RESET_WORKTREE=1` runs `git reset --hard origin/$BRANCH` and `git clean -fd`. It removes uncommitted and untracked files inside the deployment worktree.

By default, `RUN_SEED=1` runs `php artisan db:seed --force`. For normal production updates:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

The script does not use `ADMIN_*`, `APP_ENV`, `APP_URL`, `STORAGE_*`, or `AZURE_*` as deployment overrides. It writes a production `.env` with local public storage, database cache, database queue, and log mailer defaults. Configure brand, mail, store, shipping, tax, community, and Azure Storage after deployment from the admin panel or `.env`.

## Environment Variables

Do not commit real secrets. `.env.example` and `.env.local` must contain placeholders only.

- App: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `FRONTEND_URL`.
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Cache / queue / session: `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`.
- Mail: `MAIL_MAILER`, SMTP host, port, username, password, sender name, sender address.
- Storage: `STORAGE_DISK`, `FILESYSTEM_DISK`, `MEDIA_DRIVER`, `COMMUNITY_UPLOAD_DISK`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`.
- Azure: `AZURE_STORAGE_NAME`, `AZURE_STORAGE_KEY`, `AZURE_STORAGE_CONTAINER`, `AZURE_STORAGE_URL`, SAS and temporary URL settings.
- Frontend: `NEXT_PUBLIC_API_BASE_URL`, `API_PROXY_TARGET`, `NEXT_SERVER_API_BASE_URL`, `NEXT_PUBLIC_MEDIA_BASE_URL`, `NEXT_PUBLIC_SITE_URL`, `NEXT_PUBLIC_BRAND_CONTACT_EMAIL`.
- Shop / tax / shipping: `STORE_GST_RATE`, `STORE_PRICES_INCLUDE_GST`, `STORE_TAX_LABEL`, `SHIPPING_*`, `NZPOST_*`.
- Community: pagination, upload limits, attachment rules, funding link settings, and moderation settings.
- Security: disable debug mode in production, restrict `.env` permissions, rotate admin, database, SMTP, and Azure credentials.

See [docs/CONFIGURATION.md](docs/CONFIGURATION.md) for precedence rules.

## Common Commands

Backend:

```bash
cd B2C_backend
composer install
php artisan migrate
php artisan db:seed
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Queue and scheduler:

```bash
cd B2C_backend
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90
php artisan schedule:run
```

Frontend:

```bash
cd B2C_frontend
pnpm install
pnpm build
pnpm dev
pnpm lint
pnpm test
```

Tests:

```bash
cd B2C_backend
php artisan test

cd ..
pnpm test
```

Production logs:

```bash
journalctl -u terraf-frontend -f
tail -f /var/log/nginx/error.log
tail -f B2C_backend/storage/logs/laravel.log
tail -f B2C_backend/storage/logs/queue-worker.log
```

## Documentation Index

- [Installation](docs/INSTALLATION.md): automated script, manual setup, local development, and installation errors.
- [Deployment](docs/DEPLOYMENT.md): production deployment, Nginx, PHP-FPM, queue, scheduler, SSL, updates, and rollback notes.
- [Admin Guide](docs/ADMIN_GUIDE.md): Filament admin modules and operational workflows.
- [User Guide](docs/USER_GUIDE.md): user-facing browsing, shopping, order, account, and community flows.
- [Configuration](docs/CONFIGURATION.md): `.env`, admin settings, precedence, and cache refresh.
- [Storage](docs/STORAGE.md): local storage, Azure Storage, URLs, uploads, and switching.
- [Shop](docs/SHOP.md): products, SKU, stock, cart, checkout, GST, shipping, and order states.
- [Community](docs/COMMUNITY.md): posts, attachments, funding links, comments, favorites, reports, moderation, and notifications.
- [I18N](docs/I18N.md): English, Chinese, and Korean file structure and update rules.
- [Troubleshooting](docs/TROUBLESHOOTING.md): install, deployment, database, permissions, storage, HTTP errors, queue, and scheduler.
- [Maintenance](docs/MAINTENANCE.md): updates, backups, logs, cache clearing, rebuilds, and safe reruns.

## Delivery and Maintenance

Use `auto_deploy.sh` for routine updates. It protects local repository changes by default and stops when it finds uncommitted work. Before rerunning it in production, back up the database, uploaded files, and environment files, and confirm whether `RUN_SEED` should be enabled.

Before switching from local storage to Azure, run the admin Storage Settings connection and upload tests. The admin media scan can export checklists; it does not perform bulk file migration.

Before production handover, configure HTTPS, restrict unnecessary ports, change seeded admin passwords, verify email delivery, verify queue and scheduler services, and include database and uploaded-file backups in regular operations.
