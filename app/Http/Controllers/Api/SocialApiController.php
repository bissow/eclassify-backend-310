<?php

namespace App\Http\Controllers\Api;

use App\Models\BlockUser;
use App\Models\User;
use App\Models\UserFollow;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Social */
class SocialApiController extends BaseApiController
{
    /** Block User */
    public function blockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blocked_user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            BlockUser::create([
                'user_id' => Auth::user()->id,
                'blocked_user_id' => $request->blocked_user_id,
            ]);
            ResponseService::successResponse(__('User Blocked Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> blockUser');
            ResponseService::errorResponse();
        }
    }

    /** Unblock User */
    public function unblockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blocked_user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            BlockUser::where([
                'user_id' => Auth::user()->id,
                'blocked_user_id' => $request->blocked_user_id,
            ])->delete();
            ResponseService::successResponse(__('User Unblocked Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> unblockUser');
            ResponseService::errorResponse();
        }
    }

    /** Get Blocked Users */
    public function getBlockedUsers()
    {
        try {
            $blockedUsers = BlockUser::where('user_id', Auth::user()->id)->pluck('blocked_user_id');
            $users = User::whereIn('id', $blockedUsers)->select(['id', 'name', 'profile'])->get();
            ResponseService::successResponse(__('User Unblocked Successfully'), $users);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> unblockUser');
            ResponseService::errorResponse();
        }
    }

    /** Follow User */
    public function followUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $authUser = Auth::user();
            $targetUserId = (int) $request->user_id;

            if ($authUser->id === $targetUserId) {
                return ResponseService::errorResponse(__('You cannot follow yourself.'));
            }

            $targetUser = User::where('id', $targetUserId)
                ->whereNull('deleted_at')
                ->first();

            if (! $targetUser) {
                return ResponseService::errorResponse(__('User not found or has been deactivated.'));
            }

            if ($authUser->isFollowing($targetUserId)) {
                return ResponseService::errorResponse(__('You are already following this user.'));
            }

            UserFollow::create([
                'follower_id'  => $authUser->id,
                'following_id' => $targetUserId,
            ]);

            if (! empty($targetUserId)) {
                NotificationService::dispatchChunkedNotifications(
                    __('New Follower'),
                    $authUser->name . __(' has started following you.'),
                    'user-follow',
                    [
                        'follower_id' => $authUser->id,
                        'follower_name' => $authUser->name,
                        'follower_profile' => $authUser->profile,
                    ],
                    false,
                    array($targetUserId)
                );
            }

            return ResponseService::successResponse(__('User followed successfully.'), [
                'is_following' => true,
                'follower_id' => $authUser->id,
                'following_id' => $targetUserId,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> followUser');
            return ResponseService::errorResponse();
        }
    }

    /** Unfollow User */
    public function unfollowUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $authUser = Auth::user();
            $targetUserId = (int) $request->user_id;

            if ($authUser->id === $targetUserId) {
                return ResponseService::errorResponse(__('You cannot unfollow yourself.'));
            }

            $follow = UserFollow::where('follower_id', $authUser->id)
                ->where('following_id', $targetUserId)
                ->first();

            if (! $follow) {
                return ResponseService::errorResponse(__('You are not following this user.'));
            }

            $follow->delete();

            return ResponseService::successResponse(__('User unfollowed successfully.'), [
                'is_following' => false,
                'follower_id' => $authUser->id,
                'following_id' => $targetUserId,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> unfollowUser');
            return ResponseService::errorResponse();
        }
    }

    /** Get Followers */
    public function getFollowers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authUserId = (int) Auth::id();
            $targetUserId = (int) ($request->input('user_id') ?? $authUserId);
            $searchQuery = trim((string) $request->input('search', ''));

            $targetUserExists = User::query()
                ->whereKey($targetUserId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $targetUserExists) {
                ResponseService::errorResponse(__('User not found or has been deactivated.'));
            }

            $followersQuery = UserFollow::query()
                ->where('following_id', $targetUserId)
                ->with(['follower' => function ($q) {
                    $q->whereNull('deleted_at')
                        ->select('id', 'name', 'email', 'mobile', 'profile', 'is_verified');
                }])
                ->whereHas('follower', function ($q) use ($searchQuery) {
                    $q->whereNull('deleted_at');
                    if ($searchQuery !== '') {
                        $q->where(function ($sq) use ($searchQuery) {
                            $sq->where('name', 'LIKE', "%{$searchQuery}%")
                                ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                                ->orWhere('mobile', 'LIKE', "%{$searchQuery}%");
                        });
                    }
                })
                ->select('follower_id', 'following_id', 'created_at')
                ->orderByDesc('created_at');

            $followers = $followersQuery->paginate()->appends($request->query());

            $followers->getCollection()->transform(function ($followRecord) {
                $user = $followRecord->follower;
                if ($user) {
                    $user->followed_at = $followRecord->created_at;
                    $user->follower_id = $followRecord->follower_id;
                    $user->following_id = $followRecord->following_id;
                }
                return $user;
            })->filter();

            $authFollowingIds = UserFollow::query()
                ->where('follower_id', $authUserId)
                ->pluck('following_id')
                ->all();

            $authFollowerIds = UserFollow::query()
                ->where('following_id', $authUserId)
                ->pluck('follower_id')
                ->all();

            $followers->getCollection()->transform(function ($user) use ($authFollowingIds, $authFollowerIds) {
                $user->is_following = in_array($user->id, $authFollowingIds, true) ? 1 : 0;
                $user->is_followed_by = in_array($user->id, $authFollowerIds, true) ? 1 : 0;
                $user->email = HelperService::maskDemoEmail($user->email);
                $user->mobile = HelperService::maskDemoMobile($user->mobile);

                return $user;
            });

            ResponseService::successResponse(__('Followers fetched successfully'), $followers);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFollowers');
            ResponseService::errorResponse(__('Failed to fetch followers'));
        }
    }

    /** Get Following */
    public function getFollowing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $authUserId = (int) Auth::id();
            $targetUserId = (int) ($request->input('user_id') ?? $authUserId);
            $searchQuery = trim((string) $request->input('search', ''));

            $targetUserExists = User::query()
                ->whereKey($targetUserId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $targetUserExists) {
                ResponseService::errorResponse(__('User not found or has been deactivated.'));
            }

            $followingQuery = UserFollow::query()
                ->where('follower_id', $targetUserId)
                ->with(['following' => function ($q) {
                    $q->whereNull('deleted_at')
                        ->select('id', 'name', 'email', 'mobile', 'profile', 'is_verified');
                }])
                ->whereHas('following', function ($q) use ($searchQuery) {
                    $q->whereNull('deleted_at');
                    if ($searchQuery !== '') {
                        $q->where(function ($sq) use ($searchQuery) {
                            $sq->where('name', 'LIKE', "%{$searchQuery}%")
                                ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                                ->orWhere('mobile', 'LIKE', "%{$searchQuery}%");
                        });
                    }
                })
                ->select('follower_id', 'following_id', 'created_at')
                ->orderByDesc('created_at');

            $following = $followingQuery
                ->paginate()
                ->appends($request->query());

            $following->getCollection()->transform(function ($followRecord) {
                $user = $followRecord->following;
                if ($user) {
                    $user->followed_at = $followRecord->created_at;
                    $user->follower_id = $followRecord->follower_id;
                    $user->following_id = $followRecord->following_id;
                }
                return $user;
            })->filter();

            $authFollowingIds = UserFollow::query()
                ->where('follower_id', $authUserId)
                ->pluck('following_id')
                ->all();

            $authFollowerIds = UserFollow::query()
                ->where('following_id', $authUserId)
                ->pluck('follower_id')
                ->all();

            $following->getCollection()->transform(function ($user) use ($authFollowingIds, $authFollowerIds) {
                $user->is_following = in_array($user->id, $authFollowingIds, true) ? 1 : 0;
                $user->is_followed_by = in_array($user->id, $authFollowerIds, true) ? 1 : 0;
                $user->email = HelperService::maskDemoEmail($user->email);
                $user->mobile = HelperService::maskDemoMobile($user->mobile);

                return $user;
            });

            ResponseService::successResponse(__('Following list fetched successfully'), $following);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFollowing');
            ResponseService::errorResponse(__('Failed to fetch following list'));
        }
    }
}
