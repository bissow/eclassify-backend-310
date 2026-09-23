<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\UserPurchasedPackage;
use App\Services\CurrencyFormatterService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/** @tags Package */
class PackageApiController extends BaseApiController
{
    /**
     * Package categories are stored at their deepest selected level only.
     * Build each stored category together with its parent chain (root first)
     * so clients get full ancestry without us having to persist every level.
     */
    private function buildCategoriesWithParents($categoriesIds)
    {
        $allCategoriesIds = [];
        foreach ($categoriesIds as $key => $categoryId) {
            $allCategoriesIds = array_merge($allCategoriesIds, HelperService::getAllDescendantCategoryIds($categoryId));
        }
        $categories = Category::whereIn('id',$allCategoriesIds)->get()->map(function($category){
            return array(
                'id'                 => $category->id,
                'name'               => $category->name,
                'slug'               => $category->slug ?? null,
                'parent_category_id' => $category->parent_category_id,
                'translated_name'    => $category->translated_name
            );
        });
        return $categories;

        // $allCategoryPairs = null;

        // return $categories->map(function ($cat) use (&$allCategoryPairs) {
        //     $allCategoriesId = HelperService::getAllAncestorCategoryIds($parentId);
        //     $oldCategoryId = null;
        //     $allCategoryPairs = Category::without('translations')->with('parent')->whereIn('id',$allCategoriesId)->get();
        //     $categoryData = null;
        //     $parentData = array();
            // foreach ($allCategoryPairs as $key => $category) {
            //     if($key == count($allCategoryPairs)-1){
            //         $categoryData = array(
            //             'id'                 => $category->id,
            //             'name'               => $category->name,
            //             'slug'               => $category->slug ?? null,
            //             'parent_category_id' => $category->parent_category_id,
            //             'parents'            => $parentData ? array_reverse($parentData) : null
            //         );
            //     }else if($category->parent_category_id == null){
            //         $parentData[] = array(
            //             'id'                 => $category->id,
            //             'name'               => $category->name,
            //             'slug'               => $category->slug ?? null,
            //             'parent_category_id' => $category->parent_category_id,
            //         );
            //     }else if($oldCategoryId == $category->parent_category_id){
            //         $parentData[] = array(
            //             'id'                 => $category->id,
            //             'name'               => $category->name,
            //             'slug'               => $category->slug ?? null,
            //             'parent_category_id' => $category->parent_category_id,
            //         );
            //     }
            //     if($categoryData){
            //         return $categoryData;
            //     }
            //     $oldCategoryId = $category->id;
            // }
        //     return null;
        // });
    }

    /** Get Package */
    public function getPackage(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'category_id'   => 'nullable',
            'platform'      => 'nullable|in:android,ios',
            'type'          => 'nullable|in:advertisement,item_listing',
            'listing_type'  => 'nullable|in:normal,reel',
            'is_renew'      => 'nullable|in:1'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            if ($request->filled('category_id')) {
                $categoryExists = Category::where(['id' => $request->category_id, 'status' => 1])->exists();
                if (!$categoryExists) {
                    ResponseService::errorResponse(__('Category not found.'));
                }
            }

            $packages = Package::with(['translations', 'package_categories'])
                ->where('status', 1)
                ->where('is_discontinued', 0)
                ->when($request->filled('listing_type'),function ($query) use ($request) {
                    return $query->where('is_reel_allowed', $request->listing_type == 'reel' ? 1 : 0);
                });

            if ($request->filled('category_id')) {
                $categoryIdsToMatch = [(int) $request->category_id];
                $currentCatId = $request->category_id;

                while ($currentCatId) {
                    $parentId = Category::without('translations')
                        ->where('id', $currentCatId)
                        ->value('parent_category_id');
                    if ($parentId) {
                        $categoryIdsToMatch[] = (int) $parentId;
                        $currentCatId = $parentId;
                    } else {
                        $currentCatId = null;
                    }
                }

                $isRenew = $request->has('is_renew') && $request->is_renew == 1;
                $packages->where(function ($query) use ($categoryIdsToMatch, $isRenew) {
                    $query->whereHas('package_categories', function ($q) use ($categoryIdsToMatch) {
                        $q->whereIn('category_id', $categoryIdsToMatch);
                    })
                    ->when($isRenew, function ($q) {
                        $q->orWhere('is_global',1);
                    });
                });
            }else{
                $packages->where('is_global',1);
            }

            if ($request->platform === 'ios') {
                $packages->whereNotNull('ios_product_id');
            }

            if ($request->filled('type')) {
                $packages->where('type', $request->type);
            }

            if(Auth::check()){
                $packages = $packages->with(['user_purchased_packages' => function($query){
                    $query->onlyActive();
                }])->orderBy('id', 'ASC')->get();
            }else{
                $packages = $packages->orderBy('id', 'ASC')->get();
            }

            $formatter  = app(CurrencyFormatterService::class);
            $iso_code   = Setting::where('name', 'currency_iso_code')->value('value');
            $symbol     = Setting::where('name', 'currency_symbol')->value('value');
            $position   = Setting::where('name', 'currency_symbol_position')->value('value');

            $currency = (object) [
                'iso_code'        => $iso_code,
                'symbol'          => $symbol,
                'symbol_position' => $position,
                'decimal_places'  => 2
            ];

            $packages = $packages->map(function ($package) use ($formatter, $currency) {

                // Category data (only for non-global packages)
                if ($package->is_global != 1) {
                    $package['selected_category_ids'] = $package->package_categories->pluck('category_id')->toArray();
                    $package['categories_path'] = $this->buildCategoriesWithParents($package['selected_category_ids']);
                } else {
                    $package['selected_category_ids']   = [];
                    $package['categories_path']         = [];
                }

                // key_points
                if (!empty($package->key_points)) {
                    $keyPoints = json_decode($package->key_points, true);
                    $package['key_points'] = (json_last_error() === JSON_ERROR_NONE && is_array($keyPoints))
                        ? $keyPoints
                        : [];
                } else {
                    $package['key_points'] = [];
                }

                // Formatted prices
                $package['formatted_final_price'] = $formatter->formatPrice($package->final_price ?? 0, $currency);
                $package['formatted_price']       = $formatter->formatPrice($package->price ?? $package->final_price ?? 0, $currency);
                $package['user_purchased_packages'] = Auth::check() ? $package->user_purchased_packages : array();
                $package['is_purchased_before'] = false;
                if(Auth::check()){
                    $package['is_purchased_before'] = $package->whereHas('user_purchased_packages', function($query) use($package) {
                        $query->where(['user_id' => Auth::user()->id, 'package_id' => $package->id]);
                    })->count() > 0;
                }

                // Listing duration fallback
                if (empty($package->listing_duration_type) || $package->listing_duration_type === 'package') {
                    $package['listing_duration_type'] = 'package';
                    $package['listing_duration_days'] = !empty($package->listing_duration_days)
                        ? $package->listing_duration_days
                        : 'unlimited';
                }

                unset($package['categories']);
                return $package;
            });

            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getPackage');
            ResponseService::errorResponse();
        }
    }

    /** Assign Free Package */
    public function assignFreePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:packages,id',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();

            $package = Package::where(['final_price' => 0, 'id' => $request->package_id, 'status' => 1, 'is_discontinued' => 0])->firstOrFail();
            if(collect($package)->isEmpty()) {
                ResponseService::errorResponse(__('Package not found'));
            }
            $activePackage = UserPurchasedPackage::where(['package_id' => $request->package_id, 'user_id' => Auth::user()->id])->first();
            if (! empty($activePackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }

            $paymentTransactionData = PaymentTransaction::create([
                'user_id' => $user->id,
                'package_id' => $request->package_id,
                'amount' => 0,
                'original_price' => $package->price,
                'discount_price' => $package->price - $package->final_price,
                'payment_gateway' => 'Free',
                'payment_status' => 'succeed',
                'order_id'=> Str::random(10)
            ]);

            UserPurchasedPackage::create([
                'user_id' => $user->id,
                'package_id' => $request->package_id,
                'start_date' => Carbon::now(),
                'total_limit' => $package->item_limit == 'unlimited' ? null : $package->item_limit,
                'end_date' => is_null($package->duration) ? null : Carbon::now()->addDays($package->duration),
                'listing_duration_type' => $package->listing_duration_type,
                'listing_duration_days' => $package->listing_duration_days
            ]);

            // Send Notifiation to user for free package assigned
            $title = "Package Assigned";
            $body = 'Free Package hase been assigned to your account successfully.';
            if (!empty($user->id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $body,
                    'payment',
                    ['id' => $paymentTransactionData->id],
                    false,
                    array($user->id),
                    true
                );
            }
            ResponseService::successResponse(__('Package Purchased Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> assignFreePackage');
            ResponseService::errorResponse();
        }
    }

    /** App Payment Status */
    public function appPaymentStatus(Request $request)
    {
        try {
            $paypalInfo = $request->all();
            if (! empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == 'completed') {
                ResponseService::successResponse(__('Your Package will be activated within 10 Minutes'), $paypalInfo['txn_id']);
            } elseif (! empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == 'authorized') {
                ResponseService::successResponse(__('Your Transaction is Completed. Ads wil be credited to your account within 30 minutes.'), $paypalInfo);
            } else {
                ResponseService::errorResponse(__('Payment Cancelled / Declined'), (isset($_GET)) ? $paypalInfo : '');
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> appPaymentStatus');
            ResponseService::errorResponse();
        }
    }

    /** Get User Purchased Packages */
    public function getUserPurchasedPackages(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'type' => 'nullable|in:advertisement,item_listing',
            'item_type' => 'nullable|in:normal,reel',
            'category_id' => 'nullable|exists:categories,id'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $packages = Package::with(['translations', 'categories', 'package_categories'])
                ->where('status', 1)
                ->with(['user_purchased_packages' => fn($q) => $q->onlyActive()])
                ->withCount(['user_purchased_packages as is_purchased_before_count' => fn($q) => $q->where('user_id', Auth::id())])
                ->whereHas('user_purchased_packages', fn($q) => $q->onlyActive());
 
            if ($request->filled('type')) {
                $packages->where('type', $request->type);
            }

            if ($request->filled('item_type') && $request->item_type == 'reel') {
                $packages->where('is_reel_allowed', 1);
            }

            if ($request->filled('category_id')) {
                $categoryIdsToMatch = [(int) $request->category_id];
                $currentCatId = $request->category_id;

                while ($currentCatId) {
                    $parentId = Category::without('translations')
                        ->where('id', $currentCatId)
                        ->value('parent_category_id');
                    if ($parentId) {
                        $categoryIdsToMatch[] = (int) $parentId;
                        $currentCatId = $parentId;
                    } else {
                        $currentCatId = null;
                    }
                }

                $packages->where(function ($query) use ($categoryIdsToMatch) {
                    $query->whereHas('package_categories', function ($q) use ($categoryIdsToMatch) {
                        $q->whereIn('category_id', $categoryIdsToMatch);
                    })->orWhere('is_global',1);
                });
            }

            $packages = $packages->orderBy('id', 'ASC')->get();

            $formatter  = app(CurrencyFormatterService::class);
            $iso_code   = Setting::where('name', 'currency_iso_code')->value('value');
            $symbol     = Setting::where('name', 'currency_symbol')->value('value');
            $position   = Setting::where('name', 'currency_symbol_position')->value('value');

            $currency = (object) [
                'iso_code'        => $iso_code,
                'symbol'          => $symbol,
                'symbol_position' => $position,
                'decimal_places'  => 2,
            ];

            $packages = $packages->map(function ($package) use ($formatter, $currency) {

                $package->is_active           = count($package->user_purchased_packages) > 0;
                $package->is_purchased_before = ($package->is_purchased_before_count ?? 0) > 0;

                // Category data
                if ($package->is_global != 1) {
                    $package['selected_category_ids'] = $package->package_categories->pluck('category_id')->toArray();
                    $package['categories_path'] = $this->buildCategoriesWithParents($package['selected_category_ids']);
                } else {
                    $package['selected_category_ids'] = [];
                    $package['categories_path']            = [];
                }

                // key_points
                if (!empty($package->key_points)) {
                    $keyPoints = json_decode($package->key_points, true);
                    $package['key_points'] = (json_last_error() === JSON_ERROR_NONE && is_array($keyPoints))
                        ? $keyPoints
                        : [];
                } else {
                    $package['key_points'] = [];
                }

                // Formatted prices
                $package['formatted_final_price'] = $formatter->formatPrice($package->final_price ?? 0, $currency);
                $package['formatted_price']       = $formatter->formatPrice($package->price ?? $package->final_price ?? 0, $currency);

                // Listing duration fallback
                if (empty($package->listing_duration_type) || $package->listing_duration_type === 'package') {
                    $package['listing_duration_type'] = 'package';
                    $package['listing_duration_days'] = !empty($package->listing_duration_days)
                        ? $package->listing_duration_days
                        : 'unlimited';
                }

                // Purchased package details
                $package->user_purchased_packages = $package->user_purchased_packages->map(function ($purchased) use ($package) {

                    if ($purchased->start_date && $purchased->end_date) {
                        $purchased['duration'] = Carbon::parse($purchased->start_date)
                            ->diffInDays(Carbon::parse($purchased->end_date));
                    } else {
                        $purchased['duration'] = 'unlimited';
                    }

                    $purchased['item_limit']            = $purchased->total_limit;
                    $purchased['listing_duration_type'] = $purchased->listing_duration_type
                        ?? $package->listing_duration_type
                        ?? 'package';

                    $days = $purchased->listing_duration_days ?? $package->listing_duration_days ?? $package->duration;
                    $purchased['listing_duration_days'] = ($days == 0 && $days !== null) ? 'unlimited' : $days;

                    return $purchased;
                });

                unset($package['categories']);
                return $package;
            });

            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getUserPurchasedPackages');
            ResponseService::errorResponse();
        }
    }
}
