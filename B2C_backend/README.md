# Product Community API

Laravel API backend for the oyster-shell material showcase, creator idea submission community, and future collaboration platform. The frontend is assumed to live separately in Next.js + TypeScript and consume this service over REST with JSON responses.

## Stack

- PHP 8.4
- Laravel 13
- Filament 5 for the internal admin panel
- MySQL for the primary database
- Redis for cache and queues
- S3-compatible object storage for avatars and post images
- Laravel Sanctum for API authentication

## What This Backend Supports

- Authentication: register, login, logout, current user, email verification, password reset, profile update
- RBAC: `visitor`, `creator`, `sme_partner`, `moderator`, `admin`
- Account controls: `active`, `restricted`, `banned`
- Posts: create, update, delete, list, detail
- Idea media: multi-file image/document attachments, legacy image compatibility, and external 3D links
- Comments: create, reply, edit, delete, list by post and by user
- Likes: posts and comments
- Favorites: posts
- Follows: users, followers, following
- Reports: post and comment reporting
- Notifications: approvals, rejections, comments, replies, likes, favorites, featured concepts, follows, and system announcements
- Moderation: `pending`, `approved`, `rejected`, `hidden`
- Governance: user violations, moderation history, admin action logs, review history, and optional sensitive-word flagging
- Admin: review reports, moderate posts/comments, ban users
- Material CMS: materials, specs, story sections, applications, home sections, and articles
- Homepage content aggregation for hero, science, and editorial sections
- Discovery: latest, hot, popular, trending, most liked, most discussed, featured concepts
- Collaboration leads: business contacts, partnership inquiries, sample requests, university collaborations, and product development collaborations
- Lightweight concept funding support through external crowdfunding campaigns
- Internal admin panel for admins and moderators at `/admin`
- Uploads: avatar upload, idea media upload, and CMS media upload
- Search: post title/content search
- Taxonomy: categories and tags with public list endpoints and admin CRUD

## Architecture

- API-only routing in [`routes/api.php`](/c:/Users/xingz/Desktop/B2C_backend/routes/api.php)
- Web-based internal admin panel through Filament at `/admin`
- Thin controllers delegating to service classes in [`app/Services`](/c:/Users/xingz/Desktop/B2C_backend/app/Services)
- Validation through Form Requests in [`app/Http/Requests`](/c:/Users/xingz/Desktop/B2C_backend/app/Http/Requests)
- Authorization through policies in [`app/Policies`](/c:/Users/xingz/Desktop/B2C_backend/app/Policies)
- Stable response formatting via API Resources and the shared API response trait
- Queued notification creation through [`CreateUserNotificationJob`](/c:/Users/xingz/Desktop/B2C_backend/app/Jobs/CreateUserNotificationJob.php)

## Core Tables

- `users`
- `profiles`
- `password_reset_tokens`
- `posts`
- `post_images`
- `idea_media`
- `categories`
- `tags`
- `post_tags`
- `comments`
- `post_likes`
- `comment_likes`
- `favorites`
- `follows`
- `notifications`
- `reports`
- `moderation_logs`
- `user_violations`
- `admin_action_logs`
- `inquiries`
- `partnership_inquiries`
- `sample_requests`
- `funding_campaigns`
- `materials`
- `material_specs`
- `material_story_sections`
- `material_applications`
- `articles`
- `home_sections`
- `personal_access_tokens`

## Status Rules

- Creator posts and comments are created as `pending`
- Admin-created posts and comments are created as `approved`
- Public endpoints only return approved content by default
- Owners can still fetch their own pending posts/comments where applicable
- Restricted and banned users cannot create posts, comments, likes, favorites, follows, or reports
- Only creators, moderators, and admins can submit concepts through `POST /api/posts`

## Response Contract

Successful responses:

```json
{
  "success": true,
  "message": "Optional human-readable message",
  "data": {}
}
```

Paginated responses:

```json
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
```

Error responses:

```json
{
  "success": false,
  "message": "Error summary",
  "errors": {}
}
```

## Main Endpoints

### Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `PATCH /api/auth/profile`
- `POST /api/auth/email/verification-notification`
- `GET /api/auth/email/verify/{id}/{hash}`
- `GET /api/auth/reset-password/{token}`

### Posts and Comments

- `GET /api/posts`
- `POST /api/posts`
- `GET /api/posts/{id_or_slug}`
- `PATCH /api/posts/{id}`
- `DELETE /api/posts/{id}`
- `GET /api/posts/{id}/comments`
- `POST /api/posts/{id}/comments`

`POST /api/posts` and `PATCH /api/posts/{id}` support:

- legacy `images[]` + `image_alts[]`
- new `attachments[]` + `attachment_titles[]` + `attachment_alts[]` + `attachment_kinds[]`
- `model_3d_links[][url]` for external 3D links
- `remove_media_ids[]`, `remove_image_ids[]`, and `replace_media[][id|file|external_url]` on update
- post responses may also include `support_enabled`, `support_button_text`, `external_crowdfunding_url`, `campaign_status`, and `funding_campaign`
- `PATCH /api/comments/{id}`
- `POST /api/comments/{id}/reply`
- `DELETE /api/comments/{id}`

`GET /api/posts` also supports optional query params such as:

- `sort=latest|hot|popular|trending|most_liked|most_discussed`
- `user_id=`
- `creator=alicecreator`
- `school_or_company=Auckland Design Lab`
- `region=Auckland`
- `status=approved|pending|rejected|hidden`
- `category=` or `category_id=`
- `tag=`
- `featured=1`
- `pinned=1`

### Engagement

- `POST /api/posts/{id}/like`
- `DELETE /api/posts/{id}/like`
- `POST /api/comments/{id}/like`
- `DELETE /api/comments/{id}/like`
- `POST /api/posts/{id}/favorite`
- `DELETE /api/posts/{id}/favorite`
- `POST /api/users/{id}/follow`
- `DELETE /api/users/{id}/follow`

### Public User and Taxonomy

- `GET /api/users/{id}`
- `GET /api/users/{id}/posts`
- `GET /api/users/{id}/comments`
- `GET /api/users/{id}/followers`
- `GET /api/users/{id}/following`
- `GET /api/categories`
- `GET /api/tags`

### Material CMS and Homepage

- `GET /api/homepage`
- `GET /api/home-sections`
- `GET /api/materials`
- `GET /api/materials/{id_or_slug}`
- `GET /api/articles`
- `GET /api/articles/{id_or_slug}`

`GET /api/materials` also supports:

- `featured=1`

`GET /api/articles` also supports:

- `category=updates`
- `per_page=10`

### Collaboration and B2B Leads

- `POST /api/inquiries`
- `POST /api/business-contacts`
- `POST /api/partnership-inquiries`
- `POST /api/sample-requests`
- `POST /api/university-collaborations`
- `POST /api/product-development-collaborations`

Lead capture endpoints are rate-limited and support optional shared fields such as:

- `organization_type`
- `region`
- `company_website`
- `job_title`
- `source_page`
- `metadata`

### Notifications, Reports, Search

- `GET /api/notifications`
- `GET /api/notifications?read=unread&type=favorite`
- `PATCH /api/notifications/{id}/read`
- `POST /api/reports`
- `GET /api/search/posts?q=keyword`

### Admin

- `GET /api/admin/reports`
- `PATCH /api/admin/reports/{id}/status`
- `GET /api/admin/posts/ranking-formula`
- `PATCH /api/admin/posts/{id}/status`
- `PATCH /api/admin/posts/{id}/feature`
- `GET /api/admin/posts/{id}/funding-campaign`
- `PATCH /api/admin/posts/{id}/funding-campaign`
- `DELETE /api/admin/posts/{id}/funding-campaign`
- `POST /api/admin/notifications/announcements`
- `GET /api/admin/b2b-leads/export`
- `GET /api/admin/b2b-leads`
- `GET /api/admin/b2b-leads/{id}`
- `PATCH /api/admin/b2b-leads/{id}`
- `PATCH /api/admin/comments/{id}/status`
- `GET /api/admin/users/{id}/moderation-history`
- `GET /api/admin/users/{id}/admin-actions`
- `GET /api/admin/users/{id}/violations`
- `POST /api/admin/users/{id}/violations`
- `PATCH /api/admin/users/{id}/violations/{violation_id}`
- `GET /api/admin/posts/{id}/review-history`
- `GET /api/admin/comments/{id}/review-history`
- `PATCH /api/admin/users/{id}/role`
- `PATCH /api/admin/users/{id}/account-status`
- `PATCH /api/admin/users/{id}/ban`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `GET /api/admin/categories/{id}`
- `PATCH /api/admin/categories/{id}`
- `DELETE /api/admin/categories/{id}`
- `GET /api/admin/tags`
- `POST /api/admin/tags`
- `GET /api/admin/tags/{id}`
- `PATCH /api/admin/tags/{id}`
- `DELETE /api/admin/tags/{id}`
- `GET /api/admin/materials`
- `POST /api/admin/materials`
- `GET /api/admin/materials/{id}`
- `PATCH /api/admin/materials/{id}`
- `DELETE /api/admin/materials/{id}`
- `GET /api/admin/material-specs`
- `POST /api/admin/material-specs`
- `GET /api/admin/material-specs/{id}`
- `PATCH /api/admin/material-specs/{id}`
- `DELETE /api/admin/material-specs/{id}`
- `GET /api/admin/material-story-sections`
- `POST /api/admin/material-story-sections`
- `GET /api/admin/material-story-sections/{id}`
- `PATCH /api/admin/material-story-sections/{id}`
- `DELETE /api/admin/material-story-sections/{id}`
- `GET /api/admin/material-applications`
- `POST /api/admin/material-applications`
- `GET /api/admin/material-applications/{id}`
- `PATCH /api/admin/material-applications/{id}`
- `DELETE /api/admin/material-applications/{id}`
- `GET /api/admin/articles`
- `POST /api/admin/articles`
- `GET /api/admin/articles/{id}`
- `PATCH /api/admin/articles/{id}`
- `DELETE /api/admin/articles/{id}`
- `GET /api/admin/home-sections`
- `POST /api/admin/home-sections`
- `GET /api/admin/home-sections/{id}`
- `PATCH /api/admin/home-sections/{id}`
- `DELETE /api/admin/home-sections/{id}`

## Setup

1. Install dependencies:

```bash
composer install
```

Required PHP extensions for local development:

- `intl`
- `pdo_mysql`
- `mbstring`
- `openssl`

2. Create the environment file:

```bash
cp .env.example .env
```

3. Generate the app key:

```bash
php artisan key:generate
```

4. Configure MySQL, Redis, and S3-compatible storage in `.env`

5. Run migrations and seeders:

```bash
php artisan migrate --seed
```

If you use the default `SESSION_DRIVER=database`, this migration step also creates the `sessions` table required by the Filament admin panel.

6. Start the API server:

```bash
php artisan serve
```

7. Access the internal admin panel:

```text
http://127.0.0.1:8000/admin
```

8. Run the queue worker in a separate terminal:

```bash
php artisan queue:work
```

## Environment Notes

See [`.env.example`](/c:/Users/xingz/Desktop/B2C_backend/.env.example) for the full template. Important values:

- `DB_CONNECTION=mysql`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `FILESYSTEM_DISK=s3`
- `COMMUNITY_UPLOAD_DISK=s3`
- `SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000`
- `FRONTEND_URL=http://localhost:3000`
- `B2B_LEADS_NOTIFY_ADMINS=false`
- `B2B_LEAD_NOTIFICATION_RECIPIENTS=`
- `FUNDING_DEFAULT_SUPPORT_BUTTON_TEXT=Support this concept`

### MySQL TLS / Azure MySQL

If your MySQL server enforces secure transport, such as Azure Database for MySQL with `require_secure_transport=ON`, you must enable SSL in `.env`.

Typical Ubuntu server configuration:

```env
DB_CONNECTION=mysql
DB_HOST=your-server.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true
```

If your environment does not trust the system CA bundle for this server, use the provider CA PEM file path in `MYSQL_ATTR_SSL_CA` instead.

After updating `.env`, clear cached configuration before retrying:

```bash
php artisan optimize:clear
php artisan migrate --seed
```

### S3-Compatible Storage

Set these values for MinIO, Cloudflare R2, DigitalOcean Spaces, or another compatible provider:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`
- `AWS_ENDPOINT`
- `AWS_URL`
- `AWS_USE_PATH_STYLE_ENDPOINT`

## Admin Panel

The Filament admin panel is intended for internal staff only.

- Login URL: `/admin/login`
- Allowed roles: `admin`, `moderator`
- Blocked roles: regular users and banned staff
- The panel uses Laravel web sessions, so `sessions` must exist if `SESSION_DRIVER=database`
- Main navigation groups: `Community`, `Moderation`, `Taxonomy`, `System`

The panel includes:

- Dashboard widgets for users, posts, comments, pending content, open reports, banned users, and recent activity
- User management with profile editing, role changes, and ban / unban actions
- Post and comment moderation workflows
- Report review with moderator notes
- Category and tag CRUD
- Read-only moderation logs

## Sample Seeded Accounts

After `php artisan migrate --seed`:

- Admin: `admin@example.com` / `password`
- Moderator: `moderator@example.com` / `password`
- Banned sample user: `banned@example.com` / `password`

Seeded CMS content also includes:

- one published featured material at `premium-oyster-shell`
- published material specs, story sections, and application sections
- published homepage sections for hero, science, and updates
- published sample articles for frontend integration
- one approved concept with a sample live external funding campaign

## Upload Behavior

- Profile avatar upload is handled through `PATCH /api/auth/profile` with an `avatar` file field
- Legacy post image upload is handled through `POST /api/posts` and `PATCH /api/posts/{id}` with `images[]`
- Structured idea media upload is handled through `POST /api/posts` and `PATCH /api/posts/{id}` with `attachments[]`
- External 3D references are handled through `model_3d_links[][url]`
- CMS media upload is handled through admin CMS create and update endpoints with a `media` file field
- CMS media removal is handled with `remove_media=true` on admin CMS update endpoints

## Phase 1 Payload Examples

Register as a creator or business-side account:

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

Update the expanded profile:

```json
{
  "bio": "Material-focused product designer.",
  "school_or_company": "Auckland Design Lab",
  "region": "Auckland, New Zealand",
  "portfolio_url": "https://portfolio.example.com",
  "open_to_collab": true
}
```

Update a user's role as an admin:

```json
{
  "role": "sme_partner"
}
```

Update a user's account status as an admin:

```json
{
  "account_status": "restricted",
  "reason": "Under moderation review."
}
```

Request a password reset:

```json
{
  "email": "jane@example.com"
}
```

## Phase 2 Payload Examples

Create or update a material:

```json
{
  "title": "Premium Oyster Shell Composite",
  "headline": "Science-backed shell composite for premium applications",
  "summary": "Core showcase material for storytelling and collaboration.",
  "story_overview": "Recovered shell is refined into a premium composite.",
  "science_overview": "Performance and circularity data can be surfaced dynamically.",
  "status": "published",
  "is_featured": true,
  "sort_order": 1
}
```

Create a material spec:

```json
{
  "material_id": 1,
  "key": "durability",
  "label": "Durability",
  "value": "High",
  "detail": "Dense compression improves edge stability.",
  "status": "published",
  "sort_order": 1
}
```

Create a homepage section:

```json
{
  "key": "hero",
  "title": "Premium oyster-shell materials",
  "subtitle": "Material showcase",
  "content": "Drive homepage content dynamically from the API.",
  "cta_label": "Explore the material",
  "cta_url": "/materials/premium-oyster-shell",
  "payload": {
    "variant": "hero"
  },
  "status": "published",
  "sort_order": 1
}
```

Create an article:

```json
{
  "title": "Material launch update",
  "excerpt": "Published article summary.",
  "content": "Published article body.",
  "category": "updates",
  "status": "published",
  "sort_order": 1
}
```

Public homepage response shape:

```json
{
  "home_sections": [],
  "materials": [],
  "articles": []
}
```

## Phase 3 Payload Examples

Create a concept with mixed media:

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

Update a concept by removing and replacing media:

```json
{
  "remove_media_ids": [12],
  "replace_media": [
    {
      "id": 15,
      "title": "Revised spec sheet",
      "kind": "spec_sheet"
    }
  ]
}
```

Attachment metadata in post responses:

```json
{
  "images": [
    {
      "id": 1,
      "url": "https://cdn.example.com/ideas/1/render.jpg",
      "preview_url": "https://cdn.example.com/ideas/1/render.jpg",
      "thumbnail_url": "https://cdn.example.com/ideas/1/render.jpg",
      "alt_text": "Rendered material board",
      "kind": "render_image",
      "sort_order": 0
    }
  ],
  "media": [
    {
      "id": 1,
      "source_type": "upload",
      "media_type": "image",
      "kind": "render_image",
      "title": "Render board",
      "mime_type": "image/jpeg",
      "size_bytes": 123456,
      "url": "https://cdn.example.com/ideas/1/render.jpg",
      "preview_url": "https://cdn.example.com/ideas/1/render.jpg",
      "thumbnail_url": "https://cdn.example.com/ideas/1/render.jpg",
      "external_url": null,
      "sort_order": 0
    }
  ]
}
```

## Phase 4 Payload Examples

Fetch concepts by discovery order:

```text
GET /api/posts?sort=trending&category=hardware&tag=oyster-shell&featured=1
```

Filter concepts by creator and profile attributes:

```text
GET /api/posts?creator=alicecreator&school_or_company=Auckland%20Design&region=Auckland
```

Feature a concept as an admin:

```json
{
  "is_featured": true,
  "reason": "Featured for homepage discovery."
}
```

Post ranking fields in API responses:

```json
{
  "id": 42,
  "title": "Popular concept",
  "is_featured": true,
  "engagement_score": 109,
  "trending_score": 160,
  "featured_at": "2026-04-12T00:00:00Z"
}
```

Ranking formula reference:

```json
{
  "engagement_score": "likes_count * 3 + comments_count * 4 + favorites_count * 2",
  "trending_score": "((weekly_likes * 3 + weekly_comments * 4 + weekly_favorites * 2) * 10) + recency_boost",
  "window_days": 7,
  "recency_boost_max_hours": 168
}
```

## Phase 5 Payload Examples

Filter unread notifications:

```text
GET /api/notifications?read=unread&type=system_announcement
```

Example notification payload:

```json
{
  "id": 18,
  "type": "concept_featured",
  "title": "Concept featured",
  "body": "Your concept \"Oyster shell stool\" is now featured.",
  "action_url": "/posts/oyster-shell-stool",
  "target_type": "post",
  "target_id": 42,
  "target": {
    "id": 42,
    "title": "Oyster shell stool",
    "slug": "oyster-shell-stool",
    "status": "approved"
  },
  "actor": {
    "id": 1,
    "name": "Admin User"
  },
  "data": {
    "post_id": 42,
    "post_slug": "oyster-shell-stool"
  },
  "is_read": false,
  "read_at": null
}
```

Broadcast a system announcement as an admin:

```json
{
  "title": "Platform update",
  "body": "Material showcase content has been refreshed.",
  "action_url": "/materials/premium-oyster-shell",
  "roles": ["creator", "sme_partner"]
}
```

## Phase 6 Payload Examples

Record a manual user violation as staff:

```json
{
  "type": "manual_warning",
  "severity": "warning",
  "reason": "Repeated low-quality submissions after prior guidance.",
  "subject_type": "post",
  "subject_id": 42
}
```

Resolve a violation:

```json
{
  "status": "resolved",
  "resolution_note": "Issue addressed after moderator review."
}
```

Fetch a user's moderation history:

```text
GET /api/admin/users/42/moderation-history
```

Example moderation log payload:

```json
{
  "id": 91,
  "action": "post.status_updated",
  "reason": "Temporarily taken down during review.",
  "subject_type": "post",
  "subject_id": 42,
  "target_user_id": 7,
  "report_id": null,
  "metadata": {
    "from": "approved",
    "to": "hidden"
  }
}
```

Enable optional sensitive-word flagging:

```env
COMMUNITY_SENSITIVE_WORDS_ENABLED=true
COMMUNITY_SENSITIVE_WORDS=scamword,abusive-term
```

## Phase 7 Payload Examples

Submit a business contact lead:

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

Submit a partnership inquiry:

```json
{
  "name": "Leo Park",
  "company_name": "Helix Atelier",
  "organization_type": "company",
  "email": "leo@example.com",
  "message": "We want to co-develop a premium furniture capsule.",
  "collaboration_type": "partnership_inquiry",
  "collaboration_goal": "Pilot a limited-edition material application.",
  "project_stage": "prototype",
  "timeline": "Q3 2026"
}
```

Submit a sample request:

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
  "shipping_address": "1-2-3 Minami, Osaka",
  "intended_use": "Interior wall system prototyping."
}
```

Update a lead as an admin:

```json
{
  "status": "qualified",
  "internal_notes": "Strong partnership fit. Schedule a follow-up call."
}
```

Export filtered leads:

```text
GET /api/admin/b2b-leads/export?search=Helix&status=qualified
```

Lead response shape:

```json
{
  "id": 101,
  "reference": "INQ-000101",
  "lead_type": "partnership_inquiry",
  "inquiry_type": "Partnership Inquiry",
  "company_name": "Helix Atelier",
  "status": "in_review",
  "partnership_inquiry": {
    "collaboration_type": "partnership_inquiry",
    "collaboration_goal": "Pilot a limited-edition material application."
  }
}
```

## Phase 8 Payload Examples

Attach or update a funding campaign on a concept as an admin:

```json
{
  "support_enabled": true,
  "support_button_text": "Back this concept",
  "external_crowdfunding_url": "https://crowdfund.example.com/projects/oyster-shell-chair",
  "campaign_status": "live",
  "target_amount": 15000,
  "pledged_amount": 4200,
  "backer_count": 64,
  "reward_description": "Backers receive sample material tiles and early design updates.",
  "campaign_start_at": "2026-05-01T00:00:00Z",
  "campaign_end_at": "2026-06-01T00:00:00Z"
}
```

Fetch a concept with support metadata from the public API:

```json
{
  "id": 42,
  "title": "Oyster shell chair",
  "support_enabled": true,
  "support_button_text": "Back this concept",
  "external_crowdfunding_url": "https://crowdfund.example.com/projects/oyster-shell-chair",
  "campaign_status": "live",
  "target_amount": 15000,
  "pledged_amount": 4200,
  "backer_count": 64,
  "funding_campaign": {
    "support_enabled": true,
    "campaign_status": "live",
    "progress_percentage": 28
  }
}
```

Remove a funding campaign from a concept:

```text
DELETE /api/admin/posts/42/funding-campaign
```

## Running Tests

The automated test suite uses in-memory SQLite for speed while production is expected to use MySQL.

```bash
php artisan test
```

## Formatting

```bash
vendor/bin/pint
```

## Notes for Frontend Integration

- All responses are JSON and stable for REST consumption
- User, post, comment, report, and notification payloads are normalized through API Resources
- `is_liked`, `is_favorited`, and `is_following` flags are included where relevant
- Public user profile responses include `followers_count`, `following_count`, `posts_count`, and `comments_count`
- Post slugs are generated automatically from titles
- Category and tag admin endpoints generate unique slugs automatically when omitted
- Search only returns approved posts by default
- Post responses keep the legacy `images` array for image-only clients
- Post responses now also include a structured `media` array for mixed attachments
- Image attachments expose `preview_url` and `thumbnail_url`
- Post discovery responses include `engagement_score`, `trending_score`, and `featured_at`
- `sort=hot` remains supported as a backward-compatible alias for popularity ordering
- Staff can inspect the ranking formula at `GET /api/admin/posts/ranking-formula`
- Notification responses include `title`, `body`, `action_url`, and `meta.unread_count`
- `GET /api/notifications` supports `read=all|read|unread` and `type=...` filters
- System announcements are sent through `POST /api/admin/notifications/announcements` and target active users only
- Report responses now include `moderator_note`
- Staff can inspect user-level audit trails through moderation history, admin action, and violation endpoints
- Post and comment review histories are available through `GET /api/admin/posts/{id}/review-history` and `GET /api/admin/comments/{id}/review-history`
- Sensitive-word flagging is config-driven and records audit entries without breaking the existing pending-review flow
- Homepage content is available from `GET /api/homepage`
- Public material and article endpoints only return `published` content
- Material detail responses include published `specs`, `story_sections`, and `applications`
- Collaboration leads are available through the legacy `POST /api/inquiries` path and the new dedicated lead endpoints
- Admin lead management supports search, status updates, internal notes, and CSV export
- Admin email notifications for new leads are optional and controlled by `B2B_LEADS_NOTIFY_ADMINS` and `B2B_LEAD_NOTIFICATION_RECIPIENTS`
- Concept responses can expose a public `funding_campaign` block plus top-level support CTA fields when a campaign is not in `draft`
- Admins manage concept funding support through `/api/admin/posts/{id}/funding-campaign`
- Funding support is external-link only; no internal payment, checkout, or pledge processing is implemented
