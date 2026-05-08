# Admin Settings Guide

Navigation group: `System / Settings`.

Pages added or connected:

- Application Settings: site name, admin brand name, app URL, frontend URL, locale, timezone, contact/support email.
- Storage Settings: active storage driver, local disk checks, Azure credentials, public URL previews, test actions, settings cache clear, rollback driver.
- Mail Settings: existing Email Settings page now syncs selected mail settings into runtime settings.
- Shipping Settings: editable NZ-only flag, origin city/postcode, fallback rates, rural surcharge.
- NZ Post Settings: enabled flag, base URL, client id, encrypted client secret/API key, sender postcode, lookup test.
- Tax Settings: GST enabled, GST rate, prices include GST, label.
- Community Settings: guest uploads, file count/size/extension limits, external links, submission policy, sensitive words, funding button text.
- Feature Flags: store, B2B, community, funding links, guest checkout, email sending, maintenance notice.
- Handover Readiness: now reads config values that can be overridden by runtime settings.

Secrets are never displayed after saving. Secret fields use a masked placeholder and can be replaced by entering a new value.

Setting changes are cached under `app_settings.all`. Saving settings clears and warms the cache. The Storage Settings page also has a manual cache clear action.

Setting changes made by an authenticated admin are recorded in `admin_action_logs` with secret values masked.

## Final Delivery Additions

- Settings Backup & Import exports non-secret JSON, imports only known non-secret keys, clears settings cache, and records audit logs through `SettingsService`.
- Handover settings summary downloads a safe operational JSON without secrets.
- Demo Cleanup removes only records explicitly marked as demo. It does not delete admin users, runtime settings, or CMS content.
- Media Storage Scan counts media by disk, checks the first 200 records for missing files, exports a report, and provides dry-run migration actions only.
- Storage test actions persist `storage.*.last_tested_at`, `storage.*.last_test_status`, and `storage.*.last_test_message`.
- `app_setting_audit_logs` records actor, setting group/key, masked old/new values, secret flag, action, IP address, user agent, and timestamp.
- High-risk settings pages require admin access via `PanelAccess::isAdmin()`.
