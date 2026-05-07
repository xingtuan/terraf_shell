# Env And Runtime Settings Policy

`.env` cannot be removed completely. Laravel needs bootstrap configuration before the database and `app_settings` table can be loaded.

Keep in `.env`:

- `APP_KEY`
- `APP_ENV`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `CACHE_STORE` fallback
- `SESSION_DRIVER` fallback

Move to admin/runtime settings:

- storage driver
- Azure storage credentials
- local storage settings
- mail settings
- frontend URL
- site/admin names
- upload limits
- community media settings
- NZ Post settings
- shipping fallback values
- tax/GST settings
- feature switches
- handover/maintenance options

Env values remain in config files as fallback for:

- local development
- first install
- emergency recovery when runtime settings are misconfigured

Secrets in runtime settings are encrypted with Laravel encryption. Do not rotate `APP_KEY` without planning how encrypted settings will be recovered or re-entered.

