# Maintenance

This document covers post-handover maintenance: updates, backups, logs, cache clearing, rebuilds, storage, mail, and safe reruns of the installer.

## Routine Checks

Regularly verify:

- Frontend, store, cart, checkout, community, and admin availability.
- `terraf-frontend` systemd service.
- `terraf-queue` Supervisor worker.
- Cron scheduler.
- Laravel logs.
- Nginx and PHP-FPM logs.
- Database backups.
- Local storage or Azure container access.
- Admin account security.

```bash
sudo systemctl status terraf-frontend
sudo supervisorctl status terraf-queue:*
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
```

## Updating Code

Recommended:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

The script pulls code, installs dependencies, runs migrations, rebuilds caches, installs frontend dependencies, builds frontend, and restarts services.

If local changes block deployment:

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

Only use `RESET_WORKTREE=1` when local changes can be discarded.

## Database Backup

```bash
mkdir -p ~/terraf-backups
mysqldump -u oxp_user -p oxp_local > ~/terraf-backups/oxp_local-$(date +%F-%H%M%S).sql
```

Recommendations:

- Back up before deployments.
- Schedule daily backups.
- Store encrypted copies outside the server.
- Test restore procedures.

Restore example:

```bash
mysql -u oxp_user -p oxp_local < backup.sql
```

## File Backup

Local storage:

```bash
tar -czf ~/terraf-backups/storage-public-$(date +%F-%H%M%S).tar.gz -C B2C_backend/storage/app public
```

Also back up:

- `B2C_backend/.env`
- `B2C_frontend/.env.local`
- Nginx site configs
- Supervisor config
- systemd service file

Use Azure tooling or platform backup policies for Azure containers.

## Logs

```bash
tail -f B2C_backend/storage/logs/laravel.log
tail -f B2C_backend/storage/logs/queue-worker.log
sudo journalctl -u terraf-frontend -f
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -f
```

## Cache

Backend:

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Restart queue workers after queue or `.env` changes:

```bash
sudo supervisorctl restart terraf-queue:*
```

## Frontend Rebuild

```bash
cd B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
sudo systemctl restart terraf-frontend
```

Rebuild after frontend code, message files, or frontend environment variables change.

## Re-running The Installer

Safe production rerun:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

First install or controlled reseed:

```bash
sudo env RUN_SEED=1 bash auto_deploy.sh your-domain-or-ip
```

Force reset:

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

Before reset, confirm database backup, file backup, environment backups, Git worktree state, and seed strategy.

## Storage Maintenance

Local storage:

- Back up `storage/app/public`.
- Check `public/storage`.
- Check permissions.

Azure storage:

- Rotate keys.
- Check container access policy.
- Check SAS TTL.
- Use Storage Settings connection and upload tests.

Read [STORAGE.md](STORAGE.md) before switching drivers.

## Mail Maintenance

Configure production SMTP in Email Settings and test:

```bash
cd B2C_backend
php artisan email:center:test admin@example.com
```

If mail is delayed, check queue workers.

## Handover Recommendations

The client maintenance team should know how to:

- Login and change admin passwords.
- Edit homepage, materials, articles, and page sections.
- Manage products, SKU, stock, and orders.
- Adjust GST, shipping, and NZ Post.
- Handle reports and user restrictions.
- Test storage and mail.
- Read logs and escalate incidents.

Do not store real passwords in Git.

## Release Checklist

- `APP_DEBUG=false`
- HTTPS configured
- Default admin password changed
- Database and storage backed up
- Frontend build successful
- Backend migrations successful
- Queue worker running
- Scheduler running
- Mail test successful
- Storage upload test successful
- Cart, Guest Checkout, and order lookup tested
- GST and shipping verified
- Key pages checked in all supported languages
