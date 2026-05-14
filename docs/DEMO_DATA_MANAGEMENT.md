# Demo Data Management

Last updated: 2026-05-14

## Policy

Demo data must be removable without deleting real runtime settings, production content, orders, leads, uploaded media, or customer accounts. Runtime settings are operational configuration and must not be treated as disposable demo data.

## Current Safe Boundary

- Runtime settings are preserved.
- Seeders remain compatible with the existing setup flow.
- Demo cleanup should only act on records that are explicitly marked as demo or generated test content.
- Seeded shop catalog records are marked with `is_demo_content`, `seed_source`, and `seeded_at` metadata. The current shop demo seed source is `product_catalog_demo`.
- Orders, leads, uploaded media, users, CMS content, and settings should not be mass-deleted by a demo cleanup action unless future seeders add reliable demo ownership markers.
- Demo shop catalog cleanup removes cart lines, product relations, attributes, images, variants, and products only when real order history does not reference those products. Products referenced by order items remain in place and are reported as blocked.

## Operator Notes

Use the admin demo cleanup tooling only for explicitly marked demo community posts/comments and seeded demo shop catalog records. The Demo Cleanup page can also reseed the demo shop catalog through `ProductCatalogSeeder`. For any broader production handover cleanup, export or back up settings first, review affected records, and avoid destructive bulk operations.

## Future Seeder Work

If seeders are reorganized later, keep runtime defaults separate from demo showcase content. Add explicit demo markers to any removable records so cleanup tools can distinguish demonstration content from real customer or operator data.
