# Shipping Settings QA

Date: 2026-05-08

## Fixed

- Added Filament page `App\Filament\Pages\ShippingSettings` under Store Operations.
- Page shows NZ-only shipping notice.
- Page shows origin city, origin postcode, origin country, free-shipping threshold, fallback standard/express amounts, rural surcharge, default package weight fallback, GST label/rate, and NZ Post base URL.
- Page shows NZ Post enabled/configured status without exposing API keys or secrets.
- Page includes safe test actions for address lookup and shipping quote.
- Added EN / KO / ZH admin translation keys for the page.
- Added admin resource access test coverage for `/admin/shipping-settings`.
- `php artisan test` passed, including the admin access coverage.

## Current Design

Shipping settings are read-only because the current implementation is config/env based. This avoids exposing secrets and avoids creating an unnecessary settings table before operational requirements are clear.

## Still Needs Attention

- Live NZ Post API behavior must be tested in an environment with valid credentials.
- A future `ShippingSetting` model/table can be added if operators need editable non-secret shipping values from the admin UI.
- Browser QA should confirm the test actions render notifications correctly in Filament.
