# 13 — Frequently Asked Questions (FAQ)

---

## Section A: Questions from End Users

### A1. Do I need an account to browse the website?

No. You can browse all public content — the homepage, material library, articles, community posts, and product catalog — without an account. An account is required to make purchases, create community posts, interact with content (likes, comments), and save posts.

### A2. How do I change the website language?

Use the language switcher in the top navigation bar. The platform supports **English**, **Korean (한국어)**, and **Simplified Chinese (简体中文)**.

### A3. Can I checkout without creating an account?

Yes. Guest checkout is supported. You will need to provide your email address and shipping details. You can look up your order later using your order number and the email address used at checkout, via the Store → Orders page.

### A4. How do I track my order?

- **If you are logged in**: go to Account → Orders to see all your orders.
- **If you checked out as a guest**: go to Store → Orders, enter your order number (format: `OXP-XXXXXX`) and the email address you used at checkout.

### A5. What currencies does the store accept?

The store operates in **New Zealand Dollars (NZD)**. Prices are inclusive of 15% GST (New Zealand Goods and Services Tax).

### A6. How do I reset my password?

On the login page, click **Forgot Password?**. Enter your email address and click Submit. A password reset link will be sent to your email. Click the link and enter a new password. The link expires after a short period — request a new one if needed.

### A7. Why is my account restricted?

Account restrictions are applied by platform administrators for violations of community guidelines. A restricted account can still log in and browse but cannot create posts or comments. Contact the platform support for details.

### A8. Can I delete my account?

Currently, users cannot self-delete their accounts from the frontend. Contact the platform administrator to request account removal.

### A9. Why does my post show as "Pending Review"?

Depending on the platform's current moderation settings, new posts may go through a review process before being publicly visible. Moderators will review your post and either publish or reject it.

### A10. How do I report inappropriate content?

Click the three-dot menu (⋮) on any post or comment and select **Report**. Choose a reason, add any additional details, and submit. A moderator will review your report.

### A11. How many files can I attach to a community post?

You can attach up to **12 files** (images and documents) and **4 external links** per post. Individual files must be under 10 MB.

### A12. What file types can I upload to a post?

Images: JPG, JPEG, PNG, WebP, GIF
Documents: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX

---

## Section B: Questions from Administrators

### B1. How do I access the admin panel?

Navigate to `{your-backend-url}/admin`. Log in with an account that has the `admin` role.

### B2. How do I create a new admin user?

1. Register or create a user account normally.
2. In the admin panel, go to **Users → Users**.
3. Find the user and edit their record.
4. Change the **Role** to **Admin** and save.

> Or use `php artisan tinker` to manually update the role in the database.

### B3. How do I manage initial content?

Initial content is delivered as normal platform content. Use the standard product, CMS, community, and user resources to edit, archive, or replace individual records when required.

### B4. How do I update the legal pages (Privacy Policy / Terms of Service)?

Go to **Admin Panel → System → Legal Page Settings**. Edit the content for each page in all three languages. Save — changes are immediately live.

### B5. How do I configure email sending?

Go to **Admin Panel → System → Email Settings**. Enter your SMTP credentials and click **Send Test Email** to verify. Settings take effect immediately.

### B6. How do I feature a community post on the homepage?

In the admin panel, go to **Community → Posts**. Find the post and edit it. Check the **Featured** checkbox and save. The post will appear in featured post sections.

### B7. Can I switch from local storage to Azure mid-operation?

Yes, but with care:
1. Update `FILESYSTEM_DISK=azure` and configure Azure credentials.
2. Clear caches: `php artisan optimize:clear`
3. Note: existing files on local storage will not be automatically migrated. Use the Media Storage Scan tool to identify any discrepancies.

### B8. How do I handle a failed queue job?

In the backend terminal:
```bash
php artisan queue:failed       # list failed jobs
php artisan queue:retry all    # retry all failed jobs
php artisan queue:flush        # clear failed jobs (after reviewing)
```

Or check the Handover Readiness page in the admin panel for a summary.

### B9. How do I export B2B leads?

Go to **Admin Panel → B2B / Leads → B2B Leads**. Use the **Export** button to download a CSV file of all leads.

### B10. How do I add a new product category?

Go to **Admin Panel → Store → Product Categories → New Category**. Enter the category name in all three languages and a unique slug. Save.

### B11. Where can I see all orders?

Go to **Admin Panel → Store → Orders**. Use the filter options to narrow by status, date, or customer.

### B12. Can two admins edit the same record simultaneously?

The platform does not have real-time collaborative editing. If two admins edit the same record simultaneously, the last save wins. Coordinate with your team to avoid conflicts.

---

## Section C: Questions from Technical Maintainers

### C1. How do I run database migrations?

```bash
cd B2C_backend
php artisan migrate
```

For status check: `php artisan migrate:status`

### C2. How do I seed initial content?

```bash
php artisan db:seed
```

To seed a specific seeder:
```bash
php artisan db:seed --class=ProductSeeder
```

### C3. How do I check for i18n key coverage?

```bash
cd B2C_frontend
npm run check:i18n
```

### C4. Where are the application logs?

`B2C_backend/storage/logs/laravel.log`

### C5. How do I add a new translation key?

1. Add the key to `B2C_frontend/messages/en.json` first.
2. Add the equivalent translation to `ko.json` and `zh.json`.
3. Run `npm run check:i18n` to verify coverage.
4. Rebuild the frontend.

### C6. How do I verify the storage link is working?

```bash
ls -la B2C_backend/public/storage
# Should show: storage -> /path/to/storage/app/public
```

If missing: `php artisan storage:link`

### C7. How do I run the test suite?

```bash
# Backend (Laravel)
cd B2C_backend
php artisan test

# Frontend (if unit tests configured)
cd B2C_frontend
npm run test

# End-to-end tests (Playwright, from project root)
cd terraf
npx playwright test
```

### C8. How do I check PHP version requirements?

```bash
php --version
# Should be 8.2 or higher

php -m | grep -E "pdo_mysql|mbstring|openssl"
# Verify required extensions are loaded
```

---

## Section D: Questions from the Client / Business Owner

### D1. Can I change the platform name from OXP?

Yes. Update `APP_NAME` in the backend `.env` file, update frontend branding assets, and update any email templates that reference the platform name. Contact the development team for a full rebranding.

### D2. Can I add more languages?

Yes, with development effort:
- Add a new locale file to `B2C_frontend/messages/{locale}.json`
- Update the i18n routing configuration
- Add multilingual content columns for the new language in the database
- Update the admin panel to support editing in the new language

### D3. Is the payment gateway included?

No. The current platform records orders and payment intent but does not process payments. You will need to integrate a payment gateway such as Stripe, PayPal, or a local provider. This requires development work.

### D4. Can the store be used for international shipping?

The store is currently configured for New Zealand (NZD currency, NZ Post shipping). International support would require adding currency conversion, international carrier integrations, and multi-currency pricing. This requires development work.

### D5. Who owns the data?

All platform data is stored in your database (MySQL) and file storage (Azure Blob Storage or local). You retain full ownership of all data.

### D6. Can the platform handle large volumes of users and orders?

The platform is designed for moderate-scale operations. For high-traffic scenarios, consider:
- Using Redis for cache and queue (instead of database)
- Adding a CDN for static assets
- Database read replicas for heavy read workloads
- Consulting the development team for scaling recommendations

### D7. How do I back up the platform?

See [11-deployment-and-maintenance.md](./11-deployment-and-maintenance.md) for backup procedures. Key points:
- Back up the MySQL database daily
- Back up Azure Blob Storage (or local media files) regularly
- Keep the `.env` file backed up securely

---

*For additional questions not covered here, refer to the specific manual for the relevant feature, or contact the development team.*
