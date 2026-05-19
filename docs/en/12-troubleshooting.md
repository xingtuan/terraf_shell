# 12 — Troubleshooting Guide

## Overview

This guide covers common issues encountered during development, deployment, and daily operation of the OXP platform, with practical solutions for each.

---

## 1. Login Issues

### 1.1 Cannot Log In — Invalid Credentials

**Symptoms**: Login returns "Invalid credentials" error.

**Solutions**:
1. Verify the email address and password are correct (case-sensitive).
2. Ensure the user account exists in the database.
3. Check if the account is `suspended` or `banned` (cannot log in when suspended/banned).
4. If using seeded admin credentials, check `database/seeders/UserSeeder.php` for the default values.

### 1.2 Admin Cannot Access Admin Panel

**Symptoms**: Admin navigates to `/admin` but is redirected to login or sees "Unauthorized."

**Solutions**:
1. Verify the user has `role = admin` in the `users` table.
2. Confirm `APP_URL` in `.env` matches the URL being used.
3. Check Filament's guard configuration in `config/auth.php`.
4. Clear caches: `php artisan optimize:clear`

### 1.3 Session Expiring Too Quickly

**Symptoms**: Users are logged out before expected.

**Solutions**:
1. Check `SESSION_LIFETIME` in `.env` (default: 120 minutes).
2. Verify session driver is correctly configured.
3. If using database sessions, ensure the `sessions` table exists.

### 1.4 Password Reset Email Not Received

**Symptoms**: User requests password reset but receives no email.

**Solutions**:
1. Verify `MAIL_MAILER` is not `log` or `null` in production.
2. Check `MAIL_FROM_ADDRESS` is a valid sender address.
3. Review `storage/logs/laravel.log` for mail errors.
4. Test email settings via **Admin Panel → System → Email Settings → Send Test Email**.
5. Check spam folders.

---

## 2. Product Save / Admin Panel Issues

### 2.1 Product Not Saving

**Symptoms**: Clicking Save in the product form shows an error or redirects without saving.

**Solutions**:
1. Check for validation errors shown in the form (required fields, format issues).
2. Ensure the product `slug` is unique (duplicate slugs will fail validation).
3. Check `storage/logs/laravel.log` for detailed error messages.
4. Verify database permissions.

### 2.2 Image Upload Fails in Admin Panel

**Symptoms**: Uploading a product image or article cover image returns an error.

**Solutions**:
1. Check the selected storage disk (`FILESYSTEM_DISK`) is correctly configured.
2. For Azure: verify `AZURE_STORAGE_NAME`, `AZURE_STORAGE_KEY`, and `AZURE_STORAGE_CONTAINER` are correct.
3. For local: run `php artisan storage:link` and verify the symlink exists.
4. Check the file size is within limits.
5. Verify PHP's `upload_max_filesize` and `post_max_size` in `php.ini`.

### 2.3 Rich Text Editor Not Loading

**Symptoms**: Tiptap editor is blank or non-functional in admin panel.

**Solutions**:
1. Hard refresh the browser (Ctrl+Shift+R / Cmd+Shift+R).
2. Check browser console for JavaScript errors.
3. Ensure the browser is modern and JavaScript is enabled.

---

## 3. Cart and Checkout Issues

### 3.1 Items Disappearing from Cart

**Symptoms**: Cart is empty after adding items.

**Solutions**:
1. For guests: cart is stored in browser session — cookies must be enabled.
2. For authenticated users: check `carts` and `cart_items` tables in the database.
3. Ensure `SANCTUM_STATEFUL_DOMAINS` includes the frontend domain.
4. Check for API errors in browser developer tools (Network tab).

### 3.2 Checkout Fails With Address Error

**Symptoms**: Checkout form shows address validation errors.

**Solutions**:
1. Ensure all required address fields are filled.
2. If using NZ Post address validation, verify `NZPOST_ENABLED` and credentials.
3. Disable NZ Post if issues persist: set `NZPOST_ENABLED=false`.

### 3.3 Shipping Options Not Loading

**Symptoms**: No shipping options appear at checkout.

**Solutions**:
1. If `NZPOST_ENABLED=true`: verify NZ Post API credentials are valid.
2. Verify flat-rate shipping values are set in `STORE_STANDARD_SHIPPING_RATE`.
3. Check the API endpoint: `POST /api/store/shipping-options` response.

---

## 4. Email Sending Issues

### 4.1 Emails Not Being Sent

**Symptoms**: Transactional emails (order confirmation, password reset) are not received.

**Solutions**:
1. In development: check `storage/logs/laravel.log` — emails are logged when `MAIL_MAILER=log`.
2. In production: verify SMTP credentials in Email Settings (admin panel) or `.env`.
3. Test using **Admin Panel → System → Email Settings → Send Test Email**.
4. Check email spam/junk folder.
5. Verify `MAIL_FROM_ADDRESS` is not a blocked or spoofed domain.

### 4.2 Email Sent But Not Received

**Symptoms**: Email logs show "sent" but recipient does not receive it.

**Solutions**:
1. Check spam/junk folder.
2. Verify the from address is authorized to send via your mail provider (SPF/DKIM records).
3. Test with a different recipient address.
4. Check email log in admin panel: `Email → Email Logs`.

---

## 5. File Upload Issues

### 5.1 Community Post Attachment Upload Fails

**Symptoms**: User gets an error when attaching files to a community post.

**Solutions**:
1. Check file type is allowed (`IDEA_MEDIA_ALLOWED_EXTENSIONS`).
2. Check file size is within limit (`IDEA_MEDIA_MAX_FILE_SIZE_KB`).
3. Verify storage disk is configured and accessible.
4. Check if `ALLOW_GUEST_UPLOAD=false` is blocking unauthenticated uploads.
5. Review `storage/logs/laravel.log` for detailed error.

---

## 6. Translation / i18n Issues

### 6.1 Text Appears in Wrong Language or as Translation Key

**Symptoms**: User sees `community.posts.title` instead of the actual translated text.

**Solutions**:
1. Check that the translation key exists in the relevant `messages/{locale}.json` file.
2. Run `npm run check:i18n` in the frontend directory to find missing translations.
3. Rebuild the frontend: `npm run build` after updating translation files.

### 6.2 Content Not Showing in Selected Language

**Symptoms**: Product or material shows English text even when Korean or Chinese is selected.

**Solutions**:
1. Verify the content has multilingual fields filled in the admin panel.
2. If the non-English fields are empty, the system falls back to English — this is expected behavior.
3. Fill in all language fields for the content item.

---

## 7. Cache Issues

### 7.1 Changes Not Reflecting After Save

**Symptoms**: Admin saves a setting or content but the website still shows old data.

**Solutions**:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

### 7.2 Application Errors After Config Change

**Symptoms**: 500 errors appear after modifying `.env`.

**Solutions**:
```bash
php artisan optimize:clear
php artisan optimize
```

---

## 8. Database Migration Issues

### 8.1 Migration Fails with Table Already Exists Error

**Symptoms**: `php artisan migrate` returns "Table already exists."

**Solutions**:
1. Check migration status: `php artisan migrate:status`
2. If a migration was partially run, use `php artisan migrate:rollback` to undo the last batch.
3. Do not manually delete migration records from the `migrations` table unless absolutely necessary.

### 8.2 Migration Fails with Foreign Key Error

**Symptoms**: Migration fails with "Cannot add foreign key constraint."

**Solutions**:
1. Run migrations in order — foreign key migrations depend on parent tables existing.
2. Ensure MySQL strict mode is configured appropriately.
3. Check the migration file for the correct table and column names.

---

## 9. Frontend Build Issues

### 9.1 Build Fails with Type Errors

**Symptoms**: `npm run build` fails with TypeScript errors.

**Solutions**:
```bash
# Check types explicitly
npm run typecheck

# Check i18n keys
npm run check:i18n
```
Fix all reported type errors before attempting to build.

### 9.2 Build Fails with i18n Key Missing

**Symptoms**: Build output shows missing translation keys.

**Solutions**:
1. Run `npm run check:i18n` to see all missing keys.
2. Add the missing keys to all three language files (`en.json`, `ko.json`, `zh.json`).
3. Rebuild.

### 9.3 Frontend Cannot Reach Backend API

**Symptoms**: All API calls return network errors or CORS errors in browser console.

**Solutions**:
1. Verify `NEXT_PUBLIC_API_URL` in frontend `.env` matches the backend URL.
2. Check backend `CORS_ALLOWED_ORIGINS` includes the frontend origin.
3. Verify the backend is running and accessible.
4. Check for HTTPS/HTTP mismatch between frontend and backend.

---

## 10. 500 Server Errors

### 10.1 General 500 Errors

**Symptoms**: API returns `500 Internal Server Error`.

**Solutions**:
1. Check `storage/logs/laravel.log` for the full error trace.
2. Set `APP_DEBUG=true` temporarily in development to see detailed errors in API responses.
3. **Never** set `APP_DEBUG=true` in production.

### 10.2 500 Error After Deployment

**Symptoms**: Application worked before deployment, now returns 500.

**Solutions**:
```bash
# Clear and rebuild all caches
php artisan optimize:clear
php artisan optimize

# Verify migrations are up to date
php artisan migrate:status

# Check file permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Check for PHP syntax errors in new code
php -l app/Http/Controllers/SomeController.php
```

---

## 11. Queue / Background Job Issues

### 11.1 Notifications Not Being Sent

**Symptoms**: Users do not receive in-platform notifications.

**Solutions**:
1. Check if queue worker is running: `ps aux | grep queue:work`
2. Check for failed jobs: `php artisan queue:failed`
3. Review failed job details: `php artisan queue:failed --detail`
4. In development, set `QUEUE_CONNECTION=sync` to run jobs immediately.

### 11.2 Failed Jobs Accumulating

**Symptoms**: Failed jobs count increasing in Handover Readiness page.

**Solutions**:
```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs (after investigating)
php artisan queue:flush
```

---

## 12. Permission Issues

### 12.1 Storage Write Permission Denied

**Symptoms**: File upload fails with permission error, or logs cannot be written.

**Solutions**:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 12.2 User Cannot Access a Feature

**Symptoms**: API returns 403 Forbidden for a user action.

**Solutions**:
1. Check the user's role: ensure it is appropriate for the action.
2. Check the user's account_status: restricted/suspended users cannot perform certain actions.
3. Review the relevant Policy class in `app/Policies/`.

---

## 13. Logs Location

| Log | Path |
|---|---|
| Application logs | `B2C_backend/storage/logs/laravel.log` |
| Queue worker logs | `B2C_backend/storage/logs/worker.log` (if configured) |
| Frontend build logs | Console output during `npm run build` |
| Email logs | Admin Panel → Email → Email Logs |
| Moderation logs | Admin Panel → Community → Moderation Log |

---

*Related code: `B2C_backend/storage/logs/`, `B2C_backend/config/logging.php`*
