# Deployment and Maintenance

This chapter is a concise operator guide. The authoritative deployment references are:

- [Installation](../INSTALLATION.md)
- [Deployment](../DEPLOYMENT.md)
- [Maintenance](../MAINTENANCE.md)
- [Troubleshooting](../TROUBLESHOOTING.md)

## Recommended Deployment

Use the root `auto_deploy.sh` script on an Ubuntu / Debian single-server host:

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

For production updates:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

The script installs PHP 8.3, Composer, Node.js 20, pnpm, MySQL, Nginx, PHP-FPM, Supervisor and Cron. It also pulls the repository, installs dependencies, runs migrations, optionally runs seeders, builds the frontend, and configures queue and scheduler services.

## URLs

After deployment:

- Frontend: `http://your-domain-or-ip/`
- API: `http://your-domain-or-ip/api/`
- Admin: `http://your-domain-or-ip:8000/admin`
- Health check: `http://your-domain-or-ip:8000/up`

Credentials and useful commands are written to:

```text
/root/terraf-install/credentials.txt
```

## Redeployment

Normal redeploy:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

If the script reports local repository changes:

```bash
git -C /var/www/terraf_shell status --short
```

Only discard local changes when you are sure they are not needed:

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

`RESET_WORKTREE=1` performs a hard Git reset and removes untracked files.

## Maintenance Priorities

- Change the seeded administrator password before handover.
- Use `RUN_SEED=0` for normal production updates.
- Back up MySQL and uploaded files before deployment.
- Monitor `terraf-frontend`, `terraf-queue`, Nginx, PHP-FPM and MySQL.
- After enabling HTTPS, update `APP_URL`, `FRONTEND_URL`, `NEXT_PUBLIC_SITE_URL`, CORS and Sanctum domains.
- Test local / Azure storage from Storage Settings.
- Verify GST and shipping from Tax Settings and Shipping Settings.

## Common Commands

```bash
sudo systemctl status terraf-frontend
sudo supervisorctl status terraf-queue:*
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
```

Clear backend cache:

```bash
cd /var/www/terraf_shell/B2C_backend
php artisan optimize:clear
php artisan config:cache
```

Rebuild frontend:

```bash
cd /var/www/terraf_shell/B2C_frontend
pnpm build
sudo systemctl restart terraf-frontend
```
