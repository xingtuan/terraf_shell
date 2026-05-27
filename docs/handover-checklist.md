# Pre-Handover Checklist

Use this checklist before final delivery.

## Code And Dependencies

- [ ] Backend PHP version is compatible with PHP 8.3.
- [ ] `B2C_backend/composer.lock` exists.
- [ ] Production uses `composer install`, not `composer update`.
- [ ] Frontend uses Node.js 20 for deployment.
- [ ] Frontend uses pnpm.
- [ ] `auto_deploy.sh` has been reviewed for the target Ubuntu / Debian server.
- [ ] No real secrets are committed.

## Automated Installation

- [ ] [INSTALLATION.md](INSTALLATION.md) has been reviewed.
- [ ] Domain / IP, firewall, ports, and Git access are confirmed.
- [ ] First-install command is confirmed:

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

- [ ] Production update command uses `RUN_SEED=0`:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

- [ ] The team understands that `RESET_WORKTREE=1` discards local Git changes and untracked files.
- [ ] `/root/terraf-install/credentials.txt` storage and permissions are confirmed.

## Backend

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` is generated and backed up.
- [ ] Database connection works.
- [ ] `php artisan migrate --force` succeeds.
- [ ] Seeders are run only when intended.
- [ ] Cache commands succeed.

## Frontend

- [ ] `B2C_frontend/.env.local` has `NEXT_PUBLIC_API_BASE_URL=/api`.
- [ ] `NEXT_SERVER_API_BASE_URL` points to a reachable Laravel API.
- [ ] `NEXT_PUBLIC_SITE_URL` is the production frontend URL.
- [ ] `pnpm build` succeeds.
- [ ] `terraf-frontend` service runs.

## Admin

- [ ] `/admin` is reachable.
- [ ] Seeded admin password is changed or the account is disabled.
- [ ] Admin roles and handover accounts are confirmed.
- [ ] Admin locale switching works.
- [ ] Application Settings are configured.
- [ ] Email Settings are configured or log mailer is explicitly accepted.
- [ ] Storage Settings connection and upload tests pass.

## Content And Starter Data

- [ ] Homepage sections display correctly.
- [ ] Material content displays correctly.
- [ ] Article / CMS content displays correctly.
- [ ] Product starter catalog is acceptable.
- [ ] Test users, posts, and orders are removed or clearly marked.
- [ ] Seed data is described as starter content.

## Store

- [ ] Categories, products, images, variants, and SKU work.
- [ ] Stock and inventory policies match the business process.
- [ ] Stock deduction on order creation is verified.
- [ ] Pending order cancellation restores stock.
- [ ] Guest Checkout is enabled or disabled as required.
- [ ] Guest order lookup works.
- [ ] Account order history works.
- [ ] GST calculations match Tax Settings.
- [ ] Shipping Settings and NZ Post settings match business requirements.
- [ ] The absence of an online payment gateway is accepted.

## Community

- [ ] Community feature flag is correct.
- [ ] Posting, comments, likes, favorites, and follows work.
- [ ] Cover image and attachment uploads work.
- [ ] Funding links display as expected.
- [ ] Reports enter admin moderation.
- [ ] User restrictions can be managed.
- [ ] Notifications are verified with the queue running.

## Storage

- [ ] Local storage link exists or Azure Storage is configured.
- [ ] Product, homepage, material, article, and community images display.
- [ ] Storage switching strategy is confirmed.
- [ ] Historical media migration requirements are confirmed.

## Queue And Scheduler

- [ ] Supervisor `terraf-queue` runs.
- [ ] `/etc/cron.d/terraf-scheduler` exists.
- [ ] `php artisan schedule:run` can run.
- [ ] Mail, notifications, and async jobs do not accumulate.

## Security And Launch

- [ ] HTTPS is configured or launch plan is confirmed.
- [ ] Public port exposure is confirmed.
- [ ] Database backup strategy is confirmed.
- [ ] Uploaded-file or Azure backup strategy is confirmed.
- [ ] `.env`, `.env.local`, database passwords, and Azure keys are stored safely.
- [ ] Logs and escalation contacts are handed over.

## Verification Commands

```bash
cd B2C_backend
php artisan test
php artisan admin:check-translations

cd ../B2C_frontend
node scripts/check-i18n-keys.mjs
pnpm build
```

Playwright tests require running backend and frontend services:

```bash
pnpm test
```
