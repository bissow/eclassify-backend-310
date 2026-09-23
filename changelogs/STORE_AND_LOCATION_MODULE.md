# Changelog: Store / Shop & Location Discovery Module

**Date:** 2026-09-08  
**Project:** Eclassify Classified Platform Backend (Laravel 12)  
**Version:** 3.1.0  
**Compatibility:** 100% Backward-compatible with existing Item, User, and Authentication flows.

---

## 1. Executive Summary

This update adds a complete **Store / Shop / Seller Management & Geolocation Discovery Engine** to the Eclassify backend. Users can optionally create and manage their dedicated Store / Shop profile (branding, cover banner, contact, working hours, exact geolocation coordinates, address, and social channels).

Additionally, high-performance **Nearby Available Sellers & Stores API** allows mobile (Flutter) and web (Next.js) frontends to discover, filter, and sort shops/sellers by real-time distance (calculated in meters and kilometers via the Haversine formula), radius, location hierarchy (country, state, city, area), category offerings, ratings, and keyword search.

---

## 2. Database & Schema Changes

### 2.1 Added Columns to `users` Table
- `latitude` (`DECIMAL(10, 8)`, nullable)
- `longitude` (`DECIMAL(11, 8)`, nullable)
- `country` (`VARCHAR(191)`, nullable)
- `state` (`VARCHAR(191)`, nullable)
- `city` (`VARCHAR(191)`, nullable)
- `area_id` (`BIGINT UNSIGNED`, nullable)
- `has_store` (`BOOLEAN`, default `false`, indexed)

### 2.2 New Table: `stores`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Auto Increment, Primary Key | Store ID |
| `user_id` | `BIGINT UNSIGNED` | Unique, Foreign Key (`users.id` cascade) | Associated User ID (1:1) |
| `name` | `VARCHAR(191)` | Not Null | Store / Business Display Name |
| `slug` | `VARCHAR(191)` | Unique, Indexed | SEO-friendly URL Slug |
| `description` | `TEXT` | Nullable | Store description & Bio |
| `logo` | `VARCHAR(191)` | Nullable | Store Profile Logo Image Path |
| `banner` | `VARCHAR(191)` | Nullable | Store Header / Cover Banner Image Path |
| `email` | `VARCHAR(191)` | Nullable | Store Public Contact Email |
| `contact` | `VARCHAR(50)` | Nullable | Store Public Phone Number |
| `country_code` | `VARCHAR(10)` | Nullable | Dialing Country Code (e.g. `+1`, `+91`) |
| `address` | `TEXT` | Nullable | Physical Address / Shop location |
| `latitude` | `DECIMAL(10, 8)` | Nullable, Indexed | Latitude Coordinate |
| `longitude` | `DECIMAL(11, 8)` | Nullable, Indexed | Longitude Coordinate |
| `country` | `VARCHAR(191)` | Nullable, Indexed | Country name |
| `state` | `VARCHAR(191)` | Nullable, Indexed | State name |
| `city` | `VARCHAR(191)` | Nullable, Indexed | City name |
| `area_id` | `BIGINT UNSIGNED` | Nullable, Indexed | Foreign Key to `areas.id` |
| `website` | `VARCHAR(255)` | Nullable | Store Website URL |
| `tax_number` | `VARCHAR(100)` | Nullable | Business Tax / GST / VAT Registration |
| `opening_time` | `VARCHAR(20)` | Nullable | Store Opening Time (e.g. `09:00 AM`) |
| `closing_time` | `VARCHAR(20)` | Nullable | Store Closing Time (e.g. `08:00 PM`) |
| `working_days` | `JSON` | Nullable | Array of open days (e.g. `["Mon","Tue","Wed"]`) |
| `social_links` | `JSON` | Nullable | Social URLs (`{"facebook":"","instagram":""}`) |
| `status` | `ENUM('active','inactive','pending')` | Default `'active'`, Indexed | Store Visibility Status |
| `is_verified` | `BOOLEAN` | Default `false`, Indexed | Official Store Verification Badge |
| `created_at` | `TIMESTAMP` | Nullable | Creation Timestamp |
| `updated_at` | `TIMESTAMP` | Nullable | Last Update Timestamp |
| `deleted_at` | `TIMESTAMP` | Nullable | Soft Delete Timestamp |

### 2.3 New Spatie Permissions
- `store-list`: Access Store list in Admin panel
- `store-create`: Create stores
- `store-update`: Edit store details, toggle active status, and manage verification badge
- `store-delete`: Soft-delete stores

---

## 3. API Endpoints Reference

### 3.1 Authenticated Store APIs (`auth:sanctum`)

#### `POST /api/setup-store`
Setup or update authenticated user's store.

**Headers:**
```http
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Parameters:**
- `name` (string, required) - Store Name
- `description` (string, optional) - Detailed Store description
- `logo` (file, optional, image/mimes:jpeg,png,jpg,webp, max:5MB)
- `banner` (file, optional, image/mimes:jpeg,png,jpg,webp, max:7MB)
- `email` (string, optional, valid email)
- `contact` (string, optional) - Phone number
- `country_code` (string, optional) - e.g. `+1`
- `address` (string, optional) - Physical street address
- `latitude` (numeric, optional) - e.g. `28.6139`
- `longitude` (numeric, optional) - e.g. `77.2090`
- `country` (string, optional)
- `state` (string, optional)
- `city` (string, optional)
- `area_id` (integer, optional)
- `website` (string, optional, URL)
- `tax_number` (string, optional)
- `opening_time` (string, optional) - e.g. `09:00 AM`
- `closing_time` (string, optional) - e.g. `08:00 PM`
- `working_days` (json string or array, optional) - e.g. `["Monday", "Tuesday", "Wednesday"]`
- `social_links` (json string or object, optional) - e.g. `{"facebook": "https://fb.com/store", "instagram": "https://instagr.am/store"}`

**Sample Response:**
```json
{
  "error": false,
  "message": "Store setup updated successfully",
  "data": {
    "id": 1,
    "user_id": 42,
    "name": "Apex Electronics Store",
    "slug": "apex-electronics-store",
    "description": "Premium electronics, laptops and gadgets shop in downtown.",
    "logo": "https://domain.com/storage/store_logos/apex_logo.png",
    "banner": "https://domain.com/storage/store_banners/apex_banner.png",
    "email": "contact@apexelectronics.com",
    "contact": "9876543210",
    "country_code": "+1",
    "address": "124 Market Street, Silicon District",
    "latitude": 37.7749,
    "longitude": -122.4194,
    "country": "United States",
    "state": "California",
    "city": "San Francisco",
    "area_id": 15,
    "website": "https://apexelectronics.com",
    "tax_number": "TAX-998822",
    "opening_time": "09:00 AM",
    "closing_time": "09:00 PM",
    "working_days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    "social_links": {
      "facebook": "https://facebook.com/apexelectronics",
      "instagram": "https://instagram.com/apexelectronics"
    },
    "status": "active",
    "is_verified": true,
    "distance": null,
    "stats": {
      "active_items_count": 24,
      "total_items_count": 30,
      "average_rating": 4.8,
      "total_reviews": 19,
      "ratings_breakdown": {
        "1": 0, "2": 0, "3": 1, "4": 2, "5": 16
      },
      "followers_count": 142
    },
    "owner": {
      "id": 42,
      "name": "John Doe",
      "profile": "https://domain.com/storage/profile/john.jpg",
      "country_code": "+1",
      "is_verified": true
    }
  }
}
```

---

#### `GET /api/get-my-store`
Get authenticated user's store configuration.

**Response:**
```json
{
  "error": false,
  "message": "Store details fetched successfully",
  "data": {
    "has_store": true,
    "store": { ... }
  }
}
```

---

#### `POST /api/toggle-store-status`
Toggle store active/inactive visibility.

**Response:**
```json
{
  "error": false,
  "message": "Store status updated successfully",
  "data": {
    "status": "inactive"
  }
}
```

---

### 3.2 Public / Discovery APIs

#### `GET /api/get-stores` (Nearby Sellers / Stores)
Discover stores based on customer location, radius, city/state, or keyword search.

**Query Parameters:**
- `latitude` (numeric, optional) - Customer's current latitude
- `longitude` (numeric, optional) - Customer's current longitude
- `radius` (numeric, optional in km) - e.g. `25` (25 km search radius)
- `country` (string, optional)
- `state` (string, optional)
- `city` (string, optional)
- `area_id` (integer, optional)
- `search` (string, optional) - Store name, description, owner name, or address
- `is_verified` (boolean, optional) - `1` or `0`
- `sort_by` (string, optional) - `nearest`, `top_rated`, `newest`, `popular`
- `limit` (integer, optional, default: 10)
- `page` (integer, optional, default: 1)

**Sample Response:**
```json
{
  "error": false,
  "message": "Stores fetched successfully",
  "data": {
    "total": 45,
    "current_page": 1,
    "per_page": 10,
    "last_page": 5,
    "data": [
      {
        "id": 1,
        "name": "Apex Electronics Store",
        "slug": "apex-electronics-store",
        "description": "Premium electronics, laptops and gadgets shop.",
        "logo": "https://domain.com/storage/store_logos/apex_logo.png",
        "banner": "https://domain.com/storage/store_banners/apex_banner.png",
        "email": "contact@apexelectronics.com",
        "contact": "9876543210",
        "country_code": "+1",
        "address": "124 Market Street, San Francisco",
        "latitude": 37.7749,
        "longitude": -122.4194,
        "city": "San Francisco",
        "state": "California",
        "country": "United States",
        "is_verified": true,
        "distance": {
          "meters": 850,
          "kilometers": 0.85,
          "formatted": "850 m"
        },
        "stats": {
          "active_items_count": 24,
          "average_rating": 4.8,
          "total_reviews": 19,
          "followers_count": 142
        },
        "owner": {
          "id": 42,
          "name": "John Doe",
          "profile": "https://domain.com/storage/profile/john.jpg"
        }
      }
    ]
  }
}
```

---

#### `GET /api/get-store-detail`
Get complete store page details including active items catalog and buyer reviews.

**Query Parameters:**
- `slug` (string, optional) - Store slug (e.g. `apex-electronics-store`)
- `id` (integer, optional) - Store ID
- `user_id` (integer, optional) - Owner user ID
- `latitude` (numeric, optional) - For distance computation
- `longitude` (numeric, optional) - For distance computation
- `category_id` (integer, optional) - Filter store's items by category
- `items_limit` (integer, optional, default: 12)
- `items_page` (integer, optional, default: 1)

**Sample Response:**
```json
{
  "error": false,
  "message": "Store detail fetched successfully",
  "data": {
    "store": {
      "id": 1,
      "name": "Apex Electronics Store",
      "slug": "apex-electronics-store",
      "description": "...",
      "logo": "...",
      "banner": "...",
      "distance": {
        "meters": 1450,
        "kilometers": 1.45,
        "formatted": "1.45 km"
      },
      "is_following": false,
      "stats": { ... }
    },
    "items": {
      "total": 24,
      "current_page": 1,
      "per_page": 12,
      "last_page": 2,
      "data": [ ... ]
    },
    "reviews": { ... }
  }
}
```

---

#### `GET /api/get-store-slugs`
Get all active store slugs for Next.js Static Site Generation (`getStaticPaths` / `generateStaticParams`) and sitemap generators.

---

## 4. Admin Panel Integration

1. **Navigation:** Accessible under **Seller Management > Stores & Shops** in the Admin dashboard sidebar.
2. **Features:**
   - Responsive Bootstrap Table with dark and light mode styling.
   - Status Toggle Switch (instantly activates / deactivates store visibility via AJAX).
   - Verification Badge Toggle (rewards trusted sellers with an official verification badge).
   - Filters by Status (`Active`, `Inactive`) and Verification (`Verified`, `Unverified`).
   - Soft Delete & Restoration safeguards.
