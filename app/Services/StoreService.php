<?php

namespace App\Services;

use App\Models\SellerRating;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreService
{
    /**
     * Format distance in human readable format (meters or kilometers)
     *
     * @param float|null $distanceInKm Distance in kilometers
     * @return array|null ['meters' => int, 'kilometers' => float, 'formatted' => string]
     */
    public static function formatDistance(?float $distanceInKm): ?array
    {
        if ($distanceInKm === null) {
            return null;
        }

        $meters = (int) round($distanceInKm * 1000);
        $km = round($distanceInKm, 2);

        $formatted = $meters < 1000
            ? $meters . ' m'
            : $km . ' km';

        return [
            'meters'     => $meters,
            'kilometers' => $km,
            'formatted'  => $formatted,
        ];
    }

    /**
     * Generate unique store slug from name
     */
    public static function generateSlug(string $name, ?int $ignoreStoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'store-' . Str::random(6);
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Store::where('slug', $slug)
                ->when($ignoreStoreId, fn($q) => $q->where('id', '!=', $ignoreStoreId))
                ->withTrashed()
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Upload or replace store logo
     */
    public static function handleLogoUpload(UploadedFile $file, ?Store $store = null): string
    {
        $oldLogo = $store ? $store->getRawOriginal('logo') : null;
        if (!empty($oldLogo)) {
            return FileService::compressAndReplace($file, 'store_logos', $oldLogo);
        }
        return FileService::compressAndUpload($file, 'store_logos');
    }

    /**
     * Upload or replace store banner
     */
    public static function handleBannerUpload(UploadedFile $file, ?Store $store = null): string
    {
        $oldBanner = $store ? $store->getRawOriginal('banner') : null;
        if (!empty($oldBanner)) {
            return FileService::compressAndReplace($file, 'store_banners', $oldBanner);
        }
        return FileService::compressAndUpload($file, 'store_banners');
    }

    /**
     * Compute comprehensive store ratings & statistics
     */
    public static function getStoreStats(Store $store): array
    {
        $userId = $store->user_id;

        // Items count
        $activeItemsCount = $store->items()
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            })
            ->count();

        $totalItemsCount = $store->items()->count();

        // Ratings & reviews summary
        $ratingQuery = SellerRating::where('seller_id', $userId);
        $totalReviews = (clone $ratingQuery)->count();
        $averageRating = round((clone $ratingQuery)->avg('ratings') ?? 0, 1);

        $ratingsCount = [
            1 => (clone $ratingQuery)->where('ratings', 1)->count(),
            2 => (clone $ratingQuery)->where('ratings', 2)->count(),
            3 => (clone $ratingQuery)->where('ratings', 3)->count(),
            4 => (clone $ratingQuery)->where('ratings', 4)->count(),
            5 => (clone $ratingQuery)->where('ratings', 5)->count(),
        ];

        // Followers count
        $followersCount = $store->user ? $store->user->followers()->count() : 0;

        return [
            'active_items_count' => $activeItemsCount,
            'total_items_count'  => $totalItemsCount,
            'average_rating'     => $averageRating,
            'total_reviews'      => $totalReviews,
            'ratings_breakdown'  => $ratingsCount,
            'followers_count'    => $followersCount,
        ];
    }

    /**
     * Transform a Store model into a complete, standard API array
     */
    public static function formatStoreData(Store $store, ?float $userLat = null, ?float $userLng = null): array
    {
        $distanceInfo = null;
        if (isset($store->distance)) {
            $distanceInfo = self::formatDistance((float)$store->distance);
        } elseif ($userLat !== null && $userLng !== null && $store->latitude && $store->longitude) {
            $calculatedDistance = self::calculateHaversineDistance($userLat, $userLng, (float)$store->latitude, (float)$store->longitude);
            $distanceInfo = self::formatDistance($calculatedDistance);
        }

        $stats = self::getStoreStats($store);

        return [
            'id'                 => $store->id,
            'user_id'            => $store->user_id,
            'name'               => $store->name,
            'slug'               => $store->slug,
            'description'        => $store->description,
            'logo'               => $store->logo,
            'banner'             => $store->banner,
            'email'              => $store->email,
            'contact'            => $store->contact,
            'country_code'       => $store->country_code,
            'address'            => $store->address,
            'latitude'           => $store->latitude,
            'longitude'          => $store->longitude,
            'country'            => $store->country,
            'state'              => $store->state,
            'city'               => $store->city,
            'area_id'            => $store->area_id,
            'area'               => $store->relationLoaded('area') && $store->area ? [
                'id'   => $store->area->id,
                'name' => $store->area->name,
            ] : null,
            'website'            => $store->website,
            'tax_number'         => $store->tax_number,
            'opening_time'       => $store->opening_time,
            'closing_time'       => $store->closing_time,
            'working_days'       => $store->working_days,
            'social_links'       => $store->social_links,
            'status'             => $store->status,
            'is_verified'        => (bool) $store->is_verified,
            'distance'           => $distanceInfo,
            'stats'              => $stats,
            'owner'              => $store->relationLoaded('user') && $store->user ? [
                'id'           => $store->user->id,
                'name'         => $store->user->name,
                'profile'      => $store->user->profile,
                'country_code' => $store->user->country_code,
                'is_verified'  => (bool) $store->user->is_verified,
            ] : null,
            'created_at'         => $store->created_at?->toIso8601String(),
            'updated_at'         => $store->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Compute Haversine distance in KM between two lat/lng pairs
     */
    public static function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
