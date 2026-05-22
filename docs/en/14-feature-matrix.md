# 14 — Feature Matrix

This table provides a comprehensive overview of all platform features and their current implementation status as of May 2026.

**Status Legend:**
- ✅ **Complete** — Fully implemented and functional
- ⚠️ **Partial** — Implemented but with limitations; see Notes
- ❌ **Not Implemented** — Planned or stub exists but not functional
- 🔧 **Config Required** — Feature exists but requires configuration to activate

---

## Authentication & Accounts

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| User registration | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Login / logout | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Email verification | ✅ | N/A | ✅ | ✅ | ✅ Complete | Requires mail config |
| Password reset | ✅ | N/A | ✅ | ✅ | ✅ Complete | Requires mail config |
| Profile management | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Avatar upload | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Account settings (change email/password) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Account status (active/restricted/suspended/banned) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Role management (creator/moderator/admin) | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Self-service account deletion | ❌ | ✅ (admin) | ✅ | N/A | ⚠️ Partial | No frontend self-delete; admin can delete |

---

## Store & Commerce

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| Product catalog browsing | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Product search | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Product category filtering | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Product detail page | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Product variants (size, color, etc.) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Dynamic product attributes | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Product images (gallery) | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Product FAQs | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Featured products | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Inquiry-only products | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Material request on products | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Shopping cart (guest) | ✅ | N/A | ✅ | ✅ | ✅ Complete | |
| Shopping cart (authenticated) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Cart merge on login | ✅ | N/A | ✅ | N/A | ✅ Complete | |
| Guest checkout | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Authenticated checkout | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Saved address book | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Flat-rate shipping | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| NZ Post live shipping quotes | ✅ | ✅ | ✅ | N/A | 🔧 Config Required | `NZPOST_ENABLED=true` required |
| Tax (GST) calculation | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Order creation | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Order status tracking | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Guest order lookup | ✅ | N/A | ✅ | ✅ | ✅ Complete | |
| Order management (admin) | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Payment processing | ❌ | ❌ | ⚠️ | N/A | ⚠️ Partial | Payment fields exist; no gateway |
| Inventory tracking | N/A | ✅ | ✅ | N/A | ✅ Complete | Manual adjustment only |
| Auto inventory decrement on order | ❌ | ❌ | ✅ | N/A | ⚠️ Partial | Manual update required |
| Discount / coupon codes | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | |
| Product reviews / ratings | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | |
| Wishlist | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | |
| Multi-currency | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | NZD only |

---

## Community

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| Community post feed | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Post creation (rich text) | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post editing / deletion | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post categories | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Post tags | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Post cover image | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post media attachments (files) | ✅ | ✅ | ✅ | N/A | ✅ Complete | Up to 12 files |
| Post external links | ✅ | ✅ | ✅ | N/A | ✅ Complete | Up to 4 links |
| Post likes | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post saves / favorites | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Comments and replies | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Comment likes | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Follow / unfollow users | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| User profiles (public) | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post search | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post ranking (engagement / trending) | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Featured posts | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Pinned posts | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Post notifications | ✅ | ✅ | ✅ | N/A | ✅ Complete | Requires queue worker |
| Content reporting | ✅ | ✅ | ✅ | N/A | ✅ Complete | |
| Funding campaigns (backend) | N/A | ✅ | ✅ | ✅ | ✅ Complete | |
| Funding campaigns (frontend display) | ⚠️ | ✅ | ✅ | ✅ | ⚠️ Partial | Limited frontend display |
| Real-time feed updates | ❌ | N/A | N/A | N/A | ❌ Not Implemented | Manual refresh required |
| Direct messaging | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | |
| Post scheduling | ❌ | ❌ | ❌ | N/A | ❌ Not Implemented | |

---

## Moderation & Governance

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| Content reports (user submission) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Report review workflow | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Content hiding | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Content rejection | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| User warnings | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| User restriction | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| User banning | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Moderation audit log | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Admin action log | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| User violations log | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Post approval workflow | ✅ | ✅ | ✅ | N/A | ✅ Complete | Configurable |
| Sensitive word filter | N/A | ✅ | ✅ | N/A | 🔧 Config Required | Disabled by default |
| System announcements | ✅ | ✅ | ✅ | N/A | ✅ Complete | |

---

## B2B & Inquiries

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| General contact form | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| B2B inquiry form | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Partnership inquiry | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| University collaboration inquiry | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Product development inquiry | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Material request form | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| B2B lead pipeline management | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Lead assignment to team members | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Lead export (CSV) | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Admin email notification on new lead | N/A | ✅ | ✅ | N/A | 🔧 Config Required | `B2B_LEADS_NOTIFY_ADMINS=true` |
| CRM integration | ❌ | ❌ | N/A | N/A | ❌ Not Implemented | Manual export only |

---

## CMS & Content

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| Material library | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Material specs | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Material story sections | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Material applications | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Articles / blog | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Homepage sections (configurable) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Legal pages (editable) | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| SEO meta fields | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Initial content seeding | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Initial content review through standard resources | N/A | ✅ | ✅ | N/A | ✅ Complete | |

---

## Email & Notifications

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| In-platform notifications | ✅ | ✅ | ✅ | ✅ | ✅ Complete | |
| Notification bell + panel | ✅ | N/A | ✅ | ✅ | ✅ Complete | |
| Mark notifications read | ✅ | N/A | ✅ | N/A | ✅ Complete | |
| Email template system | N/A | ✅ | ✅ | ✅ | ✅ Complete | |
| Email event triggers | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Email delivery logs | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| B2B lead admin email notification | N/A | ✅ | ✅ | N/A | 🔧 Config Required | |
| Email verification flow | ✅ | N/A | ✅ | ✅ | ✅ Complete | |
| Password reset email | ✅ | N/A | ✅ | ✅ | ✅ Complete | |
| Order confirmation email | ⚠️ | ✅ | ✅ | ✅ | ⚠️ Partial | Template exists; event wiring may need verification |

---

## System & Administration

| Feature | User Side | Admin Side | DB | Multilingual | Status | Notes |
|---|---|---|---|---|---|---|
| Admin panel (Filament 5) | N/A | ✅ | N/A | ⚠️ | ✅ Complete | Admin UI primarily in English |
| Admin locale switching | N/A | ✅ | N/A | ✅ | ✅ Complete | |
| Application settings | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Feature flags | N/A | ✅ | ✅ | N/A | ✅ Complete | |
| Settings backup / restore | N/A | ✅ | N/A | N/A | ✅ Complete | |
| Handover readiness page | N/A | ✅ | N/A | N/A | ✅ Complete | |
| Media storage scan | N/A | ✅ | N/A | N/A | ✅ Complete | |
| Web installer | N/A | ✅ | N/A | N/A | ✅ Complete | |
| Health check endpoints | N/A | ✅ | N/A | N/A | ✅ Complete | |
| Analytics overview (basic) | N/A | ✅ | N/A | N/A | ✅ Complete | Basic metrics only |
| Full analytics dashboard | ❌ | ❌ | N/A | N/A | ❌ Not Implemented | No third-party dashboard integrated |

---

## Internationalization

| Feature | EN | KO | ZH | Status | Notes |
|---|---|---|---|---|---|
| Frontend UI strings | ✅ | ✅ | ✅ | ✅ Complete | |
| Product content | ✅ | ✅ | ✅ | ✅ Complete | |
| Material content | ✅ | ✅ | ✅ | ✅ Complete | |
| Article content | ✅ | ✅ | ✅ | ✅ Complete | |
| Homepage sections | ✅ | ✅ | ✅ | ✅ Complete | |
| Categories and tags | ✅ | ✅ | ✅ | ✅ Complete | |
| Legal pages | ✅ | ✅ | ✅ | ✅ Complete | |
| Email templates | ✅ | ✅ | ✅ | ✅ Complete | |
| Admin panel UI | ✅ | ⚠️ | ⚠️ | ⚠️ Partial | Admin UI primarily English |
| URL locale routing | ✅ | ✅ | ✅ | ✅ Complete | |

---

*Last updated: May 2026*
