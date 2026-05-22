# Legal Pages

Last updated: 2026-05-19

## Implemented Routes

- `/en/privacy`, `/ko/privacy`, `/zh/privacy`
- `/en/terms`, `/ko/terms`, `/zh/terms`

Footer links now point to the localized privacy policy and terms routes instead of placeholder anchors.

## Content Scope

The privacy policy covers account, checkout, guest checkout, community upload, B2B inquiry, cookie/analytics, service provider, access/correction, retention, children, contact, and policy-change topics.

The terms page covers account use, guest checkout, product availability, manual payment orders, shipping, returns/refunds placeholder language, B2B review packs, community submissions, funding/external links, user content, IP, prohibited conduct, third-party links, disclaimer, liability, changes, and contact topics.

## Production Requirement

These pages are business-appropriate general website copy. They are not a substitute for legal advice. Before production launch, OXP/Terraf should have the privacy policy, terms, returns/refunds statement, jurisdiction-specific disclosures, and contact details reviewed by qualified legal counsel.

## Localization

The default content is stored in the frontend message dictionaries:

- `B2C_frontend/messages/en.json`
- `B2C_frontend/messages/ko.json`
- `B2C_frontend/messages/zh.json`

Admins can override privacy policy and terms content in the backend via **CMS / Website Content -> Legal Pages**. Overrides are saved in `app_settings` under `legal.*` keys and exposed through `GET /api/legal-pages/{privacy|terms}?locale=en`.

Frontend legal routes merge backend overrides with the static dictionaries. Empty backend fields continue to use the frontend defaults, and an edited rich-text body replaces the default section list for that page and locale.

Keep the EN / KO / ZH dictionary key structure identical when editing defaults. Run `corepack pnpm exec node scripts/check-i18n-keys.mjs` after frontend dictionary edits.
