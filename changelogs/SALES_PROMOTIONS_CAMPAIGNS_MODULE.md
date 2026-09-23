# Changelog: Sales & Offer Page with Promotions, Campaigns & Marketing Module

**Date:** 2026-09-08  
**Project:** Eclassify Classified Platform Backend (Laravel 12)  
**Version:** 3.2.0  
**Compatibility:** 100% Backward-compatible with existing Item, Package, User, and Featured Ads systems.

---

## 1. Executive Summary

This release introduces a dedicated **Sales & Offer Zone / Page** with structured **Campaigns**, child **Promotions** (Flash Sales, Stock Clearance Sales, Deals of the Day), seller promotional item submissions, and a comprehensive **"Promote this Ad"** system (Daily Bump Up, Top Ad, Spotlight Carousel).

The entire system is integrated into subscription packages with verified-seller restrictions and supports real-time location-aware querying (country, state, city, area, and lat/long/radius distance via the Haversine formula) for both Flutter Mobile App and Next.js Web Frontend.

---

## 2. Database & Schema Changes

### 2.1 New Table: `campaigns`
Top-level seasonal or marketing events (e.g. "Black Friday 2026", "Diwali Mega Sale", "Summer Clearance Carnival").

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Auto Increment, Primary Key | Campaign ID |
| `title` | `VARCHAR(191)` | Not Null | Campaign title / name |
| `slug` | `VARCHAR(191)` | Unique, Indexed | SEO slug |
| `description` | `TEXT` | Nullable | Campaign details |
| `banner_image` | `VARCHAR(191)` | Nullable | Desktop banner image |
| `start_date` | `DATE` | Not Null, Indexed | Launch date |
| `end_date` | `DATE` | Not Null, Indexed | Expiration date |
| `status` | `ENUM('active','inactive','scheduled','expired')` | Default `'active'`, Indexed | Display status |
| `priority` | `INT` | Default `0`, Indexed | Display priority sorting |
| `metadata` | `JSON` | Nullable | Additional custom attributes |
| `created_at`, `updated_at`, `deleted_at` | `TIMESTAMP` | Soft Deletes | Timestamps |

### 2.2 New Table: `promotions`
Child promotional zones under a campaign or standalone offer categories.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Auto Increment, Primary Key | Promotion ID |
| `campaign_id` | `BIGINT UNSIGNED` | Nullable, Foreign Key (`campaigns.id`) | Parent campaign |
| `title` | `VARCHAR(191)` | Not Null | Promotion title |
| `slug` | `VARCHAR(191)` | Unique, Indexed | URL slug |
| `description` | `TEXT` | Nullable | Promotion terms & description |
| `banner_image` | `VARCHAR(191)` | Nullable | Banner creative |
| `promotion_type` | `ENUM('flash_sale','clearance_sale','deal_of_the_day','custom')` | Not Null, Indexed | Type of promotional mechanics |
| `start_date` | `DATE` | Not Null, Indexed | Promotion start date |
| `end_date` | `DATE` | Not Null, Indexed | Promotion end date |
| `start_time` | `TIME` | Nullable | Daily start time |
| `end_time` | `TIME` | Nullable | Daily end time |
| `frequency` | `ENUM('daily','weekly','custom')` | Default `'custom'` | Recurrence pattern |
| `discount` | `DECIMAL(8,2)` | Nullable | Suggested discount value |
| `discount_type` | `ENUM('percentage','flat')` | Default `'percentage'` | Discount type |
| `status` | `ENUM('active','inactive','scheduled','expired')` | Default `'active'`, Indexed | Status |
| `priority` | `INT` | Default `0`, Indexed | Display priority sorting |
| `is_countdown_enabled` | `BOOLEAN` | Default `true` | Real-time countdown timer toggle |
| `max_items_per_user` | `INT UNSIGNED` | Nullable | Limit per seller |
| `metadata` | `JSON` | Nullable | Extra custom settings |
| `created_at`, `updated_at`, `deleted_at` | `TIMESTAMP` | Soft Deletes | Timestamps |

### 2.3 New Table: `promotion_items`
Sellers' individual item entries attached to an active promotion.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Auto Increment, Primary Key | Primary Key |
| `promotion_id` | `BIGINT UNSIGNED` | Foreign Key (`promotions.id` cascade) | Promotion |
| `item_id` | `BIGINT UNSIGNED` | Foreign Key (`items.id` cascade) | Classified ad item |
| `user_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id` cascade) | Item owner / seller |
| `user_purchased_package_id` | `BIGINT UNSIGNED` | Nullable, Foreign Key (`user_purchased_packages.id`) | Entitling package subscription |
| `promotional_price` | `DECIMAL(12,2)` | Not Null | Special promotional price |
| `discount_value` | `DECIMAL(8,2)` | Default `0` | Discount amount or percentage |
| `discount_type` | `ENUM('percentage','flat')` | Default `'percentage'` | Discount calculation type |
| `stock_quantity` | `INT UNSIGNED` | Default `1` | Initial inventory quota |
| `remaining_stock_quantity` | `INT UNSIGNED` | Default `1` | Remaining inventory units |
| `valid_until` | `DATETIME` | Nullable, Indexed | End validity |
| `status` | `ENUM('active','inactive','sold_out','expired','pending','rejected')` | Default `'active'`, Indexed | Status |
| `rejection_reason` | `TEXT` | Nullable | Moderation feedback |
| `created_at`, `updated_at`, `deleted_at` | `TIMESTAMP` | Soft Deletes | Timestamps |

### 2.4 New Table: `item_ad_promotions`
Supports "Promote this Ad" feature sets: Daily Bump Up, Top Ad, and Spotlight.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Auto Increment, Primary Key | Primary Key |
| `item_id` | `BIGINT UNSIGNED` | Foreign Key (`items.id` cascade) | Promoted ad |
| `user_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id` cascade) | Seller ID |
| `user_purchased_package_id` | `BIGINT UNSIGNED` | Nullable, Foreign Key (`user_purchased_packages.id`) | Entitling package subscription |
| `promotion_type` | `ENUM('daily_bump_up','top_ad','spotlight')` | Not Null, Indexed | Marketing boost type |
| `start_date` | `DATETIME` | Not Null | Activated date/time |
| `end_date` | `DATETIME` | Nullable, Indexed | Expiration date/time |
| `bump_frequency` | `ENUM('once','daily')` | Nullable | Bump repetition |
| `last_bumped_at` | `DATETIME` | Nullable | Timestamp of last 24-hr bump |
| `status` | `ENUM('active','expired','cancelled')` | Default `'active'`, Indexed | Boost status |
| `created_at`, `updated_at`, `deleted_at` | `TIMESTAMP` | Soft Deletes | Timestamps |

### 2.5 Updated Table: `packages`
Added promotional package type and entitlement flags:
- `allows_promotions` (`BOOLEAN`, default `false`)
- `promotion_item_limit` (`INT UNSIGNED`, nullable, 0 = unlimited)
- `allowed_promotion_types` (`JSON`, nullable)
- `allows_daily_bump_up` (`BOOLEAN`, default `false`)
- `daily_bump_up_limit` (`INT UNSIGNED`, nullable, 0 = unlimited)
- `allows_top_ad` (`BOOLEAN`, default `false`)
- `top_ad_limit` (`INT UNSIGNED`, nullable, 0 = unlimited)
- `allows_spotlight` (`BOOLEAN`, default `false`)
- `spotlight_limit` (`INT UNSIGNED`, nullable, 0 = unlimited)

### 2.6 Updated Table: `user_purchased_packages`
Added usage tracking columns:
- `used_promotions_limit` (`INT UNSIGNED`, default 0)
- `used_daily_bump_up_limit` (`INT UNSIGNED`, default 0)
- `used_top_ad_limit` (`INT UNSIGNED`, default 0)
- `used_spotlight_limit` (`INT UNSIGNED`, default 0)

---

## 3. Architecture & Service Layer

- **`App\Services\PromotionService`**:
  - `checkUserEligibility(User $user, $feature)`: Enforces that user is verified (`is_verified == true`) and holds an active subscription package permitting the action.
  - `addPromotionItem(User $user, array $data)`: Validates price markdown (`discounted_price < original_price`), quota consumption, and persists item submission.
  - `promoteAd(User $user, int $itemId, string $promotionType, int $durationDays)`: Deducts boost quota, sets activation timestamps, and updates item touch points.
  - `applyDailyBumpUp(ItemAdPromotion $adPromotion)`: Resets `item.created_at` to `now()` so it ranks at the top of organic listings.
  - `expireOutdatedPromotionsAndBumps()`: Automatically marks past promotion items and boosts as expired or sold out.

- **Console Command**:
  - `promotions:rotate-deals-and-bumps`: Can be scheduled in Laravel's scheduler (`routes/console.php` or `app/Console/Kernel.php`) to run daily or hourly.

---

## 4. API Reference

### 4.1 Public Offer Zone APIs (Prefix: `/api/offers`)

| Method | Endpoint | Query Parameters | Description |
|---|---|---|---|
| `GET` | `/api/offers/campaigns` | `status`, `page`, `per_page` | Lists active and upcoming seasonal campaigns |
| `GET` | `/api/offers/campaign-detail` | `id` or `slug` | Detailed campaign with child promotions |
| `GET` | `/api/offers/promotions` | `campaign_id`, `type`, `page`, `per_page` | Lists promotional sections |
| `GET` | `/api/offers/promotion-detail` | `id` or `slug` | Promotion details with real-time countdown info |
| `GET` | `/api/offers/promotion-items` | `promotion_id`, `type`, `country`, `state`, `city`, `area_id`, `latitude`, `longitude`, `radius` | Paginated promotional items with distance & location filtering |
| `GET` | `/api/offers/flash-sales` | `country`, `state`, `city`, `latitude`, `longitude`, `radius` | Dedicated Flash Sales with countdown timer |
| `GET` | `/api/offers/clearance-sales` | Location params, `sort_by` (`discount_high_to_low`, etc.) | Clearance listings with stock indicators |
| `GET` | `/api/offers/deals-of-the-day`| Location params | 24-hour deals expiring tonight at midnight |
| `GET` | `/api/offers/spotlight-ads` | Location params | Top high-visibility spotlight ads |

### 4.2 Seller / Authenticated APIs (Prefix: `/api/seller`)

| Method | Endpoint | Headers | Body / Parameters | Description |
|---|---|---|---|---|
| `GET` | `/api/seller/promotions/available` | `Bearer <Token>` | — | Returns currently open promotions that seller can submit items to |
| `GET` | `/api/seller/promotions/my-items` | `Bearer <Token>` | `status`, `page` | Seller's submitted promotional items with stock and validity |
| `POST` | `/api/seller/promotions/add-item` | `Bearer <Token>` | `promotion_id`, `item_id`, `discounted_price`, `stock_quantity`, `valid_until` | Submits item into promotion after checking verification and package |
| `POST` | `/api/seller/promotions/update-item` | `Bearer <Token>` | `id`, `discounted_price`, `stock_quantity`, `valid_until` | Updates promo price or stock |
| `POST` | `/api/seller/promotions/toggle-item-status`| `Bearer <Token>` | `id`, `status` (`active`/`inactive`) | Toggles item visibility |
| `POST` | `/api/seller/promotions/delete-item` | `Bearer <Token>` | `id` | Soft-deletes submission and refunds package quota |
| `GET` | `/api/seller/items/promotion-options` | `Bearer <Token>` | `item_id` | Returns available boost options (`daily_bump_up`, `top_ad`, `spotlight`) & quotas |
| `POST` | `/api/seller/items/promote` | `Bearer <Token>` | `item_id`, `promotion_type`, `duration_days` | Boosts the ad using package quota |

---

## 5. Admin Panel Capabilities

1. **Campaigns Management (`/campaigns`)**:
   - Create, edit, translate, upload banners, toggle status, and delete campaigns.
2. **Promotions Management (`/promotions`)**:
   - Configure Flash Sales, Clearance Sales, Deals of the Day, countdown timer target dates, and stock requirement toggles.
3. **Promotion Items Review (`/promotions/items`)**:
   - View all seller items submitted to promotions, check discounted prices, remaining stock, seller info, and toggle or delete entries.
4. **Ad Promotions Management (`/ad-promotions`)**:
   - Monitor active Daily Bump Ups, Top Ads, and Spotlight placements, including expiration dates and bump timestamps.
5. **Packages Management (`/packages/create` & `/packages/{id}/edit`)**:
   - Choose `promotional` package type or toggle individual promotional entitlements (`allows_promotions`, `allows_daily_bump_up`, `allows_top_ad`, `allows_spotlight`) with custom quotas.

---

## 6. Seller Verification & Subscription Package Handling Enhancements

1. **Explicit Eligibility Flags**:
   - `SellerPromotionApiController::getAvailablePromotions()` and `PromotionService::getAdPromotionOptions()` now return `requires_verification`, `requires_package`, `has_active_package`, and human-readable `message` when user is unverified or lacks subscription quota.
2. **Localization Strings**:
   - Updated `resources/lang/en.json` with clear translation strings for verification requirements and subscription prompts.
3. **Frontend & App Navigation Hooks**:
   - Web frontend and mobile clients leverage these flags to navigate unverified users directly to `/user-verification` / `Routes.verification` and unsubscribed users directly to subscription package screens.

---

## 7. Package Creation & Bundled Promotional Features Enhancement

1. **Unified Promotional Bundling Across All Package Types**:
   - Resolved the issue where selecting "Promotional / Offer Package" did not render any promotional configuration controls.
   - Fixed an unclosed `<div>` container in both `resources/views/packages/create.blade.php` and `resources/views/packages/edit.blade.php` where `#promotional_features_section` had previously been nested inside `#featured_ads_section`.
   - Extracted `#promotional_features_section` as an independent card available for **all package types**:
     - `item_listing`: Admin can bundle promotional perks (sales promotions, daily bump ups, top ads, spotlight carousel) directly with regular listing limits.
     - `advertisement`: Admin can combine featured ad limits with promotional perks.
     - `promotional`: Dedicated standalone marketing package.
2. **Quota & Input Consistency**:
   - Aligned view field names `promotion_item_limit` and `daily_bump_up_limit` with database columns and `PackageController.php`.
   - Added quota inputs for `top_ad_limit` and `spotlight_limit` with toggle listeners for `#allows_top_ad` and `#allows_spotlight`.
3. **Localization Support**:
   - Added translatable strings for promotional package headings, descriptions, switches, and quota inputs in `resources/lang/en.json`.

---

## 8. Multi-Campaign Support, Countdown Localization & Resource Payload Alignment

1. **PromotionItemResource Payload Alignment**:
   - `PromotionItemResource` guarantees complete nested `item` payload (`id`, `name`, `slug`, `image`, `city`, `original_price`, `promotional_price`, `discount_value`) for promotional items, allowing Web and Mobile clients to navigate cleanly to ad detail screens.
2. **Multi-Campaign Directory & Hero Presentation**:
   - Backend APIs (`/offers/campaigns`) return all active campaigns ordered by priority and start date.
   - Frontend web (`CampaignsCarousel`) and Flutter mobile (`_CampaignHeroSlider`) now present all active campaigns seamlessly in an auto-scrolling carousel with pagination dots.
3. **Localization Updates**:
   - Added `Campaign Ends In`, `Days`, `Hrs`, `Min`, `Sec` translation keys to `resources/lang/en.json`.

---

## 9. Real-Time Promotional Badges, History & Enterprise Analytics Dashboard

1. **Real-Time Active Promotions Payload**:
   - Added `active_promotions` accessor to `App\Models\Item`:
     - Computes real-time active status (`has_active_promotions`), `is_top_ad`, `is_spotlight`, and list of active sales items (`campaign_title`, `discount_percentage`, `stock_quantity`, `claimed_count`, `valid_until`) and active boosts.
   - Updated `App\Http\Resources\ItemApiResource`: attaches `active_promotions` to both single item queries and seller listing collections (`myItem`).
2. **Seller Promotion Analytics Endpoint (`GET /api/seller/promotions/analytics`)**:
   - Aggregates overall performance metrics:
     - `active_promotions_count`, `total_promoted_ads`, `total_promotion_units_sold`, `total_estimated_promotion_revenue`, `total_buyer_savings`, `active_sales_count`, `active_boosts_count`, `active_campaigns_joined`.
     - Type breakdown for flash sales, clearance sales, deals of the day, top ads, and spotlight placements.
3. **Seller Promotion History Endpoint (`GET /api/seller/promotions/history`)**:
   - Returns paginated history across all promotional activities with filtering (`filter_type`, `campaign_id`, `status`):
     - Active duration in days (`active_duration_days`), units claimed vs total promotional stock, claim percentage, estimated promotional revenue generated, campaign affiliation, and real-time status (`active`, `ended`, `expired`).
4. **Localization & Documentation**:
   - Updated `resources/lang/en.json` with comprehensive translation keys for performance metrics and history views.

## 10. Boost Analytics & History, Daily Bump Up Badge, and Admin User-wise Analytics Screen

1. **Daily Bump Up Active Status & Visual Badge**:
   - Added `is_daily_bumped` accessor and database query logic to `App\Models\Item`.
   - Included `is_daily_bumped` in the `active_promotions` payload in `ItemApiResource`.
   - Frontend Web (`MyAdsCard.jsx`): Renders a cyan badge with `<RocketLaunchIcon />` and `t("dailyBump") || "Bumped"` when an ad has active Daily Bump Up.
   - Flutter Mobile App (`PromotionBadgeStrip`): Displays a dedicated cyan badge chip with `AppIcons.arrowsClockwise` and `'dailyBump'.translate(context)` when `promotions.isDailyBumped` is true.

2. **Unified Boosts & Sales Performance History**:
   - Upgraded `SellerPromotionApiController::getPromotionsHistory()` to support both sales (`PromotionItem`) and boosts (`ItemAdPromotion` for `daily_bump_up`, `top_ad`, `spotlight`).
   - Unified history items sorted chronologically, exposing `views`, `clicks`, `days_active`, `start_date`, `end_date`, `last_bumped_at`, and real-time status.
   - Allows sellers and admins to filter specifically by `daily_bump_up`, `top_ad`, `spotlight`, or `boosts`.

3. **Admin User-wise Promotions & Boosts Analytics Dashboard**:
   - Added `GET /ad-promotions/user-analytics`, `GET /ad-promotions/user-analytics/data`, and `GET /ad-promotions/user-analytics/history` routes in Laravel admin panel.
   - Created admin Blade view `resources/views/ad_promotions/user_analytics.blade.php` featuring:
     - Real-time Select2 user/seller dropdown search with smart auto-selection prioritizing sellers with active promotions/boosts.
     - Live KPI cards: Active Promotions, Units Sold/Claimed, Generated Revenue, and Active Boosts (bump, top, spot breakdown).
     - Full server-side paginated Bootstrap table displaying all promotional activities and marketing boosts for the selected user, connected directly to `ad-promotions.user-analytics.history` web route.
   - Added sidebar navigation entry under "Promotions & Campaigns": `User Promotions Analytics`.
   - **Model Relationship & Query Fixes**:
     - Defined missing `user_purchased_packages()`, `ad_promotions()`, and `promotion_items()` Eloquent `hasMany` relationships on `App\Models\User`.
     - Hardened user query in `ItemAdPromotionController::userAnalytics()` to query users who have items, purchased packages, ad promotions, or promotion items, with a graceful fallback to all users if none match.
     - Enhanced permission checks with `ResponseService::noAnyPermissionThenSendJson(['ad-promotion-list', 'promotion-item-list'])`.
     - Added dedicated web history endpoint `ItemAdPromotionController::userAnalyticsHistory()` bypassing Sanctum API bearer token requirements for the admin web session.

4. **Localization**:
   - Added all necessary translation keys to backend `en.json`, web `en.json`, and mobile `language.json`.


