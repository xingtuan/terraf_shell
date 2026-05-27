# Known Issues And Boundaries

This document records current operational boundaries and items that require manual confirmation.

## Payment Gateway

The system does not include an integrated third-party online payment gateway. Orders can be created, and payment status is maintained manually.

Impact:

- Checkout does not redirect to Stripe, PayPal, Windcave, or another payment provider.
- Payment statuses are `unpaid`, `paid`, and `refunded`.
- Financial reconciliation must happen outside the system.

## SSL Is Not Configured By The Script

`auto_deploy.sh` does not request or install HTTPS certificates. Configure Certbot, load balancer certificates, or reverse-proxy certificates separately.

After enabling HTTPS, update:

- `APP_URL`
- `FRONTEND_URL`
- `NEXT_PUBLIC_SITE_URL`
- CORS allowed origins
- Sanctum stateful domains
- secure cookie settings

## Azure / Local Historical Media Migration

The admin panel supports local / Azure switching, connection tests, upload tests, and media scan exports. It does not bulk-migrate historical files.

Before switching drivers, plan:

- file copying
- database path compatibility
- public URL or SAS URL behavior
- rollback strategy

## Automated Script Scope

`auto_deploy.sh` targets Ubuntu / Debian apt-based single-server deployments. It is not a Docker, Kubernetes, or multi-server high-availability deployment.

The script installs system packages and writes Nginx, Supervisor, Cron, and systemd configuration. Do not run it in an existing complex production environment without review.

## Seeder Re-runs

`RUN_SEED=1` runs:

```bash
php artisan db:seed --force
```

Seeders include starter content and sample operational records. For routine production updates, use:

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

## Seeded Admin User

When seeders run, the default admin is:

- `admin@example.com`
- `password`

Change the password or disable the account before handover.

## Port Exposure

Automated deployment exposes:

- `80`: frontend and API proxy
- `8000`: Laravel admin and health check

If public `:8000` access is not acceptable, redesign admin access through Nginx, firewall rules, VPN, or a reverse proxy.

## NZ Post Dependency

Shipping `auto` mode tries NZ Post first and falls back to manual rates. Real NZ Post quotes depend on valid credentials, service codes, network access, and API availability.

## Documentation Boundaries

Historical QA, audit, and fix-tracking documents are retained as records. If they conflict with the root README or top-level docs, use the current top-level documents and the code as the source of truth.
