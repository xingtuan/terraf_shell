# Demo Readiness Checklist

Run this on the final demo environment after `php artisan optimize:clear` and after the frontend build is deployed.

## Frontend

- [ ] Homepage loads in English.
- [ ] Language switch works for English, Korean, and Chinese.
- [ ] Material / OXP page loads.
- [ ] Store product list loads from `GET /api/products`.
- [ ] Featured products load from `GET /api/products/featured` when available.
- [ ] Product detail loads from `GET /api/products/{slug}`.
- [ ] Cart add/update/remove/clear works.
- [ ] Guest checkout submits and redirects to `/store/order-submitted/{order_number}?token=...`.
- [ ] Registered checkout submits and redirects to `/account/orders/{order_number}`.
- [ ] Guest order lookup works with order number and token.
- [ ] NZ-only shipping validation works.
- [ ] NZ Post address lookup and quote fall back safely when credentials are missing.
- [ ] B2B inquiry form submits to the live lead endpoint.
- [ ] Community feed and post detail load from live APIs.
- [ ] Funding link display respects the runtime feature flag where implemented.
- [ ] Media upload works for authenticated community posts.

## Admin

- [ ] Admin login works at `/admin`.
- [ ] Admin language switch works: English, Korean, Chinese.
- [ ] Product, variants, inventory, carts, addresses, orders, leads, CMS, Email Center, media, and handover pages load.
- [ ] Order list clearly shows guest versus registered orders.
- [ ] Lead admin shows submitted B2B/sample/partnership leads.
- [ ] Storage Settings page loads.
- [ ] Local storage test persists latest status.
- [ ] Azure test handles missing credentials without exposing secrets.
- [ ] System / Handover Readiness page loads and shows OK/Warning/Error badges.
- [ ] Settings export downloads non-secret JSON.
- [ ] Settings import rejects unknown or secret keys.
- [ ] Demo Cleanup shows counts and does not delete protected data.
- [ ] Media Storage Scan shows disk counts and exports a report.

## Installer And Package

- [ ] `/install` loads before installation.
- [ ] Installer blocks duplicate submissions with `installing.lock`.
- [ ] Installer backs up `.env` before writing.
- [ ] Installer redirects to `/admin` only after success.
- [ ] `/install` returns unavailable after `installed.lock`.
- [ ] Handover zip excludes `.env`, logs, cache, `node_modules`, `vendor`, and uploaded private media.
- [ ] `docs/HANDOVER_PACKAGE_GUIDE.md` is included.
