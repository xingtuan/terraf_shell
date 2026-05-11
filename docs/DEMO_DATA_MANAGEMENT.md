# Demo Data Management

Last updated: 2026-05-11

## Policy

Demo data must be removable without deleting real runtime settings, production content, orders, leads, uploaded media, or customer accounts. Runtime settings are operational configuration and must not be treated as disposable demo data.

## Current Safe Boundary

- Runtime settings are preserved.
- Seeders remain compatible with the existing setup flow.
- Demo cleanup should only act on records that are explicitly marked as demo or generated test content.
- Orders, leads, media, users, CMS content, and settings should not be mass-deleted by a demo cleanup action unless future seeders add reliable demo ownership markers.

## Operator Notes

Use the admin demo cleanup tooling only for explicitly marked demo community posts/comments. For any broader production handover cleanup, export or back up settings first, review affected records, and avoid destructive bulk operations.

## Future Seeder Work

If seeders are reorganized later, keep runtime defaults separate from demo showcase content. Add explicit demo markers to any removable records so cleanup tools can distinguish demonstration content from real customer or operator data.
