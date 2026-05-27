# Troubleshooting

This chapter is a concise English summary. Use [../TROUBLESHOOTING.md](../TROUBLESHOOTING.md) for the full checklist.

## Installation Failure

If you see:

```text
Existing repo has local modifications
```

inspect the deployment worktree:

```bash
git -C /var/www/terraf_shell status --short
```

Only discard local changes when you are sure:

```bash
sudo env RESET_WORKTREE=1 RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

## Frontend Is Down

```bash
sudo systemctl status terraf-frontend
sudo journalctl -u terraf-frontend -f
curl -I http://127.0.0.1:3000/
```

Check `B2C_frontend/.env.local`:

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
API_PROXY_TARGET=http://127.0.0.1:8000
```

## API or Admin Is Down

```bash
curl -I http://127.0.0.1:8000/up
sudo systemctl status php8.3-fpm
sudo nginx -t
sudo tail -f /var/log/nginx/error.log
```

Admin URL:

```text
http://your-domain-or-ip:8000/admin
```

## Images Do Not Display

Local storage:

```bash
cd /var/www/terraf_shell/B2C_backend
php artisan storage:link
ls -l public/storage
```

Azure storage:

- Run the connection test in Storage Settings.
- Run the upload test.
- Check container, key, public URL and SAS TTL.

## Cart / Checkout Issues

Check:

- Product and variant status.
- SKU stock and inventory policy.
- Guest Checkout feature flag.
- Shipping Settings output.
- Tax Settings.
- `B2C_backend/storage/logs/laravel.log`.

## GST / Shipping Does Not Update

Clear backend cache after saving Tax Settings or Shipping Settings:

```bash
cd /var/www/terraf_shell/B2C_backend
php artisan optimize:clear
```

NZ Post quotes require valid NZ Post Settings. In `auto` mode the system falls back to manual rates when NZ Post fails.

## 500 / 502 / 404

- 500: inspect Laravel logs, `.env`, APP_KEY, database and storage permissions.
- 502: check `terraf-frontend`, Laravel `/up`, PHP-FPM and Nginx upstreams.
- 404: check Nginx sites, Next routes and Laravel route cache.

## Queue and Scheduler

```bash
sudo supervisorctl status terraf-queue:*
tail -f /var/www/terraf_shell/B2C_backend/storage/logs/queue-worker.log
cat /etc/cron.d/terraf-scheduler
sudo systemctl status cron
```
