# Handover Package Guide

## Include In The Zip

- Repository source for `B2C_backend` and `B2C_frontend`.
- `README.md`, `B2C_backend/README.md`, `B2C_frontend/README.md`, and `docs/`.
- `.env.example` files only.
- Database migration and seeder files.
- Public static assets required by the frontend.
- Final settings export JSON generated from `System / Settings / Settings Backup & Import`.
- Media scan report from `Media Storage Scan`.

## Do Not Include

- `.env`, `.env.local`, or any file with real credentials.
- `storage/logs`, framework cache files, sessions, or local `installed.lock` from a developer machine.
- `node_modules`, `vendor`, `.next`, coverage output, or local build cache.
- Raw Azure keys, SMTP passwords, NZ Post secrets, database credentials, or personal API tokens.
- Uploaded production media unless the client explicitly asked for an export and the storage location is verified.

## Local Installation

1. Unzip the package.
2. Install backend dependencies with `composer install`.
3. Install frontend dependencies with `corepack pnpm install`.
4. Start the backend and visit `/install`.
5. Complete the installer steps: requirements, app, database, storage, mail, admin account, summary.
6. Confirm the installer redirects to `/admin`.
7. Run `php artisan optimize:clear`.
8. Build the frontend with `corepack pnpm build`.

## Installer URL

Use:

```text
https://your-backend-host/install
```

The installer is unavailable after `storage/app/installed.lock` or `system.installed_at` exists.

## Azure Deployment Notes

- Keep Azure account name/key/container out of plain documentation.
- Enter Azure settings in Storage Settings or the installer.
- Run the Azure storage test before switching production uploads.
- Keep existing media rows tied to their original `disk`; switching drivers affects new uploads only.
- Use Media Storage Scan before planning any migration.

## Client Hosting Migration Notes

- `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, and `SESSION_DOMAIN` are bootstrap/server values and may require config clear or restart.
- `app.frontend_url` is runtime-visible for admin readiness checks, but it does not replace every CORS/Sanctum server setting.
- Update frontend `NEXT_PUBLIC_API_BASE_URL` when backend and frontend are on different origins.

## Database Migration

- Back up the source database before migration.
- Run `php artisan migrate --force` on the target.
- Run seeders only when installing a fresh environment or intentionally adding defaults.
- Confirm `app_setting_audit_logs`, `app_settings`, orders, media, leads, and admin users migrated.

## Storage Migration

- Do not move media automatically during handover.
- Export the Media Storage Scan report.
- Plan local-to-Azure or Azure-to-local as a separate dry-run and copy job.
- Never delete the source files until target files, database disk/path values, and public URLs are verified.

## Settings Export / Import

- Export non-secret settings from `System / Settings / Settings Backup & Import`.
- Import only validated non-secret JSON.
- Re-enter secrets manually in admin after import.
- Review the settings audit log after import.

## Production Cutover

- Review initial content through the standard admin resources.
- Create or confirm the production admin account.
- Rotate any temporary installer/admin passwords.
- Confirm `/api/public-settings`, `/api/health`, `/up`, products, cart, checkout, storage upload, and email test status.
- Confirm Korean admin operation with the language switcher.

## Troubleshooting

- Installer locked: confirm no install process is running, then remove `storage/app/installing.lock`.
- Installer failed: check `storage/logs/laravel.log`; the previous `.env` is restored when a backup exists.
- Admin cannot log in: verify `SESSION_DRIVER`, `APP_KEY`, database sessions, and the admin user role.
- Frontend API calls fail: verify `NEXT_PUBLIC_API_BASE_URL`, CORS origins, and backend `/api/health`.
- Media URLs fail: verify media row `disk`, active storage driver, storage link, Azure credentials, and Media Storage Scan output.
