# Initial Content Policy

Last updated: 2026-05-14

## Policy

Initial content is part of the delivered system. Products, materials, articles, CMS sections, and community records created by seeders should be reviewed and maintained through the same production workflows as operator-created content.

## Current Boundary

- Runtime settings are preserved.
- Seeders remain compatible with the setup flow and create official starter records.
- Legacy internal marker columns may remain for compatibility, but they must not be shown to frontend or admin users.
- Seeded shop catalog records currently use the internal seed source `product_catalog_initial`.
- Orders, leads, uploaded media, users, CMS content, and settings are managed through their normal resources.
- Bulk removal workflows for initial content are not part of the production system.

## Operator Notes

Use the standard admin content resources to review, edit, archive, or publish initial delivery records. The shop catalog can be seeded through `ProductCatalogSeeder` during setup or controlled maintenance. For broader production handover review, export or back up settings first and review affected records individually.

## Future Seeder Work

If seeders are reorganized later, keep runtime defaults separate from delivery content and preserve backward compatibility with existing marker columns. Do not add user-facing labels that identify content as generated, seeded, or temporary.
