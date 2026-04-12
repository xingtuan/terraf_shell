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
- Comments: create, reply, edit, delete, list by post and by user
- Likes: posts and comments
- Favorites: posts
- Follows: users, followers, following
- Reports: post and comment reporting
- Notifications: comment, reply, like, and follow notifications
- Moderation: `pending`, `approved`, `rejected`, `hidden`
- Admin: review reports, moderate posts/comments, ban users
- Internal admin panel for admins and moderators at `/admin`
- Uploads: avatar upload and post image upload
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
- `PATCH /api/comments/{id}`
- `POST /api/comments/{id}/reply`
- `DELETE /api/comments/{id}`

`GET /api/posts` also supports optional query params such as:

- `sort=latest|hot`
- `user_id=`
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

### Notifications, Reports, Search

- `GET /api/notifications`
- `PATCH /api/notifications/{id}/read`
- `POST /api/reports`
- `GET /api/search/posts?q=keyword`

### Admin

- `GET /api/admin/reports`
- `PATCH /api/admin/reports/{id}/status`
- `PATCH /api/admin/posts/{id}/status`
- `PATCH /api/admin/comments/{id}/status`
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

## Upload Behavior

- Profile avatar upload is handled through `PATCH /api/auth/profile` with an `avatar` file field
- Post image upload is handled through `POST /api/posts` and `PATCH /api/posts/{id}` with `images[]`

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
