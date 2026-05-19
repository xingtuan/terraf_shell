# 10 — Settings and Configuration

## Overview

OXP has two tiers of configuration:

1. **Environment-level configuration** — set in the `.env` file, requires server restart to take effect, affects the application at a fundamental level (database, storage, mail server).
2. **Admin-level runtime configuration** — set through the admin panel's Settings pages, stored in the `app_settings` database table, takes effect immediately without restart.

---

## 1. Environment Variables Reference (`.env`)

The `.env` file is located at `B2C_backend/.env`. Below is the complete reference.

### 1.1 Application

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `OXP` | Application display name |
| `APP_ENV` | `local` | Environment: `local`, `production` |
| `APP_KEY` | (generated) | Laravel encryption key — must be set |
| `APP_DEBUG` | `true` | Debug mode — **set to `false` in production** |
| `APP_URL` | `http://127.0.0.1:8000` | Backend URL — must be correct for media serving |
| `APP_LOCALE` | `en` | Default locale |
| `APP_FALLBACK_LOCALE` | `en` | Fallback when translation missing |

### 1.2 Database

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database server host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `product_community` | Database name |
| `DB_USERNAME` | `root` | Database username |
| `DB_PASSWORD` | (empty) | Database password |

### 1.3 Storage

| Variable | Default | Description |
|---|---|---|
| `FILESYSTEM_DISK` | `azure` | Primary storage disk: `azure` or `public` |
| `COMMUNITY_UPLOAD_DISK` | `azure` | Disk for community uploads |
| `AZURE_STORAGE_NAME` | (required) | Azure Storage account name |
| `AZURE_STORAGE_KEY` | (required) | Azure Storage account key |
| `AZURE_STORAGE_CONTAINER` | `uploads` | Azure container name |
| `AZURE_STORAGE_URL` | (required) | Azure blob endpoint URL |
| `AZURE_STORAGE_USE_SAS_URLS` | `true` | Use time-limited SAS URLs for security |
| `AZURE_STORAGE_SAS_URL_TTL_MINUTES` | `10080` | SAS URL validity period (7 days) |
| `ALLOW_GUEST_UPLOAD` | `false` | Allow unauthenticated file uploads |

### 1.4 Cache and Queue

| Variable | Default | Description |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Queue driver: `database`, `redis`, `sync` |
| `CACHE_STORE` | `database` | Cache driver: `database`, `redis`, `file` |
| `SESSION_DRIVER` | `database` | Session storage: `database`, `file` |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |

### 1.5 Mail

| Variable | Default | Description |
|---|---|---|
| `MAIL_MAILER` | `log` | Mail driver: `smtp`, `mailgun`, `log`, `null` |
| `MAIL_HOST` | `127.0.0.1` | SMTP server host |
| `MAIL_PORT` | `2525` | SMTP port (use 587 for TLS, 465 for SSL) |
| `MAIL_USERNAME` | (empty) | SMTP username |
| `MAIL_PASSWORD` | (empty) | SMTP password |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default sender address |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Default sender name |
| `MAIL_SCHEME` | (empty) | `tls` or `ssl` |

> **Production note**: Change `MAIL_FROM_ADDRESS` to a real address before going live.

### 1.6 Frontend / CORS

| Variable | Default | Description |
|---|---|---|
| `FRONTEND_URL` | `http://localhost:3000` | Frontend application URL |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000,127.0.0.1:3000` | Trusted domains for Sanctum cookies |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000` | Origins allowed to call the API |
| `FRONTEND_PASSWORD_RESET_PATH` | `/reset-password` | Path for password reset emails |
| `FRONTEND_EMAIL_VERIFICATION_PATH` | `/email-verified` | Path for email verification emails |

### 1.7 Store / Commerce

| Variable | Default | Description |
|---|---|---|
| `STORE_CURRENCY` | `NZD` | Currency code |
| `STORE_GST_RATE` | `0.15` | Tax rate (15% for New Zealand GST) |
| `STORE_PRICES_INCLUDE_GST` | `true` | Whether listed prices include GST |
| `STORE_TAX_LABEL` | `"GST included"` | Tax label shown in cart/checkout |
| `STORE_FREE_SHIPPING_THRESHOLD` | `200` | Order value above which shipping is free |
| `STORE_STANDARD_SHIPPING_RATE` | `8` | Standard shipping cost in currency |
| `STORE_EXPRESS_SHIPPING_RATE` | `14` | Express shipping cost in currency |
| `STORE_RURAL_SHIPPING_SURCHARGE` | `5` | Rural delivery surcharge |
| `STORE_ORIGIN_POSTCODE` | (empty) | Origin postcode for shipping calculations |
| `STORE_ORIGIN_CITY` | (empty) | Origin city |
| `STORE_ORIGIN_COUNTRY` | `NZ` | Origin country code |

### 1.8 NZ Post Integration

| Variable | Default | Description |
|---|---|---|
| `NZPOST_ENABLED` | `false` | Enable NZ Post real-time shipping quotes |
| `NZPOST_API_BASE_URL` | `https://api.nzpost.co.nz` | NZ Post API endpoint |
| `NZPOST_CLIENT_ID` | (empty) | NZ Post client ID |
| `NZPOST_CLIENT_SECRET` | (empty) | NZ Post client secret |
| `NZPOST_API_KEY` | (empty) | NZ Post API key |

### 1.9 Community

| Variable | Default | Description |
|---|---|---|
| `COMMUNITY_SENSITIVE_WORDS_ENABLED` | `false` | Enable word filter |
| `COMMUNITY_SENSITIVE_WORDS` | (empty) | Comma-separated list of blocked words |
| `IDEA_MEDIA_DIRECTORY` | `ideas` | Storage directory for post attachments |
| `IDEA_MEDIA_MAX_FILES` | `12` | Max files per post |
| `IDEA_MEDIA_MAX_EXTERNAL_LINKS` | `4` | Max external links per post |
| `IDEA_MEDIA_MAX_FILE_SIZE_KB` | `10240` | Max file size (10 MB) |
| `IDEA_MEDIA_ALLOWED_EXTENSIONS` | `jpg,...,xlsx` | Allowed file extensions |

### 1.10 B2B Notifications

| Variable | Default | Description |
|---|---|---|
| `B2B_LEADS_NOTIFY_ADMINS` | `false` | Email admins on new B2B lead |
| `B2B_LEAD_NOTIFICATION_RECIPIENTS` | (empty) | Comma-separated email addresses |

### 1.11 Logging

| Variable | Default | Description |
|---|---|---|
| `LOG_CHANNEL` | `stack` | Log channel configuration |
| `LOG_LEVEL` | `debug` | Minimum log level (debug, info, warning, error) |

---

## 2. Admin-Level Runtime Settings

These settings are managed through the admin panel and stored in the `app_settings` table. They override related environment defaults where applicable.

### 2.1 Application Settings

**Location**: Admin Panel → System → Application Settings

Configurable options include:
- Application name override
- Feature flag toggles
- Public-facing contact information

### 2.2 Email Settings

**Location**: Admin Panel → System → Email Settings

Allows changing the mail server configuration without editing the `.env` file:
- SMTP host, port, encryption
- SMTP username and password
- From address and name
- Test email sending

> **Note**: Runtime email settings take precedence over `.env` mail settings.

*Related code: `app/Models/EmailSetting.php`, `app/Filament/Pages/EmailSettings.php`*

### 2.3 Shipping Settings

**Location**: Admin Panel → System → Shipping Settings

Configure all shipping rates:
- Standard and express rates
- Rural surcharge
- Free shipping threshold
- Origin location for NZ Post

### 2.4 Tax Settings

**Location**: Admin Panel → System → Tax Settings

- GST rate
- Tax label (displayed in cart and checkout)
- Whether prices include tax

### 2.5 Storage Settings

**Location**: Admin Panel → System → Storage Settings

Switch between storage backends:
- `azure` — Azure Blob Storage (recommended for production)
- `public` — Local public storage (requires storage:link)

### 2.6 NZ Post Settings

**Location**: Admin Panel → System → NZ Post Settings

Enable or disable NZ Post integration and configure API credentials.

### 2.7 Community Settings

**Location**: Admin Panel → System → Community Settings

- Submission policy (Auto Approve / Approval Required / Restricted)
- Trusted poster thresholds

### 2.8 Community Moderation Settings

**Location**: Admin Panel → System → Community Moderation Settings

- Sensitive word filtering
- Content screening rules

### 2.9 Legal Page Settings

**Location**: Admin Panel → System → Legal Page Settings

Edit the content of Privacy Policy and Terms of Service pages.

### 2.10 Feature Flags

**Location**: Admin Panel → System → Feature Flags

Enable or disable specific platform features without code deployment.

---

## 3. Settings Security

The `app_settings` table uses **encryption** for sensitive values. This means:
- Passwords and API keys stored as settings are encrypted at rest.
- Do not manually edit the `app_settings` table in the database — values may not be readable.
- Use the admin panel interface to manage all settings.

The `APP_KEY` in `.env` is used for encryption. If this key changes, all encrypted settings become unreadable.

*Related code: `app/Models/AppSetting.php`, `app/Models/AppSettingAuditLog.php`*

---

## 4. Settings Audit Log

**Location**: Admin Panel → System → (via Settings Backup page)

Every change to application settings is recorded in `app_setting_audit_logs`:
- Which setting was changed
- Old and new values (redacted for secrets)
- Which admin made the change
- Timestamp

---

## 5. Settings Backup and Import

**Location**: Admin Panel → System → Settings Backup

**Exporting settings:**
1. Click the **Export Settings** button.
2. A JSON file is downloaded containing current admin settings.
3. Store this file securely for recovery or migration.

Also accessible via: `GET /admin/settings/export`

**Importing settings:**
1. Go to **Settings Backup Import**.
2. Upload a previously exported settings JSON file.
3. Review the changes that will be applied.
4. Confirm the import.

> **Caution**: Importing settings will overwrite current values. Only import from trusted sources.

---

## 6. Frontend Environment Variables

The frontend (`B2C_frontend`) uses a separate `.env.local` or `.env.production` file:

| Variable | Description |
|---|---|
| `NEXT_PUBLIC_API_URL` | Backend API base URL (e.g., `https://api.yoursite.com`) |
| `NEXT_PUBLIC_APP_URL` | Frontend app URL |

These must be set correctly before building the frontend for production.

---

## 7. Configuration Best Practices

1. **Never commit `.env` files** to version control — they contain secrets.
2. **Set `APP_DEBUG=false`** in production — debug mode exposes sensitive error details.
3. **Use `APP_ENV=production`** in production environments.
4. **Change default passwords** — update the admin account password immediately after setup.
5. **Configure email before launch** — `MAIL_MAILER=log` in development will not send real emails.
6. **Set real CORS origins** — `CORS_ALLOWED_ORIGINS` must match the actual frontend URL in production.
7. **Use a queue worker** — set `QUEUE_CONNECTION=database` and run `php artisan queue:work` for reliable background jobs.
8. **Back up settings regularly** — use the Settings Export function after making changes.

---

*Related code: `B2C_backend/.env.example`, `B2C_backend/config/`, `B2C_backend/app/Filament/Pages/`, `B2C_backend/app/Models/AppSetting.php`*
