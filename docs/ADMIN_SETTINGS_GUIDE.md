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

