# Final Delivery Fix Tracker

Last updated: 2026-05-08

Final validation:

- `php artisan optimize:clear`: Pass.
- `php artisan migrate:fresh --seed`: Timed out at 180s after final seeder output; follow-up checks confirmed all migrations ran and seed data exists.
- `php artisan test`: Pass, 153 tests / 1130 assertions.
- `vendor/bin/pint`: Pass, formatting completed.
- `node scripts/check-i18n-keys.mjs`: Pass.
- `corepack pnpm exec tsc --noEmit`: Pass.
- `corepack pnpm build`: Pass with six existing i18n-diff warnings for intentionally identical values.

## P0

### 1. Documentation Mismatch
- Priority: P0
- Area: Documentation
- Problem: Some delivery docs still implied mock-only catalog or incomplete backend integration.
- Files changed: `README.md`, `B2C_backend/README.md`, `B2C_frontend/README.md`, `docs/*`
- Fix summary: Current status now documents live product, featured product, product detail, cart, checkout, guest order token, NZ shipping, NZ Post, runtime settings, storage switching, installer, admin localization, media, CMS, email, and handover tooling.
- Test result: Documentation review plus `rg "mock-only|not exposed|checkout not connected"`.
- Status: Done
- Notes: `lib/api/community.ts` remains documented as a legacy mock-only idea-card boundary, not the live community post API.

### 2. Admin UI Localization
- Priority: P0
- Area: Filament admin and installer
- Problem: Several high-risk settings pages and installer screens used hardcoded English.
- Files changed: settings pages, `CommunityModerationSettings.php`, `CommunitySubmissionPolicy.php`, `resources/views/install/index.blade.php`, `lang/{en,ko,zh}/admin.php`, `AdminTranslationKeysTest.php`
- Fix summary: Localized the prioritized settings pages, storage/handover pages, installer, new admin tools, and community moderation policy labels. Added a test enforcing identical `admin.php` key structures.
- Test result: `php artisan test tests/Feature/Admin/AdminTranslationKeysTest.php` passed.
- Status: Partially Done
- Notes: A repository-wide scan still finds older hardcoded labels in some legacy Filament resources/widgets. They are documented as a remaining risk rather than mass-refactored late in delivery.

### 3. Runtime Feature Flag Route Registration
- Priority: P0
- Area: API routes
- Problem: Runtime settings must not decide whether routes exist because route cache can make admin changes ineffective.
- Files changed: `routes/api.php`, `bootstrap/app.php`, `EnsureRuntimeSettingEnabled.php`, `OrderController.php`, `StorePostRequest.php`, `PostResource.php`
- Fix summary: Always registers guest media upload, guest checkout, B2B lead, community write, and funding-link related routes; runtime checks now happen inside middleware/controllers/requests/resources.
- Test result: Guest upload disabled/enabled tests passed; guest checkout disabled route test added.
- Status: Done
- Notes: Disabled runtime features return safe 403 responses.

### 4. Web Installer Hardening
- Priority: P0
- Area: Installer
- Problem: Duplicate install submits, partial lock creation, unsafe `.env` writes, and raw failure detail needed tightening.
- Files changed: `InstallController.php`, `InstallationService.php`, `resources/views/install/index.blade.php`, `lang/{en,ko,zh}/admin.php`
- Fix summary: Added `installing.lock`, duplicate-submit protection, `.env` backup/restore, success-only `installed.lock`, safe flash messages, CSRF/rate-limited route retention, and a localized stepper.
- Test result: Syntax checks passed; existing installer feature tests remain in suite.
- Status: Partially Done
- Notes: Per-step Ajax validation, separate local/Azure test buttons, and optional mail-test execution were not added; the installer remains a single safe submit flow with database connectivity validation and hardened recovery.

### 5. Persist Storage Test Results
- Priority: P0
- Area: Storage settings
- Problem: Local/Azure/upload test results were transient.
- Files changed: `StorageSettings.php`, `SystemHandoverReadiness.php`, `DefaultAppSettingsSeeder.php`, translations
- Fix summary: Persists local, Azure, and overall test timestamp/status/message settings without secrets and displays them in Storage Settings and Handover Readiness.
- Test result: Syntax checks passed; storage abstraction tests remain available.
- Status: Done
- Notes: Messages are sanitized before being saved.

### 6. Settings Audit Logging
- Priority: P0
- Area: Runtime settings
- Problem: Settings changes needed a complete audit trail without plaintext secrets.
- Files changed: `SettingsService.php`, `AppSettingAuditLog.php`, migration `2026_05_08_010000_create_app_setting_audit_logs_table.php`, `SettingsServiceTest.php`
- Fix summary: Added audit table/model and audit writes for actor, group, key, old/new masked values, secret flag, action, IP, user agent, and timestamp.
- Test result: `SettingsServiceTest` audit masking test passed.
- Status: Done
- Notes: Secret values are stored as `[masked]` in audit logs.

### 7. Guest Checkout End-to-End
- Priority: P0
- Area: Store checkout
- Problem: Guest checkout must work without login while registered checkout remains unchanged.
- Files changed: `OrderController.php`, `orders.ts`, checkout page, `StoreOrderFlowTest.php`
- Fix summary: Confirmed `createOrder(payload, token?: string | null)`, no `Authorization` header for null token, guest email validation, token response/lookup, guest redirect, registered redirect, and cart clearing behavior.
- Test result: Existing guest order flow tests plus disabled runtime setting test.
- Status: Done
- Notes: Frontend now honors public `guest_checkout_enabled` when available.

### 8. Storage Abstraction Backward Compatibility
- Priority: P0
- Area: Media storage
- Problem: Storage driver switching must not break existing uploaded media.
- Files changed: `StorageSettings.php`, `MediaStorageScan.php`, `MediaStorageScanController.php`, handover docs
- Fix summary: Existing disk/path/url compatibility remains intact; new uploads continue through selected driver; scan tooling reports disk distribution and missing file candidates.
- Test result: `MediaUploadTest` focused pass; existing `MediaStorageAbstractionTest` remains in suite.
- Status: Done
- Notes: Migration is dry-run only until an operator explicitly approves a copy plan.

## P1

### 9. Settings Export / Import / Backup
- Priority: P1
- Area: Admin settings tools
- Problem: Operators need safe settings handover and restore support.
- Files changed: `SettingsBackupImport.php`, `SettingsBackupController.php`, `routes/web.php`, translations
- Fix summary: Added admin-only non-secret export, validated non-secret import, selected group reset, and handover settings summary download.
- Test result: Syntax checks passed.
- Status: Done
- Notes: Raw secrets are omitted; encrypted full secret backup is intentionally not implemented.

### 10. Public Settings Endpoint
- Priority: P1
- Area: Public API and frontend
- Problem: Frontend needed safe runtime feature visibility without exposing secrets.
- Files changed: `PublicSettingsController.php`, `routes/api.php`, `public-settings.ts`, checkout page
- Fix summary: Added `GET /api/public-settings` with safe non-secret settings and frontend client fallback behavior.
- Test result: `PublicReadinessEndpointsTest` passed.
- Status: Done
- Notes: Endpoint omits Azure, mail, NZ Post, database, path, and admin-only secrets.

### 11. Demo Cleanup Tool
- Priority: P1
- Area: Admin system tools
- Problem: Demo content should be removable before production without deleting real data.
- Files changed: `DemoCleanup.php`, translations
- Fix summary: Added admin-only cleanup for explicitly marked demo community posts and comments, with counts and confirmation.
- Test result: Syntax checks passed.
- Status: Partially Done
- Notes: Orders, leads, media, users, and CMS content are not deleted unless future seeders mark them safely as demo.

### 12. Media Migration Planning and Scan Tool
- Priority: P1
- Area: Media operations
- Problem: Operators need visibility before storage migration.
- Files changed: `MediaStorageScan.php`, `MediaStorageScanController.php`, `routes/web.php`, translations
- Fix summary: Added media disk counts, missing file scan, scan export, and dry-run local/Azure migration actions.
- Test result: Syntax checks passed.
- Status: Partially Done
- Notes: Actual file movement is intentionally deferred.

### 13. Runtime CORS / Sanctum / Frontend URL Handling
- Priority: P1
- Area: Runtime policy and handover
- Problem: Operators need clarity on bootstrap-only versus runtime URL settings.
- Files changed: `SystemHandoverReadiness.php`, `ENV_AND_RUNTIME_SETTINGS_POLICY.md`
- Fix summary: Handover now displays effective frontend URL, CORS origins, Sanctum domains, and consistency warnings; docs clarify restart-required env values.
- Test result: Syntax checks passed.
- Status: Done
- Notes: Database-managed CORS was not added to avoid auth/session regressions.

### 14. Installer Step Wizard
- Priority: P1
- Area: Installer UI
- Problem: Installer needed clearer step flow and recovery copy.
- Files changed: `resources/views/install/index.blade.php`, translations
- Fix summary: Added localized eight-step progress, clearer sections, mobile-friendly layout, lock/error notices, and non-secret value preservation.
- Test result: Syntax checks passed.
- Status: Partially Done
- Notes: Per-step server-side wizard pages were not added; the safe single-page install flow remains.

### 15. System / Handover Readiness
- Priority: P1
- Area: Admin system readiness
- Problem: Readiness page needed broader operational checks.
- Files changed: `SystemHandoverReadiness.php`, translations
- Fix summary: Added URL, CORS/Sanctum, database, storage, latest storage/mail test, locks, admin account, demo count, public settings, health, PHP/Laravel, writable dirs, queue/cache/session, failed jobs, and migration checks.
- Test result: Syntax checks passed.
- Status: Done
- Notes: Secrets are never displayed.

### 16. Frontend Runtime Feature Handling
- Priority: P1
- Area: Frontend
- Problem: Frontend should respect safe runtime feature flags.
- Files changed: `public-settings.ts`, checkout page, frontend messages
- Fix summary: Checkout reads public settings and disables store/guest checkout only when the endpoint explicitly says disabled.
- Test result: Frontend JSON parse passed; full TypeScript/build pending final validation.
- Status: Partially Done
- Notes: Store/community/B2B/funding visibility outside checkout remains a future pass.

### 17. Admin Role / Permission Hardening
- Priority: P1
- Area: Filament access
- Problem: High-risk settings/tools should be admin-only.
- Files changed: high-risk Filament page classes, admin controllers
- Fix summary: Storage, app, feature, NZ Post, tax, email, import/export, demo cleanup, media scan, and handover pages use `PanelAccess::isAdmin()`; export controllers also abort unless admin.
- Test result: Existing admin access tests remain in suite.
- Status: Done
- Notes: There is no separate `super_admin` role in the current role enum.

### 18. Email Center Runtime Settings Integration
- Priority: P1
- Area: Email Center
- Problem: Email settings needed consistent SettingsService sync/audit and no secret exposure.
- Files changed: `EmailSettings.php`, `MailSettingsService.php`
- Fix summary: Email settings sync mailer, SMTP, sender, reply-to, admin recipients, timeout, queue flag, and secrets to runtime settings through SettingsService with masked audit.
- Test result: Syntax checks passed.
- Status: Done
- Notes: SMTP password/API key are encrypted and never logged plaintext.

## P2

### 19. Health Check APIs
- Priority: P2
- Area: Public API
- Problem: Safe operational health endpoints were needed.
- Files changed: `HealthController.php`, `routes/api.php`
- Fix summary: Added `GET /api/health`, `/api/health/database`, `/api/health/storage`, `/api/health/mail`; Laravel `/up` remains configured.
- Test result: `PublicReadinessEndpointsTest` passed.
- Status: Done
- Notes: Payloads expose status only.

### 20. Maintenance / Public Notice Setting
- Priority: P2
- Area: Runtime settings
- Problem: Operators need a soft public notice setting.
- Files changed: `FeatureFlags.php`, `DefaultAppSettingsSeeder.php`, `PublicSettingsController.php`
- Fix summary: Added `maintenance.notice_enabled`, `maintenance.notice_message`, and `maintenance.notice_level` runtime settings and public endpoint output.
- Test result: Public settings test passed.
- Status: Partially Done
- Notes: A frontend banner was not added in this sprint.

### 21. Settings Versioning / Snapshot
- Priority: P2
- Area: Settings backup
- Problem: Named snapshots would help compare/restore state.
- Files changed: `SettingsBackupImport.php`, docs
- Fix summary: Implemented export/import/reset as the safe baseline.
- Test result: Syntax checks passed.
- Status: Blocked
- Notes: Named snapshots and diff/restore are documented as future improvement.

### 22. Media Migration Implementation
- Priority: P2
- Area: Media operations
- Problem: Actual storage migration can be destructive if rushed.
- Files changed: `MediaStorageScan.php`, `MediaStorageScanController.php`, docs
- Fix summary: Added scan/export/dry-run only.
- Test result: Syntax checks passed.
- Status: Partially Done
- Notes: No files are moved or deleted automatically.

### 23. Better Demo Data Management
- Priority: P2
- Area: Seeders
- Problem: Demo cleanup requires reliable demo markers.
- Files changed: `DemoCleanup.php`, docs
- Fix summary: Cleanup only acts on existing explicit demo markers.
- Test result: Syntax checks passed.
- Status: Partially Done
- Notes: Broad seed data rewriting was not done late in delivery.

### 24. Final Demo Checklist
- Priority: P2
- Area: Documentation
- Problem: Demo operators need one pass/fail script.
- Files changed: `docs/DEMO_READINESS_CHECKLIST.md`
- Fix summary: Added frontend, admin, install, storage, checkout, community, and handover demo checklist.
- Test result: Documentation review.
- Status: Done
- Notes: Use this during the final demo run.

### 25. Deployment / Handover Package Guide
- Priority: P2
- Area: Documentation
- Problem: Delivery package contents and exclusions needed to be explicit.
- Files changed: `docs/HANDOVER_PACKAGE_GUIDE.md`
- Fix summary: Added zip contents, exclusions, install wizard URL, local/Azure deployment notes, migration notes, settings export/import, demo cleanup, admin account setup, and troubleshooting.
- Test result: Documentation review.
- Status: Done
- Notes: Do not include `.env`, uploaded secrets, node/vendor folders, or local logs in client zip.
