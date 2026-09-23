<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PromotionItemResource;
use App\Http\Resources\PromotionResource;
use App\Models\Item;
use App\Models\ItemAdPromotion;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Services\PromotionService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SellerPromotionApiController extends BaseApiController
{
    /**
     * List active promotions open for item submissions with user's quota
     */
    public function getAvailablePromotions(Request $request)
    {
        try {
            $user = Auth::user();
            $eligibility = PromotionService::checkUserEligibility($user, 'promotions');

            $promotions = Promotion::active()
                ->currentlyRunning()
                ->with(['campaign', 'translations'])
                ->withCount(['active_items as total_active_items'])
                ->orderBy('priority', 'asc')
                ->get();

            $userPackage = $eligibility['package'];
            $remainingQuota = '0';

            if ($userPackage && $userPackage->package) {
                $limit = $userPackage->package->promotion_item_limit;
                $remainingQuota = is_null($limit) || $limit === 0
                    ? 'unlimited'
                    : (string) max(0, $limit - $userPackage->used_promotions_limit);
            }

            $requiresVerification = !$user->is_verified;
            $requiresPackage = $user->is_verified && (!$eligibility['package'] || $remainingQuota === '0');

            return ResponseService::successResponse(
                __('Available promotions fetched successfully'),
                [
                    'is_verified'           => (bool) $user->is_verified,
                    'requires_verification' => $requiresVerification,
                    'requires_package'      => $requiresPackage,
                    'can_participate'       => $eligibility['eligible'],
                    'eligibility_msg'       => $eligibility['message'],
                    'remaining_quota'       => $remainingQuota,
                    'promotions'            => PromotionResource::collection($promotions),
                ]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> getAvailablePromotions');
            return ResponseService::errorResponse(__('Failed to fetch available promotions'));
        }
    }

    /**
     * List user's submitted promotion items
     */
    public function getMyPromotionItems(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = (int) ($request->limit ?? 15);
            $page = (int) ($request->page ?? 1);

            $query = PromotionItem::where('user_id', $user->id)
                ->with(['promotion.translations', 'item.currency', 'item.gallery_images'])
                ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
                ->when($request->filled('promotion_id'), fn($q) => $q->where('promotion_id', $request->promotion_id))
                ->orderBy('id', 'desc');

            $items = $query->paginate($limit, ['*'], 'page', $page);

            return ResponseService::successResponse(
                __('Your promotional items fetched successfully'),
                PromotionItemResource::collection($items)->response()->getData(true)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> getMyPromotionItems');
            return ResponseService::errorResponse(__('Failed to fetch your promotional items'));
        }
    }

    /**
     * Submit an advertisement to an active promotion
     */
    public function addPromotionItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'promotion_id'      => 'required|integer',
                'item_id'           => 'required|integer',
                'discount_type'     => 'nullable|in:percentage,flat',
                'discount_value'    => 'nullable|numeric|min:0.01',
                'promotional_price' => 'nullable|numeric|min:0.01',
                'stock_quantity'    => 'required|integer|min:1',
                'valid_until'       => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $item = Item::where('id', $request->item_id)->first();
            if (!$item) {
                return ResponseService::errorResponse(__('Advertisement not found'));
            }

            $promotion = Promotion::where('id', $request->promotion_id)->first();
            if (!$promotion) {
                return ResponseService::errorResponse(__('Promotion not found'));
            }

            $promotionItem = PromotionService::submitItemToPromotion($user, $item, $promotion, $request->all());

            return ResponseService::successResponse(
                __('Advertisement successfully added to promotion!'),
                new PromotionItemResource($promotionItem->load(['promotion', 'item']))
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> addPromotionItem');
            return ResponseService::errorResponse($th->getMessage() ?: __('Failed to add advertisement to promotion'));
        }
    }

    /**
     * Update promotional item details (price, stock, valid_until)
     */
    public function updatePromotionItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'                       => 'required|integer',
                'promotional_price'        => 'nullable|numeric|min:0.01',
                'remaining_stock_quantity' => 'nullable|integer|min:0',
                'valid_until'              => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $promoItem = PromotionItem::where('id', $request->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$promoItem) {
                return ResponseService::errorResponse(__('Promotional item not found or you do not have permission.'));
            }

            if ($request->filled('promotional_price')) {
                $originalPrice = (float) ($promoItem->item->price ?? 0);
                $newPromoPrice = (float) $request->promotional_price;
                if ($newPromoPrice >= $originalPrice) {
                    return ResponseService::errorResponse(__('Promotional price must be less than original price (:price)', ['price' => $originalPrice]));
                }
                $promoItem->promotional_price = $newPromoPrice;
                $promoItem->discount_value = round($originalPrice - $newPromoPrice, 2);
            }

            if ($request->has('remaining_stock_quantity')) {
                $promoItem->remaining_stock_quantity = (int) $request->remaining_stock_quantity;
                if ($promoItem->remaining_stock_quantity <= 0) {
                    $promoItem->status = 'sold_out';
                } elseif ($promoItem->status === 'sold_out' && $promoItem->remaining_stock_quantity > 0) {
                    $promoItem->status = 'active';
                }
            }

            if ($request->filled('valid_until')) {
                $promoItem->valid_until = Carbon::parse($request->valid_until);
            }

            $promoItem->save();

            return ResponseService::successResponse(
                __('Promotional item updated successfully'),
                new PromotionItemResource($promoItem->load(['promotion', 'item']))
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> updatePromotionItem');
            return ResponseService::errorResponse($th->getMessage() ?: __('Failed to update promotional item'));
        }
    }

    /**
     * Toggle promotion item status between active and inactive
     */
    public function togglePromotionItemStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'     => 'required|integer',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $promoItem = PromotionItem::where('id', $request->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$promoItem) {
                return ResponseService::errorResponse(__('Promotional item not found'));
            }

            if ($request->status === 'active' && $promoItem->remaining_stock_quantity <= 0) {
                return ResponseService::errorResponse(__('Cannot activate item with 0 remaining stock. Please update stock first.'));
            }

            $promoItem->status = $request->status;
            $promoItem->save();

            return ResponseService::successResponse(
                __('Status updated successfully'),
                ['id' => $promoItem->id, 'status' => $promoItem->status]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> togglePromotionItemStatus');
            return ResponseService::errorResponse(__('Failed to toggle status'));
        }
    }

    /**
     * Soft delete item from promotion
     */
    public function deletePromotionItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $promoItem = PromotionItem::where('id', $request->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$promoItem) {
                return ResponseService::errorResponse(__('Promotional item not found'));
            }

            $promoItem->delete();

            return ResponseService::successResponse(__('Promotional item removed successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> deletePromotionItem');
            return ResponseService::errorResponse(__('Failed to delete promotional item'));
        }
    }

    /**
     * Get options & quotas for "Promote this Ad"
     */
    public function getAdPromotionOptions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $item = Item::where('id', $request->item_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$item) {
                return ResponseService::errorResponse(__('Advertisement not found or you do not own it.'));
            }

            $options = PromotionService::getAdPromotionOptions($user, $item);

            return ResponseService::successResponse(
                __('Ad promotion options fetched successfully'),
                $options
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> getAdPromotionOptions');
            return ResponseService::errorResponse(__('Failed to fetch promotion options'));
        }
    }

    /**
     * Apply "Promote this Ad" (Daily Bump Up, Top Ad, Spotlight)
     */
    public function promoteAd(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id'        => 'required|integer',
                'promotion_type' => 'required|in:daily_bump_up,top_ad,spotlight',
                'days'           => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $item = Item::where('id', $request->item_id)->first();
            if (!$item) {
                return ResponseService::errorResponse(__('Advertisement not found'));
            }

            $adPromotion = PromotionService::promoteAd(
                $user,
                $item,
                $request->promotion_type,
                ['days' => $request->days]
            );

            return ResponseService::successResponse(
                __('Ad successfully promoted with :type!', ['type' => $adPromotion->type_title]),
                $adPromotion
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> promoteAd');
            return ResponseService::errorResponse($th->getMessage() ?: __('Failed to promote advertisement'));
        }
    }

    /**
     * Get seller's comprehensive promotions & campaigns performance statistics
     */
    public function getPromotionsAnalytics(Request $request)
    {
        try {
            $userId = $request->filled('user_id') ? (int) $request->user_id : Auth::id();
            if (!$userId) {
                return ResponseService::errorResponse(__('User not authenticated'));
            }

            // 1. All Promotion Items (Flash Sales, Clearance, Deal of the Day, Campaigns)
            $allPromoItems = PromotionItem::where('user_id', $userId)
                ->with(['promotion.campaign', 'item.currency'])
                ->get();

            // 2. All Boosts (Top Ad, Spotlight, Daily Bump)
            $allBoosts = ItemAdPromotion::where('user_id', $userId)
                ->with(['item.currency'])
                ->get();

            $activePromoItems = $allPromoItems->filter(fn($pi) => $pi->is_available);
            $activeBoosts = $allBoosts->filter(fn($b) => $b->is_active);

            // Compute metrics
            $totalSalesItems = $allPromoItems->count();
            $totalBoosts = $allBoosts->count();
            $totalPromotedItems = $totalSalesItems + $totalBoosts;

            $totalInitialStock = $allPromoItems->sum('stock_quantity');
            $totalRemainingStock = $allPromoItems->sum('remaining_stock_quantity');
            $totalClaimedUnits = max(0, $totalInitialStock - $totalRemainingStock);

            $estimatedPromoRevenue = 0.0;
            $estimatedSavingsGiven = 0.0;
            foreach ($allPromoItems as $pi) {
                $claimed = max(0, $pi->stock_quantity - $pi->remaining_stock_quantity);
                $estimatedPromoRevenue += ($claimed * (float) $pi->promotional_price);
                $origPrice = (float) ($pi->item?->price ?? 0);
                if ($origPrice > $pi->promotional_price) {
                    $estimatedSavingsGiven += ($claimed * ($origPrice - (float) $pi->promotional_price));
                }
            }

            // Breakdown by promotion type
            $types = ['flash_sale', 'clearance_sale', 'deal_of_the_day', 'custom'];
            $breakdownByType = [];
            foreach ($types as $type) {
                $itemsOfType = $allPromoItems->filter(fn($pi) => $pi->promotion?->promotion_type === $type);
                $claimedOfType = $itemsOfType->sum(fn($pi) => max(0, $pi->stock_quantity - $pi->remaining_stock_quantity));
                $revOfType = $itemsOfType->sum(fn($pi) => max(0, $pi->stock_quantity - $pi->remaining_stock_quantity) * (float) $pi->promotional_price);
                $breakdownByType[$type] = [
                    'total_items'   => $itemsOfType->count(),
                    'active_items'  => $itemsOfType->filter(fn($pi) => $pi->is_available)->count(),
                    'units_claimed' => $claimedOfType,
                    'revenue'       => round($revOfType, 2),
                ];
            }

            // Breakdown of boosts
            $boostTypes = ['daily_bump_up', 'top_ad', 'spotlight'];
            $breakdownByBoost = [];
            foreach ($boostTypes as $bType) {
                $bList = $allBoosts->filter(fn($b) => $b->promotion_type === $bType);
                $breakdownByBoost[$bType] = [
                    'total_count'  => $bList->count(),
                    'active_count' => $bList->filter(fn($b) => $b->is_active)->count(),
                ];
            }

            // Unique campaigns joined
            $campaignsCount = $allPromoItems->map(fn($pi) => $pi->promotion?->campaign_id)->filter()->unique()->count();

            return ResponseService::successResponse(
                __('Promotions analytics fetched successfully'),
                [
                    'summary' => [
                        'total_promoted_ads'        => $totalPromotedItems,
                        'active_promoted_ads'       => $activePromoItems->count() + $activeBoosts->count(),
                        'active_sales_items'        => $activePromoItems->count(),
                        'active_boosts'             => $activeBoosts->count(),
                        'total_units_sold'          => $totalClaimedUnits,
                        'total_promo_revenue'       => round($estimatedPromoRevenue, 2),
                        'total_buyer_savings'       => round($estimatedSavingsGiven, 2),
                        'total_campaigns_joined'    => $campaignsCount,
                    ],
                    'breakdown_by_promotion_type' => $breakdownByType,
                    'breakdown_by_boost_type'     => $breakdownByBoost,
                ]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> getPromotionsAnalytics');
            return ResponseService::errorResponse(__('Failed to fetch promotional analytics'));
        }
    }

    /**
     * Get detailed history of promoted ads and campaigns with performance metrics
     */
    public function getPromotionsHistory(Request $request)
    {
        try {
            $userId = $request->filled('user_id') ? (int) $request->user_id : Auth::id();
            if (!$userId) {
                return ResponseService::errorResponse(__('User not authenticated'));
            }

            $limit = (int) ($request->limit ?? 15);
            if ($limit <= 0) {
                $limit = 15;
            }
            if ($request->filled('offset') && !$request->filled('page')) {
                $page = (int) floor(((int) $request->offset) / $limit) + 1;
            } else {
                $page = (int) ($request->page ?? 1);
            }
            if ($page <= 0) {
                $page = 1;
            }
            $filterType = $request->filter_type ?? 'all'; // all, sales, boosts, flash_sale, clearance_sale, deal_of_the_day, daily_bump_up, top_ad, spotlight

            $now = Carbon::now();
            $records = collect();

            $includeSales = in_array($filterType, ['all', 'sales', 'flash_sale', 'clearance_sale', 'deal_of_the_day', 'custom']);
            $includeBoosts = in_array($filterType, ['all', 'boosts', 'daily_bump_up', 'top_ad', 'spotlight']);

            // 1. Fetch Sales Items
            if ($includeSales) {
                $salesQuery = PromotionItem::where('user_id', $userId)
                    ->with(['promotion.campaign', 'promotion.translations', 'item.currency'])
                    ->when($request->filled('campaign_id'), function ($q) use ($request) {
                        $q->whereHas('promotion', fn($p) => $p->where('campaign_id', $request->campaign_id));
                    })
                    ->when($request->filled('status'), function ($q) use ($request) {
                        $q->where('status', $request->status);
                    })
                    ->when(in_array($filterType, ['flash_sale', 'clearance_sale', 'deal_of_the_day']), function ($q) use ($filterType) {
                        $q->whereHas('promotion', fn($p) => $p->where('promotion_type', $filterType));
                    });

                $salesItems = $salesQuery->get();

                foreach ($salesItems as $pi) {
                    $createdAt = $pi->created_at ?: $now;
                    $endAt = $pi->valid_until ?: $now;
                    $effectiveEnd = $now->gt($endAt) ? $endAt : $now;
                    $daysActive = max(1, $createdAt->diffInDays($effectiveEnd));

                    $claimed = max(0, $pi->stock_quantity - $pi->remaining_stock_quantity);
                    $claimPercentage = $pi->stock_quantity > 0
                        ? min(100, round(($claimed / $pi->stock_quantity) * 100))
                        : 0;

                    $revenue = $claimed * (float) $pi->promotional_price;

                    $records->push([
                        'id'                       => $pi->id,
                        'record_type'              => 'sale',
                        'item_id'                  => $pi->item_id,
                        'item_name'                => $pi->item?->name,
                        'item_slug'                => $pi->item?->slug,
                        'item_image'               => $pi->item?->image,
                        'clicks'                   => (int) ($pi->item?->clicks ?? 0),
                        'views'                    => (int) ($pi->item?->clicks ?? 0),
                        'original_price'           => (float) ($pi->item?->price ?? 0),
                        'promotional_price'        => (float) $pi->promotional_price,
                        'discount_value'           => (float) $pi->discount_value,
                        'discount_type'            => $pi->discount_type,
                        'discount_percentage'      => $pi->discount_percentage,
                        'stock_quantity'           => $pi->stock_quantity,
                        'remaining_stock_quantity' => $pi->remaining_stock_quantity,
                        'claimed_units'            => $claimed,
                        'claimed_percentage'       => $claimPercentage,
                        'generated_revenue'        => round($revenue, 2),
                        'start_date'               => $pi->created_at?->toIso8601String(),
                        'valid_until'              => $pi->valid_until?->toIso8601String(),
                        'end_date'                 => $pi->valid_until?->toIso8601String(),
                        'last_bumped_at'           => null,
                        'days_active'              => $daysActive,
                        'status'                   => $pi->is_expired ? 'expired' : ($pi->remaining_stock_quantity <= 0 ? 'sold_out' : $pi->status),
                        'is_currently_active'      => $pi->is_available,
                        'promotion_type'           => $pi->promotion?->promotion_type,
                        'type_title'               => $pi->promotion?->translated_title ?? $pi->promotion?->title,
                        'promotion'                => $pi->promotion ? [
                            'id'             => $pi->promotion->id,
                            'title'          => $pi->promotion->translated_title ?? $pi->promotion->title,
                            'slug'           => $pi->promotion->slug,
                            'promotion_type' => $pi->promotion->promotion_type,
                            'campaign'       => $pi->promotion->campaign ? [
                                'id'    => $pi->promotion->campaign->id,
                                'title' => $pi->promotion->campaign->title,
                                'slug'  => $pi->promotion->campaign->slug,
                            ] : null,
                        ] : null,
                        'created_at_timestamp'     => $pi->created_at?->timestamp ?? 0,
                    ]);
                }
            }

            // 2. Fetch Boosts Items (Daily Bump, Top Ad, Spotlight)
            if ($includeBoosts) {
                $boostsQuery = ItemAdPromotion::where('user_id', $userId)
                    ->with(['item.currency'])
                    ->when($request->filled('status'), function ($q) use ($request) {
                        $q->where('status', $request->status);
                    })
                    ->when(in_array($filterType, ['daily_bump_up', 'top_ad', 'spotlight']), function ($q) use ($filterType) {
                        $q->where('promotion_type', $filterType);
                    });

                $boosts = $boostsQuery->get();

                foreach ($boosts as $b) {
                    $createdAt = $b->start_date ?: ($b->created_at ?: $now);
                    $endAt = $b->end_date ?: $now;
                    $effectiveEnd = $now->gt($endAt) ? $endAt : $now;
                    $daysActive = max(1, $createdAt->diffInDays($effectiveEnd));

                    $records->push([
                        'id'                       => $b->id,
                        'record_type'              => 'boost',
                        'item_id'                  => $b->item_id,
                        'item_name'                => $b->item?->name,
                        'item_slug'                => $b->item?->slug,
                        'item_image'               => $b->item?->image,
                        'clicks'                   => (int) ($b->item?->clicks ?? 0),
                        'views'                    => (int) ($b->item?->clicks ?? 0),
                        'original_price'           => (float) ($b->item?->price ?? 0),
                        'promotional_price'        => (float) ($b->item?->price ?? 0),
                        'discount_value'           => 0.0,
                        'discount_type'            => null,
                        'discount_percentage'      => null,
                        'stock_quantity'           => 1,
                        'remaining_stock_quantity' => $b->is_active ? 1 : 0,
                        'claimed_units'            => 0,
                        'claimed_percentage'       => 0,
                        'generated_revenue'        => 0.0,
                        'start_date'               => $b->start_date?->toIso8601String(),
                        'valid_until'              => $b->end_date?->toIso8601String(),
                        'end_date'                 => $b->end_date?->toIso8601String(),
                        'last_bumped_at'           => $b->last_bumped_at?->toIso8601String(),
                        'days_active'              => $daysActive,
                        'status'                   => $b->is_active ? 'active' : $b->status,
                        'is_currently_active'      => $b->is_active,
                        'promotion_type'           => $b->promotion_type,
                        'type_title'               => $b->type_title,
                        'promotion'                => [
                            'id'             => $b->id,
                            'title'          => $b->type_title,
                            'slug'           => $b->promotion_type,
                            'promotion_type' => $b->promotion_type,
                            'campaign'       => null,
                        ],
                        'created_at_timestamp'     => $b->created_at?->timestamp ?? 0,
                    ]);
                }
            }

            // Sort descending by timestamp
            $sorted = $records->sortByDesc('created_at_timestamp')->values();
            $total = $sorted->count();
            $lastPage = max(1, (int) ceil($total / $limit));
            $offset = ($page - 1) * $limit;
            $paginated = $sorted->slice($offset, $limit)->values();

            return ResponseService::successResponse(
                __('Promotions history fetched successfully'),
                [
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'total'        => $total,
                    'per_page'     => $limit,
                    'data'         => $paginated,
                ]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerPromotionApiController -> getPromotionsHistory');
            return ResponseService::errorResponse(__('Failed to fetch promotional history'));
        }
    }
}
