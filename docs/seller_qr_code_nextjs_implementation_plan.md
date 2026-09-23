# Next.js Web Frontend Implementation Guide: Seller QR Code & Digital Catalog

**Date:** 2026-09-10  
**Target Platform:** Next.js (App Router / Pages Router)  
**Backend API:** Eclassify Laravel 12 API (`/api/seller-qr/*`)

---

## 1. Overview & Routing Architecture

When users scan a physical QR standee with their phone's camera, their browser opens:  
`https://yourdomain.com/store-qr/[token]`

The Next.js frontend fetches the seller's catalog, checks the visitor's device location against the seller's physical store location, shows a location discrepancy warning if applicable, and renders a modern, mobile-responsive catalog with direct contact CTAs.

---

## 2. Directory Structure

```text
src/
├── app/
│   ├── store-qr/
│   │   └── [token]/
│   │       └── page.tsx            # Dedicated Public QR Store Catalog Page
│   └── dashboard/
│       └── seller-qr/
│           └── page.tsx            # Seller Dashboard: Customize & Download Standee
├── components/
│   └── seller-qr/
│       ├── LocationMismatchAlert.tsx # Warning Banner Component
│       ├── StoreHeroHeader.tsx      # Store Branding & Contact CTAs
│       ├── CatalogFilterBar.tsx     # Search & Category Pills
│       └── StandeeMockupPreview.tsx # Live Preview Component for Dashboard
└── services/
    └── sellerQrService.ts           # API Client
```

---

## 3. API Service (`services/sellerQrService.ts`)

```typescript
import axios from 'axios';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.yourdomain.com/api';

export interface LocationWarning {
  is_matched: boolean;
  warning: boolean;
  message: string | null;
  distance_km: number | null;
  distance?: {
    meters: number;
    kilometers: number;
    formatted: string;
  };
  store_location: {
    name: string;
    address: string;
    city: string;
    state: string;
    country: string;
  };
}

export interface StoreCatalogResponse {
  error: boolean;
  message: string;
  data: {
    store: any;
    location_warning: LocationWarning;
    categories: Array<{ id: number; name: string; slug: string }>;
    items: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      data: any[];
    };
    qr_details?: {
      token: string;
      title: string;
      tagline: string;
      deep_link: string;
    };
  };
}

export const sellerQrService = {
  // Public: Fetch Store Catalog by QR Token
  async getStoreByQr(
    token: string,
    params?: {
      latitude?: number;
      longitude?: number;
      city?: string;
      state?: string;
      category_id?: number;
      search?: string;
      sort_by?: string;
      page?: number;
    }
  ): Promise<StoreCatalogResponse> {
    const response = await axios.get(`${API_BASE_URL}/seller-qr/store/${token}`, { params });
    return response.data;
  },

  // Auth: Check Eligibility
  async checkEligibility(token: string) {
    const response = await axios.get(`${API_BASE_URL}/seller-qr/eligibility`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  },

  // Auth: Get My QR Standee
  async getMyQr(token: string) {
    const response = await axios.get(`${API_BASE_URL}/seller-qr/my-qr`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  },

  // Auth: Update or Generate Standee
  async generateOrUpdate(token: string, formData: FormData) {
    const response = await axios.post(`${API_BASE_URL}/seller-qr/generate-or-update`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};
```

---

## 4. Universal Web QR Page (`app/store-qr/[token]/page.tsx`)

```tsx
'use client';

import React, { useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'next/navigation';
import { sellerQrService, StoreCatalogResponse } from '@/services/sellerQrService';
import LocationMismatchAlert from '@/components/seller-qr/LocationMismatchAlert';

export default function StoreQrPage() {
  const params = useParams();
  const searchParams = useSearchParams();
  const token = params.token as string;

  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<StoreCatalogResponse['data'] | null>(null);
  const [coords, setCoords] = useState<{ lat?: number; lng?: number }>({});
  const [activeCat, setActiveCat] = useState<number | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('newest');

  // 1. Capture Browser Geolocation silently
  useEffect(() => {
    if (typeof window !== 'undefined' && 'geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          setCoords({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        },
        () => {},
        { timeout: 5000, maximumAge: 60000 }
      );
    }
  }, []);

  // 2. Fetch Store Data
  useEffect(() => {
    async function loadStore() {
      try {
        setLoading(true);
        const res = await sellerQrService.getStoreByQr(token, {
          latitude: coords.lat,
          longitude: coords.lng,
          category_id: activeCat || undefined,
          search: searchTerm || undefined,
          sort_by: sortBy,
        });
        if (!res.error) {
          setData(res.data);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    }

    if (token) {
      loadStore();
    }
  }, [token, coords, activeCat, searchTerm, sortBy]);

  if (loading) {
    return <div className="p-10 text-center">Loading digital store catalog...</div>;
  }

  if (!data) {
    return <div className="p-10 text-center">Store not found.</div>;
  }

  const { store, location_warning, categories, items, qr_details } = data;

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 pb-16">
      {/* Smart App Banner */}
      {qr_details?.deep_link && (
        <div className="bg-slate-900 text-white px-4 py-2 flex items-center justify-between text-xs">
          <span>Have the Eclassify app installed?</span>
          <a
            href={qr_details.deep_link}
            className="bg-teal-500 text-white px-3 py-1 rounded-full font-bold hover:bg-teal-600 transition"
          >
            Open in App
          </a>
        </div>
      )}

      <div className="max-w-4xl mx-auto px-4 pt-4">
        {/* Location Discrepancy Notice */}
        {location_warning && location_warning.warning && (
          <LocationMismatchAlert warning={location_warning} />
        )}

        {/* Store Header Banner */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden mb-6">
          <div
            className="h-32 bg-gradient-to-r from-teal-500 to-slate-900 bg-cover bg-center"
            style={store.banner ? { backgroundImage: `url(${store.banner})` } : {}}
          />
          <div className="px-6 pb-6 relative">
            <div className="w-20 h-20 rounded-2xl overflow-hidden border-4 border-white dark:border-slate-900 shadow-md -mt-10 mb-3 bg-white">
              <img
                src={store.logo || '/placeholder.png'}
                alt={store.name}
                className="w-full h-full object-cover"
              />
            </div>

            <div className="flex flex-wrap items-center gap-2 mb-1">
              <h1 className="text-2xl font-black text-slate-900 dark:text-white">
                {store.name}
              </h1>
              {store.is_verified && (
                <span className="bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300 text-xs px-2 py-0.5 rounded-full font-bold">
                  ✓ Verified Store
                </span>
              )}
            </div>

            <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
              {store.address || `${store.city}, ${store.state}`}
            </p>

            {/* Quick Action Buttons */}
            <div className="grid grid-cols-3 gap-2 max-w-sm">
              {store.contact && (
                <a
                  href={`tel:${store.contact}`}
                  className="py-2 text-center text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 transition"
                >
                  📞 Call
                </a>
              )}
              {store.contact && (
                <a
                  href={`https://wa.me/${store.contact.replace(/\D/g, '')}`}
                  target="_blank"
                  rel="noreferrer"
                  className="py-2 text-center text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 text-green-600 hover:border-green-500 transition"
                >
                  💬 WhatsApp
                </a>
              )}
              {store.latitude && store.longitude && (
                <a
                  href={`https://www.google.com/maps/search/?api=1&query=${store.latitude},${store.longitude}`}
                  target="_blank"
                  rel="noreferrer"
                  className="py-2 text-center text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 transition"
                >
                  📍 Directions
                </a>
              )}
            </div>
          </div>
        </div>

        {/* Catalog Search & Category Filters */}
        <div className="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm mb-6">
          <div className="flex gap-2 mb-3">
            <input
              type="text"
              placeholder="Search items in this shop..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="flex-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none focus:border-teal-500"
            />
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-bold outline-none"
            >
              <option value="newest">Newest</option>
              <option value="price_asc">Price: Low to High</option>
              <option value="price_desc">Price: High to Low</option>
              <option value="popular">Most Popular</option>
            </select>
          </div>

          {categories.length > 0 && (
            <div className="flex gap-2 overflow-x-auto pb-1">
              <button
                onClick={() => setActiveCat(null)}
                className={`px-3 py-1 text-xs font-bold rounded-full whitespace-nowrap transition ${
                  activeCat === null
                    ? 'bg-teal-500 text-white'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                }`}
              >
                All Items
              </button>
              {categories.map((c) => (
                <button
                  key={c.id}
                  onClick={() => setActiveCat(c.id)}
                  className={`px-3 py-1 text-xs font-bold rounded-full whitespace-nowrap transition ${
                    activeCat === c.id
                      ? 'bg-teal-500 text-white'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                  }`}
                >
                  {c.name}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Item Cards Grid */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {items.data.map((item) => (
            <a
              key={item.id}
              href={`/ad-details/${item.slug || item.id}`}
              className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col"
            >
              <div className="h-36 bg-slate-100 dark:bg-slate-800 relative">
                <img
                  src={item.image || item.gallery_images?.[0]?.image || '/placeholder.png'}
                  alt={item.name}
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="p-3 flex flex-col flex-1">
                <div className="text-teal-600 dark:text-teal-400 font-extrabold text-base mb-1">
                  ${item.price}
                </div>
                <div className="text-xs font-semibold text-slate-900 dark:text-white line-clamp-2 mb-2">
                  {item.name}
                </div>
                <div className="mt-auto text-[10px] text-slate-400 flex justify-between">
                  <span>{item.category?.name || 'Item'}</span>
                  <span>{item.created_at}</span>
                </div>
              </div>
            </a>
          ))}
        </div>
      </div>
    </div>
  );
}
```

---

## 5. Location Mismatch Notice Component (`components/seller-qr/LocationMismatchAlert.tsx`)

```tsx
import React, { useState } from 'react';
import { LocationWarning } from '@/services/sellerQrService';

export default function LocationMismatchAlert({ warning }: { warning: LocationWarning }) {
  const [dismissed, setDismissed] = useState(false);

  if (dismissed || !warning.warning) return null;

  return (
    <div className="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 mb-6 flex items-start gap-3 shadow-sm">
      <span className="text-amber-600 text-xl mt-0.5">⚠️</span>
      <div className="flex-1">
        <h4 className="text-xs font-black uppercase tracking-wide text-amber-800 dark:text-amber-300">
          Location Notice
        </h4>
        <p className="text-xs text-amber-900 dark:text-amber-200 mt-1 leading-relaxed">
          {warning.message}
        </p>
      </div>
      <button
        onClick={() => setDismissed(true)}
        className="text-amber-600 dark:text-amber-400 text-xs font-bold px-2 py-1 hover:bg-amber-100 dark:hover:bg-amber-900/50 rounded-lg transition"
      >
        ✕
      </button>
    </div>
  );
}
```
