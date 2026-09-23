<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Models\Referral;
use App\Models\ReferPointTransaction;
use App\Models\User;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Referral */
class ReferralApiController extends BaseApiController
{
    /** Get Refer Points Balance */
    public function getReferPointsBalance()
    {
        try {
            $user = Auth::user();
            $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled') ?? '0';

            if (empty($user->referral_code)) {
                $user->referral_code = User::generateUniqueReferralCode();
                $user->save();
            }

            $data = [
                'refer_points' => (int) $user->refer_points,
                'referral_code' => $user->referral_code,
                'refer_earn_enabled' => $referEarnEnabled == '1',
                'total_referrals' => Referral::where('referrer_id', $user->id)->count(),
                'rewarded_referrals' => Referral::where('referrer_id', $user->id)->where('is_rewarded', true)->count(),
                'used_referral_code' => $user->used_referral_code
            ];

            if ($referEarnEnabled == '1') {
                $data['refer_points_for_referrer'] = (int) (CachingService::getSystemSettings('refer_points_for_referrer') ?: 10);
                $data['refer_points_for_referred'] = (int) (CachingService::getSystemSettings('refer_points_for_referred') ?: 5);
            }

            ResponseService::successResponse(__('Data Fetched Successfully'), $data);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getReferPointsBalance');
            ResponseService::errorResponse();
        }
    }

    /** Get Refer Points History */
    public function getReferPointsHistory()
    {
        try {
            $transactions = ReferPointTransaction::where('user_id', Auth::user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate();

            ResponseService::successResponse(__('Data Fetched Successfully'), $transactions);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getReferPointsHistory');
            ResponseService::errorResponse();
        }
    }

    /** Get Referral Code */
    public function getReferralCode()
    {
        try {
            $user = Auth::user();

            if (empty($user->referral_code)) {
                $user->referral_code = User::generateUniqueReferralCode();
                $user->save();
            }

            ResponseService::successResponse(__('Data Fetched Successfully'), [
                'referral_code' => $user->referral_code,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getReferralCode');
            ResponseService::errorResponse();
        }
    }

    /** Get Referral Points Calculation based on Package Selected */
    public function calculateReferralPointsForPackage(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $user = Auth::user();
            $userReferPoints = $user->refer_points;
            if($userReferPoints == 0){
                ResponseService::validationError(__('You do not have enough refer points to use in this package'));
            }

            // Check Refer Points enabled or not
            $referPointsEnabled = CachingService::getSystemSettings('refer_earn_enabled');
            if(empty($referPointsEnabled) || $referPointsEnabled == '0'){
                ResponseService::errorResponse(__('Referral system is disabled'));
            }

            // Get Package Data
            $package = Package::where(['id' => $request->package_id, 'status' => 1])->first();
            if(!$package){
                ResponseService::errorResponse(__('Package not found'));
            }

            // Check Package is free or paid
            if($package->final_price == 0){
                ResponseService::validationError(__('Package is not paid'));
            }

            // Get Refer points data from package if available or else from global settings
            if(!empty($package->refer_max_points_usage_percentage) && !empty($package->refer_min_points_to_use) && !empty($package->refer_max_points_to_use)){
                $referMaxPointsUsagePercentage = $package->refer_max_points_usage_percentage;
                $referMinPointsToUse = $package->refer_min_points_to_use;
                $referMaxPointsToUse = $package->refer_max_points_to_use;
            } else {
                $referPointsSettingsData = CachingService::getSystemSettings(['refer_max_points_usage_percentage', 'refer_min_points_to_use', 'refer_max_points_to_use']);
                $referMaxPointsUsagePercentage = !empty($referPointsSettingsData['refer_max_points_usage_percentage']) ? $referPointsSettingsData['refer_max_points_usage_percentage'] : 0;
                $referMinPointsToUse = !empty($referPointsSettingsData['refer_min_points_to_use']) ? $referPointsSettingsData['refer_min_points_to_use'] : 0;
                $referMaxPointsToUse = !empty($referPointsSettingsData['refer_max_points_to_use']) ? $referPointsSettingsData['refer_max_points_to_use'] : 0;
            }

            // Calculate Refer Points to be used and will be floored no decimal points used
            $referPointsCanBeUsed = floor(($package->final_price * $referMaxPointsUsagePercentage) / 100);
            if($referPointsCanBeUsed > $userReferPoints){
                $referPointsCanBeUsed = $userReferPoints;
            }
            if($referPointsCanBeUsed < $referMinPointsToUse){
                ResponseService::validationError(__('You do not have enough refer points to use in this package'));
            }
            if($referPointsCanBeUsed > $referMaxPointsToUse){
                $referPointsCanBeUsed = $referMaxPointsToUse;
            }
            // Cap to final price so points are never over-consumed
            if($referPointsCanBeUsed > $package->final_price){
                $referPointsCanBeUsed = $package->final_price;
            }
            // Remaining Final Amount should be 0 or greater than zero
            $remainingFinalAmount = max(0, $package->final_price - $referPointsCanBeUsed);
            ResponseService::successResponse(__('Data Fetched Successfully'), [
                'package_final_amount' => $package->final_price,
                'refer_points_can_be_used' => $referPointsCanBeUsed,
                'remaining_final_amount' => $remainingFinalAmount,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> calculateReferralPointsForPackage');
            ResponseService::errorResponse();
        }
    }
}
