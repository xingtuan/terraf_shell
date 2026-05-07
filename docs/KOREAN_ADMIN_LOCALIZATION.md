# Korean Admin Localization

Admin translation files live in:

- `B2C_backend/lang/en/admin.php`
- `B2C_backend/lang/ko/admin.php`
- `B2C_backend/lang/zh/admin.php`

## Key Structure

Use `admin.php` for admin-facing Filament strings:

- `admin.brand.*`
- `admin.navigation.*`
- `admin.resources.*`
- `admin.pages.*`
- `admin.widgets.*`
- `admin.actions.*`
- `admin.fields.*`
- `admin.orders.*`
- `admin.leads.*`
- `admin.products.*`
- `admin.system.*`
- `admin.notifications.*`

Examples:

```php
__('admin.navigation.store_operations')
__('admin.actions.change_status')
__('admin.orders.status.shipped')
```

## How Locale Switching Works

The admin panel includes user-menu links for English, Korean, and Chinese.

- Route: `/admin/locale/{locale}`
- Middleware: `App\Middleware\SetAdminLocale`
- Storage: session key `admin_locale`
- Supported locales: `en`, `ko`, `zh`
- Fallback locale: `en`

This keeps the handover simple. A user preference column can be added later if persistent cross-device locale is required.

## Avoid Hardcoded Labels

For new Filament resources, pages, widgets, filters, actions, notifications, and enum labels:

1. Add a key to all three `admin.php` files.
2. Use `__('admin.some.key')` in the resource/page/widget.
3. For enums, return translated labels from `label()` methods.
4. Do not translate user-generated content.

The helper trait `App\Filament\Support\HasAdminResourceTranslations` centralizes common resource labels. Navigation groups use `App\Filament\Support\AdminNavigationGroup`.

## What Is Not Translated Automatically

Do not auto-translate:

- Customer names, addresses, and notes
- Lead messages and requirements
- Community post bodies and comments
- Uploaded file names
- Admin internal notes

These are stored as entered. Admin UI labels around them are localized.

## Fallback Behavior

Laravel uses English fallback when configured in `config/app.php`. If a key is missing, the raw key can appear in the UI. The admin test suite includes coverage for core admin translation keys in EN, KO, and ZH.
