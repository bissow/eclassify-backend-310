<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\SellerQrCode;
use App\Models\Store;
use App\Services\CachingService;
use App\Services\SellerQrCodeService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class SellerQrWebController extends Controller
{
    /**
     * Handle universal QR scan visit from web browser or phone camera
     */
    public function handleQrVisit(Request $request, string $token)
    {
        // Lookup QR code by token or store slug
        $qrCode = SellerQrCode::with(['store', 'user'])
            ->where('qr_code_token', $token)
            ->first();

        $store = null;
        if ($qrCode) {
            $store = $qrCode->store;
            // Record scan analytics
            $qrCode->recordScan();
        } else {
            // Check if token matches a store slug directly
            $store = Store::where('slug', $token)->orWhere('id', $token)->first();
            if ($store) {
                $qrCode = SellerQrCode::where('store_id', $store->id)->first();
                if ($qrCode) {
                    $qrCode->recordScan();
                }
            }
        }

        if (!$store) {
            abort(404, __('Store or digital catalog not found.'));
        }

        // If Next.js web frontend URL is configured and user requested redirect to Next.js
        $webUrl = CachingService::getSystemSettings('web_url');
        if ($request->boolean('redirect_frontend', false) && !empty($webUrl)) {
            $redirectUrl = rtrim($webUrl, '/') . '/store-qr/' . $token;
            return redirect()->away($redirectUrl);
        }

        $userLat = $request->filled('lat') ? (float)$request->lat : null;
        $userLng = $request->filled('lng') ? (float)$request->lng : null;
        $userCity = $request->input('city');
        $userState = $request->input('state');

        // Location mismatch calculation
        $locationWarning = SellerQrCodeService::calculateLocationMismatch($userLat, $userLng, $store, $userCity, $userState);

        // Fetch distinct categories published by this store
        $categoryIds = Item::where('user_id', $store->user_id)
            ->where('status', 'approved')
            ->getNonExpiredItems()
            ->pluck('category_id')
            ->unique()
            ->toArray();

        $categories = Category::whereIn('id', $categoryIds)->select('id', 'name', 'slug')->get();

        // Query Items
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $sortBy = $request->input('sort_by', 'newest');

        $itemsQuery = Item::with(['category', 'gallery_images', 'currency'])
            ->where('user_id', $store->user_id)
            ->where('status', 'approved')
            ->getNonExpiredItems();

        if (!empty($search)) {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($categoryId)) {
            $itemsQuery->where('category_id', $categoryId);
        }

        switch ($sortBy) {
            case 'price_asc':
                $itemsQuery->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $itemsQuery->orderBy('price', 'desc');
                break;
            case 'popular':
                $itemsQuery->orderBy('clicks', 'desc');
                break;
            case 'newest':
            default:
                $itemsQuery->orderBy('created_at', 'desc');
                break;
        }

        $items = $itemsQuery->paginate(12)->withQueryString();
        $storeStats = StoreService::getStoreStats($store);
        $settings = SellerQrCodeService::getEffectiveSettings();
        $currencySymbol = CachingService::getSystemSettings('currency_symbol') ?: '$';
        $appName = CachingService::getSystemSettings('company_name') ?: 'Eclassify';
        $appLogo = CachingService::getSystemSettings('company_logo') ?: asset('assets/images/logo/sidebar_logo.png');
        $deepLinkScheme = CachingService::getSystemSettings('depp_link_scheme') ?: 'eclassify';
        $deepLinkUrl = $deepLinkScheme . '://store-qr/' . ($qrCode ? $qrCode->qr_code_token : $store->slug);

        return view('seller_qr.catalog_view', compact(
            'store',
            'qrCode',
            'locationWarning',
            'categories',
            'items',
            'storeStats',
            'settings',
            'currencySymbol',
            'appName',
            'appLogo',
            'deepLinkUrl',
            'search',
            'categoryId',
            'sortBy',
            'request'
        ));
    }
}
