# Storage Abstraction

Media storage now goes through `App\Services\Storage\StorageManagerService` and drivers implementing `MediaStorageDriverInterface`.

Drivers:

- `LocalMediaStorageDriver`: uses Laravel Storage on the configured local disk, default `public`.
- `AzureMediaStorageDriver`: uses Laravel Storage on the `azure` disk and the existing Azure adapter.

`MediaService` keeps the existing public methods:

- `storeIdeaAttachment()`
- `storeCmsAsset()`
- `storeAvatar()`
- `upload()`
- `delete()`
- `move()`
- `deletePath()`
- `disk()`
- `url()`
- `publicUrl()`

New uploads store the active disk in `media_files.disk`. Deletes use the recorded disk for persisted media so switching the active driver does not accidentally target the wrong backend.

The active driver comes from `app_settings`:

- `storage.default_driver`: `local` or `azure`
- `storage.local.disk`: usually `public`
- `storage.azure.account_name`
- `storage.azure.account_key` encrypted
- `storage.azure.container`
- `storage.azure.url`
- `storage.azure.use_sas_urls`
- `storage.azure.sas_ttl_minutes`

Env values remain as fallback through Laravel config for local development and recovery.

