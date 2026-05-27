# Documentation Center

This directory contains the delivery documentation for OXP / Terraf Shell. The root [README.md](/README.md) is the main entry point. The top-level files in this directory are the authoritative installation, deployment, maintenance, and module references.

## Recommended Reading Order

1. [Installation](INSTALLATION.md): automated installation script, manual setup, local development, and installation errors.
2. [Deployment](DEPLOYMENT.md): production services, Nginx, PHP-FPM, frontend service, queue, scheduler, SSL, and updates.
3. [Configuration](CONFIGURATION.md): `.env`, runtime admin settings, precedence, and cache refresh.
4. [Admin Guide](ADMIN_GUIDE.md): Filament admin modules and operational workflows.
5. [User Guide](USER_GUIDE.md): user-facing site workflows.
6. [Shop](SHOP.md): products, SKU, stock, cart, checkout, GST, shipping, and orders.
7. [Community](COMMUNITY.md): posts, attachments, funding links, comments, reports, moderation, and notifications.
8. [Storage](STORAGE.md): local storage, Azure Storage, URLs, uploads, and switching.
9. [I18N](I18N.md): English, Chinese, and Korean translation structure and update rules.
10. [Troubleshooting](TROUBLESHOOTING.md): installation, deployment, storage, store, admin, i18n, and HTTP errors.
11. [Maintenance](MAINTENANCE.md): updates, backups, logs, cache clearing, rebuilds, and safe script reruns.

## Trilingual Manuals

`docs/en`, `docs/zh`, and `docs/ko` are retained as customer and operator manuals in their respective languages. When they mention installation, deployment, script parameters, or environment variables, the top-level documents remain authoritative.

## Historical Audit And QA Documents

The following files are retained as delivery and testing records. They do not replace the current installation or deployment documentation:

- `ADMIN_LOCALIZATION_QA.md`
- `ADMIN_OPERATIONS_GUIDE.md`
- `ADMIN_SETTINGS_GUIDE.md`
- `BACKEND_ADMIN_AUDIT.md`
- `DELIVERY_READINESS_CHECKLIST.md`
- `FINAL_DELIVERY_FIX_TRACKER.md`
- `GUEST_CHECKOUT_QA.md`
- `HANDOVER_PACKAGE_GUIDE.md`
- `HANDOVER_READINESS.md`
- `INITIAL_CONTENT_POLICY.md`
- `QA_CHECKLIST.md`
- `RUNTIME_SETTINGS_AUDIT.md`
- `SHIPPING_SETTINGS_QA.md`
- `WEB_INSTALLER_GUIDE.md`

If any historical record conflicts with `README.md`, `INSTALLATION.md`, `DEPLOYMENT.md`, or `CONFIGURATION.md`, use the current top-level documentation and code as the source of truth.

## Maintenance Rules

- Script, environment variable, or port changes must update `README.md`, `INSTALLATION.md`, and `DEPLOYMENT.md`.
- New admin settings must update `CONFIGURATION.md` and `ADMIN_GUIDE.md`.
- Store, order, GST, or shipping behavior changes must update `SHOP.md`.
- Community behavior changes must update `COMMUNITY.md`.
- Storage driver, URL, or upload path changes must update `STORAGE.md`.
- User-visible text changes must update the trilingual message files and the rules in `I18N.md`.
