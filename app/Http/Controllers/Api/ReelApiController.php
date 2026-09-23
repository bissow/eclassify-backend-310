<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\Item;
use App\Models\ItemOffer;
use App\Models\Reel;
use App\Models\ReelLike;
use App\Models\UserFollow;
use App\Services\HelperService;
use App\Services\ResponseService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * @tags Reel
 */
class ReelApiController extends BaseApiController
{

    /**
     * Get reels in random order with item details (public)
     */
    public function getReels(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|max:500|min:1',
                'item_id'  => 'nullable|integer|exists:items,id',
                'reel_id'  => 'nullable|integer|exists:reels,id',
                'following' => 'nullable|integer|in:1',
                'latitude'  => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius'    => 'nullable|numeric|min:0',
                'area_id'   => 'nullable|integer|exists:areas,id',
                'area_latitude'  => 'nullable|numeric',
                'area_longitude' => 'nullable|numeric',
                'city'      => 'nullable|string',
                'state'     => 'nullable|string',
                'country'   => 'nullable|string',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            // Authenticated User Id if user token available
            $userId = Auth::id();

            // Get Following Item IDs
            $followingItemIds = array();
            if ($request->following == 1){
                if($userId){
                    $followingUserIds = UserFollow::where('follower_id', $userId)->pluck('following_id');
                    if ($followingUserIds->isNotEmpty()) {
                        $followingItemIds = Item::whereIn('user_id', $followingUserIds)->getNonExpiredItems()->pluck('id');
                    }
                }
                if(empty($followingItemIds)){
                    ResponseService::warningResponse(__('You are not following any user'));
                }
            }

            // Common with query
            $with = [
                'item:id,name,slug,description,price,min_salary,max_salary,user_id,category_id,currency_id,address,city,state,country,area_id,status,latitude,longitude,published_at,region_code,country_code,item_type,sold_to,rejected_reason,admin_edit_reason,is_edited_by_admin',
                'item.gallery_images:id,item_id,image,is_default',
                'item.user:id,name,profile',
                'item.currency:id,symbol,decimal_places,decimal_separator,thousand_separator,symbol_position',
                'item.category:id,name,is_job_category',
                'item.area:id,name',
                'item.translations',
                'item.featured_items' => function($query){
                    $query->onlyActive();
                }
            ];
            if($userId){
                $with['item.item_offers'] = fn($q) => $q->where('buyer_id', $userId)->select('id','buyer_id','seller_id','item_id','amount');
                $with['item.favourites'] = fn($q) => $q->where('user_id', $userId);
            }

            // Base item query: active, non-expired, has a reel
            $itemBaseQuery = Item::query()
                ->approved()
                ->getNonExpiredItems()
                ->whereHas('reel')
                ->when($request->item_id, fn ($q) => $q->where('id', $request->item_id))
                ->when(!empty($followingItemIds), fn ($q) => $q->whereIn('id', $followingItemIds));

            // Reuse shared item location-filter logic (area > city > state > country > lat/long)
            $locationResult  = HelperService::applyLocationFilters($itemBaseQuery, $request, fn ($q) => $q);
            $locationItemIds = $locationResult['query']->pluck('id');
            $locationMessage = $locationResult['message'];

            if($userId){
                $itemBaseQuery->owner();
            }else{
                $itemBaseQuery->where('status', 'approved');
            }

            $page = (int) ($request->page ?? 1);
            $seed = HelperService::resolveRandomSeed($request, $userId, 'reels_seed');
            // Clients typically only send reel_id on page 1, but excluding it changes the candidate
            // pool. Remember it so later pages keep the same pool and ordering stays consistent.
            $pinnedReelId = HelperService::resolveStickyListingValue($request, $userId, 'reels_pinned', $request->reel_id);

            // Base query: active, non-expired, excluding the pinned reel
            $reelQuery = Reel::with($with)
                ->when($pinnedReelId, fn ($q) => $q->where('id', '!=', $pinnedReelId))
                ->whereIn('item_id', $locationItemIds)
                ->orderByRaw('RAND(' . $seed . ')');

            $total = (clone $reelQuery)->count();

            $reels = $reelQuery->paginate($request->per_page ?? 10);

            // Fetch the pinned reel separately (base query excludes it). It's only prepended on
            // page 1 — prepending it to every page would show it as a duplicate — but it counts
            // towards the total on every page so pagination metadata stays consistent.
            if ($pinnedReelId) {
                $pinnedReel = Reel::with($with)
                    ->whereHas('item', fn ($q) => $q->where('status', 'approved')->getNonExpiredItems())
                    ->find($pinnedReelId);

                if ($pinnedReel) {
                    $total++;
                    if ($page <= 1) {
                        $reels->getCollection()->prepend($pinnedReel);
                    }
                }
            }

            // Authenticated user data
            $reelIds = $reels->getCollection()->pluck('id');

            $likedIds = $userId
                ? ReelLike::where('user_id', $userId)->whereIn('reel_id', $reelIds)->pluck('reel_id')->flip()
                : collect();

            $likeCounts = ReelLike::whereIn('reel_id', $reelIds)
                ->selectRaw('reel_id, count(*) as total')
                ->groupBy('reel_id')
                ->pluck('total', 'reel_id');

            // Item user ids followed by authenticated user (for is_following flag)
            $itemUserIds = $reels->getCollection()->pluck('item.user_id')->filter()->unique();
            $followedUserIds = $userId
                ? UserFollow::where('follower_id', $userId)->whereIn('following_id', $itemUserIds)->pluck('following_id')->flip()
                : collect();

            $reels->getCollection()->transform(function ($reel) use ($likedIds, $likeCounts, $followedUserIds, $request, $userId) {
                $reel->liked_count = $likeCounts[$reel->id] ?? 0;
                $reel->is_liked    = isset($likedIds[$reel->id]);

                if($userId){
                    $reel->is_item_offered = $reel->item->item_offers->isNotEmpty();
                    $reel->offer_item_id = $reel->item->item_offers->first()?->id;
                }

                if ($reel->item) {
                    $reel->item_owner = $reel->item->user;
                    $reel->item_owner->is_following = isset($followedUserIds[$reel->item->user_id]);
                    $formattedItem = (new ItemApiResource(collect([$reel->item])))->asSingle()->resolve($request);
                    $reel->unsetRelation('item');
                    $reel->setAttribute('item', $formattedItem);
                }

                return $reel;
            });

            ResponseService::successResponse(__('Reels Fetched Successfully'), $reels, ['total' => $total, 'location_message' => $locationMessage]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ReelApiController -> getReels');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get reels for authenticated user's items
     */
    public function getMyReels(Request $request)
    {
        try {
            // Validation regarding per page and item id
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:500',
                'item_id'  => 'nullable|integer|exists:items,id',
                'reel_id'  => 'nullable|integer|exists:reels,id',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            // Common with query
            $with = [
                'item:id,name,slug,description,price,min_salary,max_salary,user_id,category_id,currency_id,address,city,state,country,area_id,status,latitude,longitude,published_at,region_code,country_code,item_type,sold_to,rejected_reason,admin_edit_reason,is_edited_by_admin,clicks',
                'item.gallery_images:id,item_id,image,is_default',
                'item.user:id,name,profile',
                'item.currency:id,symbol,decimal_places,decimal_separator,thousand_separator,symbol_position',
                'item.category:id,name,is_job_category',
                'item.area:id,name',
                'item.translations',
                'item.favourites',
            ];

            // Get Reel Data of authenticated user's items
            $reelQuery = Reel::with($with)
                ->whereHas('item', fn ($q) => $q->where('user_id', Auth::id()))
                ->when($request->reel_id, fn ($q) => $q->where('id', '!=', $request->reel_id))
                ->when($request->item_id, fn ($q) => $q->where('item_id', $request->item_id))
                ->inRandomOrder();

            $total = (clone $reelQuery)->count();

            $reels = $reelQuery->paginate($request->per_page ?? 10);

            // Fetch the pinned reel separately (base query excludes it). Only pin on page 1 —
            // otherwise it gets prepended to every page and shows up as a duplicate.
            if ($request->reel_id && (int) ($request->page ?? 1) <= 1) {
                $pinnedReel = Reel::with($with)
                    ->whereHas('item', fn ($q) => $q->where('user_id', Auth::id()))
                    ->find($request->reel_id);

                if ($pinnedReel) {
                    $reels->getCollection()->prepend($pinnedReel);
                    $total++;
                }
            }

            // Get Reels ID
            $reelIds    = $reels->pluck('id');

            // Get Like Counts of reels
            $likeCounts = ReelLike::whereIn('reel_id', $reelIds)
                ->selectRaw('reel_id, count(*) as total')
                ->groupBy('reel_id')
                ->pluck('total', 'reel_id');

            // Add Liked count and is liked data in reels data
            $reels->getCollection()->transform(function ($reel) use ($likeCounts, $request) {
                $reel->liked_count = $likeCounts[$reel->id] ?? 0;
                if($reel->item) {
                    $formattedItem = (new ItemApiResource(collect([$reel->item])))->asMyItem()->asSingle()->resolve($request);
                    $reel->unsetRelation('item');
                    $reel->setAttribute('item', $formattedItem);
                }
                return $reel;
            });

            ResponseService::successResponse(__('Reels Fetched Successfully'), $reels);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ReelApiController -> getMyReels');
            ResponseService::errorResponse();
        }
    }

    /**
     * Like / unlike a reel (auth)
     */
    public function manageReelLike(Request $request)
    {
        try {
            // Validation for reel id
            $validator = Validator::make($request->all(), [
                'reel_id' => 'required|integer|exists:reels,id',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            // Check reel liked or not
            $existing = ReelLike::where('reel_id', $request->reel_id)
                ->where('user_id', Auth::id())
                ->first();

            // Like or Unlike reel
            if ($existing) {
                $existing->delete();
                $message = __('Reel Unliked Successfully');
                $liked   = false;
            } else {
                ReelLike::create(['reel_id' => $request->reel_id, 'user_id' => Auth::id()]);
                $message = __('Reel Liked Successfully');
                $liked   = true;
            }

            // Get Liked Counts fo reel
            $likesCount = ReelLike::where('reel_id', $request->reel_id)->count();

            ResponseService::successResponse($message, ['is_liked' => $liked, 'likes_count' => $likesCount]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ReelApiController -> manageReelLike');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get liked reels for authenticated user
     */
    public function getLikedReels(Request $request)
    {
        try {
            // Validation for per page
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:500',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            // Get Reels Data
            $reels = Reel::with([
                'item:id,name,slug,description,price,min_salary,max_salary,user_id,category_id,currency_id,address,status,city,area_id,state,country,latitude,longitude,published_at,region_code,country_code,item_type,sold_to,rejected_reason,admin_edit_reason,is_edited_by_admin',
                'item.gallery_images:id,item_id,image,is_default',
                'item.user:id,name,profile',
                'item.currency:id,symbol,decimal_places,decimal_separator,thousand_separator,symbol_position',
                'item.category:id,name,is_job_category',
                'item.area:id,name',
                'item.translations',
                'item.favourites' => fn($q) => $q->where('user_id', Auth::id()),
            ])
            ->whereHas('likes', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->paginate($request->per_page ?? 10);

            // Get Reels Id
            $reelIds    = $reels->pluck('id');
            
            // Get Like Counts of reels
            $likeCounts = ReelLike::whereIn('reel_id', $reelIds)
                ->selectRaw('reel_id, count(*) as total')
                ->groupBy('reel_id')
                ->pluck('total', 'reel_id');


            // Add Liked count and is liked data in reels data
            $reels->getCollection()->transform(function ($reel) use ($likeCounts, $request) {
                $reel->liked_count = $likeCounts[$reel->id] ?? 0;
                $reel->is_liked = true;

                if ($reel->item) {
                    $formattedItem = (new ItemApiResource(collect([$reel->item])))->asSingle()->resolve($request);
                    $reel->unsetRelation('item');
                    $reel->setAttribute('item', $formattedItem);
                }
                return $reel;
            });

            ResponseService::successResponse(__('Liked Reels Fetched Successfully'), $reels);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ReelApiController -> getLikedReels');
            ResponseService::errorResponse();
        }
    }
}
