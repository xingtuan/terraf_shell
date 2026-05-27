# Community

The community module supports posts, rich text, attachments, cover images, funding links, comments, favorites, reports, moderation, user restrictions, and notifications.

## Features

Current functionality includes:

- Post listing, detail, search, and sorting.
- Tiptap rich text JSON.
- Cover images.
- Attachments / idea media.
- External 3D links.
- Funding links and funding campaign display.
- Comments and replies.
- Likes, favorites, and follows.
- Reports.
- Moderation queue.
- User violations and restrictions.
- User notifications.

Visibility is controlled by Feature Flags.

## Posts

Posts can contain title, content, summary, cover image, category, tags, attachments, external links, and funding URL. Creation and updates are handled by `PostService`, which applies authorization, moderation, sensitive-word checks, and media synchronization.

## Rich Text And Attachments

Frontend editing uses Tiptap. Upload limits come from `config/community.php` and admin Community Settings:

- Max file count.
- Max file size.
- Allowed extensions.
- Allowed MIME types.
- Image and document restrictions.

Uploads use the active storage driver.

## Cover Images

Cover image URLs are resolved by the backend:

- Local public storage: usually `/storage/...`.
- Azure: public Azure URL or temporary SAS URL.

## Funding Links

Posts can include funding URLs. Funding Links are controlled by Feature Flags. Funding Campaign records can display support text, progress-style information, target amounts, and external crowdfunding links.

Funding links are external content / support flows, not an internal payment gateway.

## Comments

Users can create comments and replies. Comments may be affected by moderation rules, sensitive words, and account restrictions.

## Likes, Favorites, Follows

Signed-in users can like posts, save posts, follow authors, and view saved content from the account area.

## Reports

Reports enter admin Reports / Moderation Queue. Admin handling should record the content, reason, action, user restriction decision, and notification decision.

## Moderation

Moderation settings support sensitive words, automatic flags, moderation queue, admin action logs, and user violation records.

If content does not appear, it may be pending review, hidden, deleted, or affected by user restrictions.

## User Restrictions

Admin governance modules support account status, violations, restrictions, and action logs.

```bash
cd B2C_backend
php artisan users:repair-account-status --dry-run
```

## Notifications

Community notifications depend on database records and queue workers.

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## Admin Modules

- Posts
- Comments
- Reports
- Tags
- Categories
- Moderation Queue
- User Violations
- User Notifications
- Moderation Logs
- Admin Action Logs
- Funding Campaigns
- Community Settings
- Community Moderation Settings

## Common Issues

- User cannot post: check feature flag, login state, account restrictions, moderation, and upload limits.
- Attachment upload fails: check size, extension, MIME, PHP limits, Nginx body size, storage driver, and permissions.
- Post does not appear: check moderation status and user restrictions.
- Funding link does not show: check Funding Links feature flag and post/campaign URL.
- Reports do not create notifications: check queue worker logs.
