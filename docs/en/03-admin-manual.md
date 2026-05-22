# 03 — Administrator Manual

## Introduction

The OXP admin panel is built on **Filament 5** and is accessible at the `/admin` path of the backend URL. This manual covers all administrative functions available in the admin panel and via admin-only API endpoints.

---

## 1. Admin Login

1. Navigate to `{BACKEND_URL}/admin`
2. Enter your administrator email and password.
3. Click **Sign In**.

> **Default admin credentials**: Set by the `UserSeeder` during initial setup. Change immediately after first login.

[Screenshot placeholder: Admin login page]

If you cannot log in:
- Verify you have the `admin` role assigned in the database.
- Confirm the backend is running and accessible.
- Check `APP_URL` in the `.env` file matches the URL you are visiting.

---

## 2. Dashboard

After login, the dashboard provides a quick overview of platform activity:

- Recent orders
- Community activity summary
- Pending reports count
- User registration counts
- Quick navigation links

[Screenshot placeholder: Admin dashboard]

---

## 3. Navigation Structure

The left sidebar organizes admin functions into groups:

| Group | Contents |
|---|---|
| **Dashboard** | Overview metrics |
| **Community** | Posts, Comments, Reports, Moderation Log, User Violations, Admin Action Log |
| **Users** | User management |
| **Store** | Orders, Products, Variants, Categories, Images, Attributes, Inventory, Carts, Addresses |
| **CMS** | Materials, Material Specs, Story Sections, Applications, Articles, Homepage Sections, Idea Media |
| **B2B / Leads** | B2B Leads, Enquiries |
| **Email** | Email Templates, Email Events, Email Logs |
| **Funding** | Funding Campaigns |
| **Community Settings** | Categories, Tags |
| **System** | All settings, tools, and handover utilities |

---

## 4. User Management

**Location**: Users → Users

### 4.1 Viewing Users

The user list shows all registered users with:
- Username and email
- Role (Creator / Moderator / Admin)
- Account Status (Active / Suspended / Banned / Restricted)
- Registration date

Use the **search bar** to find users by name or email. Use **filters** to filter by role or status.

### 4.2 Editing a User

Click any user row to open the user detail view. You can:
- View all user information
- Change the user's **role**
- Change the user's **account status**
- View associated posts, orders, and reports

### 4.3 Roles

| Role | Code | Access |
|---|---|---|
| Creator | `creator` | Standard user — browse, post, shop |
| Moderator | `moderator` | All creator capabilities + moderation actions |
| Admin | `admin` | Full admin panel access |

> **Warning**: Only assign the `admin` role to trusted individuals. Admins have unrestricted access to all platform data.

### 4.4 Account Status

| Status | Effect |
|---|---|
| Active | Normal account operation |
| Restricted | User can log in but cannot post or comment |
| Suspended | User cannot log in |
| Banned | Permanent ban — account is locked |

### 4.5 Banning a User

Banning can be performed from:
- The Users resource in admin panel → select user → change status to Banned
- The Reports resource → resolve a report with "ban" action
- Via the moderation API endpoints

> **Warning**: Banning is a significant action. Record the reason in the moderation log.

---

## 5. Product Management

**Location**: Store → Products

### 5.1 Product List

Shows all products with:
- Product name (in default language)
- Category
- Status (Active / Inactive / Draft / Archived)
- Price
- Variant count

### 5.2 Creating a Product

Click **New Product**. Fill in the following sections:

**Basic Information**
- **Name** (required, multilingual: EN / KO / ZH)
- **Slug** — auto-generated from name; must be unique
- **Category** — select from existing product categories
- **Status** — Draft, Active, Inactive, or Archived
- **Description** (multilingual)
- **Features** (multilingual)
- **Care Instructions** (multilingual)
- **Material Benefits** (multilingual)

**Pricing**
- **Price** — in NZD (GST handling depends on `STORE_PRICES_INCLUDE_GST` setting)
- **Compare At Price** — original price for sale display

**Commerce Settings**
- **Inquiry Only** — if enabled, the "Add to Cart" button is replaced with an inquiry button
- **Material Request Enabled** — allows users to request product review packs
- **Featured** — marks product as featured (shown in featured section)

**SEO**
- **SEO Title** (multilingual)
- **SEO Description** (multilingual)

**Certifications / Shipping / Returns**
- Certification details
- Shipping notes (multilingual)
- Return notes (multilingual)

**Product FAQs** — add FAQ items with question and answer (multilingual)

Click **Save** to create the product.

### 5.3 Product Variants

**Location**: Store → Product Variants

Each product can have multiple variants (e.g., different sizes or colors).

For each variant:
- **SKU** — unique identifier
- **Product** — parent product
- **Stock Quantity** — current inventory level
- **Weight (grams)**
- **Barcode** — optional

> The dynamic attribute system (`ProductAttributeDefinition`, `ProductAttributeValue`, `ProductAttributeAssignment`) allows flexible attribute definitions beyond fixed size/color fields.

### 5.4 Product Images

**Location**: Store → Product Images

Upload and manage images for each product:
- **Product** — parent product
- **Image URL or File** — uploaded to storage
- **Sort Order** — controls display order
- **Primary** — marks the main display image

### 5.5 Product Categories

**Location**: Store → Product Categories

- **Name** (multilingual: EN / KO / ZH)
- **Slug** — unique URL identifier
- **Parent Category** — for hierarchical categories

### 5.6 Inventory

**Location**: Store → Inventory

View and adjust stock levels for product variants:
- Current stock quantity per variant
- Record inventory adjustments with notes
- View adjustment history

---

## 6. Order Management

**Location**: Store → Orders

*See [08-order-and-customer-management.md](./08-order-and-customer-management.md) for the full order management reference.*

### 6.1 Order List

Shows all orders with:
- Order number (OXP-XXXXXX)
- Customer name / email
- Total amount
- Order status
- Payment status
- Date placed

### 6.2 Viewing an Order

Click any order to view:
- Order items with quantities and prices
- Shipping address
- Shipping method and cost
- Subtotal, tax, and total
- Order status history
- Payment status and reference

### 6.3 Updating Order Status

From the order detail page:
- Click **Edit** to change the order status.
- Available transitions: Pending → Confirmed → Processing → Shipped → Delivered → Cancelled.

### 6.4 Order Status Meanings

| Status | Meaning |
|---|---|
| Pending | Received, not yet confirmed |
| Confirmed | Confirmed by admin |
| Processing | Being prepared for shipment |
| Shipped | Dispatched to customer |
| Delivered | Received by customer |
| Cancelled | Cancelled by admin or customer |

---

## 7. Community Post Management

**Location**: Community → Posts

### 7.1 Post List

Shows all community posts with:
- Title and author
- Category
- Status (Draft / Published / Archived / Rejected / Pending Review)
- Featured flag
- Pinned flag
- Engagement score
- Published date

### 7.2 Editing a Post

Click any post to edit or review. Admins can:
- Change post **status** (Publish, Archive, Reject, set to Pending Review)
- Toggle **featured** status
- Toggle **pinned** status
- Edit content directly

### 7.3 Post Status Meanings

| Status | Meaning |
|---|---|
| Draft | Not yet published; only visible to author |
| Pending Review | Awaiting moderation approval |
| Published | Live and visible to all users |
| Archived | Hidden from public feed but preserved |
| Rejected | Rejected by moderator; not shown publicly |

### 7.4 Community Category and Tag Management

**Location**: Community Settings → Categories / Tags

Manage the taxonomy used by community posts:
- Create, edit, and delete categories and tags
- Categories and tags support multilingual names (EN / KO / ZH)
- Tags track whether they are initial content (internal initial-content marker flag)

---

## 8. Comment Management

**Location**: Community → Comments

View and manage all comments across the platform:
- Search by content or author
- Filter by status
- Change comment status (Publish, Archive, Reject)
- Delete comments

---

## 9. Report & Moderation Management

*See [09-moderation-and-reporting.md](./09-moderation-and-reporting.md) for the full moderation reference.*

**Location**: Community → Reports

The moderation queue shows all content reports submitted by users.

### 9.1 Report Fields

- **Reporter** — user who submitted the report
- **Target type** — Post or Comment
- **Target** — the reported content
- **Status** — Open / Under Review / Resolved / Rejected / Dismissed
- **Resolution action** — Hide / Delete / Warn / Restrict / Ban (set when resolving)
- **Feedback** — message shown to the reporter
- **Rejected reason** — reason if report was rejected

### 9.2 Moderation Actions

From a report:
- **Review** — begin reviewing (status → Under Review)
- **Dismiss** — close without action
- **Resolve** → choose action: Hide target, Reject target, Warn user, Restrict user, Ban user
- **Hide target** — hides the reported post or comment without deleting
- **Warn user** — sends a warning to the content author
- **Restrict user** — restricts the content author's posting ability
- **Ban user** — permanently bans the content author

---

## 10. B2B Lead Management

**Location**: B2B / Leads → B2B Leads

### 10.1 Lead List

Shows all B2B leads with:
- Company name
- Contact name and email
- Lead type (Material Inquiry / Partnership / Material Request / Collaboration)
- Status (New / Contacted / Qualified / Negotiating / Converted / Rejected)
- Date received

### 10.2 Lead Detail

Click any lead to view full details:
- Company information (company size, industry, production volume)
- Contact information
- Inquiry type and interest areas
- Message / description
- Assigned team member
- Status history

### 10.3 Updating Lead Status

Edit the lead to update:
- **Status** — progress through the sales pipeline
- **Assigned to** — assign to a team member
- Internal notes

### 10.4 Exporting Leads

Use the **Export** button to download leads as a CSV file for use in CRM or spreadsheet tools.

---

## 11. Content Management (CMS)

*See [07-content-management-manual.md](./07-content-management-manual.md) for the full CMS reference.*

### 11.1 Materials

**Location**: CMS → Materials

Manage the material library shown on the Material page:
- Material name, description (multilingual)
- Story cover image
- Certification and proof fields
- Related specs, story sections, and applications

### 11.2 Articles

**Location**: CMS → Articles

Manage the knowledge base articles:
- Title, slug, cover image
- Rich text content (content_json using Tiptap format)
- Published / Draft status
- Published date and reading time

### 11.3 Homepage Sections

**Location**: CMS → Homepage Sections

Manage the dynamic sections of the homepage:
- Section type (hero, feature, gallery, CTA, etc.)
- Title and subtitle (multilingual)
- Background image
- Content JSON
- Sort order
- Published / Draft status

---

## 12. Email Center

**Location**: Email → Email Templates / Email Events / Email Logs

### 12.1 Email Templates

View and edit transactional email templates. Each template has:
- Template key (e.g., `order_confirmed`, `password_reset`)
- Subject line (multilingual)
- Body content (multilingual, HTML-capable)
- Template variables (e.g., `{{customer_name}}`, `{{order_number}}`)

### 12.2 Email Events

Configure which events trigger email sending:
- Event type (e.g., order placed, user registered)
- Template to use
- Active / Inactive toggle

### 12.3 Email Logs

Review all emails sent by the system:
- Recipient address
- Template used
- Sent timestamp
- Delivery status

---

## 13. System Settings

**Location**: System → [various settings pages]

*See [10-settings-and-configuration.md](./10-settings-and-configuration.md) for a full reference.*

### 13.1 Application Settings

Manage general application settings including app name, frontend URL, and feature flags.

### 13.2 Email Settings

Configure the mail server connection:
- SMTP host, port, username, password
- From address and from name
- Test email sending

> **Note**: Email settings configured here override the `.env` file values at runtime.

### 13.3 Storage Settings

Switch between Azure Blob Storage and local disk storage.

### 13.4 Shipping Settings

Configure shipping rates:
- Standard shipping rate
- Express shipping rate
- Rural surcharge
- Free shipping threshold
- Origin postcode and city

### 13.5 Tax Settings

Configure GST/tax rates and labels.

### 13.6 Legal Page Settings

Edit the content of the Privacy Policy and Terms of Service pages directly from the admin panel. Changes are immediately reflected on the public website.

### 13.7 Community Settings

Configure community moderation policies:
- Submission policy (auto-approve, approval required, restricted)
- Sensitive word filtering settings

### 13.8 Feature Flags

Toggle specific platform features on or off at runtime without code changes.

---

## 14. Initial Content

The platform ships with initial content for products, materials, community posts, users, and CMS sections. This content is part of the delivered system.

Review and maintain initial records through the standard product, community, user, and CMS resources. Edit, archive, or replace individual records only when the business requires it.

---

## 15. Handover Readiness

**Location**: System → Handover Readiness

Before delivering the platform to a client or launching in production, use the Handover Readiness page to review the system's readiness status.

Checks include:
- App name and URL configuration
- Database connection status
- Storage disk status
- Mail configuration
- Queue mode
- Admin account presence
- Initial content status
- Failed jobs
- PHP and Laravel versions
- Storage directory permissions
- Last migration status

Each check shows: **OK**, **Warning**, or **Error**.

Resolve all **Error** items before handover. Review all **Warning** items.

---

## 16. Settings Backup & Export

**Location**: System → Settings Backup

Export the current admin settings configuration as a JSON file for:
- Backup purposes
- Migrating settings to a new environment
- Documentation of the current configuration

Import a previously exported settings file to restore settings.

---

## 17. Admin Language Switching

The admin panel supports locale switching via:
`GET /admin/locale/{locale}` (e.g., `/admin/locale/ko`)

Supported locales: `en`, `ko`, `zh`

---

## 18. Best Practices for Daily Operation

1. **Check the moderation queue daily** — Review and action open reports promptly.
2. **Monitor failed jobs** — Check the Handover Readiness page for failed background jobs.
3. **Update order statuses** — Keep order statuses current so customers see accurate information.
4. **Review B2B leads regularly** — Follow up on new leads within 1 business day.
5. **Back up settings** — Export settings configuration after any significant changes.
6. **Test email delivery** — After any mail server change, send a test email to verify delivery.
7. **Keep initial content current** — Review starter records and update them as production activity begins.
8. **Monitor storage** — Use the Media Storage Scan tool to identify orphaned or unused files.

---

*Related code: `B2C_backend/app/Filament/`, `B2C_backend/app/Models/`, `B2C_backend/routes/web.php`*
