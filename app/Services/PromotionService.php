<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Item;
use App\Models\ItemAdPromotion;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\User;
use App\Models\UserPurchasedPackage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * Check if user is verified and has subscription rights
     */
    public static function checkUserEligibility(User $user, string $feature = 'promotions'): array
    {
        // 1. Must be verified
        if (!$user->is_verified) {
            return [
                'eligible' => false,
                'message'  => __('Only verified sellers can add items to promotions and promote ads. Please verify your account first.'),
                'package'  => null,
            ];
        }

        // 2. Must have active package with entitlement
        $activePackages = UserPurchasedPackage::where('user_id', $user->id)
            ->whereDate('start_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereDate('end_date', '>=', Carbon::today())->orWhereNull('end_date');
            })
            ->with('package')
            ->get();

        $matchingPackage = null;

        foreach ($activePackages as $userPkg) {
            $pkg = $userPkg->package;
            if (!$pkg) {
                continue;
            }

            if ($feature === 'promotions' && $userPkg->canAddPromotionItem()) {
                $matchingPackage = $userPkg;
                break;
            }

            if ($feature === 'daily_bump_up' && $userPkg->canUseDailyBump()) {
                $matchingPackage = $userPkg;
                break;
            }

            if ($feature === 'top_ad' && $userPkg->canUseTopAd()) {
                $matchingPackage = $userPkg;
                break;
            }

            if ($feature === 'spotlight' && $userPkg->canUseSpotlight()) {
                $matchingPackage = $userPkg;
                break;
            }
        }

        if (!$matchingPackage) {
            $featureName = match ($feature) {
                'promotions'    => __('Promotions & Campaigns'),
                'daily_bump_up' => __('Daily Bump Up'),
                'top_ad'        => __('Top Ad'),
                'spotlight'     => __('Spotlight Promotion'),
                default         => __('Promotional Feature'),
            };

            return [
                'eligible' => false,
                'message'  => __("Your current subscription package does not include :feature or your limit has been reached. Please upgrade your package.", ['feature' => $featureName]),
                'package'  => null,
            ];
        }

        return [
            'eligible' => true,
            'message'  => null,
            'package'  => $matchingPackage,
        ];
    }

    /**
     * Submit an item into an active promotion
     */
    public static function submitItemToPromotion(User $user, Item $item, Promotion $promotion, array $data): PromotionItem
    {
        // 1. Verify item ownership & status
        if ($item->user_id !== $user->id) {
            throw new Exception(__('You do not own this advertisement.'));
        }

        if ($item->getRawOriginal('status') !== 'approved') {
            throw new Exception(__('Only approved advertisements can be added to promotions.'));
        }

        if ($item->expiry_date && Carbon::parse($item->expiry_date)->isPast()) {
            throw new Exception(__('This advertisement has expired. Please renew it first.'));
        }

        // 2. Verify promotion is currently active
        if ($promotion->status !== 'active') {
            throw new Exception(__('This promotion is not currently active.'));
        }

        $today = Carbon::today();
        if ($promotion->start_date > $today || $promotion->end_date < $today) {
            throw new Exception(__('This promotion is outside its active date window.'));
        }

        // 3. Verify user verification & package quota
        $eligibility = self::checkUserEligibility($user, 'promotions');
        if (!$eligibility['eligible']) {
            throw new Exception($eligibility['message']);
        }
        $userPackage = $eligibility['package'];

        // 4. Check if item is already actively listed in this promotion
        $existing = PromotionItem::where('promotion_id', $promotion->id)
            ->where('item_id', $item->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if ($existing) {
            throw new Exception(__('This advertisement is already submitted to this promotion.'));
        }

        // 5. Price & Discount calculation
        $originalPrice = (float) $item->price;
        $discountType = $data['discount_type'] ?? ($promotion->discount_type ?: 'percentage');
        $discountValue = isset($data['discount_value']) ? (float) $data['discount_value'] : (float) ($promotion->discount ?? 0);
        $promotionalPrice = 0;

        if ($discountType === 'percentage') {
            if ($discountValue <= 0 || $discountValue >= 100) {
                throw new Exception(__('Discount percentage must be between 1% and 99%.'));
            }
            $discountAmount = ($originalPrice * $discountValue) / 100;
            $promotionalPrice = round($originalPrice - $discountAmount, 2);
        } else {
            // Flat amount
            if (isset($data['promotional_price']) && (float) $data['promotional_price'] > 0) {
                $promotionalPrice = (float) $data['promotional_price'];
                if ($promotionalPrice >= $originalPrice) {
                    throw new Exception(__('Promotional price must be less than original price (:price).', ['price' => $originalPrice]));
                }
                $discountValue = round($originalPrice - $promotionalPrice, 2);
            } else {
                if ($discountValue <= 0 || $discountValue >= $originalPrice) {
                    throw new Exception(__('Discount amount must be greater than 0 and less than original price.'));
                }
                $promotionalPrice = round($originalPrice - $discountValue, 2);
            }
        }

        // 6. Stock input
        $stockQuantity = (int) ($data['stock_quantity'] ?? 1);
        if ($stockQuantity < 1) {
            throw new Exception(__('Stock quantity must be at least 1 unit.'));
        }

        // 7. Validity date
        $validUntil = null;
        if (!empty($data['valid_until'])) {
            $validUntil = Carbon::parse($data['valid_until']);
            $promoEnd = $promotion->getEndDateTime();
            if ($validUntil->greaterThan($promoEnd)) {
                $validUntil = $promoEnd;
            }
        } else {
            $validUntil = $promotion->getEndDateTime();
        }

        return DB::transaction(function () use ($user, $item, $promotion, $userPackage, $promotionalPrice, $discountValue, $discountType, $stockQuantity, $validUntil) {
            // Increment package used promotions count
            $userPackage->used_promotions_limit++;
            $userPackage->save();

            // Create promotion item
            return PromotionItem::create([
                'promotion_id'              => $promotion->id,
                'item_id'                   => $item->id,
                'user_id'                   => $user->id,
                'user_purchased_package_id' => $userPackage->id,
                'promotional_price'         => $promotionalPrice,
                'discount_value'            => $discountValue,
                'discount_type'             => $discountType,
                'stock_quantity'            => $stockQuantity,
                'remaining_stock_quantity'  => $stockQuantity,
                'valid_until'               => $validUntil,
                'status'                    => 'active',
            ]);
        });
    }

    /**
     * Apply "Promote this Ad" (Daily Bump Up, Top Ad, Spotlight)
     */
    public static function promoteAd(User $user, Item $item, string $promotionType, array $options = []): ItemAdPromotion
    {
        if (!in_array($promotionType, ['daily_bump_up', 'top_ad', 'spotlight'])) {
            throw new Exception(__('Invalid promotion type specified.'));
        }

        if ($item->user_id !== $user->id) {
            throw new Exception(__('You do not own this advertisement.'));
        }

        if ($item->getRawOriginal('status') !== 'approved') {
            throw new Exception(__('Only approved advertisements can be promoted.'));
        }

        $eligibility = self::checkUserEligibility($user, $promotionType);
        if (!$eligibility['eligible']) {
            throw new Exception($eligibility['message']);
        }
        $userPackage = $eligibility['package'];

        $durationDays = (int) ($options['days'] ?? ($userPackage->package->listing_duration_days ?? 7));
        if ($durationDays < 1) {
            $durationDays = 7;
        }

        $now = Carbon::now();
        $endDate = (clone $now)->addDays($durationDays);

        return DB::transaction(function () use ($user, $item, $userPackage, $promotionType, $now, $endDate) {
            // Cancel/replace existing active promotion of this type for this item
            ItemAdPromotion::where('item_id', $item->id)
                ->where('promotion_type', $promotionType)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            // Increment usage in package
            if ($promotionType === 'daily_bump_up') {
                $userPackage->used_daily_bump_up_limit++;
            } elseif ($promotionType === 'top_ad') {
                $userPackage->used_top_ad_limit++;
            } elseif ($promotionType === 'spotlight') {
                $userPackage->used_spotlight_limit++;
            }
            $userPackage->save();

            // If Daily Bump Up, immediately bump published_at to now
            if ($promotionType === 'daily_bump_up') {
                $item->published_at = $now;
                $item->renewed_at = $now;
                $item->save();
            }

            return ItemAdPromotion::create([
                'item_id'                   => $item->id,
                'user_id'                   => $user->id,
                'user_purchased_package_id' => $userPackage->id,
                'promotion_type'            => $promotionType,
                'start_date'                => $now,
                'end_date'                  => $endDate,
                'bump_frequency'            => $promotionType === 'daily_bump_up' ? 'daily' : null,
                'last_bumped_at'            => $promotionType === 'daily_bump_up' ? $now : null,
                'status'                    => 'active',
            ]);
        });
    }

    /**
     * Get user's available quota and options for "Promote this Ad"
     */
    public static function getAdPromotionOptions(User $user, Item $item): array
    {
        $bumpQuota = 0;
        $topAdQuota = 0;
        $spotlightQuota = 0;

        $activePackages = UserPurchasedPackage::where('user_id', $user->id)
            ->whereDate('start_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereDate('end_date', '>=', Carbon::today())->orWhereNull('end_date');
            })
            ->with('package')
            ->get();

        foreach ($activePackages as $userPkg) {
            $pkg = $userPkg->package;
            if (!$pkg) {
                continue;
            }

            if ($pkg->allowsDailyBumpUp()) {
                $limit = $pkg->daily_bump_up_limit;
                $rem = is_null($limit) || $limit === 0 ? 9999 : max(0, $limit - $userPkg->used_daily_bump_up_limit);
                $bumpQuota += $rem;
            }

            if ($pkg->allowsTopAd()) {
                $limit = $pkg->top_ad_limit;
                $rem = is_null($limit) || $limit === 0 ? 9999 : max(0, $limit - $userPkg->used_top_ad_limit);
                $topAdQuota += $rem;
            }

            if ($pkg->allowsSpotlight()) {
                $limit = $pkg->spotlight_limit;
                $rem = is_null($limit) || $limit === 0 ? 9999 : max(0, $limit - $userPkg->used_spotlight_limit);
                $spotlightQuota += $rem;
            }
        }

        $activePromotions = ItemAdPromotion::where('item_id', $item->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now());
            })
            ->pluck('promotion_type')
            ->toArray();

        $hasActivePackage = $activePackages->isNotEmpty();
        $totalQuota = $bumpQuota + $topAdQuota + $spotlightQuota;

        $requiresVerification = !$user->is_verified;
        $requiresPackage = $user->is_verified && (!$hasActivePackage || $totalQuota <= 0);

        $statusMessage = null;
        if ($requiresVerification) {
            $statusMessage = __('Only verified sellers can promote advertisements. Please verify your account first.');
        } elseif ($requiresPackage) {
            $statusMessage = __('Your subscription package does not have promotional credits available. Please subscribe to an active package to promote this ad.');
        }

        return [
            'is_verified'           => (bool) $user->is_verified,
            'requires_verification' => $requiresVerification,
            'requires_package'      => $requiresPackage,
            'has_active_package'    => $hasActivePackage,
            'has_quota'             => $totalQuota > 0,
            'message'               => $statusMessage,
            'can_bump'              => $user->is_verified && $bumpQuota > 0,
            'bump_remaining'        => $bumpQuota >= 9999 ? 'unlimited' : $bumpQuota,
            'can_top_ad'            => $user->is_verified && $topAdQuota > 0,
            'top_ad_remaining'      => $topAdQuota >= 9999 ? 'unlimited' : $topAdQuota,
            'can_spotlight'         => $user->is_verified && $spotlightQuota > 0,
            'spotlight_remaining'   => $spotlightQuota >= 9999 ? 'unlimited' : $spotlightQuota,
            'options'               => [
                [
                    'type'        => 'daily_bump_up',
                    'title'       => __('Daily Bump Up'),
                    'tagline'     => __('Get a fresh start every day and get up to 10 times more responses!'),
                    'icon'        => 'ph ph-rocket-launch',
                    'available'   => $user->is_verified && $bumpQuota > 0,
                    'quota_left'  => $bumpQuota >= 9999 ? 'unlimited' : $bumpQuota,
                    'is_active'   => in_array('daily_bump_up', $activePromotions),
                ],
                [
                    'type'        => 'top_ad',
                    'title'       => __('Top Ad'),
                    'tagline'     => __('Get up to 5 times more views by displaying your ad at the top!'),
                    'icon'        => 'ph ph-arrow-fat-line-up',
                    'available'   => $user->is_verified && $topAdQuota > 0,
                    'quota_left'  => $topAdQuota >= 9999 ? 'unlimited' : $topAdQuota,
                    'is_active'   => in_array('top_ad', $activePromotions),
                ],
                [
                    'type'        => 'spotlight',
                    'title'       => __('Spotlight'),
                    'tagline'     => __('Boost sales by showing your ad in this premium section!'),
                    'icon'        => 'ph ph-sparkle',
                    'available'   => $user->is_verified && $spotlightQuota > 0,
                    'quota_left'  => $spotlightQuota >= 9999 ? 'unlimited' : $spotlightQuota,
                    'is_active'   => in_array('spotlight', $activePromotions),
                ],
            ],
        ];
    }

    /**
     * Nightly / Scheduled task: Process daily bumps and expire outdated items
     */
    public static function processScheduledBumpsAndExpirations(): array
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $bumpCount = 0;
        $expiredCount = 0;

        // 1. Expire outdated ItemAdPromotions
        $expiredCount += ItemAdPromotion::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $now)
            ->update(['status' => 'expired']);

        // 2. Execute Daily Bump Up for active daily bump promotions
        $dailyBumps = ItemAdPromotion::where('status', 'active')
            ->where('promotion_type', 'daily_bump_up')
            ->where(function ($q) use ($today) {
                $q->whereNull('last_bumped_at')
                  ->orWhereDate('last_bumped_at', '<', $today);
            })
            ->with('item')
            ->get();

        foreach ($dailyBumps as $promo) {
            if ($promo->item && $promo->item->getRawOriginal('status') === 'approved') {
                $promo->item->published_at = $now;
                $promo->item->renewed_at = $now;
                $promo->item->save();

                $promo->last_bumped_at = $now;
                $promo->save();
                $bumpCount++;
            }
        }

        // 3. Expire zero-stock or outdated PromotionItems
        $expiredCount += PromotionItem::where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->where('remaining_stock_quantity', '<=', 0)
                  ->orWhere(function ($sub) use ($now) {
                      $sub->whereNotNull('valid_until')->where('valid_until', '<', $now);
                  });
            })
            ->update(['status' => 'expired']);

        // 4. Expire past Campaigns and Promotions
        Campaign::where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'expired']);

        Promotion::where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'expired']);

        return [
            'bumped_items'  => $bumpCount,
            'expired_items' => $expiredCount,
        ];
    }
}
