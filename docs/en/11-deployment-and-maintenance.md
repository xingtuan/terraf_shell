# 11 — Deployment and Maintenance

## Overview

This guide covers local development setup, production deployment, and ongoing maintenance procedures for the OXP platform.

---

## 1. System Requirements

### 1.1 Backend Requirements

| Component | Minimum Version | Recommended |
|---|---|---|
| PHP | 8.4 | 8.4+ |
| MySQL | 8.0 | 8.0+ |
| Composer | 2.x | Latest 2.x |
| Node.js | 18.x | 20 LTS |
| npm | 9.x | Latest |

PHP extensions required:
- `pdo_mysql`
- `mbstring`
- `openssl`
- `json`
- `fileinfo`
- `gd` or `imagick` (for image processing)
- `bcmath`
- `ctype`
- `tokenizer`
- `xml`
- `intl` (required for locale-aware formatting and production readiness checks)

### 1.2 Frontend Requirements

| Component | Version |
|---|---|
| Node.js | 18.x or 20 LTS |
| npm | 9.x or 10.x |

---

## 2. Local Development Setup

### 2.1 Backend Setup

```bash
# 1. Clone the repository
git clone {repository-url}
cd terraf/B2C_backend

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure .env
# Edit .env with your local database credentials and settings

# 6. Run database migrations
php artisan migrate

# 7. Seed demo data (optional)
php artisan db:seed

# 8. Create storage symlink (for local disk storage)
php artisan storage:link

# 9. Start the development server
php artisan serve
```

The backend will be available at `http://127.0.0.1:8000`.

### 2.2 Frontend Setup

```bash
# Navigate to frontend directory
cd terraf/B2C_frontend

# Install dependencies
npm install

# Configure environment
# Create .env.local with:
# NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api

# Start development server
npm run dev
```

The frontend will be available at `http://localhost:3000`.

### 2.3 Development Environment Notes

- Set `MAIL_MAILER=log` in development — emails are written to `storage/logs/laravel.log`
- Set `FILESYSTEM_DISK=public` to use local storage instead of Azure
- Set `QUEUE_CONNECTION=sync` to run jobs synchronously (no worker needed)
- Default admin credentials are set by `UserSeeder` — check the seeder for values

---

## 3. Production Deployment

### 3.0 Production Readiness Checklist

Before handing over or exposing the site publicly, confirm each item below in the production environment:

- PHP 8.4+ runtime installed for the current locked dependency set.
- PHP extensions installed: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` or `imagick`, `bcmath`, `xml`, and `intl`.
- Backend dependencies installed with `composer install --no-dev --optimize-autoloader`.
- Frontend dependencies installed with `npm install`, followed by `npm run build`.
- Database migrations applied with `php artisan migrate --force`.
- Public local storage linked with `php artisan storage:link` when `STORAGE_DISK=public` or local media storage is selected.
- Laravel caches built with `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
- Queue worker configured under Supervisor, systemd, or the hosting provider's worker manager.
- Scheduler configured to run `php artisan schedule:run` every minute.
- SMTP configured and verified by sending a test email from Email Settings.
- Admin media upload tested for a real image and a rejected unsafe file.
- `php artisan deploy:verify` returns no Errors.

### 3.1 Backend Production Deployment

```bash
# 1. Set environment
APP_ENV=production
APP_DEBUG=false

# 2. Install dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# 3. Set application key (if new environment)
php artisan key:generate

# 4. Configure .env for production
# - Set DB credentials
# - Set APP_URL to production URL
# - Set FRONTEND_URL to production frontend URL
# - Set CORS_ALLOWED_ORIGINS to production frontend URL
# - Set AZURE storage credentials
# - Set MAIL settings
# - Set QUEUE_CONNECTION=database (for async jobs)

# 5. Run migrations
php artisan migrate --force

# 6. Link public storage when local public storage is used
php artisan storage:link

# 7. Optimize Laravel caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Run deployment verification
php artisan deploy:verify

# 9. Set file permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3.2 Frontend Production Build

```bash
cd B2C_frontend

# Install dependencies
npm install

# Set production environment
# Create .env.production with:
# NEXT_PUBLIC_API_URL=https://api.yourproductiondomain.com

# Build
npm run build

# Start production server
npm run start
# Or deploy the .next/ output to your hosting provider
```

### 3.3 Required Background Services

Queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Scheduler cron:

```cron
* * * * * cd /var/www/B2C_backend && php artisan schedule:run >> /dev/null 2>&1
```

The queue worker handles notifications and asynchronous jobs. The scheduler is required for scheduled Laravel tasks. Both should be managed by Supervisor, systemd, cron, or the hosting platform, and both should be included in deployment monitoring.

### 3.4 Media Storage Verification

Azure storage remains the recommended production disk when configured. Set `STORAGE_DISK=azure`, `MEDIA_DRIVER=azure`, and the `AZURE_*` credentials in `.env`.

For local public storage, set `STORAGE_DISK=public` and `MEDIA_DRIVER=public`, then run:

```bash
php artisan storage:link
php artisan deploy:verify
```

The admin Storage Settings page and System / Handover Readiness page show warnings when local storage is selected but `public/storage` is missing or `storage/app/public` is not writable. Test an image upload from the admin panel before handover.

### 3.5 Web Server Configuration

**Nginx example for Laravel backend:**

```nginx
server {
    listen 80;
    server_name api.yoursite.com;
    root /var/www/B2C_backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 4. Queue Worker Setup

The platform uses Laravel's queue system for background jobs (primarily notifications). In production, a queue worker must be running continuously.

### 4.1 Running the Queue Worker

```bash
php artisan queue:work --sleep=3 --tries=3
```

### 4.2 Using Supervisor (Recommended for Production)

Create a Supervisor configuration file:

```ini
[program:oxp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/B2C_backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/B2C_backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start oxp-worker:*
```

---

## 5. Storage Configuration

### 5.1 Azure Blob Storage (Production)

1. Create an Azure Storage account and container.
2. Set the required environment variables:
   - `AZURE_STORAGE_NAME`
   - `AZURE_STORAGE_KEY`
   - `AZURE_STORAGE_CONTAINER`
   - `AZURE_STORAGE_URL`
3. Set `FILESYSTEM_DISK=azure` and `COMMUNITY_UPLOAD_DISK=azure`.

### 5.2 Local Storage (Development / Simple Production)

```bash
# Create symbolic link
php artisan storage:link

# Verify the link was created
ls -la public/storage
# Should show: storage -> /var/www/storage/app/public
```

Set `FILESYSTEM_DISK=public` in `.env`.

---

## 6. Maintenance Commands

### 6.1 Clearing Caches

```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

> **Note**: Run `optimize:clear` after any `.env` changes.

### 6.2 Running Migrations

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback
```

### 6.3 Queue Management

```bash
# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {job-id}

# Clear failed jobs
php artisan queue:flush
```

### 6.4 Log Management

Logs are stored in `storage/logs/laravel.log`.

```bash
# Clear log file
php artisan log:clear

# Or manually:
truncate -s 0 storage/logs/laravel.log
```

### 6.5 Storage Link Verification

```bash
php artisan storage:link
```

---

## 7. Updating the Project

### 7.1 Backend Update

```bash
# Pull latest code
git pull origin main

# Install new dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Restart queue workers
supervisorctl restart oxp-worker:*
```

### 7.2 Frontend Update

```bash
cd B2C_frontend

# Pull latest code
git pull origin main

# Install new dependencies
npm install

# Rebuild
npm run build

# Restart the server
pm2 restart oxp-frontend  # if using PM2
```

---

## 8. Database Backup Strategy

### 8.1 Daily Database Backups

```bash
# Manual backup
mysqldump -u {username} -p {database_name} > backup_$(date +%Y%m%d_%H%M%S).sql

# Automated with cron (add to crontab)
0 2 * * * mysqldump -u {username} -p{password} {database} | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

### 8.2 Backup Retention

- Keep **daily backups** for 30 days.
- Keep **weekly backups** for 12 weeks.
- Keep **monthly backups** for 12 months.
- Store backups in a separate location from the server.

### 8.3 Restore from Backup

```bash
mysql -u {username} -p {database_name} < backup_file.sql
```

---

## 9. Media Backup Strategy

If using **Azure Blob Storage**:
- Azure provides built-in redundancy.
- Enable **Azure Backup** or use AzCopy to snapshot the container regularly.

If using **local storage**:
- Include `storage/app/public/` in your backup routine.
- Back up daily with the database backup.

---

## 10. Rollback Procedure

In case a deployment causes issues:

```bash
# Roll back to previous release (if using deployment tools with releases)
# Example with Deployer or Envoyer

# Or manually:
git checkout {previous-commit-hash}

# Roll back migrations (if new migrations were deployed)
php artisan migrate:rollback

# Rebuild dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan optimize:clear
php artisan optimize

# Restart workers
supervisorctl restart oxp-worker:*
```

---

## 11. Maintenance Mode

Put the application into maintenance mode during deployments:

```bash
# Enable maintenance mode
php artisan down --message="We're performing maintenance. We'll be back shortly."

# Perform your deployment steps...

# Disable maintenance mode
php artisan up
```

---

## 12. Common Maintenance Checklist

Run this checklist weekly in production:

- [ ] Check for failed jobs: `php artisan queue:failed`
- [ ] Review error logs: `tail -n 100 storage/logs/laravel.log`
- [ ] Verify storage accessibility (upload a test file via admin panel)
- [ ] Check database disk usage
- [ ] Verify email delivery (send test email via Email Settings)
- [ ] Review pending moderation reports
- [ ] Check for pending database migrations: `php artisan migrate:status`
- [ ] Review Handover Readiness page in admin panel for any new warnings
- [ ] Back up database
- [ ] Clean up old log files if large

---

## 13. Security Checklist

Before and after deployment:

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` in production
- [ ] CORS origins set to actual frontend URL only
- [ ] Strong, unique database password
- [ ] Admin panel accessible only from trusted IPs (if possible)
- [ ] `storage/` directory not publicly accessible (except `storage/app/public/`)
- [ ] Regular dependency updates: `composer update` and `npm update`
- [ ] HTTPS enforced via web server or load balancer

---

## 14. Client Handover Checklist

Walk the client through these workflows before delivery sign-off:

1. Log in to the admin panel and change any temporary password.
2. Edit homepage CMS sections and confirm the public homepage updates.
3. Edit material page content and confirm the public material page updates.
4. Edit Contact and B2B page CMS sections in English, Korean, and Chinese.
5. Edit footer contact details, social links, and legal links, then confirm footer/contact sync.
6. Update Privacy Policy and Terms content; confirm unsafe HTML is removed and formatting remains.
7. Add or update products, variants, inventory, product images, and category visibility.
8. Place a test order and demonstrate the manual payment confirmation workflow.
9. Review community posts, reports, restrictions, and unrestriction workflow.
10. Configure SMTP and send a test email from Email Settings.
11. Upload a valid media file and confirm an unsafe file is rejected with a clear error.
12. Clear demo data or regenerate demo data only in a non-production environment.

---

*Related code: `B2C_backend/`, `B2C_frontend/`, `B2C_backend/.env.example`*
