# Storage

The system supports local public storage and Azure Blob Storage. Runtime behavior is managed by `StorageManagerService`, `StorageUrl`, and admin Storage Settings.

## Defaults

`auto_deploy.sh` configures local public storage:

```dotenv
STORAGE_DISK=public
FILESYSTEM_DISK=public
MEDIA_DRIVER=public
COMMUNITY_UPLOAD_DISK=public
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public
```

It creates:

```text
B2C_backend/public/storage -> B2C_backend/storage/app/public
```

Nginx and the frontend access public files through `/storage/...`.

## Local Storage

Files are stored under:

```text
B2C_backend/storage/app/public
```

Public URL:

```text
http://your-domain/storage/{path}
```

Manual link command:

```bash
cd B2C_backend
php artisan storage:link
```

Checks:

```bash
ls -l B2C_backend/public/storage
test -w B2C_backend/storage/app/public && echo writable
```

If `public/storage` already exists but is not a symlink, the automated script stops to avoid overwriting unknown files.

## Azure Storage

Azure uses the Laravel `azure` filesystem disk:

```dotenv
AZURE_STORAGE_NAME=
AZURE_STORAGE_KEY=
AZURE_STORAGE_CONTAINER=
AZURE_STORAGE_URL=
```

Admin Storage Settings supports driver switching, Azure credentials, connection test, upload test, temporary URL / SAS settings, recent-driver rollback, and media scan export.

If SAS temporary URLs are enabled, `StorageUrl` returns signed Azure URLs. Otherwise it builds stable URLs from the configured base URL and container.

## Upload Sources

Common uploaded media:

- Product images.
- Homepage and page section images.
- Material and article images.
- Community post cover images.
- Community attachments and idea media.
- Filament / Livewire temporary uploads.

Community upload paths and limits come from `config/community.php` and admin Community Settings.

## Media URLs

Local public storage:

```text
/storage/{path}
```

Protected or non-public local files may use:

```text
/media/files/{disk}/{path}
```

Azure URLs depend on the configured public base URL or SAS temporary URL settings.

## Frontend Media Base

```dotenv
NEXT_PUBLIC_MEDIA_BASE_URL=
```

Leave this empty in normal deployments so the backend can return resolved media URLs. Use it only when a separate CDN or media domain is required.

## Switching Local To Azure

Recommended flow:

1. Back up the database and `storage/app/public`.
2. Configure Azure in Storage Settings.
3. Run connection and upload tests.
4. Confirm newly uploaded files display.
5. Export the media scan.
6. Migrate historical files to Azure separately.
7. Check product, homepage, material, article, and community images.
8. Clear cache and rebuild frontend if URL behavior changed.

The admin media scan exports findings; it does not perform bulk migration.

## Switching Azure To Local

1. Back up the database and Azure container.
2. Download required files to matching local paths under `storage/app/public`.
3. Switch active driver to local / public.
4. Confirm `php artisan storage:link`.
5. Check media URLs.
6. Clear cache.

## Permissions

Laravel needs write access to:

```text
B2C_backend/storage
B2C_backend/bootstrap/cache
```

Manual repair:

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo chmod -R ug+rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -R -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -dR -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
```

## Troubleshooting

- Upload succeeds but image is missing: check active driver, storage link, Nginx `/storage/`, Azure container, Azure key, base URL, and upload test.
- Old images fail after Azure switch: migrate historical files or keep old storage accessible.
- Storage link fails: inspect existing `public/storage` before deleting anything.
- SAS URLs expire too quickly: increase temporary URL TTL.
- Livewire upload fails: check upload disk, PHP limits, Nginx body size, and storage permissions.
