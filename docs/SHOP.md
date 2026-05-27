# Shop

This document describes products, variants, SKU, stock, cart, checkout, Guest Checkout, GST, shipping, and order lookup.

## Product Data

Store data includes:

- Product Category.
- Product.
- Product Variant.
- Product Attribute Definition / Value.
- Product Image.
- Inventory Log.

Products can be managed in admin or initialized by seeders. `ProductCatalogSeeder` creates official starter catalog records.

## Variants And SKU

Each product can have variants with SKU, attribute values, price, stock quantity, inventory policy, and enabled status. The product detail page shows options based on available variants.

## Stock

When a variant has a deny inventory policy and non-null stock quantity:

- Order creation deducts stock.
- Pending order cancellation restores stock.
- Inventory changes are logged.

Use admin workflows or controlled maintenance operations for stock changes.

## Cart

The cart supports:

- Guest cookie sessions.
- Authenticated user carts.
- Cart merge after login.
- Variant-aware quantities.
- Recalculated subtotal, GST, shipping, and total.

## Checkout

Checkout validates cart contents, purchasability, stock, email, shipping address, shipping option, and feature flags.

There is no built-in online payment gateway. Admins maintain payment status manually.

## Guest Checkout

Guest Checkout requires the feature flag to be enabled and a valid email, shipping address, contact details, and shipping option. Guest orders receive a lookup token.

## Registered Orders

Registered users can view orders from the account center.

## GST

GST is configured in Tax Settings:

- GST enabled.
- GST rate.
- Prices include GST.
- Tax label.

Fallback environment values:

```dotenv
STORE_GST_RATE=0.15
STORE_PRICES_INCLUDE_GST=false
STORE_TAX_LABEL=GST
```

Clear cache if changes do not appear:

```bash
cd B2C_backend
php artisan optimize:clear
```

## Shipping

Shipping Settings supports NZ-only rules, origin city/postcode, free-shipping threshold, standard / express / rural rates, and quote source (`manual`, `nzpost`, `auto`).

`auto` tries NZ Post first and falls back to manual rates if needed.

## Order States

Order statuses:

| Status | Meaning |
| --- | --- |
| `pending` | Created, awaiting confirmation |
| `confirmed` | Confirmed |
| `processing` | In progress |
| `shipped` | Shipped |
| `delivered` | Delivered |
| `cancelled` | Cancelled |

Payment statuses:

| Status | Meaning |
| --- | --- |
| `unpaid` | Not paid |
| `paid` | Paid |
| `refunded` | Refunded |

There is no `failed` payment status.

## Order Lookup

Guests use order number plus guest token. Registered users view their own orders from the account center. Admins can view and manage all orders.

## Email And Notifications

Order creation, cancellation, shipment, and status changes can trigger email or notification events. Queue workers must run.

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## Common Issues

- Add to cart fails: check product status, variant status, SKU, stock, and inventory policy.
- Guest Checkout is hidden: check Feature Flags.
- GST is wrong: check Tax Settings.
- Shipping is wrong: check Shipping Settings and NZ Post credentials.
- Stock does not change: confirm the order uses a variant with deny inventory policy and stock quantity.
- Guest order lookup fails: confirm the token.
