# User Guide

This guide covers the user-facing site: content browsing, store, cart, Guest Checkout, order lookup, account pages, and community activity.

## Access And Languages

The frontend is served by Next.js:

```text
http://your-domain-or-ip/
```

Supported languages:

- English
- Chinese
- Korean

Routes are locale-based, for example `/en`, `/zh`, and `/ko`.

## Homepage And Content

Users can browse:

- Homepage sections.
- Material pages.
- Articles and CMS content.
- Store.
- Community.
- B2B / contact forms.
- Legal pages.

Feature Flags may hide or restrict modules.

## Material Pages

Material pages display admin-managed overview content, specifications, application scenarios, story sections, and related articles or page sections.

## Store

Users can browse categories, search products, view images and descriptions, choose variants, and add items to cart. Purchasability depends on product status, variant status, stock, and inventory policy.

## Cart

The cart supports guests and signed-in users:

- Guest carts use a cookie session.
- Signed-in carts are linked to the account.
- Guest carts can merge after login.
- Totals recalculate after quantity or item changes.

## Guest Checkout

Guest Checkout allows users to order without an account when the feature flag is enabled. Required information includes email, recipient details, shipping address, shipping option, and contact details.

The system does not include an online payment gateway. Payment status is maintained by admins.

## Registered Checkout

Signed-in users can use saved addresses and view orders from the account area.

Orders deduct stock when the selected variant uses a deny inventory policy and has stock quantity configured.

## Order Lookup

Guest orders are looked up by order number and guest token. Registered users can view their orders from the account center.

Order details may include items, address, shipping option, GST, total, status, payment status, shipment fields, and tracking number.

## Account Pages

Signed-in users can manage:

- Profile.
- Password.
- Email verification.
- Addresses.
- Orders.
- Community posts.
- Saved content.
- Notifications.

## Community Posting

Community posts support title, rich text content, cover image, attachments, tags, external 3D links, and funding links.

Publication may depend on moderation rules, sensitive words, and account status.

## Comments, Likes, Favorites, Follows

Signed-in users can comment, reply, like posts, save posts, follow users, and view saved content from the account area.

## Reports

Users can report community content. Reports enter the admin moderation workflow.

## Notifications

Notifications are created for community interactions, moderation events, and order updates. Queue workers must run for timely processing.

## B2B And Contact

Users can submit contact forms, B2B inquiries, sample requests, and material review requests. Admins handle these from the backend.

## Common User Issues

- Images do not display: check storage link, Azure URLs, permissions, or media paths.
- Checkout fails: check Guest Checkout, stock, shipping settings, and address support.
- Shipping looks wrong: check Shipping Settings and NZ Post configuration.
- GST looks wrong: check Tax Settings.
- Guest order lookup fails: confirm the order number and token.
- Community content is missing: it may be pending moderation, hidden, removed, or affected by account restrictions.
