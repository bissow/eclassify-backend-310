<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CampaignResource;
use App\Http\Resources\ItemApiResource;
use App\Http\Resources\PromotionItemResource;
use App\Http\Resources\PromotionResource;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\ItemAdPromotion;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Services\HelperService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OfferApiController extends BaseApiController
{
    /**
     * Get list of active campaigns
     */
    public function getCampaigns(Request $request)
    {
        try {
            $limit = (int) ($request->limit ?? 10);
            $page = (int) ($request->page ?? 1);

            $query = Campaign::active()
                ->currentlyRunning()
                ->with(['active_promotions' => function ($q) {
                    $q->withCount('active_items');
                }])
                ->orderBy('priority', 'asc')
                ->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $campaigns = $query->paginate($limit, ['*'], 'page', $page);

            return ResponseService::successResponse(
                __('Campaigns fetched successfully'),
                CampaignResource::collection($campaigns)->response()->getData(true)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getCampaigns');
            return ResponseService::errorResponse(__('Failed to fetch campaigns'));
        }
    }

    /**
     * Get campaign detail by ID or Slug
     */
    public function getCampaignDetail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'   => 'required_without:slug|integer',
                'slug' => 'required_without:id|string',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $campaign = Campaign::with(['active_promotions.translations'])
                ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
                ->when($request->filled('slug'), fn($q) => $q->where('slug', $request->slug))
                ->first();

            if (!$campaign) {
                return ResponseService::errorResponse(__('Campaign not found'));
            }

            return ResponseService::successResponse(
                __('Campaign details fetched successfully'),
                new CampaignResource($campaign)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getCampaignDetail');
            return ResponseService::errorResponse(__('Failed to fetch campaign details'));
        }
    }

    /**
     * Get list of active promotions
     */
    public function getPromotions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_id'    => 'nullable|integer',
                'promotion_type' => 'nullable|in:flash_sale,clearance_sale,deal_of_the_day,custom',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $limit = (int) ($request->limit ?? 10);
            $page = (int) ($request->page ?? 1);

            $query = Promotion::active()
                ->currentlyRunning()
                ->with(['campaign', 'translations'])
                ->withCount(['active_items as items_count'])
                ->when($request->filled('campaign_id'), fn($q) => $q->where('campaign_id', $request->campaign_id))
                ->when($request->filled('promotion_type'), fn($q) => $q->where('promotion_type', $request->promotion_type))
                ->orderBy('priority', 'asc')
                ->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $promotions = $query->paginate($limit, ['*'], 'page', $page);

            return ResponseService::successResponse(
                __('Promotions fetched successfully'),
                PromotionResource::collection($promotions)->response()->getData(true)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getPromotions');
            return ResponseService::errorResponse(__('Failed to fetch promotions'));
        }
    }

    /**
     * Get single promotion detail
     */
    public function getPromotionDetail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'   => 'required_without:slug|integer',
                'slug' => 'required_without:id|string',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $promotion = Promotion::with(['campaign', 'translations'])
                ->withCount(['active_items as items_count'])
                ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
                ->when($request->filled('slug'), fn($q) => $q->where('slug', $request->slug))
                ->first();

            if (!$promotion) {
                return ResponseService::errorResponse(__('Promotion not found'));
            }

            return ResponseService::successResponse(
                __('Promotion details fetched successfully'),
                new PromotionResource($promotion)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getPromotionDetail');
            return ResponseService::errorResponse(__('Failed to fetch promotion details'));
        }
    }

    /**
     * Get items under promotion(s) with full location-wise filtering
     */
    public function getPromotionItems(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'promotion_id'   => 'nullable|integer',
                'campaign_id'    => 'nullable|integer',
                'promotion_type' => 'nullable|in:flash_sale,clearance_sale,deal_of_the_day,custom',
                'category_id'    => 'nullable|integer',
                'min_price'      => 'nullable|numeric',
                'max_price'      => 'nullable|numeric|gte:min_price',
                'sort_by'        => 'nullable|in:price-low-to-high,price-high-to-low,newest,discount-high-to-low',
                'latitude'       => 'nullable|numeric|required_with:longitude',
                'longitude'      => 'nullable|numeric|required_with:latitude',
                'radius'         => 'nullable|numeric',
                'country'        => 'nullable|string',
                'state'          => 'nullable|string',
                'city'           => 'nullable|string',
                'area_id'        => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $limit = (int) ($request->limit ?? 15);
            $page = (int) ($request->page ?? 1);

            // Base PromotionItem query
            $query = PromotionItem::available()
                ->with([
                    'promotion.translations',
                    'item' => function ($iq) {
                        $iq->with(['category', 'currency', 'gallery_images', 'user:id,name,profile,is_verified,has_store', 'translations']);
                    }
                ]);

            // Filter by Promotion ID
            if ($request->filled('promotion_id')) {
                $query->where('promotion_id', $request->promotion_id);
            }

            // Filter by Campaign ID
            if ($request->filled('campaign_id')) {
                $query->whereHas('promotion', function ($pq) use ($request) {
                    $pq->where('campaign_id', $request->campaign_id);
                });
            }

            // Filter by Promotion Type (e.g. flash_sale, clearance_sale, deal_of_the_day)
            if ($request->filled('promotion_type')) {
                $query->whereHas('promotion', function ($pq) use ($request) {
                    $pq->where('promotion_type', $request->promotion_type);
                });
            }

            // Filter by Category
            if ($request->filled('category_id')) {
                $query->whereHas('item', function ($iq) use ($request) {
                    $iq->where('category_id', $request->category_id);
                });
            }

            // Filter by Price range
            if ($request->filled('min_price')) {
                $query->where('promotional_price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('promotional_price', '<=', $request->max_price);
            }

            // Location filtering on item
            $hasCoords = $request->filled('latitude') && $request->filled('longitude');
            $hasRadius = $hasCoords && $request->filled('radius');

            $query->whereHas('item', function ($iq) use ($request, $hasCoords, $hasRadius) {
                $iq->where('status', 'approved')->getNonExpiredItems();

                if ($request->filled('country')) {
                    $iq->where('country', $request->country);
                }
                if ($request->filled('state')) {
                    $iq->where('state', $request->state);
                }
                if ($request->filled('city')) {
                    $iq->where('city', $request->city);
                }
                if ($request->filled('area_id')) {
                    $iq->where('area_id', $request->area_id);
                }

                if ($hasRadius) {
                    $lat = (float) $request->latitude;
                    $lng = (float) $request->longitude;
                    $radius = (float) $request->radius;
                    $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";
                    $iq->whereRaw("{$haversine} <= ?", [$radius]);
                }
            });

            // Sorting
            if ($request->sort_by === 'price-low-to-high') {
                $query->orderBy('promotional_price', 'asc');
            } elseif ($request->sort_by === 'price-high-to-low') {
                $query->orderBy('promotional_price', 'desc');
            } elseif ($request->sort_by === 'discount-high-to-low') {
                $query->orderBy('discount_value', 'desc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $items = $query->paginate($limit, ['*'], 'page', $page);

            return ResponseService::successResponse(
                __('Promotion items fetched successfully'),
                PromotionItemResource::collection($items)->response()->getData(true)
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getPromotionItems');
            return ResponseService::errorResponse(__('Failed to fetch promotional items'));
        }
    }

    /**
     * Shortcut: Get Flash Sales with countdown & stock
     */
    public function getFlashSales(Request $request)
    {
        $request->merge(['promotion_type' => 'flash_sale']);
        return $this->getPromotionItems($request);
    }

    /**
     * Shortcut: Get Stock Clearance Sales
     */
    public function getClearanceSales(Request $request)
    {
        $request->merge(['promotion_type' => 'clearance_sale']);
        return $this->getPromotionItems($request);
    }

    /**
     * Shortcut: Get Deals of the Day (24hr rotating deals)
     */
    public function getDealsOfTheDay(Request $request)
    {
        $request->merge(['promotion_type' => 'deal_of_the_day']);
        return $this->getPromotionItems($request);
    }

    /**
     * Get Spotlight promoted ads with location filtering
     */
    public function getSpotlightAds(Request $request)
    {
        try {
            $limit = (int) ($request->limit ?? 10);
            $page = (int) ($request->page ?? 1);

            $query = Item::with(['category', 'currency', 'gallery_images', 'user:id,name,profile,is_verified,has_store', 'translations'])
                ->where('status', 'approved')
                ->getNonExpiredItems()
                ->spotlight();

            // Location filtering
            if ($request->filled('country')) {
                $query->where('country', $request->country);
            }
            if ($request->filled('state')) {
                $query->where('state', $request->state);
            }
            if ($request->filled('city')) {
                $query->where('city', $request->city);
            }
            if ($request->filled('area_id')) {
                $query->where('area_id', $request->area_id);
            }

            if ($request->filled('latitude') && $request->filled('longitude')) {
                $lat = (float) $request->latitude;
                $lng = (float) $request->longitude;
                $radius = (float) ($request->radius ?? 100);
                $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";
                $query->selectRaw("items.*, {$haversine} AS distance")
                      ->orderBy('distance', 'asc');
            } else {
                $query->orderBy('id', 'desc');
            }

            $items = $query->paginate($limit, ['*'], 'page', $page);

            return ResponseService::successResponse(
                __('Spotlight ads fetched successfully'),
                (new ItemApiResource($items))->asDetail()
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'OfferApiController -> getSpotlightAds');
            return ResponseService::errorResponse(__('Failed to fetch spotlight ads'));
        }
    }
}
