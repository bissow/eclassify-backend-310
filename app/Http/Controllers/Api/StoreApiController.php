<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\Item;
use App\Models\SellerRating;
use App\Models\Store;
use App\Models\User;
use App\Services\ResponseService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * @tags Store
 */
class StoreApiController extends BaseApiController
{
    /**
     * Setup / Update Store
     */
    public function setupStore(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();

            if (!$user) {
                ResponseService::errorResponse(__('User not authenticated'));
            }

            $validator = Validator::make($request->all(), [
                'name'         => 'required|string|max:191',
                'description'  => 'nullable|string',
                'logo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'banner'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:7168',
                'email'        => 'nullable|email|max:191',
                'contact'      => 'nullable|string|max:50',
                'country_code' => 'nullable|string|max:10',
                'address'      => 'nullable|string',
                'latitude'     => 'nullable|numeric|between:-90,90',
                'longitude'    => 'nullable|numeric|between:-180,180',
                'country'      => 'nullable|string|max:191',
                'state'        => 'nullable|string|max:191',
                'city'         => 'nullable|string|max:191',
                'area_id'      => 'nullable|integer|exists:areas,id',
                'website'      => 'nullable|url|max:255',
                'tax_number'   => 'nullable|string|max:100',
                'opening_time' => 'nullable|string|max:20',
                'closing_time' => 'nullable|string|max:20',
                'working_days' => 'nullable',
                'social_links' => 'nullable',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $store = Store::where('user_id', $user->id)->first();

            $storeData = [
                'user_id'      => $user->id,
                'name'         => $request->input('name'),
                'description'  => $request->input('description'),
                'email'        => $request->input('email', $user->email),
                'contact'      => $request->input('contact', $user->mobile),
                'country_code' => $request->input('country_code', $user->country_code),
                'address'      => $request->input('address', $user->address),
                'latitude'     => $request->filled('latitude') ? (float)$request->latitude : null,
                'longitude'    => $request->filled('longitude') ? (float)$request->longitude : null,
                'country'      => $request->input('country'),
                'state'        => $request->input('state'),
                'city'         => $request->input('city'),
                'area_id'      => $request->filled('area_id') ? (int)$request->area_id : null,
                'website'      => $request->input('website'),
                'tax_number'   => $request->input('tax_number'),
                'opening_time' => $request->input('opening_time'),
                'closing_time' => $request->input('closing_time'),
                'status'       => 'active',
            ];

            // Parse json inputs if passed as strings
            if ($request->has('working_days')) {
                $days = $request->input('working_days');
                $storeData['working_days'] = is_array($days) ? $days : json_decode($days, true);
            }

            if ($request->has('social_links')) {
                $links = $request->input('social_links');
                $storeData['social_links'] = is_array($links) ? $links : json_decode($links, true);
            }

            // Slug management
            if (!$store || ($store->name !== $request->name && empty($store->slug))) {
                $storeData['slug'] = StoreService::generateSlug($request->name, $store?->id);
            }

            // Logo and banner file uploads
            if ($request->hasFile('logo')) {
                $storeData['logo'] = StoreService::handleLogoUpload($request->file('logo'), $store);
            }

            if ($request->hasFile('banner')) {
                $storeData['banner'] = StoreService::handleBannerUpload($request->file('banner'), $store);
            }

            if ($store) {
                $store->update($storeData);
            } else {
                $store = Store::create($storeData);
            }

            // Sync user model location & store flag
            $user->update([
                'has_store' => true,
                'latitude'  => $storeData['latitude'] ?? $user->latitude,
                'longitude' => $storeData['longitude'] ?? $user->longitude,
                'country'   => $storeData['country'] ?? $user->country,
                'state'     => $storeData['state'] ?? $user->state,
                'city'      => $storeData['city'] ?? $user->city,
                'area_id'   => $storeData['area_id'] ?? $user->area_id,
            ]);

            DB::commit();

            $store->load(['user', 'area']);
            $formatted = StoreService::formatStoreData($store);

            ResponseService::successResponse(__('Store setup updated successfully'), $formatted);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'StoreApiController -> setupStore');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get My Store
     */
    public function getMyStore(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                ResponseService::errorResponse(__('User not authenticated'));
            }

            $store = Store::with(['user', 'area'])->where('user_id', $user->id)->first();

            if (!$store) {
                return ResponseService::successResponse(__('No store found for user'), [
                    'has_store' => false,
                    'store'     => null,
                ]);
            }

            $formatted = StoreService::formatStoreData($store);

            ResponseService::successResponse(__('Store details fetched successfully'), [
                'has_store' => true,
                'store'     => $formatted,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreApiController -> getMyStore');
            ResponseService::errorResponse();
        }
    }

    /**
     * Toggle Store Status (active / inactive)
     */
    public function toggleStoreStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $store = Store::where('user_id', $user->id)->first();

            if (!$store) {
                ResponseService::errorResponse(__('Store not found'));
            }

            $newStatus = $store->status === 'active' ? 'inactive' : 'active';
            $store->update(['status' => $newStatus]);

            ResponseService::successResponse(__('Store status updated successfully'), [
                'status' => $newStatus,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreApiController -> toggleStoreStatus');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Stores / Nearby Available Sellers List
     */
    public function getStores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'radius'      => 'nullable|numeric|min:0.1',
            'country'     => 'nullable|string',
            'state'       => 'nullable|string',
            'city'        => 'nullable|string',
            'area_id'     => 'nullable|integer',
            'search'      => 'nullable|string',
            'is_verified' => 'nullable|boolean',
            'sort_by'     => 'nullable|in:nearest,top_rated,newest,oldest,popular',
            'page'        => 'nullable|integer|min:1',
            'limit'       => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $limit = (int) ($request->limit ?? 10);
            $page = (int) ($request->page ?? 1);
            $userLat = $request->filled('latitude') ? (float)$request->latitude : null;
            $userLng = $request->filled('longitude') ? (float)$request->longitude : null;
            $radius = $request->filled('radius') ? (float)$request->radius : null;

            $query = Store::active()->with(['user:id,name,profile,country_code,is_verified', 'area:id,name']);

            // Filter by search keyword
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by verified status
            if ($request->has('is_verified')) {
                $query->where('is_verified', (bool)$request->is_verified);
            }

            // Filter by location hierarchy
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

            // Apply Nearby filter / Distance calculation
            $hasCoords = ($userLat !== null && $userLng !== null);
            if ($hasCoords) {
                $query->nearby($userLat, $userLng, $radius);
            }

            // Sorting logic
            $sortBy = $request->sort_by ?? ($hasCoords ? 'nearest' : 'newest');

            switch ($sortBy) {
                case 'nearest':
                    if ($hasCoords) {
                        // Already sorted by distance in nearby scope
                    } else {
                        $query->orderBy('created_at', 'desc');
                    }
                    break;

                case 'top_rated':
                    $query->leftJoin('seller_ratings', 'seller_ratings.seller_id', '=', 'stores.user_id')
                          ->groupBy('stores.id')
                          ->orderByRaw('AVG(seller_ratings.ratings) DESC');
                    break;

                case 'popular':
                    $query->withCount('items')->orderBy('items_count', 'desc');
                    break;

                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;

                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $paginated = $query->paginate($limit, ['stores.*'], 'page', $page);

            $formattedStores = collect($paginated->items())->map(function ($store) use ($userLat, $userLng) {
                return StoreService::formatStoreData($store, $userLat, $userLng);
            });

            $response = [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'last_page'    => $paginated->lastPage(),
                'data'         => $formattedStores,
            ];

            ResponseService::successResponse(__('Stores fetched successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreApiController -> getStores');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Store Detail by Slug or ID
     */
    public function getStoreDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'nullable|integer',
            'slug'        => 'nullable|string',
            'user_id'     => 'nullable|integer',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'category_id' => 'nullable|integer',
            'items_limit' => 'nullable|integer|min:1|max:50',
            'items_page'  => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $userLat = $request->filled('latitude') ? (float)$request->latitude : null;
            $userLng = $request->filled('longitude') ? (float)$request->longitude : null;

            $store = Store::with(['user', 'area'])
                ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
                ->when($request->filled('slug'), fn($q) => $q->where('slug', $request->slug))
                ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
                ->first();

            if (!$store) {
                ResponseService::errorResponse(__('Store not found'));
            }

            // Check if current authenticated user is following this store's owner
            $isFollowing = false;
            if (Auth::guard('sanctum')->check()) {
                $authUser = Auth::guard('sanctum')->user();
                $isFollowing = $authUser->isFollowing($store->user_id);
            }

            $formattedStore = StoreService::formatStoreData($store, $userLat, $userLng);
            $formattedStore['is_following'] = $isFollowing;

            // Fetch Store Active Items
            $itemsLimit = (int) ($request->items_limit ?? 12);
            $itemsPage = (int) ($request->items_page ?? 1);

            $itemsQuery = Item::with([
                'category:id,name,image,is_job_category,price_optional,slug',
                'gallery_images:id,image,item_id,is_default',
                'featured_items',
                'currency',
            ])
            ->where('user_id', $store->user_id)
            ->where('status', 'approved')
            ->getNonExpiredItems()
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->orderBy('created_at', 'desc');

            $itemsPaginated = $itemsQuery->paginate($itemsLimit, ['*'], 'items_page', $itemsPage);
            $formattedItems = (new ItemApiResource(collect($itemsPaginated->items())))->asSingle();

            $reviews = SellerRating::with('buyer:id,name,profile')
                ->where('seller_id', $store->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            $response = [
                'store'   => $formattedStore,
                'items'   => [
                    'total'        => $itemsPaginated->total(),
                    'current_page' => $itemsPaginated->currentPage(),
                    'per_page'     => $itemsPaginated->perPage(),
                    'last_page'    => $itemsPaginated->lastPage(),
                    'data'         => $formattedItems,
                ],
                'reviews' => $reviews,
            ];

            ResponseService::successResponse(__('Store detail fetched successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreApiController -> getStoreDetail');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Store Slugs for Next.js SEO / Static Routes
     */
    public function getStoreSlugs(Request $request)
    {
        try {
            $slugs = Store::active()
                ->whereNotNull('slug')
                ->select('id', 'slug', 'updated_at')
                ->paginate(500);

            ResponseService::successResponse(__('Store slugs fetched successfully'), $slugs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreApiController -> getStoreSlugs');
            ResponseService::errorResponse();
        }
    }
}
