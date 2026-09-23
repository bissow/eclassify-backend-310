<?php

namespace App\Http\Controllers\Api;

use App\Models\NumberOtp;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\SocialLogin;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * @tags Authentication
 */
class AuthApiController extends BaseApiController
{

    /** User Signup */
    public function userSignup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:email,google,phone,apple',
                'firebase_id' => 'nullable|string',
                'platform_type' => 'nullable|in:android,ios',
                'region_code' => 'nullable|string',
                'country_code' => 'nullable|string',
                'mobile' => 'required_if:type,phone',
                'password' => 'nullable|string|min:6',
                'is_login' => 'boolean',
                'email' => 'required_if:type,email'
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $type = $request->type;
            $firebase_id = $request->firebase_id;
            if (in_array($type, ['phone', 'email', 'google']) && ! empty($request->password) && $request->boolean('is_login')) {

                if ($type == 'phone') {
                    $mobile = ltrim($request->mobile, '+');
                    $countryCode = ltrim($request->country_code, '+');
                    $user = User::where('mobile', $mobile) ->withTrashed() ->first();
                } else {
                    $user = User::where('email', $request->email) ->withTrashed() ->first();
                }

                if (!$user) {
                    return ResponseService::errorResponse(
                        __('User not found. Please signup first.')
                    );
                }

                if ($user->deleted_at) {
                    return ResponseService::errorResponse(
                        __('User is deactivated. Please contact the administrator.')
                    );
                }

                if (in_array($type, ['phone', 'email']) && empty($user->password)) {
                    return ResponseService::errorResponse(
                        __('Password is not set. Please set your password using the forgot password option.')
                    );
                }

                if (! Hash::check($request->password, $user->password)) {
                    return ResponseService::errorResponse(__('Invalid password.'));
                }
            }
            $socialLogin = null;
            if (! empty($firebase_id)) {
                $socialLogin = SocialLogin::where('firebase_id', $firebase_id)
                    ->where('type', $type)
                    ->with('user', fn($q) => $q->withTrashed())
                    ->whereHas('user', fn($q) => $q->role('User'))
                    ->first();
            }

            if ($socialLogin && ! empty($socialLogin->user->deleted_at)) {
                return ResponseService::errorResponse(__('User is deactivated. Please Contact the administrator'));
            }
            $newUser = 0;
            if (empty($socialLogin)) {
                DB::beginTransaction();

                if ($request->type == 'phone') {
                    $unique['mobile'] = $request->mobile;
                } else {
                    $unique['email'] = $request->email;
                }

                $existingUser = User::withTrashed()->where($unique)->first();

                if ($existingUser && $existingUser->trashed()) {
                    return ResponseService::errorResponse(__('Your account has been deactivated.'), null, config('constants.RESPONSE_CODE.DEACTIVATED_ACCOUNT'));
                }

                $dataToUpdate = array();
                if($request->region_code){
                    $dataToUpdate = [
                        'region_code' => $request->region_code,
                    ];
                }

                if($request->hasFile('profile')) {
                    $dataToUpdate['profile'] = $request->file('profile')->store('user_profile', 'public');
                }
                
                if ($request->filled('password')) {
                    $dataToUpdate['password'] = Hash::make($request->password);
                }
                $user = User::updateOrCreate($unique, array_merge($request->all(), $dataToUpdate));
                if ($user->wasRecentlyCreated) {
                    $newUser = 1;
                }


                if (! empty($firebase_id)) {
                    SocialLogin::updateOrCreate(
                        ['type' => $type, 'user_id' => $user->id],
                        ['firebase_id' => $firebase_id]
                    );
                }
                $user->assignRole('User');
                $auth = User::find($user->id);
                DB::commit();
            } else {
                if ($socialLogin->user && ! empty($countryCode)) {
                    $socialLogin->user->update([
                        'country_code' => ltrim($countryCode, '+'),
                    ]);
                }
                $auth = User::find($socialLogin->user_id);
            }
            if (! $auth->hasRole('User')) {
                ResponseService::errorResponse(__('Invalid Login Credentials'), null, config('constants.RESPONSE_CODE.INVALID_LOGIN'));
            }
            if (! empty($request->fcm_id)) {
                UserFcmToken::updateOrCreate(['fcm_token' => $request->fcm_id], ['user_id' => $auth->id, 'platform_type' => $request->platform_type, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
            }
            $auth->fcm_id = $request->fcm_id;
            if (! empty($request->registration)) {
                $token = null;
            } else {
                $token = $auth->createToken($auth->name ?? '')->plainTextToken;
            }
            $auth->new_user = $newUser;
            Auth::guard('sanctum')->setUser($auth);
            
            if ($auth) {
                NotificationService::sendNewDeviceLoginEmail($auth, $request);
            }
            ResponseService::successResponse(__('User logged-in successfully'), $auth, ['token' => $token]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> Signup');
            ResponseService::errorResponse();
        }
    }

    /** Reset Password */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|string',
                'country_code' => 'required|string',
                'new_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $mobile = ltrim($request->number, '+');
            $countryCode = ltrim($request->country_code, '+');

            $mobileWithCode = $countryCode . $mobile;

            $user = User::where('type', 'phone')
                ->where('mobile', $mobile)->first();

            if (! $user) {
                ResponseService::errorResponse(__('User not found.'));
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            ResponseService::successResponse(__('Password reset successfully.'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> resetPassword');
            ResponseService::errorResponse();
        }
    }

    /** Logout */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }
            if ($request->fcm_token) {
                UserFcmToken::where('user_id', $user->id)
                    ->where('fcm_token', $request->fcm_token)
                    ->delete();
            }

            // remove current token
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            auth()->guard('sanctum')->forgetUser();

            return ResponseService::successResponse(__('User logged out successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> Logout');

            return ResponseService::errorResponse();
        }
    }

    /** Delete User */
    public function deleteUser()
    {
        try {
            User::findOrFail(Auth::user()->id)->forceDelete();
            ResponseService::successResponse(__('User Deleted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> deleteUser');
            ResponseService::errorResponse();
        }
    }

    /** User Exists */
    public function userExists(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'nullable|string',
            'country_code' => 'required_with:mobile|string',
            'firebase_id' => 'required_without:mobile|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $mobile = ltrim($request->mobile, '+');
            $countryCode = ltrim($request->country_code, '+');
            $firebaseId = $request->has('firebase_id') ? $request->firebase_id : null;
            $userExists = User::when($mobile, function ($query) use ($mobile,$countryCode) {
                        $query->where(['mobile' => $mobile])->where('country_code', 'LIKE', '%'.$countryCode.'%'); // Need to improve in future
                    })
                    ->when($firebaseId, function ($query) use ($firebaseId) {
                        $query->whereHas('socialLogins', function ($query) use ($firebaseId) {
                            $query->where('firebase_id', $firebaseId);
                        });
                    })
                    ->withTrashed()
                    ->first();

                if (! $userExists) {
                    return ResponseService::errorResponse(
                        __('User does not exist'),
                        ['user_exists' => false]
                    );
                }

                if ($userExists->deleted_at) {
                    return ResponseService::errorResponse(
                        __('User is deactivated. Please contact the administrator.'),
                        ['user_exists' => false]
                    );
                }
            if ($userExists) {
                return ResponseService::successResponse(
                    __('User already exists'),
                    ['user_exists' => true]
                );
            }

            return ResponseService::errorResponse(
                __('User does not exist'),
                ['user_exists' => false]
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> userExists');
            return ResponseService::errorResponse();
        }
    }

    /** Get OTP */
    public function getOtp(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->query(), [
                'country_code' => 'required|string|max:5',
                'number' => 'required|string|max:15',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseService::validationError($validator->errors()->first());
            }

            $countryCode = ltrim(trim($request->query('country_code')), '+');
            $number = preg_replace('/\D/', '', $request->query('number'));
            $toNumber = '+' . $countryCode . $number;

            $provider = Setting::where('name', 'otp_service_provider')->value('value');

            if ($provider === 'twilio') {

                $twilioSettings = Setting::whereIn('name', [
                    'twilio_account_sid',
                    'twilio_auth_token',
                    'twilio_my_phone_number',
                ])->pluck('value', 'name');

                if ($twilioSettings->count() < 3) {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('Twilio settings are missing.'));
                }

                $client = new \Twilio\Rest\Client(
                    $twilioSettings['twilio_account_sid'],
                    $twilioSettings['twilio_auth_token']
                );

                try {
                    $client->lookups->v1->phoneNumbers($toNumber)->fetch();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('Invalid phone number.'));
                }

                $otp = rand(100000, 999999);
                $expireAt = now()->addMinutes(10);

                $otpRecord = NumberOtp::updateOrCreate(
                    ['number' => $number],
                    [
                        'otp' => bcrypt($otp),
                        'expire_at' => $expireAt,
                        'attempts' => 0,
                    ]
                );

                $client->messages->create($toNumber, [
                    'from' => $twilioSettings['twilio_my_phone_number'],
                    'body' => "Your OTP is: $otp. It expires in 10 minutes.",
                ]);

                DB::commit();
                return ResponseService::successResponse(__('OTP sent successfully.'));
            }

            if ($provider === '2factor') {

                $apiKey = Setting::where('name', 'twofactor_api_key')->value('value');

                if (empty($apiKey)) {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('2Factor API key missing.'));
                }

                $url = "https://2factor.in/API/V1/{$apiKey}/SMS/{$number}/AUTOGEN";

                $response = Http::get($url)->json();

                if (!isset($response['Status']) || $response['Status'] !== 'Success') {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('Failed to send OTP via 2Factor.'));
                }

                $otpRecord = NumberOtp::updateOrCreate(
                    ['number' => $number],
                    [
                        'session_id' => $response['Details'],
                        'expire_at' => now()->addMinutes(5),
                        'attempts' => 0,
                    ]
                );

                DB::commit();
                return ResponseService::successResponse(__('OTP sent successfully.'));
            }

            DB::commit();
            return ResponseService::errorResponse(__('Invalid OTP provider configured.'));
        } catch (\Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'OTP Controller -> getOtp');
            return ResponseService::errorResponse();
        }
    }

    /** Verify OTP */
    public function verifyOtp(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'number' => 'required|string',
                'country_code' => 'required|string',
                'otp' => 'required|numeric|digits:6',
                'password' => 'nullable|string|min:6',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseService::validationError($validator->errors()->first());
            }

            $number = preg_replace('/\D/', '', $request->number);
            $countryCode = $request->country_code;

            $provider = Setting::where('name', 'otp_service_provider')->value('value');

            $otpRecord = NumberOtp::where('number', $number)->first();

            if (! $otpRecord) {
                DB::rollBack();
                return ResponseService::errorResponse(__('OTP not found.'));
            }


            if ($provider === 'twilio') {

                if (now()->isAfter($otpRecord->expire_at)) {
                    DB::rollBack();
                    return ResponseService::validationError(__('OTP has expired.'));
                }

                if ($otpRecord->attempts >= 3) {
                    $otpRecord->delete();
                    DB::commit();
                    return ResponseService::validationError(__('OTP expired after 3 failed attempts.'));
                }

                if (! Hash::check($request->otp, $otpRecord->otp)) {
                    $otpRecord->increment('attempts');
                    DB::commit();
                    return ResponseService::validationError(__('Invalid OTP.'));
                }

                $otpRecord->delete();
            } elseif ($provider === '2factor') {

                $apiKey = Setting::where('name', 'twofactor_api_key')->value('value');

                if (empty($apiKey)) {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('2Factor API key missing.'));
                }

                if (empty($otpRecord->session_id)) {
                    DB::rollBack();
                    return ResponseService::errorResponse(__('OTP session expired.'));
                }

                $url = "https://2factor.in/API/V1/{$apiKey}/SMS/VERIFY/{$otpRecord->session_id}/{$request->otp}";

                $response = Http::get($url)->json();

                if (
                    ! isset($response['Status']) ||
                    $response['Status'] !== 'Success'
                ) {
                    DB::rollBack();
                    return ResponseService::validationError(__('Invalid or expired OTP.'));
                }

                $otpRecord->delete();
            } else {
                DB::rollBack();
                return ResponseService::errorResponse(__('Invalid OTP provider configured.'));
            }

            $user = User::where('mobile', $number)
                ->where('type', 'phone')
                ->first();

            if (! $user) {
                $user = User::createWithReferralCode([
                    'mobile' => $number,
                    'type' => 'phone',
                    'country_code' => $countryCode,
                    'password' => ! empty($request->password) ? Hash::make($request->password) : null,
                ]);
                $user->assignRole('User');
            }else{
                if (! empty($countryCode)) {
                    $user->country_code = $countryCode;
                }
                $user->save();
            }

            $token = $user->createToken($user->name ?? '')->plainTextToken;
            Auth::guard('sanctum')->setUser($user);

            DB::commit();

            return ResponseService::successResponse(
                __('User logged-in successfully'),
                $user,
                ['token' => $token]
            );
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'OTP Controller -> verifyOtp');
            return ResponseService::errorResponse();
        }
    }
}
