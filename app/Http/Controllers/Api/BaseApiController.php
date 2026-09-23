<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferPointTransaction;
use App\Models\User;
use App\Services\CachingService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class BaseApiController extends Controller
{
    public function __construct()
    {
        if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER) && ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->middleware('auth:sanctum');
        }
    }

    /**
     * Extract a single file path from a raw value.
     * Handles both old JSON-encoded data and plain path strings.
     */
    protected function extractFilePath($rawValue)
    {
        if (empty($rawValue)) {
            return '';
        }

        if (json_validate($rawValue)) {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0] ?? '';
            }
            return is_string($decoded) ? $decoded : '';
        }

        return $rawValue;
    }

    protected function awardReferralPointsForUser($userId, $package)
    {
        try {
            $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
            if ($referEarnEnabled != '1') {
                Log::info("Refer Points not enabled");
                return;
            }

            if (empty($package) || $package->final_price <= 0) {
                Log::info("Package is not paid, Refer points will not be used");
                return;
            }

            $referral = Referral::where('referred_id', $userId)
                ->where('is_rewarded', false)
                ->first();

            if (empty($referral)) {
                Log::info("Referral is already rewarded");
                return;
            }

            $pointsForReferrer = (int) (CachingService::getSystemSettings('refer_points_for_referrer') ?: 10);
            $pointsForReferred = (int) (CachingService::getSystemSettings('refer_points_for_referred') ?: 5);

            // Credit referrer
            $referrer = User::lockForUpdate()->find($referral->referrer_id);
            if ($referrer) {
                $referrer->increment('refer_points', $pointsForReferrer);
                $referrer->refresh();

                ReferPointTransaction::create([
                    'user_id' => $referrer->id,
                    'points' => $pointsForReferrer,
                    'transaction_type' => 'credit',
                    'type' => 'earned_by_referral',
                    'remark' => 'Earned ' . $pointsForReferrer . ' points - referred user purchased ' . ($package->name ?? 'a package'),
                    'points_remaining_after' => $referrer->refer_points,
                    'reference_id' => $referral->id,
                    'reference_type' => 'referral',
                ]);

                NotificationService::dispatchChunkedNotifications(
                    'Referral Reward',
                    'You earned ' . $pointsForReferrer . ' refer points!',
                    'refer_earn',
                    ['referral_id' => $referral->id],
                    false,
                    [$referrer->id],
                    true
                );
            }

            // Credit referred user
            $referredUser = User::lockForUpdate()->find($userId);
            if ($referredUser) {
                $referredUser->increment('refer_points', $pointsForReferred);
                $referredUser->refresh();

                ReferPointTransaction::create([
                    'user_id' => $referredUser->id,
                    'points' => $pointsForReferred,
                    'transaction_type' => 'credit',
                    'type' => 'earned_as_referred',
                    'remark' => 'Earned ' . $pointsForReferred . ' points for first paid plan purchase via referral',
                    'points_remaining_after' => $referredUser->refer_points,
                    'reference_id' => $referral->id,
                    'reference_type' => 'referral',
                ]);
            }

            $referral->update([
                'is_rewarded' => true,
                'rewarded_at' => now(),
            ]);
            Log::info("Referral Rewarded from paid package purchased");
        } catch (Throwable $th) {
            Log::error('Error awarding referral points: ' . $th->getMessage());
        }
    }
}
