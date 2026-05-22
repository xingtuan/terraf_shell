# Admin Operations Guide

Use `/admin` to run OXP operations. Admin-only areas require the `admin` role; moderation areas may also allow `moderator`.

## Products

Open Store Operations -> Products.

- Use the list filters for category, stock status, published state, featured, bestseller, and new arrival.
- Edit a product through the tabs: Basic Info, Storefront Content, Pricing & Inventory, Images, SEO, and Publishing-related controls.
- Variant SKU, NZD price, inventory policy, and stock quantity are managed in Pricing & Inventory.
- Storefront price and stock state come from the active default variant.
- Use Product Media or the product Images tab for gallery images.
- Product deletion is blocked with a clear admin notification when cart items or order history still reference the product.

## Orders

Open Store Operations -> Orders.

- The list shows order number, registered customer or guest email, total, payment status, fulfillment status, shipping country/city, and created date.
- Filter by status, payment status, guest/registered customer type, shipping country/city, payment method, and date range.
- Use row actions to update fulfillment status, update manual payment status, add an internal note, or quickly mark confirmed, processing, shipped, delivered, or cancelled.
- Status changes continue to call the existing `OrderService` email dispatch path.

## Carts And Addresses

Open Store Operations -> Carts for abandoned cart review.

- Carts are read-only.
- Use the abandoned filter for carts with items and no activity for 7 days.
- Customer carts show the linked user; guest carts show the session key.

Open Store Operations -> Addresses for customer address review.

- Addresses are read-only.
- The list shows linked user, recipient, country, city, postal code, and default flag.
- Avoid copying or exporting address data unless needed for fulfillment support.

## B2B Leads And Inquiries

Open B2B / Leads -> All Leads for all frontend lead submissions.

- Business contacts, material requests, partnership inquiries, university collaborations, and product development collaborations are stored in the unified lead table.
- Use filters for lead type, status, priority, assignee, source page, follow-up, and dates.
- Use actions to assign a lead, change status, change priority, add notes, mark follow-up date, resolve, archive, or export CSV.

Open B2B / Leads -> General Enquiries for legacy business contact enquiries.

- This is a filtered operator view of the same unified lead storage.
- Convert-to-lead is not a separate operation because legacy enquiries already create lead records.

## Community Moderation

Open Community -> Concepts, Comments, Reports, Funding Campaigns, and Moderation Queue.

- Posts show status, author, category, funding URL, attachment count, comments, likes, and reports.
- Comments and reports can be approved, rejected, hidden, or otherwise handled through their row actions.
- Funding links must be external `http` or `https` URLs. `javascript:` and `data:` style URLs are rejected.
- Initial community posts and shop catalog records are delivery content. Review or archive them through the standard admin resources only when the business requires it.

## CMS Content

Open Content / CMS.

- Homepage Sections manage dynamic homepage content.
- Materials manage material intro, story overview, science overview, media, certifications, downloads, specs, story sections, and applications.
- Articles manage published editorial/news content.
- Use EN, KO, and ZH fields where available. Chinese may reuse English image assets if no Chinese asset is available.
- OXP is the current oyster shell product line. CXP is Carbon XP. MXP is pending/not available and should not be presented as a live product.

## Email Center

Open Email Center -> Settings, Events, Templates, and Logs.

- Settings shows whether sending is enabled, current provider, failed email count, and last sent email.
- Use Send test email after changing provider settings.
- Events enable or disable each email stage.
- Templates are localized by locale. Missing locales fall back to English.
- Secrets are masked in admin and should not be copied into docs or screenshots.

## Admin Language

Use the user menu in the admin panel to switch English, Korean, or Chinese.

- The selected locale is stored in the session.
- Admin labels fall back to English when a translation key is missing.
- User-generated content, lead messages, post bodies, and customer notes are not translated automatically.

## System Readiness

Open System / Handover -> System / Handover Readiness.

Review:

- App URL and frontend URL
- Environment
- Database connection
- Storage disk and storage link
- Mail enabled state and provider
- Queue, cache, and session drivers
- Key admin account
- Initial content
- Failed jobs
- PHP and Laravel versions
- Writable directories
- Last migration

Resolve all Error badges before handover. Warning badges are acceptable only when intentionally configured, such as local `sync` queue mode.
