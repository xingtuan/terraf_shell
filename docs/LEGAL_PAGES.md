# Legal Pages

Last updated: 2026-05-11

## Implemented Routes

- `/en/privacy`, `/ko/privacy`, `/zh/privacy`
- `/en/terms`, `/ko/terms`, `/zh/terms`

Footer links now point to the localized privacy policy and terms routes instead of placeholder anchors.

## Content Scope

The privacy policy covers account, checkout, guest checkout, community upload, B2B inquiry, cookie/analytics, service provider, access/correction, retention, children, contact, and policy-change topics.

The terms page covers account use, guest checkout, product availability, manual payment orders, shipping, returns/refunds placeholder language, B2B samples, community submissions, funding/external links, user content, IP, prohibited conduct, third-party links, disclaimer, liability, changes, and contact topics.

## Production Requirement

These pages are business-appropriate general website copy. They are not a substitute for legal advice. Before production launch, OXP/Terraf should have the privacy policy, terms, returns/refunds statement, jurisdiction-specific disclosures, and contact details reviewed by qualified legal counsel.

## Localization

The content is stored in the frontend message dictionaries:

- `B2C_frontend/messages/en.json`
- `B2C_frontend/messages/ko.json`
- `B2C_frontend/messages/zh.json`

Keep the EN / KO / ZH key structure identical. Run `corepack pnpm exec node scripts/check-i18n-keys.mjs` after edits.
