# QA Checklist

Last updated: 2026-05-08

## Required Checks

| Check | Status | Notes |
|---|---|---|
| Product public API still works | Pass | Covered by `ProductCatalogTest` and full suite |
| Product admin resource can create/update product | Pass | `AdminOperationsCenterTest` |
| Order creation still works | Pass | Covered by `StoreOrderFlowTest` |
| Order admin status update works | Pass | `AdminOperationsCenterTest` table action |
| Lead submission appears in admin | Pass | Business, material, and partnership leads visible in All Leads |
| Inquiry submission appears in admin | Pass | Legacy enquiry visible in General Enquiries |
| Material request appears in admin or unified Lead Center | Pass | Unified Lead Center |
| Partnership inquiry appears in admin or unified Lead Center | Pass | Unified Lead Center |
| Community post with funding URL is stored and manageable | Pass | Safe external funding URL accepted and visible in admin |
| Unsafe funding URL protocols rejected | Pass | `javascript:` rejected |
| Email event/template models work | Pass | KO template and event model test |
| Korean language files load | Pass | Core admin translation key test |
| Admin translation keys avoid missing-key output for core pages | Pass | Core key loop in EN/KO/ZH |
| New admin resources are reachable by admin | Pass | Carts, addresses, media files, handover readiness |
| New admin-only resources block moderator access | Pass | Access resource test |
| Admin EN/KO/ZH translation keys have identical structure | Pass | `AdminTranslationKeysTest` |
| Settings audit log masks secrets | Pass | `SettingsServiceTest` |
| Public settings endpoint excludes secrets | Pass | `PublicReadinessEndpointsTest` |
| Health endpoints return safe status payloads | Pass | `PublicReadinessEndpointsTest` |
| Guest media upload route remains registered and is runtime gated | Pass | `MediaUploadTest` |
| Guest checkout route remains registered and is runtime gated | Pass | `StoreOrderFlowTest` |
| Storage test results persist | Pending manual | Verify from Storage Settings after test actions |
| Settings export/import validates safely | Pending manual | Verify in admin with non-secret JSON |
| initial content review preserves production data | Pending manual | Confirm initial content remains editable through standard resources |
| Media scan export works | Pending manual | Verify downloaded JSON report |

## Command Results

```text
php artisan test
PASS: 134 tests, 1027 assertions

vendor/bin/pint
PASS: formatting completed

php artisan optimize:clear
PASS: config, cache, compiled, events, routes, views, blade-icons, and filament caches cleared

2026-05-08 final pass:
php artisan optimize:clear
PASS

php artisan migrate:fresh --seed
TIMED OUT at 180s after DefaultAppSettingsSeeder output; follow-up verification showed all migrations ran and seed data exists (`users=11 products=8 settings=71`).

php artisan test
PASS: 153 tests, 1130 assertions

vendor/bin/pint
PASS: formatting completed

node scripts/check-i18n-keys.mjs
PASS: all frontend locale keys present

corepack pnpm exec tsc --noEmit
PASS

corepack pnpm build
PASS with existing i18n-diff warnings for six intentionally identical values (`sibling`, `inactive`, and `https://company.com`).
```
