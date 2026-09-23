<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\ItemOffer;
use App\Models\Notifications;
use App\Models\Referral;
use App\Models\SellerRating;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * @tags User
 */
class UserApiController extends BaseApiController
{
    /**
     * Get User Info
     */
    public function getUser(Request $request)
    {
        try {
            $auth = Auth::user();

            if (! $auth) {
                ResponseService::errorResponse(__('User not authenticated'));
            }

            if (! $auth->hasRole('User')) {
                ResponseService::errorResponse(__('Invalid User Role'));
            }

            $relations = ['fcm_tokens'];
            if (Schema::hasTable('stores')) {
                $relations[] = 'store';
            }
            if (Schema::hasTable('areas')) {
                $relations[] = 'area';
            }

            $user = User::withCount([
                'followers as followers_count',
                'following as following_count'
            ])->with($relations)->find($auth->id);

            $user->total_seller_unread_chat_count = ItemOffer::where('seller_id', $auth->id)
                ->withCount(['sellerChat as unread_chat_count' => function ($q) use ($auth) {
                    $q->where('is_read', 0)->where('sender_id', '!=', $auth->id);
                }])->get()->sum('unread_chat_count');

            $user->total_buyer_unread_chat_count = ItemOffer::where('buyer_id', $auth->id)
                ->withCount(['buyerChat as unread_chat_count' => function ($q) use ($auth) {
                    $q->where('is_read', 0)->where('sender_id', '!=', $auth->id);
                }])->get()->sum('unread_chat_count');

            ResponseService::successResponse(__('User fetched successfully'), $user);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> GetUser');
            ResponseService::errorResponse();
        }
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            DB::beginTransaction();
            $app_user = Auth::user();

            $rules = [
                'name' => 'nullable|string',
                'profile' => 'nullable|mimes:jpg,jpeg,png|max:7168',
                'email' => 'nullable|email|unique:users,email,' . $app_user->id,
                'mobile' => [
                    'nullable',
                    Rule::unique('users')->ignore($app_user->id)->where(function ($query) use ($request) {
                        return $query->where('country_code', '+' . $request->country_code);
                    }),
                ],
                'fcm_id' => 'nullable',
                'address' => 'nullable',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'country' => 'nullable|string|max:191',
                'state' => 'nullable|string|max:191',
                'city' => 'nullable|string|max:191',
                'area_id' => 'nullable|integer|exists:areas,id',
                'show_personal_details' => 'boolean',
                'country_code' => 'nullable|string',
                'region_code' => 'nullable|string',
            ];

            // Referral code can only be added once (on first profile update)
            if (! $app_user->used_referral_code) {
                $rules['referral_code'] = 'nullable|string|exists:users,referral_code';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            // Allow specific fields to be updated in user data
            $allowedFields = [
                'name', 'mobile', 'address', 'latitude', 'longitude', 
                'country', 'state', 'city', 'area_id', 'show_personal_details', 
                'country_code', 'region_code'
            ];
            if ($app_user->type !== 'google') {
                $allowedFields[] = 'email';
            }
            // Referral code can only be added once (on first profile update)
            if (!$app_user->used_referral_code) {
                $allowedFields[] = 'referral_code';
            }

            $data = $request->only($allowedFields);

            if ($request->hasFile('profile')) {
                $data['profile'] = FileService::compressAndReplace($request->file('profile'), 'profile', $app_user->getRawOriginal('profile'));
            }

            if (! empty($request->fcm_id)) {
                UserFcmToken::updateOrCreate(['fcm_token' => $request->fcm_id], ['user_id' => $app_user->id, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
            }

            // Process referral code only if user hasn't used one before
            if (! $app_user->used_referral_code && ! empty($request->referral_code)) {
                $referrer = User::where('referral_code', strtoupper($request->referral_code))->first();
                
                if ($referrer) {
                    // Mark as used so it can't be changed again
                    $data['used_referral_code'] = true;
                }

                if ($request->filled('referral_code')) {
                    $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
                    if ($referEarnEnabled == '1') {
                        $referrer = User::where('referral_code', $request->referral_code)->first();
                        if ($referrer && $referrer->id !== $app_user->id) {
                            $alreadyReferred = Referral::where('referred_id', $app_user->id)->exists();
                            if (!$alreadyReferred) {
                                Referral::create([
                                    'referrer_id' => $referrer->id,
                                    'referred_id' => $app_user->id,
                                    'is_rewarded' => false,
                                ]);
                            }
                        }
                    }
                }
            }

            $data['show_personal_details'] = $request->show_personal_details;
            if($request->has('notification')){
                $data['notification'] = $request->notification == 1 ? 1 : 0;
            }
            $app_user->update($data);
            $app_user->refresh();

            DB::commit();
            ResponseService::successResponse(__('Profile Updated Successfully'), $app_user);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> updateProfile');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Seller Details
     */
    public function getSeller(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $relations = [];
            if (Schema::hasTable('stores')) {
                $relations[] = 'store';
            }
            if (Schema::hasTable('areas')) {
                $relations[] = 'area';
            }

            $seller = User::withCount([
                'followers as followers_count',
                'following as following_count'
            ])->with($relations)->findOrFail($request->id);

            $ratingQuery = SellerRating::where('seller_id', $seller->id)->with('buyer:id,name,profile');
            $totalOneRatings = $ratingQuery->clone()->where('ratings', 1)->count();
            $totalTwoRatings = $ratingQuery->clone()->where('ratings', 2)->count();
            $totalThreeRatings = $ratingQuery->clone()->where('ratings', 3)->count();
            $totalFourRatings = $ratingQuery->clone()->where('ratings', 4)->count();
            $totalFiveRatings = $ratingQuery->clone()->where('ratings', 5)->count();
            $ratings = $ratingQuery->clone()->paginate(10);
            $averageRating = $ratings->avg('ratings');

            $isFollowing = 0;
            if (Auth::check()) {
                $authUser = Auth::user();
                $isFollowing = $authUser->isFollowing($seller->id) ? 1 : 0;
            }

            $response = [
                'seller' => [
                    ...$seller->toArray(),
                    'average_rating' => $averageRating,
                    'is_following' => $isFollowing,
                ],
                'ratings' => $ratings,
                'ratings_count' => array(
                    1 => $totalOneRatings ?? 0,
                    2 => $totalTwoRatings ?? 0,
                    3 => $totalThreeRatings ?? 0,
                    4 => $totalFourRatings ?? 0,
                    5 => $totalFiveRatings ?? 0,
                ),
            ];

            ResponseService::successResponse(__('Seller Details Fetched Successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getSeller');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Seller Slugs
     */
    public function getSellerSlug(Request $request)
    {
        try {
            $sellers = User::select('id')
                ->whereNull('deleted_at')
                ->paginate(500);

            if ($sellers->isEmpty()) {
                return ResponseService::errorResponse(__('No active seller found.'));
            }

            return ResponseService::successResponse(__('Active Seller fetched successfully.'), $sellers);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategoriesSlug');
            ResponseService::errorResponse();
        }
    }

    /**
     * Get Notification List
     */
    public function getNotificationList(Request $request)
    {
        try {
            $user = Auth::user();
            $authId = $user->id;
            $userCreatedAt = $user->created_at;
            $id = $request->id;

            $query = Notifications::with([
                    'item.area',
                    'item.translations',
                    'item.category:id,name,is_job_category',
                    'item.currency:id,symbol,decimal_places,decimal_separator,thousand_separator,symbol_position',
                    'item.gallery_images:id,item_id,image,is_default',
                    'item.user:id,name,profile',
                ])
                ->where(function ($q) use ($authId, $userCreatedAt) {
                    $q->whereRaw('FIND_IN_SET(?, user_id)', [$authId])
                        ->orWhere(function ($sq) use ($userCreatedAt) {
                            $sq->where('send_to', 'all')
                                ->where('created_at', '>=', $userCreatedAt);
                        });
                });

            if (! empty($id)) {
                $notifications = $query->where('id', $id)->first();
                if (! $notifications) {
                    return ResponseService::successResponse(__('Notification not found'), null);
                }
                $notificationCollection = collect([$notifications]);
            } else {
                $notifications = $query->orderBy('id', 'DESC')->paginate();
                $notificationCollection = $notifications->getCollection();
            }

            foreach ($notificationCollection as $notification) {
                if ($notification->item) {
                    $formattedItem = (new ItemApiResource(collect([$notification->item])))->asSingle()->resolve($request);
                    $notification->unsetRelation('item');
                    $notification->setAttribute('item', $formattedItem);
                }
            }

            return ResponseService::successResponse(__('Notification fetched successfully'), $notifications);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getNotificationList');

            return ResponseService::errorResponse();
        }
    }
}
