# Runtime Settings Audit

Scope: backend `config/*.php`, `app/Services`, `app/Support`, `app/Providers`, `app/Filament/Pages`, `app/Http/Controllers`, `app/Jobs`, `app/Console`, and `database/seeders`.

Classification:

- A: bootstrap-only. Must remain in `.env` because Laravel needs it before the database can be read.
- B: runtime application setting. Should be stored in `app_settings` and editable from admin after install.
- C: legacy env fallback. May remain in config for local development and emergency recovery, but DB settings override it.

| Config Area | Current env key | Current config path | Used by | Can move to DB settings? | Bootstrap-only? | Proposed setting key | Priority |
|---|---|---|---|---|---|---|---|
| App key | APP_KEY, APP_PREVIOUS_KEYS | `app.key`, `app.previous_keys` | Laravel encryption, cookies, encrypted casts | No | Yes | N/A | A |
| App environment | APP_ENV, APP_DEBUG | `app.env`, `app.debug` | framework boot, exception rendering | No | Yes | N/A | A |
| App URL | APP_URL | `app.url`, public disk URL | URL generation, installer, public storage | Partial | Yes for bootstrap fallback | `app.url` | A/C |
| App display | APP_NAME, APP_LOCALE, APP_FALLBACK_LOCALE | `app.name`, `app.locale`, `app.fallback_locale` | UI, mail, translations | Yes | No | `app.site_name`, `app.default_locale`, `app.supported_locales` | B |
| Frontend URLs | FRONTEND_URL, FRONTEND_PASSWORD_RESET_PATH, FRONTEND_EMAIL_VERIFICATION_PATH | `services.frontend.*` | AuthService, email payloads, handover checks | Yes | No | `app.frontend_url` | B |
| Database | DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_URL, DB_SOCKET, DB_CHARSET, DB_COLLATION, DB_SSL* | `database.*` | Laravel DB, migrations, cache/session DB stores | No | Yes | N/A | A |
| Redis | REDIS_* | `database.redis.*`, cache/queue/session fallbacks | cache, queue, sessions | No for bootstrap infra | Yes | N/A | A/C |
| Cache | CACHE_STORE, CACHE_PREFIX, DB_CACHE_* | `cache.*` | bootstrap cache and settings cache | No for default fallback | Yes | N/A | A |
| Session | SESSION_DRIVER, SESSION_* | `session.*` | web/admin session bootstrap | No for default fallback | Yes | N/A | A |
| Queue | QUEUE_CONNECTION, DB_QUEUE_*, REDIS_QUEUE_*, SQS_* | `queue.*` | jobs, email queue | Mostly fallback | Sometimes | Future `queue.default` if needed | C |
| Logging | LOG_CHANNEL, LOG_LEVEL, LOG_* | `logging.*` | framework logging | Mostly fallback | Sometimes | Future `logging.level` if needed | C |
| Filesystem default | FILESYSTEM_DISK | `filesystems.default` | Storage facade fallback | Yes | No | `storage.default_driver`, `storage.local.disk` | B |
| Community upload disk | COMMUNITY_UPLOAD_DISK | `community.uploads.disk` | MediaService, Filament file uploads, StorageUrl | Yes | No | `storage.default_driver` | B |
| Local storage URL | APP_URL derived | `filesystems.disks.public.url` | local public URLs | Indirect | App URL fallback only | `app.url`, `storage.local.disk` | B/C |
| Azure storage | AZURE_STORAGE_NAME, AZURE_STORAGE_KEY, AZURE_STORAGE_CONTAINER, AZURE_STORAGE_URL | `filesystems.disks.azure.*` | Azure adapter, StorageUrl, MediaService | Yes | No | `storage.azure.account_name`, `storage.azure.account_key`, `storage.azure.container`, `storage.azure.url` | B |
| Azure SAS | AZURE_STORAGE_USE_SAS_URLS, AZURE_STORAGE_SAS_URL_TTL_MINUTES | `community.uploads.azure.*` | StorageUrl temporary URLs | Yes | No | `storage.azure.use_sas_urls`, `storage.azure.sas_ttl_minutes` | B |
| Community uploads | ALLOW_GUEST_UPLOAD, IDEA_MEDIA_* | `community.uploads.*`, `community.idea_media.*` | routes, post requests, media validation | Yes | No | `community.allow_guest_upload`, `community.max_files`, `community.max_file_size_kb`, `community.allowed_extensions`, `community.max_external_links` | B |
| Community moderation | COMMUNITY_SUBMISSION_POLICY, COMMUNITY_SENSITIVE_WORDS_* | `community.moderation.*` | SensitiveContentService, post policy | Yes | No | `community.submission_policy`, `community.sensitive_words_enabled`, `community.sensitive_words` | B |
| B2B lead notifications | B2B_LEADS_NOTIFY_ADMINS, B2B_LEAD_NOTIFICATION_RECIPIENTS | `community.b2b_leads.*` | B2BLeadService | Yes | No | `feature.b2b_lead_notifications`, `community.b2b_lead_notification_recipients` | B |
| Funding default text | FUNDING_DEFAULT_SUPPORT_BUTTON_TEXT | `community.funding.default_support_button_text` | FundingCampaignService | Yes | No | `community.default_funding_support_button_text` | B |
| Store currency | STORE_CURRENCY | `store.currency` | cart and shipping resources | Yes | No | `store.currency` | B |
| Tax | STORE_GST_RATE, STORE_PRICES_INCLUDE_GST, STORE_TAX_LABEL | `store.tax.*` | TaxService, ShippingSettings | Yes | No | `tax.gst_rate`, `tax.prices_include_gst`, `tax.label`, `tax.gst_enabled` | B |
| Shipping fallback | STORE_FREE_SHIPPING_THRESHOLD, STORE_STANDARD_SHIPPING_RATE, STORE_EXPRESS_SHIPPING_RATE, STORE_RURAL_SHIPPING_SURCHARGE, STORE_ORIGIN_* | `store.shipping.*` | ShippingQuoteService, ShippingSettings | Yes | No | `shipping.free_shipping_threshold`, `shipping.fallback_standard_amount`, `shipping.fallback_express_amount`, `shipping.rural_surcharge`, `shipping.origin_city`, `shipping.origin_postcode` | B |
| NZ Post | NZPOST_ENABLED, NZPOST_API_BASE_URL, NZPOST_CLIENT_ID, NZPOST_CLIENT_SECRET, NZPOST_API_KEY | `store.nzpost.*` | NzPostClient, AddressLookupService | Yes | No | `nzpost.enabled`, `nzpost.base_url`, `nzpost.client_id`, `nzpost.client_secret`, `nzpost.api_key` | B |
| Mail transport | MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME, MAIL_FROM_* | `mail.*` | MailSettingsService, EmailDispatchService | Yes | No | `mail.mailer`, `mail.host`, `mail.port`, `mail.username`, `mail.password`, `mail.encryption`, `mail.from_address`, `mail.from_name` | B/C |
| Mail providers | POSTMARK_API_KEY, RESEND_API_KEY, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION | `services.postmark`, `services.resend`, `services.ses` | provider mailers | Yes for chosen provider secrets | No | `mail.provider_api_key`, `mail.provider_region` | C |
| CORS/Sanctum/Auth | CORS_ALLOWED_ORIGINS, SANCTUM_STATEFUL_DOMAINS, AUTH_* | `cors.*`, `sanctum.*`, `auth.*` | auth middleware/bootstrap | Usually fallback | Usually yes | Future security settings only with care | C |

Direct runtime `env()` usages found outside config:

- `app/Support/StorageUrl.php`: replaced with config fallback.
- `app/Services/MediaService.php`: replaced by `StorageManagerService`.
- `app/Filament/Pages/SystemHandoverReadiness.php`: replaced with config fallback.

