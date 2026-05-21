# OXP Platform — Known Issues and Limitations

**Last Updated**: May 2026  
**Codebase**: Terraf / B2C_backend + B2C_frontend

This document catalogues known issues, unimplemented features, and areas requiring post-handover attention. It is intended for the development team and technical stakeholders.

---

## Critical (Must Address Before Live Payments)

### KI-001: No Payment Gateway Integrated

**Severity**: Critical  
**Area**: Store / Commerce  
**Status**: Not Implemented

**Description**: The order data model records `payment_method` and `payment_reference` fields and tracks `payment_status` (unpaid/paid/refunded/failed), but no payment gateway is connected. Orders are created with `payment_status = unpaid`.

**Current workaround**: Administrators manually confirm payment and update `payment_status` to `paid` via the admin panel after receiving payment through a separate channel.

**Recommended fix**: Integrate a payment provider appropriate for the New Zealand market. Options: Windcave (Payment Express), Stripe NZ, PayPal. Integration requires:
- A new `PaymentController` or webhook handler
- Order status transition triggered on successful payment
- Automatic inventory decrement on payment (see KI-002)

**Files to modify**: `app/Services/OrderService.php`, `app/Models/Order.php`, add `app/Http/Controllers/Api/PaymentController.php`

---

## High (Important for Accurate Operations)

### KI-002: Inventory Not Automatically Decremented on Order

**Severity**: High  
**Area**: Store / Inventory  
**Status**: Partial — tracked but not automated

**Description**: `ProductVariant.stock_quantity` is tracked per variant, and the `inventory_adjustments` table exists for audit logging. However, when an order is placed or confirmed, the `stock_quantity` field is **not automatically decremented**.

**Current workaround**: Administrators manually edit `stock_quantity` via **Admin Panel → Store → Inventory** after fulfilling each order.

**Recommended fix**: Add inventory decrement logic triggered when order transitions to `confirmed` or `processing` status:
- Option A: Add an `OrderObserver` that hooks into the `updating` event and decrements stock when status changes.
- Option B: Add the decrement inside `OrderService::confirmOrder()`.

**Files to modify**: `app/Services/OrderService.php` or add `app/Observers/OrderObserver.php`

---

### KI-003: Order Confirmation Email Trigger Not Independently Verified

**Severity**: High  
**Area**: Email / Notifications  
**Status**: Needs verification

**Description**: The email template system (`email_templates`, `email_events` tables) and the `B2BLeadSubmittedMail` mailable are confirmed working. The `order_confirmed` email event template exists in the database. However, the exact trigger wiring from order status change → email dispatch has not been verified end-to-end in a production-like environment.

**Risk**: Customers may not receive order confirmation emails.

**Recommended fix**: Test manually:
1. Configure SMTP in Email Settings.
2. Place a test order.
3. Change order status to `confirmed` in admin panel.
4. Verify confirmation email is received.

Check `app/Http/Controllers/Api/OrderController.php` and `app/Services/OrderService.php` for mail dispatch calls.

---

## Medium (Important but Not Immediately Blocking)

### KI-004: Funding Campaign Frontend Display Limited

**Severity**: Medium  
**Area**: Community / Funding Campaigns  
**Status**: Backend complete; frontend partial

**Description**: `FundingCampaign` model, `PartnershipInquiry` model, admin management via `FundingCampaignResource`, and database migrations are complete. The campaign can be linked to a community post (`idea_id`). However, the public-facing frontend:
- Does not show campaign progress (amount raised vs. goal)
- Does not show a "Support" / pledge action button with functional workflow
- Campaign list view is limited

**Recommended fix**: Design and implement frontend campaign display components:
- Campaign progress bar (funded amount / goal)
- Support/pledge CTA button
- Campaign status badge (active/completed/cancelled)

**Files to check**: `B2C_frontend/src/components/community/`, `B2C_backend/app/Models/FundingCampaign.php`

---

### KI-005: Admin Panel UI Not Fully Localized to Korean/Chinese

**Severity**: Medium (if non-English admins required)  
**Area**: Admin Panel / i18n  
**Status**: Partial

**Description**: The Filament 5 admin panel has locale switching (`/admin/locale/{locale}`). Some field labels and navigation items are localized, but the bulk of the Filament-generated UI (table column headers, form labels, button text, notifications) remains in English.

**Impact**: Non-English-speaking administrators may find the panel difficult to use.

**Recommended fix**: Add Filament language packs for Korean and Chinese, and systematically add translation keys to all Filament resource `label()`, `placeholder()`, and `helperText()` definitions.

---

## Low (UX Improvements / Future Enhancements)

### KI-006: Community Feed Does Not Auto-Refresh

**Severity**: Low  
**Area**: Community  
**Status**: Not Implemented

**Description**: New posts, likes, and comments do not appear in the feed without a manual page refresh. There is no WebSocket or polling mechanism.

**Recommended fix**: Implement either:
- A short polling interval (e.g., every 30 seconds) using `setInterval` and the existing feed API
- WebSocket-based real-time updates (requires Laravel Echo or similar)

---

### KI-007: No Integrated Analytics Dashboard

**Severity**: Low  
**Area**: Analytics  
**Status**: Basic only

**Description**: A basic analytics endpoint (`GET /api/admin/analytics/overview`) returns aggregate metrics (total users, posts, orders, community activity). No third-party analytics service (Google Analytics, Plausible, Mixpanel) is integrated.

**Recommended fix**: Add a client-side analytics script (e.g., Google Analytics 4 or Plausible snippet) to the frontend `layout.tsx`, and optionally add a backend event tracking service.

---

### KI-008: No Self-Service Account Deletion

**Severity**: Low  
**Area**: User Accounts  
**Status**: Not Implemented (admin-only)

**Description**: Users cannot delete their own accounts from the frontend. Administrators can delete user records from the admin panel. This may be required for GDPR / privacy compliance depending on the platform's operating jurisdiction.

**Recommended fix**: Add a "Delete Account" option to the user account settings page. Backend endpoint: implement `DELETE /api/account` (requires authentication and possibly a confirmation step).

---

### KI-009: No Discount / Coupon Code System

**Severity**: Low  
**Area**: Store / Commerce  
**Status**: Not Implemented

**Description**: No discount code, coupon, or promotional pricing system exists. There are no `coupon_codes`, `discounts`, or `promotions` tables.

**Recommended fix**: Design a promotions system. Minimum viable implementation: a `coupon_codes` table with code, discount type (percentage/fixed), expiry, and a usage check at checkout.

---

### KI-010: No Product Review or Rating System

**Severity**: Low  
**Area**: Store / Commerce  
**Status**: Not Implemented

**Description**: There are no `product_reviews` or `product_ratings` tables or UI.

**Recommended fix**: Add a product review model linked to verified purchasers, and a frontend review form on the product detail page.

---

### KI-011: NZ-Only Shipping Configuration

**Severity**: Low (if international is required)  
**Area**: Store / Shipping  
**Status**: By design, NZ-only

**Description**: Shipping is configured for New Zealand: NZD currency, NZ Post integration, rural surcharge logic. No international carrier integration or multi-currency pricing exists.

**Recommended fix for international**: Add currency conversion service, international carrier API integrations, and multi-currency product pricing.

---

## Informational (Design Decisions / Not Issues)

### KI-D00: Azure Flysystem Package Maintenance

Composer audit reports `league/flysystem-azure-blob-storage` as abandoned. It is currently retained to avoid a risky storage rewrite during delivery hardening. Plan a follow-up replacement with the maintained Azure Flysystem adapter after validating upload, URL generation, file visibility, and admin media workflows against staging data.

### KI-D01: Manual Payment Confirmation by Design

The platform was delivered without payment gateway integration per initial project scope. This is a known design constraint, not a bug. See KI-001 for resolution path.

### KI-D02: Admin Panel Primarily in English by Design

The Filament 5 admin panel's primary language is English. This was the agreed operating assumption. Non-English localization is possible as a follow-up task (see KI-005).

### KI-D03: Queue Connection Uses Database Driver

`QUEUE_CONNECTION=database` is the default. This is adequate for moderate traffic. For high-volume production, switching to Redis (`QUEUE_CONNECTION=redis`) is recommended for better performance and reliability.

---

## Reporting New Issues

Issues discovered after handover should be tracked in the project's agreed issue tracker. When filing a new issue, include:

1. Steps to reproduce
2. Expected behavior
3. Actual behavior
4. Environment (production / staging / local)
5. Relevant logs from `B2C_backend/storage/logs/laravel.log`

---

*Related documentation: `docs/en/15-release-notes-and-handover.md`, `docs/handover-checklist.md`*
