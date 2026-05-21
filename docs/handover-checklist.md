# OXP Platform — Handover Checklist

**Project**: OXP / Terraf  
**Date**: May 2026  
**Purpose**: Pre-launch verification checklist. Complete all items before handing the platform to the client for live operation.

---

## How to Use This Checklist

- **OK**: Item verified and complete.
- **Warning**: Item requires attention before launch but is not blocking.
- **Error**: Item must be resolved before launch — do not go live with unresolved Errors.

Work through all sections with the handover team. Sign off each section when complete.

---

## 1. Infrastructure

| # | Item | Status | Notes |
|---|---|---|---|
| 1.1 | Production server provisioned with PHP 8.4+ and required PHP extensions, including `intl` | ☐ | |
| 1.2 | MySQL 8.0+ database accessible and configured | ☐ | |
| 1.3 | Azure Blob Storage account created and container provisioned | ☐ | |
| 1.4 | SSL/TLS certificates installed on all production domains | ☐ | |
| 1.5 | Backend deployed and accessible at production URL | ☐ | |
| 1.6 | Frontend deployed and accessible at production URL | ☐ | |
| 1.7 | Domain DNS records configured correctly | ☐ | |
| 1.8 | Queue worker running with process manager (Supervisor recommended) | ☐ | |
| 1.9 | Scheduler configured to run `php artisan schedule:run` every minute | ☐ | |

---

## 2. Environment Configuration

| # | Item | Status | Notes |
|---|---|---|---|
| 2.1 | `APP_ENV=production` set in backend `.env` | ☐ | |
| 2.2 | `APP_DEBUG=false` set in backend `.env` | ☐ | |
| 2.3 | `APP_KEY` generated and set | ☐ | |
| 2.4 | `APP_URL` set to production backend URL | ☐ | |
| 2.5 | `FRONTEND_URL` set to production frontend URL | ☐ | |
| 2.6 | `CORS_ALLOWED_ORIGINS` set to production frontend URL only | ☐ | |
| 2.7 | `SANCTUM_STATEFUL_DOMAINS` includes production frontend domain | ☐ | |
| 2.8 | Database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) configured | ☐ | |
| 2.9 | Azure storage credentials (`AZURE_STORAGE_NAME`, `AZURE_STORAGE_KEY`, etc.) configured | ☐ | |
| 2.10 | `STORAGE_DISK` / `MEDIA_DRIVER` set to the confirmed storage choice (`azure` or `public`) | ☐ | |
| 2.11 | Frontend `.env.production` has correct `NEXT_PUBLIC_API_URL` | ☐ | |
| 2.12 | No development/debug secrets or placeholder values remaining in production `.env` | ☐ | |
| 2.13 | Media upload size/type limits reviewed (`MEDIA_IMAGE_MAX_FILE_SIZE_KB`, `MEDIA_ATTACHMENT_MAX_FILE_SIZE_KB`) | ☐ | |
| 2.14 | `php artisan deploy:verify` returns no Errors | ☐ | |

---

## 3. Database

| # | Item | Status | Notes |
|---|---|---|---|
| 3.1 | `php artisan migrate --force` completed successfully on production | ☐ | |
| 3.2 | Migration status clean: `php artisan migrate:status` shows all migrations as "Ran" | ☐ | |
| 3.3 | Initial admin user account created | ☐ | |
| 3.4 | Admin user password changed from any default/seeded value | ☐ | |
| 3.5 | Demo content removed via **Admin Panel → System → Demo Data Cleanup** | ☐ | |
| 3.6 | Database backup process configured and first backup taken | ☐ | |

---

## 4. Email

| # | Item | Status | Notes |
|---|---|---|---|
| 4.1 | SMTP server credentials configured in **Admin Panel → System → Email Settings** | ☐ | |
| 4.2 | Test email sent and received successfully | ☐ | |
| 4.3 | `MAIL_FROM_ADDRESS` set to a real, authorized sender address | ☐ | |
| 4.4 | SPF/DKIM records configured for the sending domain | ☐ | |
| 4.5 | `MAIL_MAILER` is NOT `log` or `null` in production | ☐ | |
| 4.6 | Email templates reviewed | ☐ | |

---

## 5. Content

| # | Item | Status | Notes |
|---|---|---|---|
| 5.1 | Privacy Policy entered in all three languages (EN, KO, ZH) | ☐ | |
| 5.2 | Privacy Policy reviewed by legal counsel | ☐ | |
| 5.3 | Terms of Service entered in all three languages (EN, KO, ZH) | ☐ | |
| 5.4 | Terms of Service reviewed by legal counsel | ☐ | |
| 5.5 | "Last Updated" dates on legal pages set correctly | ☐ | |
| 5.6 | Real product catalog added and reviewed | ☐ | |
| 5.7 | Product content entered in all three languages | ☐ | |
| 5.8 | Material library content added and reviewed | ☐ | |
| 5.9 | Material content entered in all three languages | ☐ | |
| 5.10 | Homepage sections reviewed and approved | ☐ | |
| 5.11 | Contact page CMS content reviewed in EN, KO, and ZH | ☐ | |
| 5.12 | B2B page CMS content reviewed in EN, KO, and ZH | ☐ | |
| 5.13 | Footer contact, social links, and legal links reviewed | ☐ | |
| 5.14 | All demo content removed | ☐ | |
| 5.15 | SEO titles and descriptions filled for key pages | ☐ | |

---

## 6. Commerce Configuration

| # | Item | Status | Notes |
|---|---|---|---|
| 6.1 | Shipping rates configured (**Admin Panel → System → Shipping Settings**) | ☐ | |
| 6.2 | Standard shipping rate verified for target market | ☐ | |
| 6.3 | Express shipping rate set | ☐ | |
| 6.4 | Rural surcharge configured if applicable | ☐ | |
| 6.5 | Free shipping threshold set | ☐ | |
| 6.6 | Tax rate configured (NZ GST 15% is default) | ☐ | |
| 6.7 | Currency confirmed as NZD | ☐ | |
| 6.8 | Payment collection method documented for admin team | ☐ | **No payment gateway — manual process** |
| 6.9 | NZ Post enabled and credentials configured (if using live rates) | ☐ | Optional |
| 6.10 | Inventory quantities set for all active product variants | ☐ | |

---

## 7. Security

| # | Item | Status | Notes |
|---|---|---|---|
| 7.1 | Admin panel password changed from any default | ☐ | |
| 7.2 | All admin accounts use real email addresses | ☐ | |
| 7.3 | `storage/` directory not directly web-accessible (except `storage/app/public/`) | ☐ | |
| 7.4 | HTTPS enforced on all domains | ☐ | |
| 7.5 | `APP_DEBUG=false` confirmed (critical) | ☐ | |
| 7.6 | No hardcoded credentials in committed code | ☐ | |
| 7.7 | `.env` file not in version control | ☐ | |
| 7.8 | Public debug routes checked: `/api/debug-footer-payload` is absent | ☐ | |
| 7.9 | Legal page HTML sanitizer verified with unsafe script/link test content | ☐ | |
| 7.10 | Media upload validation rejects scripts, HTML, SVG, archives, and oversized files | ☐ | |

---

## 8. Operations

| # | Item | Status | Notes |
|---|---|---|---|
| 8.1 | Queue worker running: `php artisan queue:work` via Supervisor | ☐ | |
| 8.2 | No failed jobs: `php artisan queue:failed` returns empty | ☐ | |
| 8.3 | Log rotation configured | ☐ | |
| 8.4 | Media backup process configured | ☐ | |
| 8.5 | Monitoring and alerting configured | ☐ | |
| 8.6 | Team trained on admin panel operation | ☐ | |
| 8.7 | Admin panel Handover Readiness page reviewed — no Errors remaining | ☐ | |
| 8.8 | Public storage link verified when local storage is selected | ☐ | |
| 8.9 | SMTP test email sent from Email Settings and received | ☐ | |
| 8.10 | Media upload tested from admin and public community form | ☐ | |

---

## 9. B2B / Lead Management

| # | Item | Status | Notes |
|---|---|---|---|
| 9.1 | B2B email notification configured if required (`B2B_LEADS_NOTIFY_ADMINS=true`) | ☐ | Optional |
| 9.2 | `B2B_LEAD_NOTIFICATION_RECIPIENTS` set to correct email(s) | ☐ | |
| 9.3 | Team trained on lead pipeline management | ☐ | |

---

## 10. Documentation Handover

| # | Item | Status | Notes |
|---|---|---|---|
| 10.1 | `/docs/en/` — English documentation package reviewed | ☐ | 16 files |
| 10.2 | `/docs/ko/` — Korean documentation package reviewed | ☐ | 16 files |
| 10.3 | `/docs/zh/` — Chinese documentation package reviewed | ☐ | 16 files |
| 10.4 | Source code repository access transferred to client | ☐ | |
| 10.5 | Production server credentials transferred to client | ☐ | |
| 10.6 | Azure storage credentials transferred to client | ☐ | |
| 10.7 | Admin panel login credentials provided to client | ☐ | |
| 10.8 | Client trained to edit homepage, material, Contact, B2B, footer/contact, and legal content | ☐ | |
| 10.9 | Client trained to manage products, orders, manual payment status, community posts, reports, and user restrictions | ☐ | |
| 10.10 | Demo data cleanup/regeneration procedure documented and reviewed | ☐ | |

---

## 11. Known Limitations Acknowledged

The client has been made aware of the following limitations (see [15-release-notes-and-handover](./en/15-release-notes-and-handover.md) for full details):

| # | Known Limitation | Acknowledged |
|---|---|---|
| L1 | No payment gateway — orders are placed as Unpaid; manual payment confirmation required | ☐ |
| L2 | Inventory is not automatically decremented on order — manual update required | ☐ |
| L3 | Funding campaign frontend display is limited | ☐ |
| L4 | Order confirmation email trigger should be end-to-end tested | ☐ |
| L5 | Admin panel UI is primarily in English | ☐ |
| L6 | Community feed does not auto-refresh | ☐ |
| L7 | No integrated analytics dashboard | ☐ |

---

## Sign-Off

| Role | Name | Date | Signature |
|---|---|---|---|
| Development Lead | | | |
| Project Manager | | | |
| Client Representative | | | |

---

*This checklist is part of the OXP platform documentation package.*  
*Reference: `docs/en/15-release-notes-and-handover.md` for detailed handover notes.*
