# Integration Guide & Changelog: Standalone Photo Editor & Rich Text Engine with Indian Mobile Number & URL Auto-Formatting

**Project Name:** Eclassify Classified Platform Backend (Laravel 12)  
**Working Directory:** `c:\Users\nilan\Downloads\Eclassify\eclassify-backend`  
**Date:** 2026-09-17  
**Module:** Reusable Photo Editor Plugin, Intervention Image Service, Rich Text Sanitizer & Smart Indian Contact/URL Linkifier  
**Version:** 3.3.0  
**Compatibility:** 100% Backward-compatible with existing Store, Item, Gallery, and API modules.

---

## Modified & Created Files Summary

| # | Action | Relative File Path | Absolute File Path |
|---|---|---|---|
| 1 | **CREATE** | `database/migrations/2026_09_17_000001_create_edited_images_table.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\database\migrations\2026_09_17_000001_create_edited_images_table.php` |
| 2 | **CREATE** | `database/migrations/2026_09_17_000002_add_description_json_to_items_table.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\database\migrations\2026_09_17_000002_add_description_json_to_items_table.php` |
| 3 | **CREATE** | `app/Models/EditedImage.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Models\EditedImage.php` |
| 4 | **MODIFY** | `app/Models/Item.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Models\Item.php` |
| 5 | **CREATE** | `app/Services/ContentFormatterService.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Services\ContentFormatterService.php` |
| 6 | **CREATE** | `app/Services/ImageEditorService.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Services\ImageEditorService.php` |
| 7 | **CREATE** | `app/Http/Controllers/Api/ImageEditorApiController.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Http\Controllers\Api\ImageEditorApiController.php` |
| 8 | **MODIFY** | `app/Http/Controllers/Api/ItemApiController.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Http\Controllers\Api\ItemApiController.php` |
| 9 | **MODIFY** | `app/Http/Controllers/ItemController.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Http\Controllers\ItemController.php` |
| 10 | **MODIFY** | `app/Http/Resources/ItemApiResource.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\app\Http\Resources\ItemApiResource.php` |
| 11 | **MODIFY** | `routes/api.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\routes\api.php` |
| 12 | **CREATE** | `public/assets/js/custom/photo-editor.js` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\public\assets\js\custom\photo-editor.js` |
| 13 | **CREATE** | `public/assets/css/custom/photo-editor.css` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\public\assets\css\custom\photo-editor.css` |
| 14 | **CREATE** | `resources/views/components/photo-editor.blade.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\resources\views\components\photo-editor.blade.php` |
| 15 | **MODIFY** | `resources/views/items/create.blade.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\resources\views\items\create.blade.php` |
| 16 | **MODIFY** | `resources/views/items/update.blade.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\resources\views\items\update.blade.php` |
| 17 | **MODIFY** | `resources/views/items/index.blade.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\resources\views\items\index.blade.php` |
| 18 | **MODIFY** | `resources/views/layouts/footer_script.blade.php` | `c:\Users\nilan\Downloads\Eclassify\eclassify-backend\resources\views\layouts\footer_script.blade.php` |

---

## 1. Executive Summary

This release introduces native **Image Editing During Upload/Capture** and **Rich Text Formatting for Item Descriptions** across the Eclassify ecosystem (Admin Panel, Web Frontend in Next.js, and Mobile App in Flutter).

### Key Highlights:
1. **Reusable Standalone Photo Editor Architecture**:
   - Zero-dependency client-side HTML5 canvas photo editor plugin (`PhotoEditor`) supporting crop (Free, 1:1, 4:3, 16:9), rotate, flip, filters (Grayscale, Sepia, Warm, Cool, Vintage, Invert), adjustments (Brightness, Contrast, Saturation), text overlays with custom styles/colors/backgrounds, and pre-styled badge stickers (SALE, HOT, NEW, VERIFIED, etc.).
   - Reusable Blade component `<x-photo-editor />` / `@include('components.photo-editor')` embedded into the Admin Panel layouts.
   - Built-in integration with Item creation and edit forms (`items/create.blade.php`, `items/update.blade.php`), enabling admins and users to edit any uploaded or existing photo thumbnail seamlessly before submission.
2. **Enterprise Backend Image Processing Engine (`ImageEditorService`)**:
   - Powered by `Intervention\Image` in Laravel 12.
   - Supports pipeline transformations: Crop, Resize, Rotate, Flip, Brightness, Contrast, Preset Filters, Text Overlays with customizable font size, text color, background highlight box, and sticker badges.
   - Automatic web-ready compression, optimization via `OptimizerChainFactory`, and watermark application.
   - Full audit tracking with `edited_images` database table featuring **SoftDeletes** and DB Transactions.
3. **Rich Text Formatting & Auto-Link Engine (`ContentFormatterService`)**:
   - **XSS Prevention**: Automatically cleanses dangerous tags (`<script>`, `<iframe>`, `javascript:`, inline events) while preserving rich text markup (`<p>`, `<b>`, `<strong>`, `<i>`, lists, headings).
   - **Smart Indian Mobile Number Auto-Linkifier**: Automatically detects 10-digit Indian phone numbers (starting with 6, 7, 8, 9 with optional `+91` or `0` prefix and separators), beautifies them into standard Indian format (`+91 XXXXX XXXXX`), and turns them into clickable `tel:+91...` and WhatsApp action links.
   - **URL Auto-Linkifier**: Automatically detects unlinked web URLs (`http`, `https`, `www.`) and converts them into secure clickable links (`target="_blank" rel="noopener noreferrer nofollow"`).
   - **Structured Metadata Extraction**: Exposes `extractIndianMobileNumbers()` and `extractUrls()` for structured API consumption.
4. **Item Model & API Enhancements**:
   - `Item` model accessors: `formatted_description`, `extracted_contacts`, and `extracted_links`.
   - `ItemApiResource` automatically delivers `formatted_description`, `extracted_contacts`, and `extracted_links` for both single item details and list item translations.
   - REST API endpoints for image editing (`/api/v1/image/edit`, `/api/v1/image/preview`, `/api/v1/image/history`) and text formatting (`/api/v1/format-content`).

---

## 2. Database & Schema Changes

### 2.1 New Table: `edited_images`
Migration: `database/migrations/2026_09_17_000001_create_edited_images_table.php`

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment | Unique record ID |
| `user_id` | `BIGINT UNSIGNED` | Nullable, Indexed, Foreign Key (`users.id` cascade) | User who edited the image |
| `item_id` | `BIGINT UNSIGNED` | Nullable, Indexed, Foreign Key (`items.id` cascade) | Associated advertisement / product |
| `original_path` | `VARCHAR(2048)` | Not Null | Path to original source image |
| `edited_path` | `VARCHAR(2048)` | Not Null | Path to processed/edited image |
| `transformations` | `JSON` | Nullable | Parameters used (crop, rotate, filters, text) |
| `disk` | `VARCHAR(64)` | Default `'public'` | Storage filesystem disk |
| `file_size` | `BIGINT UNSIGNED` | Nullable | File size in bytes |
| `mime_type` | `VARCHAR(128)` | Nullable | Mime type (e.g. `image/jpeg`) |
| `created_at` | `TIMESTAMP` | Nullable | Timestamp created |
| `updated_at` | `TIMESTAMP` | Nullable | Timestamp updated |
| `deleted_at` | `TIMESTAMP` | Nullable | Timestamp soft deleted |

---

## 3. Backend Architecture & Components

### 3.1 `App\Services\ContentFormatterService`
Handles string and HTML transformation:
- `formatItemDescription(?string $content): string`
- `makeIndianMobileNumbersClickable(string $html): string`
- `makeUrlsClickable(string $html): string`
- `extractIndianMobileNumbers(?string $content): array`
- `extractUrls(?string $content): array`
- `sanitizeHtml(string $html): string`

### 3.2 `App\Services\ImageEditorService`
Central processing engine using `Intervention\Image`:
- `processAndSave($sourceFileOrPath, array $operations, string $folder, ?int $userId, ?int $itemId, bool $addWatermark): array`
- `applyTransformations($image, array $operations)`
- `applyWatermark($image)`

### 3.3 `App\Http\Controllers\Api\ImageEditorApiController`
API controller handling image editing, preview generation, and text formatting:
- `POST /api/image/edit`: Transform and persist image edits with DB transaction.
- `POST /api/image/preview`: Transient preview generation.
- `POST /api/format-content`: Test and format raw content with URL and phone parsing.
- `GET /api/image/history`: Fetch user's editing audit history.
- `DELETE /api/image/{id}`: Soft delete an edited image record.

---

## 4. Admin Panel & Rich Text Enhancements

1. **Quill Rich Text Editor Integration (`items/create.blade.php`, `items/update.blade.php`)**:
   - Replaced TinyMCE with Quill Rich Text Editor (`assets/extensions/quill/quill.js` and `quill.snow.css`).
   - Integrated full text color picker and background highlight color picker palettes directly into the Quill toolbar.
   - Built-in custom image uploader that uploads images via `POST /api/editor/upload-image` and embeds the uploaded URL cleanly.
   - Seamless two-way integration with Gemini AI description generator and multi-language auto-translate tabs.
   - Dual-format data persistence on form submission:
     - `description`: Clean, unformatted plain-text without any `<p>` or `<strong>` HTML tags (safe for mobile list views, plain text tables, and SEO snippets).
     - `description_json`: Full Quill Delta JSON preserving rich formatting, headers, lists, colors, background highlights, and embedded images.
     - Fallback mechanism: If `description_json` is null or empty, admin views, website frontend, and mobile apps seamlessly fall back to `description`.

2. **Interactive Canvas Photo Editor (WhatsApp / Canva Style Repositionable Overlays)**:
   - File: `public/assets/js/custom/photo-editor.js` & `public/assets/css/custom/photo-editor.css`.
   - Text overlays and badge stickers are non-destructive, draggable DOM overlay items inside `#pe-overlay-layer`.
   - Overlays use relative normalized coordinates `(xRatio, yRatio)` so resizing, rotating, or scaling the viewport maintains exact relative placement.
   - Users can freely click/tap to drag any text overlay or badge anywhere across the image.
   - Clicking an overlay selects it with an active dashed outline and displays a delete (&times;) button.
   - Sidebar controls (text input, font size slider, color picker, background highlight, bold, italic) update the selected overlay in real-time.
   - When user clicks **"Apply & Save"** or **"Apply Crop"**, overlays are automatically baked directly onto the high-resolution HTML5 canvas context before generating the output JPEG blob.
   - Full Undo / Redo history stack tracks overlay states, positions, and canvas modifications.

---

## 5. API Reference Summary

### `POST /api/image/edit`
**Headers:** `Authorization: Bearer <token>` (optional), `Accept: application/json`  
**Payload (multipart/form-data or json):**
```json
{
  "image": "<file>",
  "operations": {
    "rotate": 90,
    "flip": "h",
    "crop": { "x": 0, "y": 0, "width": 500, "height": 500 },
    "brightness": 10,
    "contrast": 5,
    "filter": "warm",
    "text": "SPECIAL OFFER",
    "color": "#FFFFFF",
    "bg_color": "#E11D48",
    "font_size": 24,
    "position": "bottom-center",
    "stickers": [
      { "badge": "SALE", "x": 20, "y": 20 }
    ],
    "quality": 85
  },
  "item_id": 12,
  "add_watermark": true
}
```

**Response:**
```json
{
  "error": false,
  "message": "Image processed successfully.",
  "data": {
    "id": 1,
    "path": "item_images/edited_abc123_1789587880.jpg",
    "url": "https://example.com/storage/item_images/edited_abc123_1789587880.jpg",
    "width": 500,
    "height": 500,
    "transformations": { ... }
  },
  "code": 200
}
```

### `POST /api/format-content`
**Payload:**
```json
{
  "content": "Call me on 9876543210 or visit https://myshop.com"
}
```

**Response:**
```json
{
  "error": false,
  "message": "Content formatted successfully.",
  "data": {
    "original": "Call me on 9876543210 or visit https://myshop.com",
    "formatted": "Call me on <a href=\"tel:+919876543210\" class=\"eclassify-phone-link text-decoration-none font-weight-bold\" data-phone=\"9876543210\" data-whatsapp=\"https://wa.me/919876543210\" title=\"Call +91 98765 43210 | Chat on WhatsApp\" target=\"_blank\" rel=\"noopener noreferrer\"><span class=\"eclassify-phone-badge\"><i class=\"bi bi-telephone-fill me-1\"></i>+91 98765 43210</span></a> or visit <a href=\"https://myshop.com\" class=\"eclassify-url-link text-primary text-decoration-underline\" target=\"_blank\" rel=\"noopener noreferrer nofollow\">https://myshop.com</a>",
    "extracted_contacts": [
      {
        "number": "9876543210",
        "formatted": "+91 98765 43210",
        "tel_link": "tel:+919876543210",
        "whatsapp_link": "https://wa.me/919876543210"
      }
    ],

---

## 6. Release 3.4.0 Enhancements: Screen-Fit Image Editor, Rich Modal Views & Advanced Text Tools

**Date:** 2026-09-18  
**Scope:** Admin Panel Advertisement View Modal & Advanced Standalone Photo Editor Tools

### 6.1 Advertisement View Modal: Rich Content & Fallback Display
- **Location:** `resources/views/items/index.blade.php`
- **Issue Resolved:** In the Admin Panel Item Management table, clicking an item opened the `#editModal` popup which was displaying only unformatted plain text `description` instead of rich content from `description_json`.
- **Solution Implemented:**
  - Integrated `parseQuillDeltaToHtml(ops)` converter function directly into the item view script.
  - Prioritizes `row.formatted_description` (pre-formatted by `ContentFormatterService`) or parses `row.description_json` (Quill Delta JSON).
  - Robust fallback: If `description_json` is absent or invalid, seamlessly falls back to plain `row.description`.
  - Added modern expand/collapse container (`#adDescriptionContainer` with `.ad-desc-container.is-collapsed` and `#adDescToggle`) with smooth gradient fade-out that gracefully expands/collapses long descriptions without truncating or breaking HTML tags.

### 6.2 Studio-Grade Screen-Fit & Native Viewport Architecture
- **Location:** `public/assets/js/custom/photo-editor.js` & `public/assets/css/custom/photo-editor.css`
- **Dynamic Fit Engine (`fitCanvasToViewport()`):**
  - Measures the `#pe-canvas-viewport` container and computes the optimal aspect-ratio fitted width and height for `#pe-canvas-wrapper`.
  - Guarantees the canvas and all interactive draggable overlays fit cleanly inside the screen with zero awkward overflow, edge cut-offs, or double scrollbars.
  - Debounced window `resize` handler ensures seamless responsiveness across mobile devices, tablets, and wide desktop displays.
- **Viewport Zoom HUD Controls:**
  - Floating HUD placed non-intrusively in the canvas viewport with Zoom Out (`-`), Zoom In (`+`), and Reset Zoom (`Fit` / percentage).
  - Supports viewport mousewheel zoom with `Ctrl` or `Cmd` keys.

### 6.3 Expanded Text & Typography Customization Suite
- **Font Size Steppers & Presets:**
  - Added dedicated stepper buttons `[-]` and `[+]` alongside the `#pe-range-font-size` slider (12px to 96px).
  - Added one-click quick size pills: `16px`, `24px`, `28px`, `36px`, and `48px`.
- **Font Family Selector:**
  - Dropdown `#pe-select-font-family` with:
    - Modern Sans-Serif (`sans-serif`)
    - Classic Serif (`serif`)
    - Monospace (`monospace`)
    - Impact / Bold (`Impact, sans-serif`)
    - Handwriting (`'Comic Sans MS', cursive, sans-serif`)
- **Alignment & Formatting Tools:**
  - Alignment trio: Left, Center, and Right text alignment.
  - Style quad: **Bold** (`pe-btn-text-bold`), *Italic* (`pe-btn-text-italic`), <u>Underline</u> (`pe-btn-text-underline`), and Uppercase (`pe-btn-text-case`).
  - High-Contrast Outline Shadow toggle (`pe-btn-text-shadow`) for high visibility across complex photo backgrounds.
- **Quick Color Swatches:**
  - Color palettes for both Text and Background colors with quick circular swatches (`White`, `Black`, `Red`, `Amber`, `Green`, `Blue`, `Purple`, and `Transparent`).
- **Overlay Quick Actions:**
  - Duplicate selected overlay with subtle offset.
  - Center selected overlay on canvas.
  - Delete selected overlay.
  - Clear all overlays.

### 6.4 Orientation Straightening & Custom Badges
- **Fine Straighten Slider (`#pe-range-fine-rot`):**
  - Allows subtle orientation adjustments from `-45°` to `+45°` with real-time degrees indicator and dedicated Reset Straighten button.
- **Aspect Ratio Additions:**
  - Added `9:16 (Story)` and `3:2 (Classic Photo)` aspect ratios to the Crop panel.
- **Custom Badge Creator:**
  - In addition to standard badges (SALE, HOT DEAL, NEW, VERIFIED, FEATURED, BEST OFFER, TOP RATED, URGENT), users can enter custom badge text with a custom background color.
- **Desktop Keyboard Shortcuts:**
  - `Delete` / `Backspace`: Deletes selected overlay.
  - `Ctrl + Z`: Undo.
  - `Ctrl + Y` / `Ctrl + Shift + Z`: Redo.
  - `Escape`: Deselects overlay or closes modal.
- **High-Resolution Canvas Baking:**
  - `bakeOverlaysToCanvas()` applies all typographic styles (font family, underline, shadow, alignment, uppercase, rounded background highlights) directly to the native HTML5 Canvas context before export.

### 6.5 Direct Mouse-Based Text Resizing with Anchored Bounding Box (Canva / Figma Style)
- **Visual Bounding Box & 8 Drag Handles:**
  - When an overlay (text or badge) is selected, a sleek bounding outline (`.pe-overlay-box`) appears with 8 dedicated handles (`.pe-resize-handle`):
    - 4 Corner Handles: `tl` (Top-Left), `tr` (Top-Right), `br` (Bottom-Right), `bl` (Bottom-Left) with `nwse-resize` and `nesw-resize` directional cursors.
    - 4 Edge Handles: `tc` (Top-Center), `bc` (Bottom-Center), `ml` (Middle-Left), `mr` (Middle-Right) with `ns-resize` and `ew-resize` directional cursors.
    - 26px invisible hit area (`::after` pseudo-element) ensures effortless grabbing across touch and mouse inputs.
- **Opposite-Corner Anchored Scaling:**
  - When dragging any handle, the opposite corner or edge acts as the stationary anchor point in layer coordinates.
  - Dynamically recalculates the center position so the opposite corner does not jump or drift while resizing.
- **Actual Font Size Adjustment (No Distortion):**
  - Scales the actual `font-size` on the text layer rather than artificially stretching the container, preserving natural text kerning, line-height, and typographic proportions.
  - Clamped to safe bounds (12px minimum to 140px maximum).
- **Real-Time Sidebar Synchronization:**
  - Dragging any handle updates the sidebar font size slider (`#pe-range-font-size`), pixel label (`#pe-val-font-size`), and quick size pills in real time.
- **Full Non-Destructive Interaction:**
  - Dragging inside the text continues to move/reposition the text layer smoothly.
  - Clicking on the text layer selects it; double-clicking focuses the text input in the sidebar for immediate typing.
  - Full Undo / Redo support (`Ctrl+Z` / `Ctrl+Y`) captures every resize action into the history stack.

