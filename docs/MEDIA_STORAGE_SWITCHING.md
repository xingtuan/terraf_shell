# Media Storage Switching

Changing the active storage driver affects new uploads only.

Existing media:

- `idea_media` already stores `disk`.
- `media_files` now stores `disk`.
- Existing `media_files` rows are backfilled during migration from the active upload disk at migration time.
- Stored public URLs are not rewritten automatically.

Local storage:

1. Set Storage Settings driver to `local`.
2. Keep local disk as `public`.
3. Ensure `storage/app/public` is writable.
4. Ensure `public/storage` exists. Use the Storage Settings action or run `php artisan storage:link`.
5. Test local storage and test upload.

Azure Blob Storage:

1. Set Storage Settings driver to `azure`.
2. Enter account name, account key, container, and optional storage URL.
3. Choose SAS URL behavior and TTL.
4. Save.
5. Test Azure connection and test upload.

Rollback:

- Storage Settings records the previous active driver before a switch.
- Use Roll back driver if tests fail.
- Rollback changes where new uploads go; it does not move files.

Migration between disks:

- Automatic media migration is intentionally not enabled in this sprint.
- Media Storage Scan counts files by disk, checks the first 200 records for missing files, exports a report, and provides dry-run local-to-Azure and Azure-to-local actions.
- A future migration should copy files, verify checksums/existence, update recorded disks only after verification, and keep a rollback manifest.

Test result persistence:

- Local test results are stored in `storage.local.last_tested_at`, `storage.local.last_test_status`, and `storage.local.last_test_message`.
- Azure test results are stored in `storage.azure.last_tested_at`, `storage.azure.last_test_status`, and `storage.azure.last_test_message`.
- Upload test results are stored in `storage.last_tested_at`, `storage.last_test_status`, and `storage.last_test_message`.
- Messages are sanitized before persistence and must not contain secrets.
