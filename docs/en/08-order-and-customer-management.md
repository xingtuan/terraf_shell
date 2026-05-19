# 08 — Order and Customer Management

## Overview

This manual covers order processing, customer account management, and address management from the administrator's perspective.

---

## 1. Order Management

### 1.1 Order List

**Location**: Admin Panel → Store → Orders

The order list displays all orders with:
- Order number (format: `OXP-XXXXXX`)
- Customer (name and email for registered users; guest email for guest orders)
- Order total
- Order status
- Payment status
- Date placed

**Filtering options:**
- Filter by order status (Pending, Confirmed, Processing, Shipped, Delivered, Cancelled)
- Filter by payment status (Unpaid, Paid, Refunded, Failed)
- Search by order number or customer email
- Sort by date, total, or status

### 1.2 Order Detail View

Click any order to open the full order detail:

**Order Information**
- Order number
- Placement date and time
- Customer type (guest or registered)
- Customer email / user account link

**Order Items**
- Product variant name and SKU
- Quantity ordered
- Unit price at time of order (price snapshot)
- Line total

**Financial Summary**
- Subtotal
- Shipping cost
- Tax amount (GST)
- Grand total
- Discount (if applicable)

**Shipping Information**
- Full shipping address (snapshot at time of order)
- Shipping method selected
- Shipping quote snapshot (including carrier and service level if NZ Post was used)

**Status Information**
- Current order status
- Status timestamps (confirmed_at, processing_at, shipped_at, delivered_at, cancelled_at)
- Current payment status

*Related code: `app/Models/Order.php`, `app/Filament/Resources/OrderResource.php`*

### 1.3 Updating Order Status

From the order detail page, click **Edit** to modify:
- **Order Status** — select the new status from the dropdown
- **Payment Status** — update after receiving payment confirmation
- **Payment Reference** — record the payment processor reference number
- **Tracking information** (add to shipping notes if applicable)

**Status flow recommendations:**
1. New orders arrive as **Pending** — review and confirm quickly.
2. Set to **Confirmed** to acknowledge receipt.
3. Set to **Processing** when the order is being prepared.
4. Set to **Shipped** when the package is dispatched.
5. Set to **Delivered** when you receive delivery confirmation or after an appropriate delivery window.

### 1.4 Cancelling an Order

To cancel an order:
1. Open the order.
2. Edit and change status to **Cancelled**.
3. Add a note if appropriate.
4. Save.

> **Note**: Cancellation does not automatically trigger a refund. Process refunds manually through your payment processor.

---

## 2. Order Status Reference

| Status | When Used | Customer Impact |
|---|---|---|
| **Pending** | Order placed, not yet reviewed | Customer sees "Order received" |
| **Confirmed** | Admin acknowledges order | Customer sees "Order confirmed" |
| **Processing** | Item being picked and packed | Customer sees "Being prepared" |
| **Shipped** | Package dispatched | Customer sees "Shipped" |
| **Delivered** | Delivery confirmed | Customer sees "Delivered" |
| **Cancelled** | Order cancelled | Customer sees "Cancelled" |

## 3. Payment Status Reference

| Status | When Used |
|---|---|
| **Unpaid** | Default on order creation; payment not yet received |
| **Paid** | Payment confirmed by admin |
| **Refunded** | Full or partial refund issued |
| **Failed** | Payment attempt failed |

> **Reminder**: The platform does not include an automatic payment gateway. All payment status changes are manual.

---

## 4. Guest Order Tracking

Guest customers can look up their orders without an account:

- **Frontend**: `/store/orders`
- **Required**: Order number (OXP-XXXXXX) and email address used at checkout.
- The system returns the order details if both match.

Administrators can view all guest orders in the order list; they are distinguished by having no associated user account (only a guest email).

*Related code: `app/Http/Controllers/Api/OrderController.php::lookupGuest()`*

---

## 5. Customer Account Management

### 5.1 Viewing Customer Accounts

**Location**: Admin Panel → Users → Users

Filter by role to see regular customers (role: `creator`). Each customer profile shows:
- Registration date
- Account status
- Profile information
- Associated orders
- Community activity (posts, comments)

### 5.2 Account Status Management

If a customer reports issues with their account, administrators can:
- Reset account status to **Active** if it was incorrectly suspended
- **Suspend** accounts with unusual activity (temporary)
- **Ban** accounts for policy violations (permanent)
- **Restrict** accounts to prevent posting while maintaining shopping ability

> **Important**: Use account moderation carefully. Notify the customer before taking action when possible.

---

## 6. Address Management

### 6.1 Admin Address View

**Location**: Admin Panel → Store → Addresses

Administrators can view all saved customer addresses. This is primarily for support purposes (e.g., verifying a customer's default address if they report checkout issues).

Each address record shows:
- Associated user
- Full address details
- Default flag
- Creation date

### 6.2 Customer Address Self-Management

Customers manage their own addresses from:
- **Frontend**: Account → Addresses
- They can add, edit, delete, and set a default address.

---

## 7. Cart Management (Admin View)

**Location**: Admin Panel → Store → Carts

Administrators can view active shopping carts for debugging and support purposes:
- Customer's cart items
- Quantities and product variants
- Cart creation and update timestamps

This view is read-only for administrators; carts are managed by customers and the API.

---

## 8. Order Analytics

The admin panel provides basic order analytics via:
- `GET /api/admin/analytics/overview` — platform-wide engagement and transaction summary
- Dashboard widgets on the main admin dashboard

For detailed sales reporting, export orders using the admin panel's filter and export features or query the database directly.

---

## 9. Inventory Reconciliation

After processing orders, update inventory manually:

1. Go to **Admin Panel → Store → Inventory**.
2. Find the product variant.
3. Click **Edit**.
4. Update the `stock_quantity` field.
5. Record an inventory adjustment note.

> **Recommendation**: Establish a regular end-of-day or end-of-week inventory reconciliation process to keep stock levels accurate.

*Related code: `app/Models/InventoryAdjustment.php`, `app/Filament/Resources/InventoryResource.php`*

---

## 10. Common Order Processing Scenarios

### 10.1 Order Placed but Not Paid

1. Verify with the customer that payment was attempted.
2. Check your payment processor for the transaction.
3. If payment confirmed: update `payment_status` to Paid.
4. If payment failed: update `payment_status` to Failed and ask customer to retry.

### 10.2 Customer Wants to Cancel

1. If order is **Pending** or **Confirmed**: cancel without issue.
2. If order is **Processing**: evaluate whether it can still be stopped.
3. If order is **Shipped**: the item cannot be recalled; process a return/refund after delivery.

### 10.3 Wrong Address Provided

1. Check order status — if still **Pending** or **Confirmed**, you may be able to update the address.
2. Contact the customer to confirm the correct address.
3. Note: shipping address on the order is a snapshot and does not update automatically if the customer updates their saved address.

### 10.4 Item Out of Stock After Order Placed

1. Contact the customer to inform them of the delay or substitution options.
2. Update the order status to reflect the situation.
3. Issue a refund if the customer prefers.

---

*Related code: `B2C_backend/app/Models/Order.php`, `B2C_backend/app/Services/OrderService.php`, `B2C_backend/app/Filament/Resources/OrderResource.php`*
