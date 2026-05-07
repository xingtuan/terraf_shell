# Handover Readiness

The Filament page System / Handover -> System / Handover Readiness gives operators a single delivery checklist without exposing secrets.

## Checks Displayed

- App name
- App URL
- Frontend URL
- Environment
- Database connection status
- Storage disk status
- Mail enabled status
- Selected mail provider
- Queue connection mode
- Cache driver
- Session driver
- Upload disk
- Storage link
- Key admin account
- Demo seed data
- Failed jobs
- PHP version
- Laravel version
- Writable `storage`
- Writable `bootstrap/cache`
- Last migration

## Badge Meaning

- OK: Ready or correctly configured.
- Warning: Usable, but requires operator awareness before production.
- Error: Must be fixed before handover.

## Required Production Review

Before zip handover or client deployment:

1. Run `php artisan migrate --seed` on the target environment.
2. Confirm at least one admin account exists.
3. Confirm `APP_URL` and `FRONTEND_URL` are correct.
4. Confirm storage disk and upload disk are configured.
5. Run `php artisan storage:link` when using local public storage.
6. Configure Email Center and send a test email.
7. Confirm queue mode. Use a worker-backed queue for production.
8. Clean demo community content.
9. Check failed jobs and resolve any failures.
10. Run `php artisan optimize:clear` after `.env` changes.

## Security Notes

The readiness page intentionally does not show:

- Passwords
- API keys
- SMTP secrets
- Database passwords
- Storage account keys

Use environment management or the Email Center secret fields for those values.
