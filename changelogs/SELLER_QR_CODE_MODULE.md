# Changelog: Seller QR Code & Digital Catalog Standee Module

**Date:** 2026-09-10  
**Project:** Eclassify Classified Platform Backend (Laravel 12)  
**Module:** Seller QR Code, Standee Generator & Location-Aware Catalog Engine  
**Version:** 3.2.0  
**Compatibility:** 100% Backward-compatible with existing Store, Item, Subscription, and Auth modules.

---

## 1. Executive Summary

This release introduces the **Seller QR Code & Digital Catalog Standee Feature**.
Verified sellers who subscribe to an active package with the QR feature enabled can now generate a unique, branded QR Code standee (inspired by Google Pay / UPI acrylic standees and counter posters).

### Key Highlights:
1. **Subscription Package Integration**:
   - Package entitlement toggle: `allows_seller_qr_code` added to `packages` table.
   - Admin can include this feature in *any* package tier: Ad Listing, Featured Ads, or Promotional Campaigns.
2. **Branded Standee & Poster Generator (PDF / SVG / PNG)**:
   - Export high-resolution print-ready standees in multiple page sizes: `standee` (148mm × 210mm UPI ratio), `a4` (210mm × 297mm poster), and `a5` (tabletop card).
   - Vector SVG and high-DPI PNG generation for designer printing and social media distribution.
3. **Admin Customization & Master Controls**:
   - Master feature toggle: `seller_qr_enabled`.
   - Seller permission controls: `seller_qr_allow_user_logo`, `seller_qr_allow_user_customization`.
   - Default standee texts (Title, Tagline, Footer text, Platform Logo, Brand Colors).
   - Live interactive standee mockup preview in admin settings.
4. **Location Priority & Discrepancy Warning Engine**:
   - Real-time geofence comparison between customer device GPS / selected city and store coordinates.
   - When browsing items from an out-of-area seller (beyond configurable threshold, e.g. 25 km), the system displays a clear, dismissible warning:
     > *"Notice: You are browsing items from [Store Name] located in [City, State] (approx. X km away). This seller is outside your current or selected area."*
   - Users are never blocked; they can freely view, filter, sort, search, and contact the seller.
5. **Universal Deep Linking**:
   - Encodes a universal canonical route: `{WEB_URL}/store-qr/{token}`.
   - If scanned via regular phone camera / browser: loads the responsive, mobile-first Web Store Catalog with an "Open in App" banner.
   - If opened with app installed or scanned in the Flutter app's built-in scanner: deep links to the dedicated `SellerStoreQRScreen`.

---

## 2. Database & Schema Changes

### 2.1 Added Columns to `packages` Table
- `allows_seller_qr_code` (`BOOLEAN`, default `false`, indexed)

### 2.2 New Table: `seller_qr_codes`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment | Unique record ID |
| `user_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id` cascade) | Seller / Store Owner ID |
| `store_id` | `BIGINT UNSIGNED` | Nullable, Foreign Key (`stores.id` cascade) | Associated Store ID |
| `qr_code_token` | `VARCHAR(64)` | Unique, Indexed | Cryptographic lookup token (`sqr_...`) |
| `title` | `VARCHAR(191)` | Nullable | Standee header heading |
| `tagline` | `TEXT` | Nullable | Standee subheading / subtext |
| `qr_style` | `VARCHAR(50)` | Default `'standee'` | Frame layout style |
| `primary_color` | `VARCHAR(10)` | Default `'#00B2CA'` | Primary brand color (HEX) |
| `secondary_color` | `VARCHAR(10)` | Default `'#0F172A'` | Accent / button color (HEX) |
| `center_logo_type` | `VARCHAR(50)` | Default `'store_logo'` | `store_logo`, `platform_logo`, `custom`, `none` |
| `center_logo` | `VARCHAR(191)` | Nullable | Path to custom uploaded center logo |
| `scans_count` | `BIGINT UNSIGNED` | Default `0` | Cumulative scan impressions |
| `last_scanned_at` | `TIMESTAMP` | Nullable | Timestamp of most recent scan |
| `is_active` | `BOOLEAN` | Default `true`, Indexed | Administrative active status |
| `metadata` | `JSON` | Nullable | Extensible metadata |
| `created_at` | `TIMESTAMP` | Nullable | Created timestamp |
| `updated_at` | `TIMESTAMP` | Nullable | Updated timestamp |
| `deleted_at` | `TIMESTAMP` | Nullable | Soft delete timestamp |

### 2.3 New Spatie Permissions
- `seller-qr-list`: Access seller QR codes listing in Admin panel
- `seller-qr-manage`: Activate, deactivate, preview, or download seller QR standees
- `seller-qr-setting`: Manage default templates, branding, and permissions

---

## 3. API Endpoints Reference

### 3.1 Authenticated Seller Endpoints (`auth:sanctum`)

#### `GET /api/seller-qr/eligibility`
Check if authenticated seller has an active subscription package granting QR code access.

**Response (Eligible):**
```json
{
    "error": false,
    "message": "Eligibility status fetched successfully",
    "data": {
        "eligible": true,
        "has_package": true,
        "package": {
            "id": 4,
            "name": "Gold Pro Merchant",
            "type": "item_listing",
            "allows_seller_qr_code": true
        },
        "has_store": true,
        "store": {
            "id": 12,
            "name": "Apex Electronics & Gadgets",
            "slug": "apex-electronics",
            "is_verified": true,
            "logo": "https://domain.com/storage/store_logos/logo.png"
        },
        "can_customize_logo": true,
        "can_customize_colors": true,
        "message": "User is eligible for Seller QR Code feature."
    }
}
```

---

#### `GET /api/seller-qr/my-qr`
Retrieve authenticated seller's configured QR code, deep links, preview data URI, and scan analytics.

**Response:**
```json
{
    "error": false,
    "message": "Seller QR code details fetched successfully",
    "data": {
        "has_qr": true,
        "eligible": true,
        "qr_code": {
            "id": 7,
            "token": "sqr_8fa93c21b0e4d76a",
            "title": "Apex Electronics & Gadgets",
            "tagline": "Explore all verified ads, items and exclusive offers",
            "qr_style": "standee",
            "primary_color": "#00B2CA",
            "secondary_color": "#0F172A",
            "center_logo_type": "store_logo",
            "center_logo_url": "https://domain.com/storage/store_logos/logo.png",
            "qr_url": "https://domain.com/store-qr/sqr_8fa93c21b0e4d76a",
            "deep_link": "eclassify://store-qr/sqr_8fa93c21b0e4d76a",
            "scans_count": 142,
            "last_scanned_at": "2026-09-10T17:45:00.000000Z",
            "is_active": true,
            "qr_base64_svg": "data:image/svg+xml;base64,..."
        },
        "download_links": {
            "pdf_standee": "https://domain.com/api/seller-qr/download?token=sqr_8fa93c21b0e4d76a&format=pdf&size=standee",
            "pdf_a4": "https://domain.com/api/seller-qr/download?token=sqr_8fa93c21b0e4d76a&format=pdf&size=a4",
            "pdf_a5": "https://domain.com/api/seller-qr/download?token=sqr_8fa93c21b0e4d76a&format=pdf&size=a5",
            "svg": "https://domain.com/api/seller-qr/download?token=sqr_8fa93c21b0e4d76a&format=svg",
            "png": "https://domain.com/api/seller-qr/download?token=sqr_8fa93c21b0e4d76a&format=png"
        }
    }
}
```

---

#### `POST /api/seller-qr/generate-or-update`
Generate or update the seller's custom standee configuration.

**Parameters (multipart/form-data):**
- `title` (string, optional) - Custom heading on the standee
- `tagline` (string, optional) - Subtext on the standee
- `qr_style` (string, optional) - `'standee'`, `'standard'`, `'compact'`, `'card'`
- `primary_color` (string, optional) - Hex color e.g. `'#00B2CA'`
- `secondary_color` (string, optional) - Hex color e.g. `'#0F172A'`
- `center_logo_type` (string, optional) - `'store_logo'`, `'platform_logo'`, `'custom'`, `'none'`
- `center_logo` (file, optional, image max: 3MB) - Custom center logo file

---

### 3.2 Public Endpoints (App & Web Frontend)

#### `GET /api/seller-qr/store/{identifier}`
Target endpoint when any user scans a Seller QR Code.  
`identifier` can be either `qr_code_token` (e.g. `sqr_...`) or store `slug`.

**Query Parameters:**
- `latitude` (float, optional) - Device GPS latitude
- `longitude` (float, optional) - Device GPS longitude
- `city` (string, optional) - Customer's selected city
- `state` (string, optional) - Customer's selected state
- `search` (string, optional) - Search keyword in store ads
- `category_id` (int, optional) - Filter by category
- `sort_by` (string, optional) - `'newest'`, `'price_asc'`, `'price_desc'`, `'popular'`, `'in_offer'`
- `page` (int, optional) - Page number (default: 1)
- `limit` (int, optional) - Items per page (default: 12)

**Response:**
```json
{
    "error": false,
    "message": "Store QR catalog fetched successfully",
    "data": {
        "store": {
            "id": 12,
            "name": "Apex Electronics & Gadgets",
            "slug": "apex-electronics",
            "logo": "https://domain.com/storage/store_logos/logo.png",
            "banner": "https://domain.com/storage/store_banners/banner.png",
            "is_verified": true,
            "city": "Mumbai",
            "state": "Maharashtra",
            "contact": "9876543210",
            "country_code": "+91"
        },
        "location_warning": {
            "is_matched": false,
            "warning": true,
            "message": "Notice: You are browsing items from Apex Electronics located in Mumbai, Maharashtra (approx. 45.2 km away). This seller is outside your current or selected area.",
            "distance_km": 45.2,
            "distance": {
                "meters": 45200,
                "kilometers": 45.2,
                "formatted": "45.2 km"
            },
            "threshold_km": 25.0,
            "store_location": {
                "name": "Apex Electronics & Gadgets",
                "address": "Colaba Causeway, Mumbai",
                "city": "Mumbai",
                "state": "Maharashtra",
                "country": "India"
            }
        },
        "categories": [
            { "id": 1, "name": "Mobiles", "slug": "mobiles" },
            { "id": 3, "name": "Laptops", "slug": "laptops" }
        ],
        "items": {
            "total": 24,
            "current_page": 1,
            "per_page": 12,
            "last_page": 2,
            "data": [ ... ]
        }
    }
}
```

---

#### `GET /api/seller-qr/download`
Direct download endpoint for standee PDF, PNG, or SVG.

**Query Parameters:**
- `token` (string, required if not authenticated)
- `format` (string, `'pdf'` | `'png'` | `'svg'`)
- `size` (string, `'standee'` | `'a4'` | `'a5'`)

---

## 5. Updates & Bug Fixes (v3.2.1)

### 5.1 Embedded Default Platform Logo into QR Code
- Added `seller_qr_default_center_logo_type` to `DefaultSettingService` and `SellerQrSettingController` with choices (`platform_logo`, `store_logo`, `none`), defaulting to `platform_logo`.
- When sellers configure or generate their standee, the center logo automatically embeds the platform brand logo or custom choice by default.
- Added radio controls in admin panel `seller_qr/settings.blade.php` to customize this setting.

### 5.2 Admin Modal Scroll & Action Button Fix
- In `resources/views/stores/index.blade.php`, resolved Bootstrap 5 flex height clipping on `#createStoreModal` and `#editStoreModal` by setting `max-height: calc(100vh - 210px); overflow-y: auto;` on `.modal-body`. Submit and Cancel buttons are now permanently visible on all screen sizes.

### 5.3 Live Standee Mockup Contrast Fix
- In `resources/views/seller_qr/settings.blade.php`, enhanced the live standee mockup with explicit dark text styling (`color: #0f172a !important;`), a dedicated storefront badge icon, and an embedded center logo mockup for "Example Store Name".

### 5.4 Cross-Platform Eligibility & Payload Harmonization
- In `SellerQrApiController.php`, returned both `eligible` and `is_eligible` keys, along with `allows_seller_qr_code`, `svg_raw`, `format`, `size`, and store address metadata to guarantee compatibility across web and mobile app clients.

---

## 6. Updates & Bug Fixes (v3.2.2)

### 6.1 Fixed Undefined Variable `$request` in `catalog_view.blade.php:593`
- **Modified Files**:
  - `app/Http/Controllers/SellerQrWebController.php`
  - `resources/views/seller_qr/catalog_view.blade.php`
- **Changes**:
  - Added `'request' => $request` to `compact(...)` in `SellerQrWebController::handleQrVisit()`.
  - Replaced `@if(!$request->has('lat') && !$request->has('lng'))` with `@if(!request()->has('lat') && !request()->has('lng'))` in `catalog_view.blade.php`.
  - Enhanced top app deep-link banner to display app logo and name dynamically.
  - Enhanced catalog footer to render platform footer logo and customizable footer branding text.

### 6.2 Standee & QR Code Download Resilience (PNG, SVG, PDF)
- **Modified Files**:
  - `app/Services/SellerQrCodeService.php`
  - `app/Http/Controllers/Api/SellerQrApiController.php`
- **Changes**:
  - Added `generateQrPngWithGd()` to `SellerQrCodeService.php`, providing a pure GD rasterizer with center logo overlay that does not depend on the PHP `imagick` extension.
  - In `downloadStandee()`, added support for `token`, `qr_code_token`, `store_slug`, `slug`, `store_id`, `user_id`, or session/sanctum authenticated sellers.
  - Resolved `platform_logo` center logo path for PNG and SVG exports.
  - Invoked `ob_end_clean()` prior to file output to prevent dirty buffer truncation of binary streams.
  - Added `Access-Control-Allow-Origin: *` and `Cache-Control: no-cache, private` download headers.

### 6.3 Admin Panel Master Branding Controls & Standee Consistency
- **Modified Files**:
  - `app/Services/DefaultSettingService.php`
  - `app/Services/SellerQrCodeService.php`
  - `app/Http/Controllers/SellerQrSettingController.php`
  - `resources/views/seller_qr/settings.blade.php`
  - `resources/views/seller_qr/standee_template.blade.php`
- **Changes**:
  - Added `seller_qr_badge_text` (e.g. "DIGITAL STORE & CATALOG") and `seller_qr_catalog_banner_text` to default settings and admin form.
  - Unified standee hierarchy across admin mockup, blade PDF template, web mockup, and mobile preview.
  - Added real-time JS preview binding for `#seller_qr_badge_text`.
  - Guaranteed `storage_path('app/temp')` creation prior to Mpdf initialization.

---

## 7. Updates & Bug Fixes (v3.2.3)

### 7.1 Fixed Catalog Items List Not Displaying ("1 Item in Catalog" Issue)
- **Problem**: `getStoreByQr()` returned paginated items formatted with `(new ItemApiResource(...))->asSingle()`. In `ItemApiResource.php`, calling `asSingle()` returns `Arr::first($data)` (a single item object/map) instead of an array/list of items. As a result, `items.data` in the API JSON was `{ id: ..., name: ... }` rather than an array `[ { id: ..., name: ... } ]`.
  - On the Next.js website: `const items = data?.items?.data || [];` resulted in `items` being an object, so `items.length` was `undefined`, causing `items.length > 0` to evaluate to `false` and rendering the `<NoData />` placeholder despite the header correctly showing `totalItems = 1`.
  - In the Flutter app: `rawItems = itemsMap['data'] as List? ?? [];` failed type casting on the map and defaulted to an empty list `[]`.
- **Solution**:
  - In `SellerQrApiController.php:getStoreByQr()`, removed `->asSingle()` and replaced it with `->toArray($request)`.
  - Mapped items so all items have `name`, `address`, `translation`, `image`, `formatted_price`, and `slug` reliably set at root level.
  - Also fixed `StoreApiController.php:getStoreDetail()` where `asSingle()` was similarly misused on paginated store items.

### 7.2 Fixed QR Code Not Displaying & Auto-Generation on First Access
- **Problem**: When eligible sellers opened `/seller-qr`, if they had not manually saved yet, `getMyQr()` returned `'qr_code' => null`, leaving the preview empty and download buttons disabled. When a QR code existed, `svg_raw` was returned as a Base64 Data URI string, which rendered invisibly when passed to React's `dangerouslySetInnerHTML`.
- **Solution**:
  - In `SellerQrApiController.php:getMyQr()`, if the user has an active store and is eligible, the controller now automatically creates a default `SellerQrCode` record on the fly so a valid token, deep link, and preview are immediately available.
  - Returned both `svg_raw` (raw `<svg>...</svg>` XML string), `raw_svg`, `qr_base64_svg` (Data URI), and direct preview image URLs `qr_png_url` and `qr_svg_url`.
  - Updated `generateOrUpdate()` to also return `svg_raw`, `raw_svg`, and `qr_base64_svg` immediately upon saving.

### 7.3 Standee Download Route & Header Reliability
- **Problem**: Redundant route registration inside the `auth:sanctum` middleware group in `routes/api.php` caused unauthenticated browser anchor clicks to fail.
- **Solution**:
  - Removed redundant `download` route under `auth:sanctum` group in `routes/api.php`, maintaining the public `seller-qr/download` route with support for tokens, slugs, and seller authentication.
  - Added `Access-Control-Expose-Headers: Content-Disposition, Content-Type` to download responses.
  - Added `if (ob_get_level()) { ob_end_clean(); }` to `SellerQrSettingController.php:downloadStandee()` in the admin panel.

---

## 8. Updates & Improvements (v3.2.4)

### 8.1 Resolution of PDF Download Failure (`TypeError: Failed to fetch`)
- **Root Cause**: During PDF generation via Mpdf, store logos and footer logos with absolute HTTP URLs (e.g. `http://127.0.0.1:8000/...`) triggered Mpdf's internal HTTP file downloader over loopback. In single-threaded PHP execution (`php artisan serve`), this caused a deadlock where the server waited on its own pending connection, timing out and dropping the client connection.
- **Solution**:
  - Implemented `SellerQrCodeService::imageToBase64Uri(?string $imagePath)` to convert local images, relative assets, and storage files into inline Base64 data URIs (`data:image/png;base64,...`).
  - Updated `SellerQrCodeService::renderStandeeHtml()` to embed all logos directly as Base64 data URIs, completely eliminating outbound HTTP loopback calls. Standee PDF generation now executes entirely offline in ~0.3s.

### 8.2 SEO-Friendly QR Slug Architecture & Seller Customization
- **Slug Generation**: Updated `SellerQrCodeService::generateQrToken()` to generate human-readable, SEO-optimized URL slugs from store slugs or user names (e.g. `progoti-labs`, `progoti-labs-2`) instead of random opaque strings (`sqr_...`).
- **Custom Slug Configuration**: In `SellerQrApiController::generateOrUpdate()`, sellers can now provide and update their own custom slug (`custom_slug`), validated with regex and unique across stores.
- **Universal Lookup**: `SellerQrApiController::getStoreByQr()` now resolves stores seamlessly by QR token, custom slug, store slug, or store ID with backward compatibility.

### 8.3 Admin Configurable Store Catalog Base URL
- Added `seller_qr_catalog_base_url` setting in `DefaultSettingService.php`, `SellerQrSettingController.php`, and `resources/views/seller_qr/settings.blade.php`.
- In `SellerQrCode::getQrUrlAttribute()`, the custom base URL is dynamically respected (e.g., `https://myclassified.com/store-qr/{slug}`) with graceful fallback to `web_url` or root URL.

### 8.4 Dedicated Admin QR Center Logo Upload & Preview
- Added `seller_qr_center_logo` file upload in admin settings with live thumbnail preview and validation.
- QR code center embedding automatically uses the dedicated admin center logo when `seller_qr_default_center_logo_type` is set to `platform_logo`.

### 8.5 Admin Permission Synchronization to Web & Mobile
- Returned `can_customize_colors`, `can_customize_logo`, `can_customize_slug`, and `catalog_base_url` in `SellerQrApiController::getMyQr()`.
- Enforced admin restrictions server-side in `generateOrUpdate()`.

---

## 9. Updates & Bug Fixes (v3.2.5)

### 9.1 Missing `Illuminate\Support\Str` Class Import in `SellerQrApiController`
- **Problem**: When saving standee configuration (`/api/seller-qr/generate`), the API failed with:
  `Class "App\Http\Controllers\Api\Str" not found At Line : 359`
- **Root Cause**: `Str::before(...)` and `Str::slug(...)` were invoked without importing `use Illuminate\Support\Str;` in the file namespace.
- **Solution**:
  - Added `use Illuminate\Support\Str;` to imports in `SellerQrApiController.php`.

### 9.2 Admin Panel Center Logo Preview Reactivity & "No Embedded Logo" Fix
- **Problem**: In `seller_qr/settings.blade.php`, toggling "No Embedded Logo" (`none`) did not update or hide the center logo badge in the live standee mockup preview.
- **Solution**:
  - Added dynamic inline style `display: {{ ($settings['default_center_logo_type'] ?? 'platform_logo') === 'none' ? 'none' : 'flex' }};` to `#mockCenterLogo`.
  - Added live jQuery change handlers for `input[name="seller_qr_default_center_logo_type"]` and file input `#seller_qr_center_logo` (via `FileReader`) to instantaneously reflect selection in the preview mockup.

### 9.3 Unified Center Logo Resolution & Clean SVG Downloads
- **Problem**: Redundant SVG handler block and inconsistent center logo fallback between SVG, PNG, and PDF formats.
- **Solution**:
  - Unified center logo resolution across SVG, PNG, and PDF formats:
    - If `center_logo_type === 'none'`, strictly passes `null` to generate clean QR codes without logos.
    - If `center_logo_type === 'platform_logo'`, prioritizes admin center logo with fallback to footer logo.
  - Resolved undefined `$settings` variable in `SellerQrApiController::getStoreByQr()`.

---

## 10. Updates & Bug Fixes (v3.2.6)

### 10.1 Embedded Logo Integration in SVG and PDF Standee Exports
- **Problem**: In PDF and SVG export of the QR code standee, the embedded center logo was visible only in PNG export, but missing in SVG and PDF exports.
- **Root Cause**:
  - `simple-qrcode`'s `merge()` method internally only executes image merging when `format === 'png'` (`vendor/simplesoftwareio/simple-qrcode/src/Generator.php`). When generating SVG, `merge()` is completely ignored by the library.
  - As a result, `generateRawQrSvg()` produced an SVG with no center logo.
  - In turn, `renderStandeeHtml()` injected this raw SVG into the mPDF standee template via `$qrBase64`, causing the PDF export to also lack the embedded logo.
- **Solution**:
  - In `SellerQrCodeService::generateRawQrSvg()`, injected an SVG `<g id="qrCenterLogoBadge">` element right before `</svg>` containing:
    - A circular white background badge: `<circle cx="..." cy="..." r="..." fill="#ffffff" stroke="$primaryColor" stroke-width="2.5" />`
    - A base64-encoded vector `<image href="..." xlink:href="..." preserveAspectRatio="xMidYMid meet" />`
  - This natively renders the center logo in SVG exports, PDF standee documents, and Flutter's `SvgPicture.string()` without any image quality degradation.

### 10.2 Circular Badge Design Uniformity across PNG, SVG, and Previews
- **Problem**: PNG export generated a flat rectangular center box with GD, while SVG and web/admin preview mockups featured circular badges.
- **Solution**:
  - Updated `generateQrPngWithGd()` to draw an antialiased circular white badge (`imagefilledellipse`) with a primary-color border (`imageellipse`) matching the SVG badge dimensions and live previews.
  - Routed `generateRawQrPng()` directly through `generateQrPngWithGd()` to guarantee uniform look across all server environments regardless of whether Imagick is installed.

### 10.3 Standee PDF Layout & Visual Alignment with Previews
- **Problem**: In `standee_template.blade.php`, `.brand-badge` had white text on near-transparent background (`color: #ffffff; background: {{ $primaryColor }}15;`), making it invisible in PDFs. Also, the store card was missing the store logo/avatar shown in the Admin, Web, and Mobile live previews.
- **Solution**:
  - Fixed `.brand-badge` text color to `color: {{ $primaryColor }};`.
  - Updated the store card inside `standee_template.blade.php` to include the store logo avatar on the left in a neat mPDF-compatible table layout matching the live preview mockup across Admin Panel, Web Frontend, and Flutter Mobile App.

### 10.4 Centralized Center Logo Resolution Helper
- **Solution**:
  - Added `SellerQrCodeService::resolveCenterLogoPath(SellerQrCode $qrCode, ?array $settings = null, ?Store $store = null): ?string`.
  - Replaced duplicate resolution logic across `SellerQrApiController::getMyQr()`, `SellerQrApiController::downloadStandee()`, and `SellerQrCodeService::renderStandeeHtml()`.
