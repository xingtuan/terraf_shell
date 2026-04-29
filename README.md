# Shellfin / terraf — Monorepo

A full-stack, API-driven monorepo for the **Shellfin** brand platform. The repository contains two top-level applications that are developed and deployed independently but designed to work together as a single product.

| Directory | Role |
|---|---|
| [`B2C_frontend/`](./B2C_frontend/) | Next.js 16 multi-language storefront, community hub, and brand site |
| [`B2C_backend/`](./B2C_backend/) | Laravel 13 REST API, admin panel, CMS, and community engine |

The platform is built around Shellfin's oyster shell material narrative and covers three audience segments:

- **B2C** — Premium tableware and home goods product catalog
- **B2B** — Raw material and partnership inquiry pipeline
- **Community** — Creator concept submission, collaboration, and engagement

---

## Repository Structure

```text
.
├── B2C_frontend/               # Next.js 16 frontend application
│   ├── app/                    # App Router pages with locale segments
│   ├── components/             # Layout, section, community, and UI components
│   ├── hooks/                  # Custom React hooks
│   ├── lib/
│   │   ├── api/                # API client modules (one per resource domain)
│   │   ├── auth/               # Browser-side token storage
│   │   ├── data/               # Intentional mock/fallback data
│   │   ├── i18n.ts             # Locale configuration and message loading
│   │   └── types.ts            # Shared TypeScript types
│   ├── messages/               # Translation files (en / ko / zh)
│   └── public/                 # Static assets
│
├── B2C_backend/                # Laravel 13 backend application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/    # REST API controllers
│   │   │   ├── Requests/           # Form request validation
│   │   │   └── Resources/          # JSON API response resources
│   │   ├── Models/             # Eloquent models (30+)
│   │   ├── Services/           # Business logic layer (15+ services)
│   │   ├── Policies/           # Authorization policies
│   │   ├── Filament/           # Admin panel resources and widgets
│   │   ├── Filesystem/         # Azure Blob Storage adapter
│   │   ├── Jobs/               # Queued background jobs
│   │   └── Support/            # Shared utilities and API response helper
│   ├── config/                 # Laravel configuration files
│   ├── database/
│   │   ├── migrations/         # Database schema migrations
│   │   ├── seeders/            # Sample data seeders
│   │   └── factories/          # Model factories for testing
│   ├── routes/
│   │   ├── api.php             # REST API routes (prefix: /api)
│   │   └── web.php             # Web routes (Filament admin at /admin)
│   └── tests/                  # PHPUnit feature tests
│
└── README.md                   # This file
```

---

## Tech Stack

### Frontend

| Layer | Technology |
|---|---|
| Framework | Next.js 16 (App Router) |
| Language | TypeScript 5.7 |
| UI | React 19 + Radix UI + shadcn/ui |
| Styling | Tailwind CSS 4 + PostCSS |
| Forms | react-hook-form + Zod |
| Rich Text | Tiptap |
| Internationalization | Custom i18n (EN / KO / ZH) |
| Icons | lucide-react |
| Analytics | Vercel Analytics |
| Package Manager | pnpm (via Corepack) |

### Backend

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Admin Panel | Filament 5 |
| API Authentication | Laravel Sanctum (token-based) |
| Database | MySQL 8+ |
| Cache & Queue | Redis |
| File Storage | Azure Blob Storage (primary) + local public disk (fallback) |
| Testing | PHPUnit 12.5+ |
| Code Style | Laravel Pint |

---

## Architecture Overview

The project follows a clean API-first architecture with three logical layers:

```text
Browser
  └── Next.js pages and components
        ├── Local data layer       (lib/data/* — intentional mock boundaries)
        └── REST API layer         (lib/api/* — live backend calls)
              └── Laravel Controller
                    └── Service
                          ├── Model / Policy / Resource
                          ├── MySQL database
                          ├── Azure Blob Storage
                          └── Redis queue
```

### Authentication

- **Frontend consumers** use Sanctum Personal Access Tokens stored in `localStorage`.
- **Admin panel** uses standard Laravel web sessions (separate authentication from the API).
- Token key in the browser: `shellfin.community.auth-token`

### API Response Contract

All backend endpoints return a consistent envelope:

```json
// Success
{ "success": true, "message": "Optional message", "data": {} }

// Paginated
{ "success": true, "message": null, "data": [], "meta": { "current_page": 1, "per_page": 20, "total": 100, "last_page": 5 } }

// Error
{ "success": false, "message": "Error summary", "errors": { "field": ["reason"] } }
```

---

## Current Integration Status

| Feature Area | Frontend Data Source | Backend Endpoint | Status |
|---|---|---|---|
| Homepage content | `lib/api/homepage.ts` | `GET /api/homepage` | Live |
| Materials | `lib/api/materials.ts` | `GET /api/materials` | Live |
| Articles | `lib/api/articles.ts` | `GET /api/articles` | Live |
| Auth (register / login / logout) | `lib/api/auth.ts` | `/api/auth/*` | Live |
| Community posts | `lib/api/posts.ts` | `/api/posts` | Live |
| Community comments | `lib/api/comments.ts` | `/api/posts/{id}/comments` | Live |
| Post interactions (like / favorite) | `lib/api/interactions.ts` | `/api/posts/{id}/like`, `/favorite` | Live |
| B2B inquiries | `lib/api/leads.ts` | `POST /api/business-contacts`, etc. | Live |
| Notifications | `lib/api/notifications.ts` | `GET /api/notifications` | Live |
| Search | `lib/api/search.ts` | `GET /api/search/posts` | Live |
| User profiles | `lib/api/users.ts` | `GET /api/users/{id}` | Live |
| Product catalog | `lib/api/products.ts` | *(not implemented)* | Mock only |
| Community idea cards | `lib/api/community.ts` | *(not implemented)* | Mock only |

The mock-only modules are **intentional design boundaries**, not hidden TODOs. The backend models and migrations for products exist but the public API endpoints are not yet exposed.

---

## Local Development

### Prerequisites

- Node.js 20+
- pnpm (via Corepack: `corepack enable`)
- PHP 8.3+
- Composer
- MySQL 8+
- Redis

### Start the Backend

```bash
cd B2C_backend
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_*, REDIS_*, and storage settings in .env
php artisan migrate --seed
php artisan serve
```

For lightweight local development without Azure and Redis, override these in `.env`:

```env
QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
FILESYSTEM_DISK=public
COMMUNITY_UPLOAD_DISK=public
```

Start the queue worker (required for async notifications when `QUEUE_CONNECTION=redis`):

```bash
php artisan queue:work
```

Backend runs at: `http://127.0.0.1:8000`  
Admin panel at: `http://127.0.0.1:8000/admin`

### Start the Frontend

```bash
cd B2C_frontend
corepack pnpm install
cp .env.example .env.local
# Set NEXT_PUBLIC_API_BASE_URL if running frontend and backend on separate ports
corepack pnpm dev
```

Frontend runs at: `http://localhost:3000`

If the frontend and backend are on different ports and there is no reverse proxy, update `.env.local`:

```env
NEXT_PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api
```

---

## Key Environment Variables

### Frontend (`.env.local`)

| Variable | Default | Description |
|---|---|---|
| `NEXT_PUBLIC_API_BASE_URL` | `/api` | Backend API base URL |
| `NEXT_PUBLIC_MEDIA_BASE_URL` | *(empty)* | CDN URL for media assets |

### Backend (`.env`)

| Variable | Example | Description |
|---|---|---|
| `APP_URL` | `http://127.0.0.1:8000` | Backend base URL |
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` | — | MySQL connection details |
| `SESSION_DRIVER` | `database` | Required for Filament admin panel |
| `QUEUE_CONNECTION` | `redis` | Job queue driver |
| `CACHE_STORE` | `redis` | Cache driver |
| `FILESYSTEM_DISK` | `azure` | Primary storage disk |
| `COMMUNITY_UPLOAD_DISK` | `azure` | Disk used for community media uploads |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000` | Domains allowed for Sanctum SPA auth |
| `FRONTEND_URL` | `http://localhost:3000` | CORS origin for the frontend |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000` | Comma-separated CORS origins |

---

## Seeded Test Accounts

After running `php artisan migrate --seed`:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Moderator | `moderator@example.com` | `password` |
| Banned user | `banned@example.com` | `password` |

---

## Running Tests

### Backend

```bash
cd B2C_backend
php artisan test
```

Tests use in-memory SQLite so no database setup is required. Coverage areas include: authentication, posts, comments, interactions, notifications, search, B2B leads, CMS, admin moderation, media upload, and taxonomy.

### Frontend

```bash
cd B2C_frontend
corepack pnpm exec tsc --noEmit   # Type checking
corepack pnpm build               # Production build check
```

---

## Useful Commands

### Backend

```bash
php artisan test           # Run PHPUnit test suite
vendor/bin/pint            # Format PHP code
php artisan optimize:clear # Clear all cached config and routes
```

### Frontend

```bash
corepack pnpm dev          # Start development server
corepack pnpm build        # Production build
corepack pnpm lint         # Run ESLint
node scripts/check-i18n-keys.mjs  # Validate i18n key coverage
```

---

## Further Documentation

- [Frontend README](./B2C_frontend/README.md) — Routing, component structure, i18n, API integration details
- [Backend README](./B2C_backend/README.md) — Full API reference, database schema, admin panel, payload examples
