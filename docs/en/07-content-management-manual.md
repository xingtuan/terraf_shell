# 07 — Content Management Manual

## Overview

This manual covers all content managed through the OXP admin panel, including the material library, articles, homepage sections, legal pages, and footer content. All content supports multilingual editing in English, Korean, and Simplified Chinese.

---

## 1. Material Library

### 1.1 Purpose

The material library showcases the brand's flagship sustainable materials. It is the scientific and narrative core of the website, presenting each material's specifications, story, applications, and certifications.

**Frontend location**: `/material`

### 1.2 Material Fields

**Location**: Admin Panel → CMS → Materials

| Field | Type | Multilingual | Description |
|---|---|---|---|
| `name` | Text | Yes | Material name |
| `description` | Rich text | Yes | Main material description |
| `story_cover_image` | Image | No | Cover image for the material story section |
| `internal initial-content marker` | Boolean | No | Marks initial/seeder content |
| `seeded_at` | Timestamp | No | When initial content was seeded |
| Certification fields | Text | Yes | Quality and compliance certifications |

### 1.3 Material Specs

**Location**: Admin Panel → CMS → Material Specs

Technical specification tables for each material. Each spec has:
- Parent material reference
- Spec name / label (multilingual)
- Spec value (multilingual)
- Sort order

### 1.4 Material Story Sections

**Location**: Admin Panel → CMS → Story Sections

Narrative sections that tell the material's story. Each section has:
- Parent material reference
- Section heading (multilingual)
- Section content (multilingual, rich text)
- Section image
- Sort order

### 1.5 Material Applications

**Location**: Admin Panel → CMS → Applications

Use case showcases for each material:
- Parent material reference
- Application title (multilingual)
- Application description (multilingual)
- Application image
- Sort order

*Related code: `app/Models/Material.php`, `app/Models/MaterialSpec.php`, `app/Models/MaterialStorySection.php`, `app/Models/MaterialApplication.php`*

---

## 2. Articles

### 2.1 Purpose

Articles form the platform's knowledge base and blog. They are created by administrators and cover topics relevant to the brand's materials, sustainability, and industry.

**Frontend location**: `/articles`

### 2.2 Article Fields

**Location**: Admin Panel → CMS → Articles

| Field | Type | Multilingual | Description |
|---|---|---|---|
| `title` | Text | Yes | Article headline |
| `slug` | Text | No | URL identifier (auto-generated from title) |
| `cover_image` | Image | No | Header image for article list and detail |
| `content_json` | Rich text (JSON) | No | Full article content in Tiptap JSON format |
| `excerpt` | Text | Yes | Short summary shown in article lists |
| `reading_time` | Integer | No | Estimated reading time in minutes |
| `status` | Enum | No | Published or Draft |
| `published_at` | Timestamp | No | When the article became public |

### 2.3 Creating an Article

1. Go to **CMS → Articles → New Article**.
2. Enter the **title** in all three languages (EN, KO, ZH).
3. Upload a **cover image**.
4. Write the article content using the rich text editor.
5. Add a brief **excerpt** for each language.
6. Set the **status** to Published when ready.
7. Click **Save**.

### 2.4 Publishing and Unpublishing

- Change the status to **Published** to make the article visible on the website.
- Change the status to **Draft** to hide it from public view.
- The `published_at` timestamp is set automatically when first published.

*Related code: `app/Models/Article.php`, `app/Filament/Resources/ArticleResource.php`*

---

## 3. Homepage Sections

### 3.1 Purpose

The homepage is built from configurable sections managed in the admin panel. This allows the homepage layout and content to be updated without code changes.

**Frontend location**: `/` (homepage)

### 3.2 Homepage Section Fields

**Location**: Admin Panel → CMS → Homepage Sections

| Field | Type | Multilingual | Description |
|---|---|---|---|
| `section_type` | Text | No | Identifies which frontend component renders this section |
| `title` | Text | Yes | Section headline |
| `subtitle` | Text | Yes | Section supporting text |
| `content_json` | JSON | No | Structured section content (layout-dependent) |
| `background_image` | Image | No | Section background image |
| `sort_order` | Integer | No | Controls display order on the page |
| `is_published` | Boolean | No | Whether this section appears on the homepage |

### 3.3 Managing Homepage Content

1. Go to **CMS → Homepage Sections**.
2. Click any section to edit it.
3. Update the title, subtitle, images, or content as needed.
4. Adjust `sort_order` to reorder sections on the page.
5. Toggle `is_published` to show or hide individual sections.
6. Save changes.

> **Note**: The frontend maps `section_type` values to specific React components. Changing the `section_type` of an existing section may cause display issues. Consult the development team before changing section types.

*Related code: `app/Models/HomeSection.php`, `app/Filament/Resources/HomeSectionResource.php`*

---

## 4. Legal Pages

### 4.1 Purpose

The Privacy Policy and Terms of Service pages are managed directly from the admin panel and served dynamically via the API.

**Frontend locations**: `/privacy`, `/terms`

### 4.2 Editing Legal Pages

**Location**: Admin Panel → System → Legal Page Settings

The legal page editor provides:
- Separate editors for **Privacy Policy** and **Terms of Service**
- Content editable in **English**, **Korean**, and **Simplified Chinese**
- Rich text editing support
- A **"Last Updated" date** field for each page

### 4.3 Recommended Process

1. Have the legal text reviewed by the appropriate legal counsel.
2. Enter the approved text in all three languages.
3. Set the **"Last Updated"** date to the current date.
4. Save — changes appear on the website immediately.

> **Important**: Do not use placeholder or draft legal text in production. Publish only legally reviewed content.

*Related code: `app/Filament/Pages/LegalPageSettings.php`, `app/Http/Controllers/Api/LegalPageController.php`*

---

## 5. Footer Content

The footer contains navigation links and contact information. In the current implementation, footer links are managed through the homepage sections system and/or hardcoded in the frontend footer component.

**Frontend component**: `B2C_frontend/components/footer.tsx`

Contact the development team to update footer links or content.

---

## 6. Multilingual Content Editing

All content-heavy fields support three languages: English (`en`), Korean (`ko`), and Simplified Chinese (`zh`).

### 6.1 In the Admin Panel

When editing a product, material, article, or homepage section, the admin panel displays separate input fields for each language:
- `name_en`, `name_ko`, `name_zh`
- `description_en`, `description_ko`, `description_zh`
- etc.

Fill in all three language versions to ensure a complete multilingual experience.

### 6.2 Fallback Behavior

If a translation is missing for the current visitor's language, the system falls back to the **English version** of the content.

### 6.3 Frontend Translation Files

UI strings (button labels, error messages, section headings) are stored in:
- `B2C_frontend/messages/en.json`
- `B2C_frontend/messages/ko.json`
- `B2C_frontend/messages/zh.json`

These files require a developer to update and a new frontend build to deploy.

---

## 7. Images and Media

### 7.1 Uploading Images in Admin Panel

Images are uploaded directly in the relevant admin resource (product, material, article, homepage section). When you click an image upload field:
1. Select the image file from your computer.
2. The image is uploaded to Azure Blob Storage (or local disk if in development).
3. A URL or storage path is saved with the record.

**Supported formats**: JPG, JPEG, PNG, WebP, GIF

### 7.2 Product Images

**Location**: Store → Product Images

Product images are managed separately:
- Upload one or more images per product
- Set the `sort_order` to control display sequence
- Mark one image as **Primary** to be the main display image

### 7.3 Media Storage Management

**Location**: Admin Panel → System → Media Storage Scan

This tool scans storage and identifies:
- Files in storage that are not referenced by any record (orphaned files)
- Records that reference missing files

Use this periodically to keep storage clean.

---

## 8. SEO Fields

Products and articles include SEO metadata fields:
- `seo_title` — used as the browser tab title and `<title>` tag
- `seo_description` — used as the `<meta name="description">` tag

These are multilingual and should be filled for each language to optimize search engine visibility in different markets.

---

## 9. Initial Content

Initial content is seeded during setup with `php artisan db:seed`. It is part of the delivered system and should be managed through the standard content resources.

Review seeded articles, materials, homepage sections, and store content as normal production content. Edit, archive, or replace individual records only when required.

---

## 10. Content Management Best Practices

1. **Always fill all three language versions** of multilingual fields before publishing.
2. **Use descriptive slugs** that are meaningful in English (slugs are shared across languages).
3. **Optimize images** before upload — keep file sizes reasonable (under 2 MB per image when possible).
4. **Review legal pages** with legal counsel before publishing.
5. **Test homepage sections** after changes to ensure they render correctly.
6. **Archive rather than delete** articles and posts that are no longer relevant — deletion is permanent.
7. **Back up settings** after major content structure changes using the Settings Backup tool.

---

*Related code: `B2C_backend/app/Filament/Resources/`, `B2C_backend/app/Models/`, `B2C_frontend/components/sections/`*
