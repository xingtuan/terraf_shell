# QA Checklist

Last updated: 2026-05-07

## Required Checks

| Check | Status | Notes |
|---|---|---|
| Product public API still works | Pass | Covered by `ProductCatalogTest` and full suite |
| Product admin resource can create/update product | Pass | `AdminOperationsCenterTest` |
| Order creation still works | Pass | Covered by `StoreOrderFlowTest` |
| Order admin status update works | Pass | `AdminOperationsCenterTest` table action |
| Lead submission appears in admin | Pass | Business, sample, and partnership leads visible in All Leads |
| Inquiry submission appears in admin | Pass | Legacy enquiry visible in General Enquiries |
| Sample request appears in admin or unified Lead Center | Pass | Unified Lead Center |
| Partnership inquiry appears in admin or unified Lead Center | Pass | Unified Lead Center |
| Community post with funding URL is stored and manageable | Pass | Safe external funding URL accepted and visible in admin |
| Unsafe funding URL protocols rejected | Pass | `javascript:` rejected |
| Email event/template models work | Pass | KO template and event model test |
| Korean language files load | Pass | Core admin translation key test |
| Admin translation keys avoid missing-key output for core pages | Pass | Core key loop in EN/KO/ZH |
| New admin resources are reachable by admin | Pass | Carts, addresses, media files, handover readiness |
| New admin-only resources block moderator access | Pass | Access resource test |

## Command Results

```text
php artisan test
PASS: 134 tests, 1027 assertions

vendor/bin/pint
PASS: formatting completed

php artisan optimize:clear
PASS: config, cache, compiled, events, routes, views, blade-icons, and filament caches cleared
```
