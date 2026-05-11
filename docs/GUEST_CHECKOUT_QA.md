# Guest Checkout QA

Date: 2026-05-11

## Verified In Code

- `B2C_frontend/lib/api/orders.ts` accepts `token?: string | null` for `createOrder`.
- `B2C_frontend/lib/api/client.ts` only sends `Authorization` when `options.token` is truthy.
- Checkout passes `session.token` directly, so guests submit without login.
- Cart sidebar now gives guests two explicit choices: guest checkout or sign in to checkout.
- Cart sidebar guest checkout closes the sidebar and navigates directly to the localized checkout route.
- Cart sidebar sign-in still opens the auth modal and does not start guest checkout.
- Full cart page also shows guest checkout plus sign-in-to-checkout options for guests.
- Backend `POST /api/orders` is outside the authenticated route group.
- `StoreOrderRequest` requires `guest_email` for guests and prohibits saved `address_id` for guests.
- `OrderService` creates `guest_order_token`, snapshots shipping/tax, deducts stock, and clears cart items.
- `OrderController@showGuest` supports order-number plus token lookup.
- Guest checkout redirects to `store/order-submitted/{order_number}?token=...`.
- Registered checkout redirects to `account/orders/{order_number}?submitted=1`.
- Frontend now clears the guest cart session key and cookie before reloading the cart after a guest order.

## Tests Added Or Existing

- Existing backend coverage: `StoreOrderFlowTest::test_guest_cart_can_create_guest_order_request`.
- Existing backend coverage: `StoreOrderFlowTest::test_guest_order_lookup_requires_valid_token`.
- Existing backend coverage: registered checkout through guest-cart merge.
- Added frontend API client test coverage that `requestApi` omits `Authorization` for `token: null` and sends it for a real token.
- `php artisan test` passed.
- `corepack pnpm test`, `corepack pnpm exec tsc --noEmit`, and `corepack pnpm build` passed.
- 2026-05-11 frontend checks: `corepack pnpm test`, `corepack pnpm exec tsc --noEmit`, and `corepack pnpm build` passed.

## Still Needs Attention

- Browser QA should confirm cookie behavior when frontend and backend run on separate domains/ports.
- Email delivery for guest order confirmation depends on Email Center/provider configuration.
