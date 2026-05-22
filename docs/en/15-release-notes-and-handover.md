# 15 — Release Notes and Handover Document

## Platform Summary

| Item | Details |
|---|---|
| **Platform Name** | OXP |
| **Codebase** | Terraf |
| **Documentation Date** | May 2026 |
| **Backend Framework** | Laravel 13 (PHP 8.2+) |
| **Frontend Framework** | Next.js 16 (React 19, TypeScript) |
| **Admin Panel** | Filament 5 |
| **Database** | MySQL 8+ |
| **Default Storage** | Azure Blob Storage |
| **Supported Languages** | English, Korean, Simplified Chinese |

---

## What Has Been Delivered

The following modules have been fully implemented and delivered:

### Core Platform
- ✅ User registration, login, email verification, password reset
- ✅ User profiles with avatar, bio, and location
- ✅ Multilingual frontend (EN / KO / ZH) via Next.js App Router
- ✅ Admin panel with full CRUD for all content and user management

### Store & Commerce
- ✅ Product catalog with categories, variants, and dynamic attributes
- ✅ Product images and detailed specifications
- ✅ Shopping cart (guest and authenticated)
- ✅ Checkout flow with shipping rate calculation
- ✅ Guest checkout and guest order lookup
- ✅ Order management with full status lifecycle
- ✅ Inventory tracking system (manual)
- ✅ Tax/GST calculation and display
- ✅ NZ Post shipping integration (configurable)

### Community
- ✅ Post creation with Tiptap rich text editor
- ✅ Post media attachments (images and documents)
- ✅ Post categories, tags, likes, saves, and views
- ✅ Comments and threaded replies with likes
- ✅ Follow / following relationships
- ✅ Community search
- ✅ Post ranking (engagement and trending scores)
- ✅ Featured and pinned posts
- ✅ In-platform notification system
- ✅ Content reporting workflow

### Moderation & Governance
- ✅ Full report review workflow (dismiss, hide, warn, restrict, ban)
- ✅ Moderation audit log
- ✅ User violations tracking
- ✅ Admin action log
- ✅ Configurable submission policies

### B2B & Inquiries
- ✅ General contact form
- ✅ B2B structured inquiry form
- ✅ Partnership, university, and product development inquiry forms
- ✅ Material request form
- ✅ Lead pipeline management in admin panel
- ✅ Lead export to CSV

### Content Management
- ✅ Material library with specs, story sections, and applications
- ✅ Articles / knowledge base
- ✅ Configurable homepage sections
- ✅ Legal pages (Privacy Policy and Terms of Service) — editable via admin panel
- ✅ SEO metadata fields

### Email & Notifications
- ✅ Email template system with multilingual support
- ✅ Email event triggers
- ✅ Email delivery logs
- ✅ B2B lead admin email notifications (configurable)

### System & Administration
- ✅ Settings system (application, email, storage, shipping, tax, community)
- ✅ Feature flags
- ✅ Settings backup and import
- ✅ Handover readiness page
- ✅ Initial content system with seeding support
- ✅ Media storage scan
- ✅ Health check API endpoints
- ✅ Web installer

---

## Known Limitations and Incomplete Areas

The following areas have known limitations or partial implementation. These are documented for client awareness and recommended follow-up:

### 1. Payment Gateway (Not Implemented)
**Description**: The order data model records payment method and reference, but no actual payment gateway (e.g., Stripe, PayPal, ANZ Bank, Windcave) is connected.

**Current behavior**: Orders are placed as `Unpaid`. Admins must manually confirm payment and update the payment status.

**Recommended next step**: Integrate a payment gateway appropriate for the target market (New Zealand recommended: Windcave/Payment Express or Stripe).

**Risk level**: High — must be addressed before accepting real payments from customers.

---

### 2. Automatic Inventory Management (Partial)
**Description**: Stock levels are tracked per product variant in the database, but the system does not automatically decrement stock when an order is placed.

**Current behavior**: Admins must manually update inventory quantities after fulfilling orders.

**Recommended next step**: Implement an `OrderObserver` or modify the `OrderService` to deduct stock quantities when an order transitions to "Confirmed" or "Processing" status.

**Risk level**: Medium — important for accurate stock visibility.

---

### 3. Funding Campaign Frontend (Partial)
**Description**: The funding campaign data model, admin management, and database are fully implemented. However, the frontend display of campaign status, progress, and support functionality is limited.

**Current behavior**: Campaign data is stored and manageable in admin, but the public-facing campaign page/widget has minimal display.

**Recommended next step**: Design and implement frontend campaign display components including progress visualization and support action.

**Risk level**: Low — depends on whether funding campaigns are a priority feature for launch.

---

### 4. Order Confirmation Email (Needs Verification)
**Description**: The email template and event system is built. The `order_confirmed` event template exists. However, the exact trigger wiring between order status change and email dispatch has not been independently verified.

**Recommended next step**: Test the complete email flow: place a test order, confirm it in admin, and verify the confirmation email is received.

**Risk level**: Medium — important for customer communication.

---

### 5. Admin Panel UI Localization (Partial)
**Description**: The admin panel interface is primarily in English. Admin locale switching (`/admin/locale/{locale}`) affects some labels, but the full Filament admin UI is not comprehensively translated to Korean or Chinese.

**Recommended next step**: If admin panel operation in Korean or Chinese is required, additional Filament localization work is needed.

**Risk level**: Low to Medium — depends on the language preference of platform administrators.

---

### 6. Real-time Community Updates (Not Implemented)
**Description**: The community feed does not auto-refresh with new posts or interactions. Users must manually refresh the page.

**Recommended next step**: Implement WebSocket or polling-based updates for the community feed if real-time experience is desired.

**Risk level**: Low — UX improvement rather than blocking functionality.

---

### 7. Analytics Dashboard (Basic)
**Description**: A basic analytics overview endpoint exists (`/api/admin/analytics/overview`) with aggregate metrics. There is no integrated third-party analytics dashboard (e.g., Google Analytics, Plausible, Mixpanel).

**Recommended next step**: Integrate an analytics platform for business intelligence reporting.

**Risk level**: Low — informational capability enhancement.

---

## Pre-Launch Checklist

The following items must be completed before the platform goes live with real customers:

### Infrastructure
- [ ] Production server provisioned with correct PHP version (8.2+)
- [ ] MySQL database created and accessible
- [ ] Azure Blob Storage account created and configured
- [ ] SSL/TLS certificate installed on production domains
- [ ] Backend and frontend deployed to production servers
- [ ] CORS origins set to production frontend URL only

### Configuration
- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] `APP_URL` set to production backend URL
- [ ] `FRONTEND_URL` set to production frontend URL
- [ ] `CORS_ALLOWED_ORIGINS` set to production frontend URL
- [ ] Database credentials set
- [ ] Azure storage credentials configured
- [ ] Mail server configured and tested

### Database
- [ ] `php artisan migrate --force` completed successfully
- [ ] Admin user account created
- [ ] Initial content reviewed as delivery content
- [ ] Real product catalog, materials, and articles added

### Content
- [ ] Privacy Policy reviewed by legal counsel and updated in all three languages
- [ ] Terms of Service reviewed by legal counsel and updated in all three languages
- [ ] Homepage content reviewed and approved
- [ ] Product content reviewed and approved in all three languages
- [ ] Material content reviewed and approved in all three languages
- [ ] All "Last Updated" dates on legal pages set correctly

### Email
- [ ] SMTP server configured
- [ ] Email sending tested
- [ ] From address is a valid, deliverable address
- [ ] Email templates reviewed

### Commerce
- [ ] Shipping rates configured correctly for target market
- [ ] Tax rate configured (NZ GST: 15%)
- [ ] Currency confirmed (NZD)
- [ ] Payment method determined and documented for admins

### Security
- [ ] Admin account password changed from default
- [ ] Admin email changed to a real operational address
- [ ] No development/debug credentials in production `.env`

### Operations
- [ ] Queue worker running (Supervisor or equivalent)
- [ ] Log rotation configured
- [ ] Database backup process configured and tested
- [ ] Media backup process configured and tested
- [ ] Monitoring and alerting configured
- [ ] Handover Readiness page reviewed and all items are OK or Warning (no Errors)

---

## Assumptions Made During Development

1. The platform operates in New Zealand, using NZD currency and NZ Post for shipping.
2. Azure Blob Storage is the primary file storage solution.
3. GST is the applicable tax, at a rate of 15%.
4. The admin panel is primarily operated by English-speaking administrators.
5. The community is intended for a global audience; all frontend UI supports EN/KO/ZH.
6. Payment processing is a separate integration to be handled post-handover.

---

## Recommended Next Improvements (Post-Handover)

Based on codebase analysis, the following improvements are recommended for prioritization after initial launch:

1. **Payment gateway integration** — Critical for real commerce operations.
2. **Automatic inventory decrement** — Important for stock accuracy.
3. **Funding campaign frontend** — If campaigns are a priority feature.
4. **Email flow end-to-end testing** — Verify all transactional emails work.
5. **Analytics integration** — For business intelligence.
6. **Real-time community updates** — For improved community UX.
7. **Product review system** — Common e-commerce trust signal.
8. **Discount / coupon codes** — For promotional campaigns.
9. **International shipping support** — If expanding beyond New Zealand.
10. **Admin panel UI localization** — If non-English admin operators required.

---

## Source Code Delivery

The complete source code is delivered as the **Terraf** repository, containing:
- `B2C_backend/` — Laravel 13 backend and Filament admin panel
- `B2C_frontend/` — Next.js 16 frontend application
- `docs/` — This documentation package
- `tests/` — Playwright end-to-end tests
- `README.md` — Project summary
- `SYSTEM_QA_REPORT.md` — QA verification report

---

## Contact for Technical Support

For technical questions related to this delivery, contact the development team through the channels agreed in the project contract.

---

*Documentation prepared by the OXP development team — May 2026*
