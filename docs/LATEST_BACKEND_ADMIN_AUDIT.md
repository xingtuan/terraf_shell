# Latest Backend/Admin Audit

Date: 2026-05-08

Scope checked: `routes/api.php`, backend models/controllers/requests/resources/services, `app/Services/Shipping`, `app/Services/Store`, Filament resources/pages/widgets/provider, backend admin locale files, root/backend/frontend READMEs, frontend API clients, and checkout pages.

## A. Already Implemented And Working-Looking

- Product public API: `GET /api/products`, `GET /api/products/featured`, `GET /api/products/{slug}`, and `GET /api/product-categories` are present and used by `B2C_frontend/lib/api/products.ts`.
- Product admin: existing Filament product, product category, product image, product variant, product attribute definition/value, and inventory resources are present.
- Product variants and attributes: variants are modeled, surfaced in API resources, used by cart/order snapshots, and managed in admin.
- Inventory adjustment: `InventoryAdjustment` flow exists through `ProductVariant::adjustStock()` and the variant/inventory admin actions.
- Cart and address admin: existing Carts and Addresses resources load read-only operational views.
- Guest checkout: `POST /api/orders` is public, `StoreOrderRequest` requires `guest_email` only for guests, and `OrderResource` returns `guest_order_token`.
- Registered checkout: authenticated users can checkout with manual shipping fields or saved NZ addresses.
- Order admin: existing `OrderResource` supports review, manual payment status, internal notes, status actions, timeline fields, and email dispatch hooks.
- Shipping quote: `/api/store/shipping-options` exists and calculates fallback NZ shipping plus GST/tax snapshots.
- NZ Post address lookup: `/api/store/address-search` and `/api/store/address-details` exist with safe fallback data when credentials are unavailable.
- Tax/GST calculation: `TaxService` and `ShippingQuoteService` use configured GST rate and included/excluded behavior.
- B2B leads, enquiries, partnership inquiries, and material requests: public submission endpoints and admin lead/enquiry resources exist.
- Community moderation: reports, posts/comments moderation, moderation logs, user violations, moderation settings, and queue pages exist.
- Funding campaign links: external funding metadata/resources exist; no internal payment/pledge processing is attempted.
- Media files: central media tracking and admin media library resource exist.
- Email Center: settings, events, templates, logs, preview/test commands, and localized template support exist.
- CMS materials: materials, specs, story sections, applications, articles, and home sections resources exist.
- OXP/CXP/MXP material family content: material CMS structure supports localized content and seeded material proof fields; content accuracy still needs client editorial QA.
- System / Handover page: readiness checks page exists.
- Admin localization: `lang/en/admin.php`, `lang/ko/admin.php`, and `lang/zh/admin.php` now have matching key structures; admin locale switcher persists in session.

## B. Implemented But Incomplete

- Shipping Settings admin page is read-only because shipping values currently come from config/env. This is appropriate for secrets, but non-secret DB editing would require a future `ShippingSetting` model/table.
- Payment handling is manual-payment order request management only. There is no payment gateway by design.
- Funding campaigns are external-link/admin metadata only. There is no fundraising platform by design.
- Admin localization was improved in priority Store/Order/Variant/Inventory/Cart/Address/Media/Email/Widget areas, but some lower-priority Filament CMS/community strings remain hardcoded and should be cleaned in a later localization pass.
- Product catalog has both newer controller logic and an older `ProductCatalogService`; verify no stale service path is reintroduced.

## C. Implemented But Needs QA

- Full browser QA for Filament pages: products, variants, inventory, carts, addresses, orders, leads, enquiries, community moderation, Email Center, media library, System / Handover, and Shipping Settings.
- Live NZ Post credentials: fallback behavior is covered, but real NZ Post address/rate responses need environment-specific testing.
- Email delivery: admin test email/logging exists, but real SMTP/provider delivery requires configured credentials.
- Production database migration order and seed data should be verified on a clean MySQL database.
- Frontend checkout should be smoke-tested in browser for guest and registered flows after backend URL/cookie domain settings are configured.

## D. Missing

- Full payment gateway integration.
- Full internal fundraising/pledge processing.
- Editable DB-backed shipping settings.
- Automatic translation of user-generated content. This is intentionally not implemented.
- Live frontend `lib/api/community.ts` replacement for remaining community idea-card mock boundary.

## E. Documentation Outdated

- Root README incorrectly said product catalog was mock-only. Updated to document live product/cart/checkout/shipping APIs.
- Backend README commerce endpoints were missing live guest checkout, guest order lookup, shipping quote, NZ Post lookup, and admin shipping settings notes. Updated.
- Frontend README still described `products.ts` as mock-only and checkout as future work. Updated to document live product catalog, shipping client, and guest/registered checkout.

## Specific Area Matrix

| Area | Audit Result |
|---|---|
| Product public API | Implemented and frontend-wired |
| Product admin | Implemented; priority labels localized |
| Product variants | Implemented; admin action labels localized |
| Product attributes | Implemented; needs continued QA |
| Inventory adjustment | Implemented; stock adjustment action localized |
| Cart admin | Implemented; guest/abandoned labels localized |
| Address admin | Implemented; section labels localized |
| Guest checkout | Implemented; token nullable and guest lookup exists |
| Registered checkout | Implemented |
| Order admin | Implemented; priority labels/actions localized |
| Shipping quote | Implemented with fallback |
| NZ Post address lookup | Implemented with fallback |
| Tax/GST calculation | Implemented |
| B2B leads | Implemented |
| Enquiries | Implemented |
| Partnership inquiries | Implemented |
| Material requests | Implemented |
| Community moderation | Implemented |
| Funding campaign links | Implemented as external links |
| Media files | Implemented; priority labels localized |
| Email Center | Implemented; settings labels partially localized |
| CMS materials | Implemented |
| Material family content | Implemented structurally; editorial QA needed |
| System / Handover page | Implemented |
| Admin localization files | Implemented with automated key parity check |

## Verification Run

- `php artisan optimize:clear` passed.
- `php artisan migrate` passed with nothing pending.
- `php artisan admin:check-translations` passed.
- `php artisan test` passed: 134 tests, 1035 assertions.
- `vendor/bin/pint` passed and formatted PHP files.
- `corepack pnpm exec tsc --noEmit` passed.
- `node scripts/check-i18n-keys.mjs` passed.
- `corepack pnpm test` passed: 22 frontend tests.
- `corepack pnpm build` passed. The existing i18n diff warning still reports six intentionally identical values/placeholders (`sibling`, `inactive`, and `https://company.com`).
