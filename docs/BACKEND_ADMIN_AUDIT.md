# Backend Admin Audit

Date: 2026-05-07

Scope reviewed: `B2C_backend/routes/api.php`, `app/Models`, `app/Http/Controllers/Api`, `app/Http/Requests`, `app/Http/Resources`, `app/Services`, `app/Filament/Resources`, `app/Filament/Pages`, `app/Filament/Widgets`, `database/migrations`, `database/seeders`, `lang/en`, `lang/ko`, `lang/zh`, README and backend docs.

The backend already had strong API and model coverage. The largest gap was not data storage, but operator visibility in Filament: carts, addresses, central media, handover readiness, lead priority/follow-up, demo content cleanup, and a Korean-ready navigation structure were not exposed clearly enough.

| Feature area | Existing database tables/models | Existing API routes | Existing Filament admin resource/page/widget | Missing admin visibility | Missing backend functionality | Required fix | Priority |
|---|---|---|---|---|---|---|---|
| Authentication and users | `users`, Sanctum tokens, sessions, password reset tables; `User` | `/api/auth/*` | `UserResource`, dashboard account widget | Navigation was governance-oriented but not Korean-ready | Locale preference is session-based, not stored on user | Added translated navigation and locale switch route. User-level preference can be added later if needed | P1 |
| Profiles and roles | `profiles`, `users.role`, `users.account_status`; `Profile`, `User` | `/api/auth/profile`, admin user moderation APIs | `UserResource`, `UserViolationResource`, governance widgets | Role/status labels were partly hardcoded | None blocking | Enum labels now return translations | P1 |
| Store products | `products`, `product_variants`, `product_attribute_*`; `Product`, `ProductVariant` | `/api/products`, `/api/product-categories` | `ProductResource`, `ProductVariantResource`, `InventoryResource` | Product list lacked some operational columns and filters | None blocking. Product variants already exist; no fake variants were added | Added SKU, stock, featured, bestseller, new, publish filters and clearer form tabs | P0 |
| Product categories | `product_categories`; `ProductCategory` | `/api/product-categories` | `ProductCategoryResource` | Navigation needed store grouping | None blocking | Moved to Store Operations with translated labels | P1 |
| Product images | `product_images`; `ProductImage` | Product resources include gallery images | `ProductImageResource`, product form gallery | Media was split across product image and uploads | None blocking | Product image resource moved under Media Library and product form has image tab | P1 |
| Product inventory / stock / SKU | `product_variants`, `inventory_adjustments`, fallback fields on `products` | Cart/order APIs consume effective SKU/stock | `InventoryResource`, product variant/product forms | Product list needed stock status and low-stock scanability | None blocking | Added stock columns/filters and product variant management remains canonical | P0 |
| Cart and cart items | `carts`, `cart_items`; `Cart`, `CartItem` | `/api/cart`, `/api/cart/items` | None before; added `CartResource` | No abandoned cart view | None blocking | Added read-only carts resource with abandoned and guest filters | P0 |
| Orders and order items | `orders`, `order_items`; `Order`, `OrderItem` | `/api/orders`, guest lookup routes | `OrderResource`, store widgets | List did not show guest/customer/shipping/payment enough for operations | None blocking | Added operational columns, filters, note/payment/status actions, timeline updates | P0 |
| Guest orders | `orders.guest_email`, `guest_order_token` | `/api/orders/guest/{order}` | `OrderResource` | Guest email/customer type was not obvious | None blocking | Added guest email column and guest/registered filter | P0 |
| Addresses | `addresses`; `Address` | `/api/addresses` | None before; added `AddressResource` | No admin view of customer addresses | None blocking | Added read-only addresses resource | P0 |
| Shipping | Shipping fields on `orders`, shipping service classes | `/api/store/shipping-options`, `/api/store/address-search` | `OrderResource` | City/country filters and shipping detail were limited | No live carrier admin dashboard | Added order shipping columns/filters; carrier configuration remains env/service-based | P1 |
| Manual payment status | `orders.payment_status`, `payment_reference` | Order APIs store manual payment state | `OrderResource` | Payment state was present but not quickly actionable | No gateway by design | Added update payment action and manual reference field | P0 |
| B2B leads | Unified `inquiries` model as `B2BLead`, detail tables | `/api/business-contacts`, `/api/admin/b2b-leads` | `B2BLeadResource`, `LeadOperationsOverview`, `RecentLeads` | Priority/follow-up were missing; unified center needed clearer label | No CRM sync | Added priority, follow-up date, status actions, widgets, CSV export remains | P0 |
| Inquiries | `inquiries`; `B2BLead` legacy type | `/api/inquiries` | `EnquiryResource` | Follow-up and priority missing | Convert-to-lead is implicit because storage is unified | Added priority/follow-up fields and admin actions | P0 |
| Business contacts | `inquiries.lead_type=business_contact` | `/api/business-contacts` | `B2BLeadResource`, `EnquiryResource` filters | Separate page not needed because table is unified | No separate business contacts table | Exposed through All Leads and General Enquiries | P0 |
| Partnership inquiries | `partnership_inquiries`; `PartnershipInquiry` | `/api/partnership-inquiries` | `B2BLeadResource` | Needs type filter and detail visibility | None blocking | Unified lead center shows related detail and type | P0 |
| Sample requests | `sample_requests`; `SampleRequest` | `/api/sample-requests` | `B2BLeadResource` | Needs type filter and detail visibility | None blocking | Unified lead center shows sample detail and type | P0 |
| University collaborations | `partnership_inquiries.collaboration_type` | `/api/university-collaborations` | `B2BLeadResource` | No separate resource, but duplicate resource would be noisy | None blocking | Exposed by lead type/filter in All Leads | P1 |
| Product development collaborations | `partnership_inquiries.collaboration_type` | `/api/product-development-collaborations` | `B2BLeadResource` | No separate resource, but duplicate resource would be noisy | None blocking | Exposed by lead type/filter in All Leads | P1 |
| Community posts | `posts`, `post_images`, `idea_media`; `Post` | `/api/posts`, `/api/admin/posts/{post}/status` | `PostResource`, moderation pages/widgets | Demo content cleanup and funding URL validation needed | None blocking | Added demo content flag, cleanup action, funding URL validation | P0 |
| Comments | `comments`, `comment_likes`; `Comment` | `/api/comments`, admin status routes | `CommentResource`, moderation queue/widgets | Existing queue usable | None blocking | Navigation and widget labels localized | P1 |
| Likes/favorites/follows | `post_likes`, `comment_likes`, `favorites`, `follows` | Engagement APIs | Counts visible on posts/users | No standalone admin resources | Standalone moderation is not needed | Keep as metrics unless abuse tooling is needed later | P2 |
| Funding campaigns / funding links | `posts.funding_url`, `funding_campaigns`; `FundingCampaign` | `/api/admin/posts/{post}/funding-campaign` | `FundingCampaignResource`, `PostResource` | Unsafe protocols were not explicitly rejected | Internal payment platform is intentionally out of scope | Added safe external URL rule; retain external-link-only approach | P0 |
| Reports | `reports`; `Report` | `/api/admin/reports` | `ReportResource`, moderation widgets | Existing queue usable | None blocking | Navigation translated under Community | P1 |
| Moderation logs | `moderation_logs`, `admin_action_logs` | Governance history APIs | `ModerationLogResource`, `AdminActionLogResource` | Grouping needed clarity | None blocking | Moved under Users & Governance | P1 |
| User violations | `user_violations`; `UserViolation` | `/api/admin/users/{user}/violations` | `UserViolationResource` | Existing resource usable | None blocking | Navigation translated under Users & Governance | P1 |
| Notifications | `notifications`; `UserNotification` | `/api/notifications`, admin announcement API | `UserNotificationResource` | Resource naming needed operator language | None blocking | Label changed to announcements under governance | P2 |
| Media files / uploads | `media_files`, `idea_media`, `product_images`, `post_images` | `/api/media/upload`, attachment routes | `IdeaMediaResource`, `ProductImageResource`; added `MediaFileResource` | No central media library | Delete safety checks needed | Added read-only Media Library with preview/download and no broad deletes | P0 |
| Materials CMS | `materials`; `Material` | `/api/materials`, admin CMS APIs | `MaterialResource` | Existing localized form was present but nav needed CMS grouping | Family metadata is still represented as material entries, not a dedicated enum | Keep OXP/CXP/MXP as managed material records/content until a dedicated family table is required | P1 |
| Material specs | `material_specs`; `MaterialSpec` | Material APIs include specs | `MaterialSpecResource`, nested material form | Existing resource usable | None blocking | Grouped under Content / CMS | P1 |
| Material story sections | `material_story_sections`; `MaterialStorySection` | Material APIs include story sections | `MaterialStorySectionResource`, nested material form | Existing resource usable | None blocking | Grouped under Content / CMS | P1 |
| Material applications | `material_applications`; `MaterialApplication` | Material APIs include applications | `MaterialApplicationResource`, nested material form | Existing resource usable | None blocking | Grouped under Content / CMS | P1 |
| OXP/CXP/MXP family content | `materials` and related CMS tables | `/api/materials` | `MaterialResource` | No dedicated "Material Family" label in nav | Dedicated availability state for MXP is not modeled | Documented operating rule: OXP current, CXP is Carbon XP, MXP pending/not available. Add dedicated family table only if product roadmap needs it | P2 |
| Articles | `articles`; `Article` | `/api/articles`, admin article APIs | `ArticleResource` | Existing resource usable | None blocking | Grouped under Content / CMS | P1 |
| Home sections | `home_sections`; `HomeSection` | `/api/homepage`, `/api/home-sections`, admin APIs | `HomeSectionResource` | Existing resource usable | None blocking | Grouped under Content / CMS | P1 |
| Email settings | `email_settings`; `EmailSetting` | Artisan/test services, admin page | `EmailSettings` page | Delivery state was not visible enough | None blocking | Added enabled/provider/failed/last-sent status and test email action label | P0 |
| Email events | `email_events`; `EmailEvent` | Email dispatch services | `EmailEventResource` | Existing event switches usable | None blocking | Grouped under Email Center and labels translated | P1 |
| Email templates | `email_templates`, `email_template_versions`; `EmailTemplate` | Email renderer/dispatch services | `EmailTemplateResource` | Existing localization present | None blocking | Grouped under Email Center and labels translated | P1 |
| Email logs | `email_logs`; `EmailLog` | Dispatch services create logs | `EmailLogResource` | Existing logs usable | None blocking | Grouped under Email Center | P1 |
| Dashboard widgets | Models aggregate store/leads/community | No public route | Store, lead, community, moderation widgets | Missing lead and demo-content operational stats | None blocking | Added lead week/overdue and community uploads/demo stats | P1 |
| System/handover readiness | App config, DB, storage, mail, queue, failed jobs | No API route | None before; added `SystemHandoverReadiness` page | No single pre-handover checklist | Secrets must not be exposed | Added System / Handover page with OK/Warning/Error badges | P0 |

## Admin Navigation Result

Filament navigation is now organized for non-technical operations:

1. Dashboard
2. Store Operations
3. B2B / Leads
4. Community
5. Content / CMS
6. Email Center
7. Users & Governance
8. Media Library
9. System / Handover

The admin brand is `Terraf OXP Admin` in English, with Korean and Chinese translations. No backend admin branding remains as `Shellfin Operations`.

## Notes

- Public API routes were not renamed or removed.
- Store product variants already exist through `product_variants`; this work exposed the current model instead of inventing fake variants.
- Community funding remains external-link-only. No payment, pledge, or fundraising ledger was added.
- User-generated content is not translated automatically.
- Korean localization covers admin shell, navigation, core resource labels, actions, statuses, widgets, and system readiness labels. Older deeply nested field labels should continue to be migrated to `admin.php` as resources evolve.
