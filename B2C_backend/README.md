# OXP Backend API

Laravel 13 REST API backend for the OXP platform. It provides authentication, community features, CMS content delivery, B2B lead capture, media management, and an internal moderation panel.

The frontend is a separate Next.js application that consumes this service over REST with JSON responses.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Admin Panel | Filament 5 |
| API Authentication | Laravel Sanctum (token-based) |
| Database | MySQL 8+ |
| Cache & Queue | Redis |
| File Storage | Azure Blob Storage (primary) + local `public` disk (fallback) |
| File System Adapter | League Flysystem Azure Blob Storage |
| Testing | PHPUnit 12.5+ |
| Code Style | Laravel Pint |

---

## Architecture

```text
routes/api.php
  └── Controller (thin — delegates immediately)
        └── FormRequest (validation + basic authorization)
              └── Service (business logic, transactions, aggregation)
                    ├── Model / Eloquent
                    ├── Policy (fine-grained authorization)
                    ├── API Resource (response serialization)
                    ├── Storage (Azure / local)
                    └── Queue Job (async notifications)
```

- **Controllers** (`app/Http/Controllers/Api/`) — receive the request, call the service, return a response
- **Form Requests** (`app/Http/Requests/`) — validate input fields and handle basic authorization checks
- **Services** (`app/Services/`) — contain all business logic, database transactions, and cross-model coordination
- **Resources** (`app/Http/Resources/`) — serialize models into stable JSON shapes, inject viewer-aware flags (`is_liked`, `is_favorited`, `is_following`, `can_edit`, `can_delete`)
- **Policies** (`app/Policies/`) — gate post, comment, report, and notification operations per user role and ownership
- **Middleware** (`app/Middleware/`) — `EnsureUserNotBanned` blocks restricted/banned users from write operations
- **Support** (`app/Support/ApiResponse.php`) — shared trait for consistent `{ success, message, data, meta }` envelope

---

## Response Contract

Every endpoint returns one of these three shapes:

```json
// Single resource or action confirmation
{
  "success": true,
  "message": "Optional human-readable message",
  "data": {}
}

// Paginated list
{
  "success": true,
  "message": null,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}

// Validation error or application error
{
  "success": false,
  "message": "Error summary",
  "errors": {
    "field": ["Validation message"]
  }
}
```

---

## Setup

### Requirements

- PHP 8.3+ with extensions: `intl`, `pdo_mysql`, `mbstring`, `openssl`
- Composer
- MySQL 8+
- Redis

### Installation

```bash
cd B2C_backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database, Redis, and storage settings in `.env`, then:

```bash
php artisan migrate --seed
php artisan serve
```

Start the queue worker in a separate terminal (required for async notifications):

```bash
php artisan queue:work
```

**API** is available at: `http://127.0.0.1:8000`  
**Admin panel** is available at: `http://127.0.0.1:8000/admin`

### Lightweight Local Configuration

To run without Azure Blob Storage or Redis:

```env
QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
FILESYSTEM_DISK=public
COMMUNITY_UPLOAD_DISK=public
```

---

## Environment Variables

See `.env.example` for the full reference. Key variables:

### Core

```env
APP_NAME=OXP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_community
DB_USERNAME=root
DB_PASSWORD=
```

### Session, Cache, Queue

```env
SESSION_DRIVER=database        # Required by Filament admin panel
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### CORS and Authentication

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
FRONTEND_URL=http://localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
```

### Azure Blob Storage

```env
FILESYSTEM_DISK=azure
COMMUNITY_UPLOAD_DISK=azure
AZURE_STORAGE_NAME=your-storage-account-name
AZURE_STORAGE_KEY=your-storage-account-key
AZURE_STORAGE_CONTAINER=uploads
AZURE_STORAGE_URL=https://your-storage-account-name.blob.core.windows.net
AZURE_STORAGE_USE_SAS_URLS=true
AZURE_STORAGE_SAS_URL_TTL_MINUTES=10080
```

### MySQL TLS (Azure Database for MySQL)

If your MySQL server enforces `require_secure_transport=ON`:

```env
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true
```

After updating `.env`, clear cached config:

```bash
php artisan optimize:clear
```

### Upload Limits

```env
IDEA_MEDIA_DIRECTORY=ideas
IDEA_MEDIA_MAX_FILES=12
IDEA_MEDIA_MAX_FILE_SIZE_KB=10240
IDEA_MEDIA_ALLOWED_EXTENSIONS=jpg,jpeg,png,webp,gif,pdf,doc,docx,ppt,pptx,xls,xlsx
```

### Feature Flags

```env
COMMUNITY_SENSITIVE_WORDS_ENABLED=false
COMMUNITY_SENSITIVE_WORDS=
B2B_LEADS_NOTIFY_ADMINS=false
B2B_LEAD_NOTIFICATION_RECIPIENTS=
ALLOW_GUEST_UPLOAD=false
```

---

## Database Schema

### User & Identity

| Table | Description |
|---|---|
| `users` | Core user accounts |
| `profiles` | Extended profile data (bio, school, region, portfolio, open-to-collab) |
| `personal_access_tokens` | Sanctum API tokens |
| `sessions` | Web sessions for Filament admin |
| `password_reset_tokens` | Password reset flow |

### Community Content

| Table | Description |
|---|---|
| `posts` | Creator concept submissions |
| `post_images` | Legacy image attachments |
| `idea_media` | Structured media attachments (images, documents, 3D links) |
| `comments` | Post comments and replies |
| `categories` | Content categories |
| `tags` | Content tags |
| `post_tags` | Post-to-tag pivot |

### Community Interaction

| Table | Description |
|---|---|
| `post_likes` | Post likes |
| `comment_likes` | Comment likes |
| `favorites` | Saved/favorited posts |
| `follows` | User follow relationships |

### Governance & Moderation

| Table | Description |
|---|---|
| `reports` | Content reports submitted by users |
| `moderation_logs` | Full moderation action history |
| `user_violations` | Per-user violation records with severity and status |
| `admin_action_logs` | Admin action audit trail |

### B2B Leads

| Table | Description |
|---|---|
| `inquiries` | Generic inquiry submissions (legacy) |
| `b2b_leads` | Unified B2B lead table |
| `partnership_inquiries` | Partnership inquiry details |
| `sample_requests` | Sample request details |
| `business_contacts` | Business contact leads |

### Commerce

| Table | Description |
|---|---|
| `products` | Product catalog |
| `product_categories` | Product categories |
| `carts` | Shopping carts |
| `cart_items` | Cart line items |
| `orders` | Orders |
| `order_items` | Order line items |
| `addresses` | User shipping/billing addresses |

### CMS Content

| Table | Description |
|---|---|
| `materials` | Material showcase entries |
| `material_specs` | Material technical specifications |
| `material_story_sections` | Material narrative sections |
| `material_applications` | Material application examples |
| `articles` | News and blog articles |
| `home_sections` | Dynamic homepage sections |

### Other

| Table | Description |
|---|---|
| `notifications` | User notifications |
| `funding_campaigns` | External crowdfunding campaign metadata on concepts |
| `media_files` | Centralized media file tracking |

### Content Status Values

| Status | Meaning |
|---|---|
| `pending` | Awaiting moderator review (default for creator submissions) |
| `approved` | Visible publicly |
| `rejected` | Hidden, creator notified |
| `hidden` | Taken down post-approval |

### User Roles

| Role | Description |
|---|---|
| `visitor` | Read-only access, cannot post |
| `creator` | Can submit concepts; posts go to `pending` |
| `sme_partner` | SME/business partner access level |
| `moderator` | Can approve/reject/hide content; access to Filament panel |
| `admin` | Full access including user management and analytics |

---

## API Endpoints

### Health

```
GET /          API status
GET /up        Laravel health check
```

### Authentication

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
PATCH  /api/auth/profile
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
POST   /api/auth/email/verification-notification
GET    /api/auth/email/verify/{id}/{hash}
```

### Community Posts

```
GET    /api/posts
POST   /api/posts                            # Requires creator, moderator, or admin role
GET    /api/posts/{id_or_slug}
PATCH  /api/posts/{id}
DELETE /api/posts/{id}
```

**`GET /api/posts` query parameters:**

| Parameter | Example | Description |
|---|---|---|
| `q` | `oyster` | Keyword search |
| `sort` | `latest\|hot\|popular\|trending\|most_liked\|most_discussed` | Sort order |
| `category` | `furniture` | Filter by category slug |
| `category_id` | `3` | Filter by category ID |
| `tag` | `oyster-shell` | Filter by tag slug |
| `user_id` | `42` | Filter by author ID |
| `creator` | `janedoe` | Filter by username |
| `creator_role` | `creator` | Filter by author role |
| `school_or_company` | `Auckland Design Lab` | Filter by institution |
| `region` | `Auckland` | Filter by region |
| `featured` | `1` | Only featured concepts |
| `pinned` | `1` | Only pinned concepts |
| `status` | `approved` | Content status (staff only for non-approved) |

**Post create/update fields:**

- `title`, `content`, `category_id`, `tags[]`
- `images[]`, `image_alts[]` — legacy image upload
- `attachments[]`, `attachment_titles[]`, `attachment_alts[]`, `attachment_kinds[]` — structured media upload
- `model_3d_links[][url]`, `model_3d_links[][title]` — external 3D references
- `remove_media_ids[]`, `remove_image_ids[]` — remove existing media on update
- `replace_media[][id|file|external_url]` — replace existing media on update

### Comments

```
GET    /api/posts/{id}/comments
POST   /api/posts/{id}/comments
PATCH  /api/comments/{id}
POST   /api/comments/{id}/reply
DELETE /api/comments/{id}
```

### Engagement

```
POST   /api/posts/{id}/like
DELETE /api/posts/{id}/like
POST   /api/comments/{id}/like
DELETE /api/comments/{id}/like
POST   /api/posts/{id}/favorite
DELETE /api/posts/{id}/favorite
POST   /api/users/{id}/follow
DELETE /api/users/{id}/follow
```

### Users and Taxonomy

```
GET    /api/users/{id}
GET    /api/users/{id}/posts
GET    /api/users/{id}/comments
GET    /api/users/{id}/followers
GET    /api/users/{id}/following
GET    /api/categories
GET    /api/tags
```

### CMS Content and Homepage

```
GET    /api/homepage
GET    /api/home-sections
GET    /api/materials
GET    /api/materials/{id_or_slug}
GET    /api/articles
GET    /api/articles/{id_or_slug}
```

**`GET /api/materials` query parameters:** `featured=1`

**`GET /api/articles` query parameters:** `category=updates`, `per_page=10`

### Commerce

```
GET    /api/product-categories
GET    /api/products
GET    /api/products/{slug}
GET    /api/cart
POST   /api/cart/items
PATCH  /api/cart/items/{id}
DELETE /api/cart/items/{id}
GET    /api/addresses
POST   /api/addresses
PATCH  /api/addresses/{id}
DELETE /api/addresses/{id}
GET    /api/orders
GET    /api/orders/{id}
```

### B2B Leads

All lead endpoints are rate-limited and support optional shared fields: `organization_type`, `region`, `company_website`, `job_title`, `source_page`, `metadata`.

```
POST   /api/inquiries                              # Legacy generic inquiry
POST   /api/business-contacts
POST   /api/partnership-inquiries
POST   /api/sample-requests
POST   /api/university-collaborations
POST   /api/product-development-collaborations
```

### Notifications, Reports, Search

```
GET    /api/notifications
GET    /api/notifications?read=unread&type=favorite
PATCH  /api/notifications/{id}/read
POST   /api/reports
GET    /api/search/posts?q=keyword
```

**`GET /api/search/posts` supports the same query parameters as `GET /api/posts`.**

### Media Upload

```
POST   /api/media/upload
DELETE /api/media
POST   /api/media/upload/guest        # Requires ALLOW_GUEST_UPLOAD=true
```

Uploaded files are organized by type: `images / videos / audios / documents / others`  
Path format: `{type}/{category}/{YYYY/MM}/{uuid}.{ext}`

---

## Admin API Endpoints

All `/api/admin/*` routes require the `admin` or `moderator` role.

### Content Moderation

```
GET    /api/admin/reports
PATCH  /api/admin/reports/{id}/status
PATCH  /api/admin/posts/{id}/status
PATCH  /api/admin/posts/{id}/feature
PATCH  /api/admin/comments/{id}/status
GET    /api/admin/posts/{id}/review-history
GET    /api/admin/comments/{id}/review-history
GET    /api/admin/posts/ranking-formula
```

### User Management

```
PATCH  /api/admin/users/{id}/role
PATCH  /api/admin/users/{id}/account-status
PATCH  /api/admin/users/{id}/ban
GET    /api/admin/users/{id}/moderation-history
GET    /api/admin/users/{id}/admin-actions
GET    /api/admin/users/{id}/violations
POST   /api/admin/users/{id}/violations
PATCH  /api/admin/users/{id}/violations/{violation_id}
```

### Taxonomy CRUD

```
GET|POST              /api/admin/categories
GET|PATCH|DELETE      /api/admin/categories/{id}
GET|POST              /api/admin/tags
GET|PATCH|DELETE      /api/admin/tags/{id}
```

### CMS CRUD

```
GET|POST              /api/admin/materials
GET|PATCH|DELETE      /api/admin/materials/{id}
GET|POST              /api/admin/material-specs
GET|PATCH|DELETE      /api/admin/material-specs/{id}
GET|POST              /api/admin/material-story-sections
GET|PATCH|DELETE      /api/admin/material-story-sections/{id}
GET|POST              /api/admin/material-applications
GET|PATCH|DELETE      /api/admin/material-applications/{id}
GET|POST              /api/admin/articles
GET|PATCH|DELETE      /api/admin/articles/{id}
GET|POST              /api/admin/home-sections
GET|PATCH|DELETE      /api/admin/home-sections/{id}
```

### B2B Lead Management

```
GET    /api/admin/b2b-leads
GET    /api/admin/b2b-leads/{id}
PATCH  /api/admin/b2b-leads/{id}
GET    /api/admin/b2b-leads/export         # CSV export with search and status filters
```

### Funding Campaigns

```
GET    /api/admin/posts/{id}/funding-campaign
PATCH  /api/admin/posts/{id}/funding-campaign
DELETE /api/admin/posts/{id}/funding-campaign
```

### Notifications and Analytics

```
POST   /api/admin/notifications/announcements
GET    /api/admin/analytics/overview
```

---

## Admin Panel (Filament)

The Filament admin panel at `/admin` is for internal staff only.

- **Login URL:** `/admin/login`
- **Allowed roles:** `admin`, `moderator`
- **Session-based:** requires `SESSION_DRIVER=database` and the `sessions` table

### Navigation Groups

| Group | Contents |
|---|---|
| Community | Concepts, comments, idea media, reports |
| Content | Materials, specs, story sections, applications, articles, homepage sections |
| Growth | B2B leads, funding campaigns |
| Governance | Users, violations, moderation logs, admin action logs |
| Taxonomy | Categories, tags |
| System | Notifications |

### Panel Capabilities

- Dashboard widgets: operational stats, analytics snapshot, recent governance activity
- User management: roles, account status, verification, extended profiles, moderation history
- Concept management: engagement metrics, featured state, funding campaign visibility, creator context
- Idea media oversight: file metadata, previews, moderation review
- Comment, report, violation, and audit log workflows
- CMS content management for all material and article types
- B2B lead management with status tracking, internal notes, and CSV export
- System announcement broadcasting to targeted user roles

---

## Seeded Test Accounts

After `php artisan migrate --seed`:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Moderator | `moderator@example.com` | `password` |
| Banned user | `banned@example.com` | `password` |

Seeded CMS content includes:

- One published featured material at slug `premium-oyster-shell`
- Published material specs, story sections, and application sections
- Published homepage sections (hero, science, updates)
- Published sample articles
- One approved concept with a sample live external funding campaign

---

## Upload Behavior

| Upload Type | Endpoint | Field |
|---|---|---|
| Profile avatar | `PATCH /api/auth/profile` | `avatar` |
| Legacy post images | `POST /api/posts` or `PATCH /api/posts/{id}` | `images[]` |
| Structured idea media | `POST /api/posts` or `PATCH /api/posts/{id}` | `attachments[]` |
| External 3D references | `POST /api/posts` or `PATCH /api/posts/{id}` | `model_3d_links[][url]` |
| CMS media | Admin CMS create/update endpoints | `media` |
| CMS media removal | Admin CMS update endpoints | `remove_media=true` |

---

## Payload Examples

### Register

```json
{
  "name": "Jane Doe",
  "username": "janedoe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "creator"
}
```

### Update Profile

```json
{
  "bio": "Material-focused product designer.",
  "school_or_company": "Auckland Design Lab",
  "region": "Auckland, New Zealand",
  "portfolio_url": "https://portfolio.example.com",
  "open_to_collab": true
}
```

### Create Concept with Mixed Media

```json
{
  "title": "Oyster shell stool concept",
  "content": "Concept submission with sketches, renders, a PDF deck, and a 3D reference link.",
  "attachment_titles": ["Render board", "Pitch deck"],
  "attachment_alts": ["Rendered shell stool board", null],
  "attachment_kinds": ["render_image", "pdf_presentation"],
  "model_3d_links": [
    {
      "url": "https://sketchfab.com/models/oyster-shell-stool",
      "title": "3D exploration"
    }
  ]
}
```

### Submit a Business Contact Lead

```json
{
  "name": "Ariana Kim",
  "company_name": "Blue Current",
  "organization_type": "brand",
  "email": "ariana@example.com",
  "job_title": "Partnership Director",
  "region": "Seoul",
  "company_website": "https://bluecurrent.example.com",
  "message": "We want to discuss a premium packaging collaboration.",
  "source_page": "materials:hero"
}
```

### Submit a Sample Request

```json
{
  "name": "Mika Tan",
  "company_name": "Carbon Form",
  "organization_type": "manufacturer",
  "email": "mika@example.com",
  "country": "Japan",
  "region": "Osaka",
  "message": "We need evaluation samples for an interior pilot.",
  "material_interest": "Pressed oyster-shell panel",
  "quantity_estimate": "10 sheets",
  "shipping_country": "Japan",
  "shipping_region": "Osaka",
  "intended_use": "Interior wall system prototyping."
}
```

### Attach Funding Campaign to Concept (Admin)

```json
{
  "support_enabled": true,
  "support_button_text": "Back this concept",
  "external_crowdfunding_url": "https://crowdfund.example.com/projects/oyster-shell-chair",
  "campaign_status": "live",
  "target_amount": 15000,
  "pledged_amount": 4200,
  "backer_count": 64,
  "campaign_start_at": "2026-05-01T00:00:00Z",
  "campaign_end_at": "2026-06-01T00:00:00Z"
}
```

### Broadcast System Announcement (Admin)

```json
{
  "title": "Platform update",
  "body": "Material showcase content has been refreshed.",
  "action_url": "/materials/premium-oyster-shell",
  "roles": ["creator", "sme_partner"]
}
```

---

## Post Ranking

Posts support several discovery sort modes. The ranking formulas are:

```
engagement_score = (likes_count × 3) + (comments_count × 4) + (favorites_count × 2)
trending_score   = ((weekly_likes × 3 + weekly_comments × 4 + weekly_favorites × 2) × 10) + recency_boost
```

Trending uses a 7-day rolling window. The full formula is available to staff at `GET /api/admin/posts/ranking-formula`.

---

## Analytics Overview

`GET /api/admin/analytics/overview` returns:

```json
{
  "generated_at": "2026-04-28T00:00:00Z",
  "summary": {
    "total_concepts": 42,
    "approved_concepts": 30,
    "total_views": 1840,
    "support_enabled_concepts": 4
  },
  "categories": { "most_common": [], "highest_engagement": [] },
  "activity": { "schools_or_companies": [], "user_groups": [] },
  "attention": {
    "most_viewed_concepts": [],
    "best_performing_cta_sources": []
  },
  "funding": {
    "readiness_formula": {
      "formula": "(engagement_score) + (favorites_count × 3) + (views_count / 10) + featured_bonus + collab_bonus + support_bonus"
    },
    "most_likely_concepts": []
  }
}
```

---

## Running Tests

The test suite uses in-memory SQLite, so no database setup is required.

```bash
php artisan test
```

Coverage areas: authentication, user profiles, products, cart and orders, community posts and comments, interactions and notifications, search and discovery, B2B leads, CMS content, admin moderation and governance, media upload, taxonomy.

## Code Formatting

```bash
vendor/bin/pint
```

---

## Notes for Frontend Integration

- All responses are JSON and stable for REST consumption
- User, post, comment, report, and notification payloads are normalized through API Resources
- `is_liked`, `is_favorited`, and `is_following` flags are included in responses where applicable
- Public user profile responses include `followers_count`, `following_count`, `posts_count`, `comments_count`
- Post slugs are generated automatically from titles
- Search only returns `approved` posts by default; `status` filter is available to staff
- Post responses include both a legacy `images` array (for backward compatibility) and a structured `media` array for mixed attachments
- Post discovery responses include `engagement_score`, `trending_score`, and `featured_at`
- `sort=hot` is a backward-compatible alias for popularity ordering
- Notification responses include `title`, `body`, `action_url`, and `meta.unread_count`
- Concept funding support is external-link only — no internal payment or pledge processing
- CTA source analytics are derived from `inquiries.source_page` and ranked by lead volume
- `GET /api/posts` and `GET /api/search/posts` share the same filter and sort parameter set
- Post detail views increment `posts.views_count` for public approved concepts (feeds analytics)
- Sensitive-word flagging is config-driven and records audit entries without blocking the pending-review flow
- Admin lead management supports search, status updates, internal notes, and CSV export
- Result ordering uses stable `created_at` + `id` tie-breaks for consistent pagination under load
