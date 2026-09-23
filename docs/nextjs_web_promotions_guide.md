# Next.js Web Frontend Implementation Guide: Sales / Offer Zone, Promotions & Campaigns

This guide explains how to integrate the **Sales / Offers Page (`/offers`)**, **Promotions**, **Campaigns**, and **"Promote this Ad"** features in the Next.js frontend (App Router or Pages Router).

---

## 1. Route Structure & Page Hierarchy

Recommended Next.js App Router structure:

```text
src/app/
├── (public)/
│   └── offers/
│       ├── page.tsx                  // Dedicated Offer Zone / Sales Page
│       ├── [slug]/
│       │   └── page.tsx              // Individual Campaign or Promotion Detail
│       ├── flash-sales/
│       │   └── page.tsx              // Dedicated Flash Sales Page
│       ├── clearance/
│       │   └── page.tsx              // Dedicated Stock Clearance Page
│       └── deals-of-the-day/
│           └── page.tsx              // Dedicated Deals of the Day Page
└── (seller)/
    └── account/
        └── my-ads/
            └── [id]/
                ├── promote/
                │   └── page.tsx      // "Promote this Ad" Modal / Page
                └── add-to-promo/
                    └── page.tsx      // Submit Item to Active Promotion
```

---

## 2. API Service Configuration

Create `src/services/offerService.ts`:

```typescript
import axios from 'axios';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://your-domain.com/api';

export interface LocationFilter {
  country?: string;
  state?: string;
  city?: string;
  area_id?: number;
  latitude?: number;
  longitude?: number;
  radius?: number;
}

export const offerService = {
  // Public Offers & Campaigns
  getCampaigns: async () => {
    const res = await axios.get(`${API_BASE}/offers/campaigns?status=active`);
    return res.data.data;
  },

  getCampaignDetail: async (slugOrId: string | number) => {
    const key = typeof slugOrId === 'number' ? 'id' : 'slug';
    const res = await axios.get(`${API_BASE}/offers/campaign-detail?${key}=${slugOrId}`);
    return res.data.data;
  },

  getFlashSales: async (loc?: LocationFilter) => {
    const res = await axios.get(`${API_BASE}/offers/flash-sales`, { params: loc });
    return res.data.data;
  },

  getDealsOfTheDay: async (loc?: LocationFilter) => {
    const res = await axios.get(`${API_BASE}/offers/deals-of-the-day`, { params: loc });
    return res.data.data;
  },

  getClearanceSales: async (loc?: LocationFilter, sortBy?: string) => {
    const res = await axios.get(`${API_BASE}/offers/clearance-sales`, {
      params: { ...loc, sort_by: sortBy }
    });
    return res.data.data;
  },

  getSpotlightAds: async (loc?: LocationFilter) => {
    const res = await axios.get(`${API_BASE}/offers/spotlight-ads`, { params: loc });
    return res.data.data;
  },

  // Seller Operations (Requires Auth Token)
  getAvailablePromotions: async (token: string) => {
    const res = await axios.get(`${API_BASE}/seller/promotions/available`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    return res.data.data;
  },

  addPromotionItem: async (
    token: string,
    payload: {
      promotion_id: number;
      item_id: number;
      discounted_price: number;
      stock_quantity?: number;
      valid_until?: string;
    }
  ) => {
    const res = await axios.post(`${API_BASE}/seller/promotions/add-item`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });
    return res.data;
  },

  getAdPromotionOptions: async (token: string, itemId: number) => {
    const res = await axios.get(`${API_BASE}/seller/items/promotion-options?item_id=${itemId}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    return res.data.data;
  },

  promoteAd: async (
    token: string,
    payload: {
      item_id: number;
      promotion_type: 'daily_bump_up' | 'top_ad' | 'spotlight';
      duration_days?: number;
    }
  ) => {
    const res = await axios.post(`${API_BASE}/seller/items/promote`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });
    return res.data;
  }
};
```

---

## 3. High-Performance Countdown Timer Hook & Component

Create `src/components/offers/CountdownTimer.tsx`:

```tsx
'use client';

import React, { useEffect, useState } from 'react';

interface CountdownTimerProps {
  initialSeconds: number;
  className?: string;
  onExpire?: () => void;
}

export const CountdownTimer: React.FC<CountdownTimerProps> = ({
  initialSeconds,
  className = '',
  onExpire,
}) => {
  const [remaining, setRemaining] = useState(initialSeconds);

  useEffect(() => {
    if (remaining <= 0) return;

    const interval = setInterval(() => {
      setRemaining((prev) => {
        if (prev <= 1) {
          clearInterval(interval);
          onExpire?.();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [remaining, onExpire]);

  if (remaining <= 0) {
    return (
      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-700">
        Ended
      </span>
    );
  }

  const days = Math.floor(remaining / 86400);
  const hours = Math.floor((remaining % 86400) / 3600);
  const minutes = Math.floor((remaining % 3600) / 60);
  const seconds = remaining % 60;

  const pad = (n: number) => n.toString().padStart(2, '0');

  return (
    <div className={`inline-flex items-center gap-1 font-mono text-sm font-bold text-red-600 ${className}`}>
      <span className="animate-pulse">🔥</span>
      {days > 0 && <span className="bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{days}d</span>}
      <span className="bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{pad(hours)}h</span>
      <span>:</span>
      <span className="bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{pad(minutes)}m</span>
      <span>:</span>
      <span className="bg-red-50 px-1.5 py-0.5 rounded border border-red-200">{pad(seconds)}s</span>
    </div>
  );
};
```

---

## 4. Offer Item Card Component

Create `src/components/offers/OfferItemCard.tsx`:

```tsx
'use client';

import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { CountdownTimer } from './CountdownTimer';

export interface PromotionItem {
  id: number;
  item_id: number;
  original_price: number;
  discounted_price: number;
  discount_percentage: number;
  stock_quantity?: number;
  remaining_quantity?: number;
  countdown_remaining_seconds: number;
  distance_in_km?: number;
  item: {
    name: string;
    slug?: string;
    image?: string;
    city?: string;
    country?: string;
  };
}

export const OfferItemCard: React.FC<{ item: PromotionItem }> = ({ item }) => {
  const percentSaved = Math.round(item.discount_percentage);

  return (
    <div className="group relative rounded-xl border border-gray-100 bg-white p-3 shadow-sm hover:shadow-md transition duration-200">
      {/* Badge & Discount */}
      <div className="absolute top-4 left-4 z-10 flex flex-col gap-1">
        <span className="rounded-md bg-gradient-to-r from-red-600 to-amber-600 px-2 py-1 text-xs font-bold text-white shadow">
          {percentSaved}% OFF
        </span>
      </div>

      {/* Image */}
      <Link href={`/ad/${item.item.slug || item.item_id}`} className="relative block h-48 w-full overflow-hidden rounded-lg bg-gray-100">
        <Image
          src={item.item.image || '/placeholder-item.jpg'}
          alt={item.item.name}
          fill
          className="object-cover group-hover:scale-105 transition duration-300"
        />
      </Link>

      {/* Details */}
      <div className="mt-3 space-y-1.5">
        <div className="flex items-center justify-between">
          <CountdownTimer initialSeconds={item.countdown_remaining_seconds} />
          {item.distance_in_km !== undefined && (
            <span className="text-xs text-gray-500 font-medium">
              📍 {item.distance_in_km < 1 ? '< 1 km' : `${item.distance_in_km.toFixed(1)} km`}
            </span>
          )}
        </div>

        <h3 className="line-clamp-1 font-semibold text-gray-900 group-hover:text-primary transition">
          <Link href={`/ad/${item.item.slug || item.item_id}`}>{item.item.name}</Link>
        </h3>

        {/* Pricing */}
        <div className="flex items-baseline gap-2 pt-1">
          <span className="text-lg font-extrabold text-primary">
            ${item.discounted_price.toLocaleString()}
          </span>
          <span className="text-sm text-gray-400 line-through">
            ${item.original_price.toLocaleString()}
          </span>
        </div>

        {/* Stock status (if applicable) */}
        {item.remaining_quantity !== null && item.remaining_quantity !== undefined && (
          <div className="pt-2">
            <div className="flex justify-between text-xs text-gray-500 mb-1">
              <span>Remaining:</span>
              <span className="font-bold text-amber-600">{item.remaining_quantity} left</span>
            </div>
            <div className="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
              <div
                className="h-full bg-amber-500 rounded-full"
                style={{
                  width: `${Math.min(100, (item.remaining_quantity / (item.stock_quantity || item.remaining_quantity)) * 100)}%`,
                }}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
```

---

## 5. Main Offer Zone Page (`/offers/page.tsx`)

```tsx
import React from 'react';
import { offerService } from '@/services/offerService';
import { OfferItemCard } from '@/components/offers/OfferItemCard';
import Link from 'next/link';
import Image from 'next/image';

export const revalidate = 60; // ISR cache revalidation every 60s

export default async function OffersPage() {
  const [campaigns, flashSales, dealsOfTheDay, clearanceSales, spotlightAds] = await Promise.all([
    offerService.getCampaigns(),
    offerService.getFlashSales(),
    offerService.getDealsOfTheDay(),
    offerService.getClearanceSales(),
    offerService.getSpotlightAds(),
  ]);

  return (
    <main className="min-h-screen bg-slate-50 py-8">
      <div className="container mx-auto px-4 max-w-7xl">
        {/* Campaign Hero Carousel / Banners */}
        {campaigns?.length > 0 && (
          <section className="mb-10">
            <div className="overflow-hidden rounded-2xl shadow-lg relative h-64 md:h-80 w-full bg-gradient-to-r from-purple-700 to-indigo-800">
              <Image
                src={campaigns[0].banner_image || '/placeholder-banner.jpg'}
                alt={campaigns[0].title}
                fill
                priority
                className="object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex flex-col justify-end p-6 md:p-10 text-white">
                <span className="inline-block px-3 py-1 bg-amber-400 text-gray-900 rounded-full text-xs font-bold w-fit mb-2">
                  {campaigns[0].highlight_badge || 'Seasonal Offer'}
                </span>
                <h1 className="text-3xl md:text-5xl font-black">{campaigns[0].title}</h1>
                <p className="mt-1 text-sm md:text-base text-gray-200 line-clamp-2 max-w-xl">
                  {campaigns[0].description}
                </p>
              </div>
            </div>
          </section>
        )}

        {/* 1. FLASH SALES */}
        {flashSales?.items?.length > 0 && (
          <section className="mb-12">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-2xl font-black text-gray-900 flex items-center gap-2">
                  ⚡ Flash Sales
                </h2>
                <p className="text-sm text-gray-500">Limited time deals ending soon!</p>
              </div>
              <Link href="/offers/flash-sales" className="text-sm font-semibold text-primary hover:underline">
                View All →
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {flashSales.items.map((item: any) => (
                <OfferItemCard key={item.id} item={item} />
              ))}
            </div>
          </section>
        )}

        {/* 2. DEALS OF THE DAY */}
        {dealsOfTheDay?.items?.length > 0 && (
          <section className="mb-12">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-2xl font-black text-gray-900 flex items-center gap-2">
                  🏷️ Deals of the Day
                </h2>
                <p className="text-sm text-gray-500">Refreshes every night at midnight</p>
              </div>
              <Link href="/offers/deals-of-the-day" className="text-sm font-semibold text-primary hover:underline">
                View All →
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {dealsOfTheDay.items.map((item: any) => (
                <OfferItemCard key={item.id} item={item} />
              ))}
            </div>
          </section>
        )}

        {/* 3. STOCK CLEARANCE */}
        {clearanceSales?.items?.length > 0 && (
          <section className="mb-12">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-2xl font-black text-gray-900 flex items-center gap-2">
                  📦 Clearance Corner
                </h2>
                <p className="text-sm text-gray-500">Massive markdowns while stocks last</p>
              </div>
              <Link href="/offers/clearance" className="text-sm font-semibold text-primary hover:underline">
                View All →
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {clearanceSales.items.map((item: any) => (
                <OfferItemCard key={item.id} item={item} />
              ))}
            </div>
          </section>
        )}
      </div>
    </main>
  );
}
```

---

## 6. Location Filtering on Next.js Client

When the user selects their location in the header navbar or allows browser Geolocation:

```typescript
navigator.geolocation.getCurrentPosition((pos) => {
  const { latitude, longitude } = pos.coords;
  // Re-fetch or update router query:
  router.push(`/offers?latitude=${latitude}&longitude=${longitude}&radius=30`);
});
```

The backend automatically applies the Haversine formula and returns nearby promotional listings sorted by proximity.
