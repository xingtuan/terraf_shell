# Admin Guide

The admin panel is a Laravel Filament application:

```text
http://your-domain-or-ip:8000/admin
```

The admin UI supports English, Chinese, and Korean locale switching from the user menu.

## Login And Accounts

When seeders are enabled, the default admin is:

- Email: `admin@example.com`
- Password: `password`

This account is for initialization and acceptance checks only. Change its password or disable it before production handover.

## Dashboard

Use the dashboard to monitor orders, community moderation, reports, content status, system settings, mail, storage, queue, and handover readiness.

## Content Management

Admin content modules cover:

- Homepage sections.
- Page sections.
- Material content, specifications, applications, and story sections.
- Articles / CMS content.
- Legal pages.
- Brand, logo, contact email, and site URLs.

Frontend pages read this content through APIs. If changes do not appear immediately:

```bash
cd B2C_backend
php artisan optimize:clear
```

## Page Sections

When editing page sections, check:

- English, Chinese, and Korean title / summary / body.
- Sort order.
- Publish status.
- Media URL availability under the current storage driver.

## Products And SKU

Store administration includes:

- Product Categories.
- Products.
- Product Variants.
- Product Attribute Definitions / Values.
- Product Images.
- Inventory and inventory logs.

Orders deduct stock for variants with a deny inventory policy and a non-null stock quantity. Pending order cancellation restores stock. Do not update stock directly in the database unless performing a controlled maintenance operation.

## Orders

The order module supports guest and registered-user orders. Admins can view and update:

- Order status.
- Payment status.
- Shipment information.
- Tracking number.
- Internal notes.
- GST, shipping, and totals.

The system does not include an online payment gateway. Payment state is maintained manually according to the operational payment process.

Order statuses:

- `pending`
- `confirmed`
- `processing`
- `shipped`
- `delivered`
- `cancelled`

Payment statuses:

- `unpaid`
- `paid`
- `refunded`

## Shipping And GST

Admin settings include:

- Tax Settings: GST enabled, GST rate, tax-inclusive pricing, tax label.
- Shipping Settings: NZ-only, origin city/postcode, free-shipping threshold, standard / express / rural rates, quote source.
- NZ Post Settings: API credentials and quote/address service settings.

Clear cache if saved settings do not take effect:

```bash
cd B2C_backend
php artisan optimize:clear
```

## Community Management

Community modules include:

- Posts.
- Comments.
- Reports.
- Tags and categories.
- Moderation Queue.
- User Violations.
- User Notifications.
- Moderation Logs.
- Admin Action Logs.
- Funding Campaigns.
- Community Settings.
- Community Moderation Settings.

Posts may contain cover images, rich text JSON, attachments, external 3D links, and funding URLs. Moderation policies and sensitive-word handling are controlled from Community Settings.

## Reports And User Restrictions

Report handling should include:

1. Review the reported content and context.
2. Record the moderation action.
3. Restrict the user when required.
4. Notify affected users when appropriate.

Account state repair can be dry-run first:

```bash
cd B2C_backend
php artisan users:repair-account-status --dry-run
```

## Notifications

Order, community, moderation, and interaction notifications depend on the queue worker.

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## Email Center

Email Center includes events, templates, logs, previews, and test send commands.

```bash
cd B2C_backend
php artisan email:center:seed
php artisan email:center:preview order.created --locale=en
php artisan email:center:test admin@example.com
```

Production should use SMTP or another real mail driver, not long-term `MAIL_MAILER=log`.

## Brand And Logo

Application Settings controls site name, frontend URL, admin brand, default locale, timezone, contact email, support email, and logo upload.

Logo files use the current storage driver. After storage switching, verify the logo URL.

## Storage Settings

Storage Settings supports:

- Local public storage.
- Azure Blob Storage.
- Storage link checks and creation.
- Azure connection test.
- Upload test.
- Recent driver rollback.
- Media scan export.

Media scan exports findings only. It does not bulk-migrate files.

## Feature Flags

Feature Flags control B2C Store, B2B Inquiry, Community, Funding Links, Guest Checkout, maintenance mode, and multilingual maintenance messages.

## Initial Data Policy

Seed data is treated as starter delivery content, not disposable temporary content. After production handover:

- Keep required starter content.
- Remove or replace sample users, posts, and test records.
- Change the seeded admin password.
- Use `RUN_SEED=0` for normal production updates.

## Handover Checks

Before handover, verify admin accounts, mail, storage, GST, shipping, legal pages, queue, scheduler, and key frontend pages in all supported languages.
