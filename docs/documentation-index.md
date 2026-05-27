# Documentation Index

This file lists current documentation status for delivery, maintenance, and future updates.

## Current Authoritative Documents

| Document | Status | Purpose |
| --- | --- | --- |
| [../README.md](../README.md) | Current | Project entry point, stack, feature overview, quick start, and documentation index |
| [INSTALLATION.md](INSTALLATION.md) | Current | Automated installer, manual setup, local development, and installation errors |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Current | Production services, SSL, updates, and rollback notes |
| [ADMIN_GUIDE.md](ADMIN_GUIDE.md) | Current | Admin panel modules and operations |
| [USER_GUIDE.md](USER_GUIDE.md) | Current | User-facing workflows |
| [CONFIGURATION.md](CONFIGURATION.md) | Current | `.env`, runtime settings, precedence, and cache |
| [STORAGE.md](STORAGE.md) | Current | Local / Azure storage, URLs, uploads, and switching |
| [SHOP.md](SHOP.md) | Current | Products, SKU, stock, cart, checkout, GST, shipping, and orders |
| [COMMUNITY.md](COMMUNITY.md) | Current | Posts, attachments, funding links, reports, moderation, and notifications |
| [I18N.md](I18N.md) | Current | EN / ZH / KO structure and checks |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Current | Installation, deployment, storage, HTTP errors, queue, and scheduler troubleshooting |
| [MAINTENANCE.md](MAINTENANCE.md) | Current | Updates, backups, logs, cache clearing, rebuilds, and safe reruns |

## Trilingual Manuals

| Directory | Status | Purpose |
| --- | --- | --- |
| [en](en) | Operator manual | English customer / operator manual |
| [zh](zh) | Operator manual | Chinese customer / operator manual |
| [ko](ko) | Operator manual | Korean customer / operator manual |

The top-level documents are authoritative for deployment, script parameters, and environment variables.

## Historical And Special-Purpose Documents

| Document | Status | Notes |
| --- | --- | --- |
| [INITIAL_CONTENT_POLICY.md](INITIAL_CONTENT_POLICY.md) | Current policy | Starter content policy |
| [ENV_AND_RUNTIME_SETTINGS_POLICY.md](ENV_AND_RUNTIME_SETTINGS_POLICY.md) | Policy reference | Environment and runtime setting policy |
| [WEB_INSTALLER_GUIDE.md](WEB_INSTALLER_GUIDE.md) | Auxiliary | Web Installer guide |
| [HANDOVER_PACKAGE_GUIDE.md](HANDOVER_PACKAGE_GUIDE.md) | Handover reference | Handover package organization |
| [HANDOVER_READINESS.md](HANDOVER_READINESS.md) | Handover reference | Handover readiness checks |
| [DELIVERY_READINESS_CHECKLIST.md](DELIVERY_READINESS_CHECKLIST.md) | QA reference | Delivery checklist |
| [QA_CHECKLIST.md](QA_CHECKLIST.md) | QA reference | Test checklist |
| [GUEST_CHECKOUT_QA.md](GUEST_CHECKOUT_QA.md) | QA reference | Guest Checkout QA record |
| [SHIPPING_SETTINGS_QA.md](SHIPPING_SETTINGS_QA.md) | QA reference | Shipping Settings QA record |
| [ADMIN_LOCALIZATION_QA.md](ADMIN_LOCALIZATION_QA.md) | QA reference | Admin localization checks |
| [RUNTIME_SETTINGS_AUDIT.md](RUNTIME_SETTINGS_AUDIT.md) | Audit reference | Runtime settings audit |
| [BACKEND_ADMIN_AUDIT.md](BACKEND_ADMIN_AUDIT.md) | Audit reference | Backend admin audit |
| [LATEST_BACKEND_ADMIN_AUDIT.md](LATEST_BACKEND_ADMIN_AUDIT.md) | Audit reference | Latest backend audit record |
| [FINAL_DELIVERY_FIX_TRACKER.md](FINAL_DELIVERY_FIX_TRACKER.md) | Historical record | Final delivery fix tracker |
| [known-issues.md](known-issues.md) | Current | Known issues and boundaries |
| [handover-checklist.md](handover-checklist.md) | Current | Pre-handover checklist |

## Cleaned-Up Stale Items

- Old frontend variable `NEXT_PUBLIC_API_URL` is replaced by `NEXT_PUBLIC_API_BASE_URL`.
- Deployment default path follows `auto_deploy.sh`: `/var/www/terraf_shell`.
- Old server IP addresses should not appear in docs or default test config.
- Redis is optional, not a default requirement; cache / queue / session use database drivers by default.
- Seed data is described as formal starter content, not disposable temporary content.
- Stock deduction, funding link display, and admin localization are documented according to current code.
