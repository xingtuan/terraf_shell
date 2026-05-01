# OXP Frontend

The Next.js 16 frontend for the OXP brand platform. It serves as a multi-language brand site, B2C product storefront, B2B inquiry hub, and community space for creators working with oyster shell materials.

Built with the App Router, TypeScript, Tailwind CSS 4, and a clear separation between server-rendered content pages and client-interactive community features.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Next.js 16 (App Router) |
| Language | TypeScript 5.7 |
| UI Library | React 19 |
| Styling | Tailwind CSS 4 + PostCSS |
| Components | Radix UI primitives + shadcn/ui |
| Forms | react-hook-form + Zod |
| Rich Text | Tiptap |
| Icons | lucide-react |
| Carousel | Embla Carousel |
| Charts | Recharts |
| Toasts | Sonner |
| Date utilities | date-fns |
| Analytics | Vercel Analytics |
| Package Manager | pnpm (via Corepack) |

---

## Local Development

Install dependencies:

```bash
corepack enable
corepack pnpm install
```

Copy the environment template and configure:

```bash
cp .env.example .env.local
```

Start the development server:

```bash
corepack pnpm dev
```

Production build:

```bash
corepack pnpm build
```

Type check without emitting:

```bash
corepack pnpm exec tsc --noEmit
```

Validate i18n key coverage across all locales:

```bash
node scripts/check-i18n-keys.mjs
```

### Environment Variables

| Variable | Default | Description |
|---|---|---|
| `NEXT_PUBLIC_API_BASE_URL` | `/api` | Backend API base URL. Change to an absolute URL (e.g., `http://127.0.0.1:8000/api`) when running frontend and backend on different ports without a reverse proxy. |
| `NEXT_PUBLIC_MEDIA_BASE_URL` | *(empty)* | Optional CDN base URL for media assets served from Azure Blob Storage. |
| `NEXT_PUBLIC_BRAND_CONTACT_EMAIL` | *(empty)* | Optional confirmed OXP contact email. Leave empty until the client confirms the final address. |

---

## Routing

All pages are under the `[locale]` dynamic segment. The root path `/` redirects to `/en`.

| Route | Page |
|---|---|
| `/[locale]` | Homepage |
| `/[locale]/material` | Material showcase page |
| `/[locale]/articles` | Article listing |
| `/[locale]/articles/[slug]` | Article detail |
| `/[locale]/store` | B2C product store |
| `/[locale]/store/[slug]` | Product detail |
| `/[locale]/store/checkout` | Checkout |
| `/[locale]/store/orders` | Order list |
| `/[locale]/store/orders/[orderNumber]` | Order detail |
| `/[locale]/b2b` | B2B partnership and inquiry page |
| `/[locale]/contact` | Contact page |
| `/[locale]/community` | Community hub (post feed) |
| `/[locale]/community/[slug]` | Post detail |
| `/[locale]/community/profile/[username]` | User profile |
| `/[locale]/account` | Account overview |
| `/[locale]/account/profile` | Profile settings |
| `/[locale]/account/addresses` | Address management |

Supported locales: `en` (default), `ko`, `zh`

---

## Directory Structure

```text
app/
  page.tsx                          # Root redirect → /en
  [locale]/
    layout.tsx                      # Global layout with locale context
    page.tsx                        # Homepage
    material/page.tsx               # Material page
    articles/
      page.tsx                      # Article list
      [slug]/page.tsx               # Article detail
    store/
      page.tsx                      # Product store
      [slug]/page.tsx               # Product detail
      checkout/page.tsx
      orders/
        page.tsx
        [orderNumber]/page.tsx
    b2b/page.tsx                    # B2B inquiry page
    contact/page.tsx                # Contact page
    community/
      page.tsx                      # Community hub
      [slug]/page.tsx               # Post detail
      profile/[username]/page.tsx   # User profile
    account/
      page.tsx
      profile/page.tsx
      addresses/page.tsx

components/
  layout/
    header.tsx                      # Top navigation with locale switcher
    footer.tsx                      # Footer navigation
  sections/                         # Homepage and page section components
    hero.tsx
    why-it-matters.tsx
    material-story.tsx
    applications.tsx
    material-facts.tsx
    collaboration.tsx
    credibility.tsx
    final-cta.tsx
    product-grid.tsx
    b2b-inquiry-form.tsx
    community-ideas.tsx
    contact-details.tsx
  community/                        # Community-specific components
    community-hub.tsx               # Post feed and filtering
    community-post-detail.tsx       # Post detail with comments
    community-auth-panel.tsx        # Login / register modal
    comment-tree.tsx
    post-card.tsx
  account/                          # Account and profile components
  articles/                         # Article list and detail components
  store/                            # Store and product components
  auth/                             # Authentication panels
  ui/                               # Base UI primitives (buttons, dialogs, etc.)

hooks/
  use-section-in-view.ts            # Intersection Observer hook for reveal animations

lib/
  api/
    client.ts                       # Central HTTP client (auth, base URL, error handling)
    auth.ts                         # Authentication endpoints
    posts.ts                        # Community posts
    comments.ts                     # Post comments
    interactions.ts                 # Likes, favorites, follows
    notifications.ts                # User notifications
    search.ts                       # Post search
    users.ts                        # User profiles
    materials.ts                    # Material CMS
    articles.ts                     # Articles
    homepage.ts                     # Homepage content aggregation
    leads.ts                        # B2B lead / inquiry submission
    inquiries.ts                    # Legacy wrapper → delegates to leads.ts
    cart.ts                         # Shopping cart
    orders.ts                       # Orders
    addresses.ts                    # User addresses
    media.ts                        # Media upload
    products.ts                     # ⚠ Mock only — no backend endpoint yet
    community.ts                    # ⚠ Mock only — no backend endpoint yet
    adapters.ts                     # Response transformation helpers
    normalizers.ts                  # Data normalization utilities
    request-throttle.ts             # Client-side request rate limiting
  auth/
    token-storage.ts                # localStorage wrapper for Sanctum tokens
  data/                             # Intentional fallback/mock data
    products.ts                     # Product mock data (pending backend)
    materials.ts                    # Material spec fallback data
    community.ts                    # Community idea card mock data
  i18n.ts                           # Locale list, message loading, URL helpers
  resolve-locale.ts                 # Locale validation middleware helper
  types.ts                          # Shared TypeScript type definitions

messages/
  en.json                           # English translations
  ko.json                           # Korean translations
  zh.json                           # Chinese translations

scripts/
  check-i18n-keys.mjs               # Validates key parity across all locale files

public/                             # Static assets (images, fonts, etc.)
```

---

## Internationalization

All user-facing strings are stored in `messages/*.json`. The i18n system is custom-built (no third-party i18n library) and driven by the `[locale]` dynamic segment.

**Configuration:** `lib/i18n.ts`

```typescript
export const locales = ['en', 'ko', 'zh']
export const defaultLocale = 'en'
```

**Helper functions in `lib/i18n.ts`:**

- `getMessages(locale)` — loads and returns the message catalog for a locale
- `getLocalizedHref(locale, slug)` — builds a locale-prefixed URL

**Adding new copy:** Edit the appropriate key in all three `messages/*.json` files. Never hard-code strings in components. Run `node scripts/check-i18n-keys.mjs` to verify key parity.

**Adding a new locale:** Add the locale code to the `locales` array in `lib/i18n.ts`, create the corresponding `messages/{locale}.json` file, and update `next.config.mjs` remote patterns if locale-specific CDN paths are used.

---

## API Client

All backend calls go through the central client at `lib/api/client.ts`. It handles:

- **Base URL resolution** from `NEXT_PUBLIC_API_BASE_URL`
- **Bearer token injection** from `localStorage` via `lib/auth/token-storage.ts`
- **Query parameter serialization**
- **Unified error handling** — throws a typed `ApiError` on non-2xx responses
- **Response parsing** — unwraps the backend's `{ success, data, meta }` envelope

The token is stored at key `oxp.community.auth-token` in `localStorage`.

### Authentication Flow

1. User submits credentials in `CommunityAuthPanel`
2. Frontend calls `lib/api/auth.login()` → `POST /api/auth/login`
3. Backend returns a Sanctum Personal Access Token
4. Token is persisted to `localStorage`
5. Subsequent requests include `Authorization: Bearer <token>`
6. On page load, `GET /api/auth/me` restores the authenticated user state
7. On logout, `POST /api/auth/logout` is called and the local token is cleared

---

## Component Architecture

Pages are composed of two types of components:

### Server Components (static / content pages)

Used for: Homepage, Material, Store listing, B2B, Contact, Articles.

These are `async` React Server Components. They fetch data at render time and produce stable, SEO-friendly HTML. They do not manage state or subscribe to browser events.

### Client Components (interactive community pages)

Used for: Community feed, post detail, auth panel, account pages, inquiry forms.

These use `"use client"` and manage authentication state, form submission, real-time interactions (likes, favorites, comments), and pagination.

---

## Data Layer

### Live Backend Modules

The following API modules make real requests to the Laravel backend:

| Module | Endpoints |
|---|---|
| `auth.ts` | `/api/auth/*` |
| `posts.ts` | `/api/posts`, `/api/posts/{id}` |
| `comments.ts` | `/api/posts/{id}/comments`, `/api/comments/{id}` |
| `interactions.ts` | `/api/posts/{id}/like`, `/api/posts/{id}/favorite`, `/api/users/{id}/follow` |
| `notifications.ts` | `/api/notifications` |
| `search.ts` | `/api/search/posts` |
| `users.ts` | `/api/users/{id}` and sub-resources |
| `materials.ts` | `/api/materials` |
| `articles.ts` | `/api/articles` |
| `homepage.ts` | `/api/homepage` |
| `leads.ts` | `/api/business-contacts`, `/api/partnership-inquiries`, etc. |
| `cart.ts` | `/api/cart` |
| `orders.ts` | `/api/orders` |
| `addresses.ts` | `/api/addresses` |
| `media.ts` | `/api/media/upload` |

### Mock-Only Modules

These modules return local data from `lib/data/` because the corresponding backend endpoints are not yet implemented:

| Module | Reason |
|---|---|
| `products.ts` | Product catalog public API not yet exposed on the backend |
| `community.ts` | Community idea submission API not yet implemented |

These are deliberate boundaries, not technical debt. The page structure and components are already in place and will adopt live data when the backend endpoints are ready.

---

## Page Composition

### Homepage

Composed of: `HeroSection`, `WhyItMattersSection`, `MaterialStorySection`, `ApplicationsSection`, `MaterialFactsSection`, `CollaborationSection`, `CredibilitySection`, `FinalCtaSection`

Data source: `GET /api/homepage` aggregates materials, articles, and home sections in a single request.

### Material Page

Showcases the oyster shell material with specs, story sections, certifications, and applications. Data from `GET /api/materials/{slug}`.

### Store Page

Product grid with category filtering. Currently renders from mock data (`lib/data/products.ts`).

### B2B Page

Collaboration pitch with inquiry form. Submission goes to `POST /api/partnership-inquiries` or `POST /api/business-contacts`.

### Community Page

Post feed with sorting (latest, hot, trending, popular), filtering by category and tag, and full interaction support. Requires authentication for posting, liking, and favoriting.

### Account Pages

Profile editing, address management, and order history. All require authenticated session.

---

## Design Direction

The visual language follows a premium, minimal, editorial style:

- Spacious layouts with controlled whitespace
- Quiet luxury color palette
- Typography-forward sections
- Smooth reveal animations via `useIntersectionObserver` (see `hooks/use-section-in-view.ts`)
- No unnecessary interactive states or decorative elements

---

## Next Steps for Backend Integration

1. **Product catalog** — Connect `lib/api/products.ts` to `GET /api/products` and `GET /api/product-categories`
2. **Community idea cards** — Implement idea submission and listing API endpoints, then wire `lib/api/community.ts`
3. **Checkout flow** — Integrate `POST /api/orders` and payment processing
4. **Community advanced UI** — Add create-post form, reply thread, follow button, report dialog, notification center, and full user profile page
