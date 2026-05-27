# 00 — Project Overview

## What Is OXP?

**OXP** is a full-stack, multilingual B2C and B2B digital platform developed for a sustainable materials brand. The platform serves as the primary digital touchpoint for customers, creators, and business partners, combining an e-commerce store, a community hub, a content library, and a B2B inquiry pipeline in a single unified system.

The codebase is internally referred to as **Terraf**. The platform name shown to users is **OXP**.

---

## Business Purpose

OXP is designed to:

1. **Educate and engage** — Present the brand's material science, certifications, and sustainability credentials to a global audience.
2. **Sell direct** — Enable customers to browse and purchase products through a modern e-commerce store with cart, checkout, and order tracking.
3. **Build community** — Allow registered users to share ideas, projects, and knowledge through community posts, comments, likes, and follow relationships.
4. **Generate B2B leads** — Capture qualified business inquiries for wholesale, distribution, manufacturing, material requests, and partnership collaborations.
5. **Centralize administration** — Provide administrators with a powerful admin panel to manage all platform content, users, orders, and settings.

---

## Target Users

| User Type | Description |
|---|---|
| **Public visitors** | Anyone browsing without an account. Can view the website, materials, articles, products, and community posts. |
| **Registered users** | Authenticated customers who can checkout, manage orders, create community posts, and interact with content. |
| **B2B partners** | Businesses and professionals submitting inquiries for wholesale, partnerships, or collaborations. |
| **Community creators** | Registered users who actively contribute community content (posts, comments, funding ideas). |
| **Moderators** | Trusted users with elevated permissions to review and action content reports. |
| **Administrators** | Platform operators with full access to the admin panel, all content, user management, and settings. |

---

## Supported Languages

The platform is fully multilingual across all user-facing interfaces:

| Language | Code | Coverage |
|---|---|---|
| English | `en` | Default language; complete frontend and admin panel |
| Simplified Chinese | `zh` | Complete frontend; admin panel partially localized |
| Korean | `ko` | Complete frontend; admin panel partially localized |

Translations are stored in `B2C_frontend/messages/{locale}.json`. The backend API returns content in the requested locale when multilingual fields are available.

The admin panel supports locale switching via `/admin/locale/{locale}`.

---

## Main Modules

### 1. Public Website
The marketing and discovery layer of the platform:
- **Homepage** — Hero, value propositions, material showcase, community previews, collaboration CTAs.
- **Material Library** — Detailed material pages with specs, story sections, applications, and certifications.
- **Articles** — Knowledge base / blog articles with rich text content and cover images.
- **Legal Pages** — Privacy policy and terms of service, editable via the admin panel.

### 2. Account System
User authentication and profile management:
- Registration, login, email verification, password reset
- Profile management (bio, avatar, location)
- Address book for shipping addresses
- Account dashboard with community activity and order history

### 3. Store
Full e-commerce functionality:
- Product catalog with categories, search, and filtering
- Product detail pages with variants, images, specifications, and FAQs
- Shopping cart (guest and authenticated)
- Checkout with shipping quote and order placement
- Order tracking for both guest and registered users

### 4. Community
User-generated content platform:
- Create, edit, and publish community posts with rich text and media attachments
- Comments, replies, likes, and saves
- Follow / following user relationships
- Funding links on posts (crowdfunding-style campaigns)
- Search across community content
- Report inappropriate content

### 5. B2B & Contact
Lead generation and inquiry capture:
- General contact / inquiry form
- Structured B2B inquiry form with interest categorization
- Partnership, university collaboration, and product development inquiry forms
- Material request form
- Admin review and lead qualification pipeline

### 6. Admin Panel
Built on Filament 5 (Laravel):
- User management, role assignment, and account moderation
- Full CRUD for all content (products, materials, articles, homepage sections, legal pages)
- Order processing and status management
- Community moderation queue (reports, post/comment status, user restrictions)
- Email center (templates, events, logs)
- Settings system (application, mail, storage, shipping, tax, community policies)
- Handover readiness checklist
- Initial content maintained through standard resources

---

## Platform Status Summary (May 2026)

| Module | Status |
|---|---|
| Authentication & accounts | Complete |
| Community (posts, comments, interactions) | Complete |
| Community moderation & reporting | Complete |
| B2B inquiries & lead management | Complete |
| Product catalog (with variants & attributes) | Complete |
| Shopping cart (guest & authenticated) | Complete |
| Checkout & order creation | Complete |
| Order management (admin) | Complete |
| Guest checkout | Complete |
| Payment processing integration | Not implemented (payment method recorded, gateway not connected) |
| NZ Post shipping integration | Optional / configurable (disabled by default) |
| Funding links and campaigns | Implemented for external funding links and campaign display; not an internal payment gateway |
| Email transactional system | Implemented (templates, events, logs) — requires SMTP configuration |
| Multilingual content (EN/KO/ZH) | Frontend, backend API messages, validation messages, and admin translation files are maintained across EN / ZH / KO |
| Media / file upload | Complete (Azure Blob Storage or local disk) |
| Analytics | Basic admin analytics endpoint; no third-party dashboard |

---

## Known Limitations

- **Payment gateway**: The order model records payment method and reference, but no actual payment gateway (Stripe, PayPal, etc.) is connected. Orders are placed as "unpaid" and must be manually confirmed.
- **NZ Post shipping**: Real-time shipping quotes from NZ Post are implemented but disabled by default. Flat-rate shipping rules are applied instead.
- **Funding links and campaigns**: Posts and campaign records can expose public funding links and progress-style campaign information where configured.
- **Email**: The email template and event system is fully built, but requires SMTP server configuration before transactional emails will be sent in production.
- **Admin panel UI localization**: Admin locale switching is implemented with English, Chinese, and Korean backend/admin translation files.

---

*Related code: `B2C_backend/` (Laravel), `B2C_frontend/` (Next.js)*
