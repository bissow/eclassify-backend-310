<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Models\PluginLicense;
use App\Models\ReferPointTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPurchasedPackage;
use App\Services\CachingService;
use App\Services\CurrencyFormatterService;
use App\Services\FileService;
use App\Services\Payment\PaymentService;
use App\Services\PaymentReceiptService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Str;
use Throwable;

/** @tags Payment */
class PaymentApiController extends BaseApiController
{
    /** Get Payment Settings */
    public function getPaymentSettings()
    {
        try {
            $result = PaymentConfiguration::select(['currency_code', 'payment_method', 'api_key', 'status'])->where('status', 1)->get();
            $response = [];
            foreach ($result as $payment) {
                if (strtolower($payment->payment_method) == 'stripe' || strtolower($payment->payment_method) == 'razorpay') {
                    $response[$payment->payment_method] = $payment->toArray();
                } else {
                    $response[$payment->payment_method] = $payment->except(['api_key']);
                }
            }

            $disabledPluginSlugs = PluginLicense::where('type', 'payment')->where('is_enabled', false)->pluck('plugin_slug');
            foreach ($disabledPluginSlugs as $slug) {
                unset($response[$slug]);
            }

            $settings = Setting::whereIn('name', [
                'account_holder_name',
                'bank_name',
                'account_number',
                'ifsc_swift_code',
                'bank_transfer_status',
            ])->get();

            $bankDetails = [];
            $bankTransferEnabled = false;

            foreach ($settings as $row) {
                if ($row->name === 'bank_transfer_status') {
                    $bankTransferEnabled = (int) $row->value === 1;
                    continue;
                }

                $bankDetails[$row->name] = $row->value;
            }

            if ($bankTransferEnabled) {
                $bankDetails['status'] = 1;
                $response['bankTransfer'] = $bankDetails;
            }
            ResponseService::successResponse(__('Data Fetched Successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getPaymentSettings');
            ResponseService::errorResponse();
        }
    }

    /** Get Payment Intent */
    public function getPaymentIntent(Request $request)
    {
        // Built-in gateways stay a fixed list; installed type=payment plugins
        // (e.g. PayU) add themselves here automatically — see PluginLicense.
        $builtInGateways = ['Stripe', 'Razorpay', 'Paystack', 'Paytabs', 'PhonePe', 'FlutterWave', 'bankTransfer', 'PayPal', 'DPO'];
        $pluginGateways = PluginLicense::where('type', 'payment')->where('is_enabled', true)->pluck('plugin_slug')->all();

        $validator = Validator::make($request->all(), [
            'package_id' => 'required',
            'payment_method' => ['required', Rule::in(array_merge($builtInGateways, $pluginGateways))],
            'platform_type' => 'required_if:payment_method,==,Paystack|string',
            'refer_points_used' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            DB::beginTransaction();

            if ($request->payment_method !== 'bankTransfer') {
                $paymentConfigurations = PaymentConfiguration::where(['status' => 1, 'payment_method' => $request->payment_method])->first();
                if (empty($paymentConfigurations)) {
                    ResponseService::errorResponse(__('Payment is not Enabled'));
                }
            } else {
                $bankTransferEnabled = Setting::where('name', 'bank_transfer_status')->value('value');
                if ($bankTransferEnabled != 1) {
                    ResponseService::errorResponse(__('Bank Transfer is not enabled.'));
                }
            }

            $package = Package::whereNot('final_price', 0)->where('status', 1)->find($request->package_id);
            if(collect($package)->isEmpty()) {
                ResponseService::errorResponse(__('Package not found'));
            }

            $purchasedPackage = UserPurchasedPackage::onlyActive()->where(['user_id' => Auth::user()->id, 'package_id' => $request->package_id])->first();
            if (! empty($purchasedPackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }
            if ($request->payment_method === 'bankTransfer') {
                $existingTransaction = PaymentTransaction::where('user_id', Auth::user()->id)
                    ->where('package_id', $request->package_id)
                    ->where('payment_gateway', $request->payment_method)
                    ->whereIn('payment_status', ['pending', 'under review'])
                    ->exists();
                    
                    if ($existingTransaction) {
                        return ResponseService::errorResponse(__("A Bank Transfer transaction for this package already exists."));
                    }
            }
            // Handle refer points usage
            $referPointsUsed = (int) ($request->refer_points_used ?? 0);
            $finalAmount = $package->final_price;

            if ($referPointsUsed > 0) {
                $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
                if ($referEarnEnabled != '1') {
                    ResponseService::errorResponse(__('Refer & Earn is not enabled'));
                }

                $user = Auth::user();
                if ($user->refer_points < $referPointsUsed) {
                    ResponseService::errorResponse(__('Insufficient refer points'));
                }

                // Get effective settings (package-level or global fallback)
                $maxPercentage = $package->refer_max_points_usage_percentage
                    ?? (int) (CachingService::getSystemSettings('refer_max_points_usage_percentage') ?: 10);
                $minPoints = $package->refer_min_points_to_use
                    ?? (int) (CachingService::getSystemSettings('refer_min_points_to_use') ?: 5);
                $maxPoints = $package->refer_max_points_to_use
                    ?? (int) (CachingService::getSystemSettings('refer_max_points_to_use') ?: 50);

                $maxFromPercentage = (int) floor($package->final_price * $maxPercentage / 100);
                $effectiveMax = min($maxFromPercentage, $maxPoints);

                if ($referPointsUsed < $minPoints) {
                    ResponseService::errorResponse(__('Minimum') . ' ' . $minPoints . ' ' . __('points required to use'));
                }
                if ($referPointsUsed > $effectiveMax) {
                    ResponseService::errorResponse(__('Maximum') . ' ' . $effectiveMax . ' ' . __('points can be used for this package'));
                }

                $finalAmount = $package->final_price - $referPointsUsed;
            }

            // If package becomes free via points - handle directly
            if ($finalAmount <= 0 && $referPointsUsed > 0) {
                $paymentTransactionData = PaymentTransaction::create([
                    'user_id' => Auth::user()->id,
                    'package_id' => $request->package_id,
                    'amount' => 0,
                    'original_price' => $package->price,
                    'discount_price' => $package->price - $package->final_price,
                    'payment_gateway' => 'ReferPoints',
                    'payment_status' => 'succeed',
                    'order_id' => 'refer_' . uniqid(),
                    'refer_points_used' => $referPointsUsed,
                ]);

                // Create purchased package directly
                UserPurchasedPackage::create([
                    'package_id' => $package->id,
                    'user_id' => Auth::user()->id,
                    'start_date' => Carbon::now(),
                    'end_date' => is_null($package->duration) ? null : Carbon::now()->addDays($package->duration),
                    'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                    'listing_duration_type' => $package->listing_duration_type,
                    'listing_duration_days' => $package->listing_duration_days,
                    'payment_transactions_id' => $paymentTransactionData->id,
                ]);

                // Deduct points
                $user = User::lockForUpdate()->find(Auth::user()->id);
                $user->decrement('refer_points', $referPointsUsed);
                $user->refresh();

                ReferPointTransaction::create([
                    'user_id' => $user->id,
                    'points' => $referPointsUsed,
                    'transaction_type' => 'debit',
                    'type' => 'used_for_purchase',
                    'remark' => 'Used ' . $referPointsUsed . ' points for ' . $package->name . ' purchase (free via points)',
                    'package_original_price' => $package->price,
                    'package_discounted_price' => $package->final_price,
                    'points_used' => $referPointsUsed,
                    'points_remaining_after' => $user->refer_points,
                    'final_payment_amount' => 0,
                    'reference_id' => $paymentTransactionData->id,
                    'reference_type' => 'payment_transaction',
                ]);

                // Award referral points if applicable
                $this->awardReferralPointsForUser(Auth::user()->id, $package);

                DB::commit();
                ResponseService::successResponse(__('Package purchased successfully using refer points'), [
                    'payment_transaction' => $paymentTransactionData,
                ]);
            }

            $orderId = ($request->payment_method === 'bankTransfer') ? uniqid() . '-' . 'p' . '-' . $package->id : null;

            // Add Payment Data to Payment Transactions Table
            $paymentTransactionData = PaymentTransaction::create([
                'user_id' => Auth::user()->id,
                'package_id' => $request->package_id,
                'amount' => $finalAmount,
                'original_price' => $package->price,
                'discount_price' => $package->price - $package->final_price,
                'payment_gateway' => ucfirst($request->payment_method),
                'payment_status' => 'pending',
                'order_id' => $orderId,
                'refer_points_used' => $referPointsUsed,
            ]);

            if ($request->payment_method === 'bankTransfer') {
                DB::commit();
                ResponseService::successResponse(__('Bank transfer initiated. Please complete the transfer and update the transaction.'), [
                    'payment_transaction_id' => $paymentTransactionData->id,
                    'payment_transaction' => $paymentTransactionData,
                ]);
            }

            // Plugin gateways (PayU etc.) get their success/failed return URLs
            // built inside PaymentService::create() itself, off this context —
            // this controller no longer needs to know that plugin gateways
            // require them at all. Built-in gateways ignore $context entirely.
            $paymentIntent = PaymentService::create($request->payment_method, [
                'payment_transaction_id' => $paymentTransactionData->id,
                'platform_type' => $request->platform_type,
            ])->createAndFormatPaymentIntent(round($finalAmount, 2), [
                'payment_transaction_id' => $paymentTransactionData->id,
                'package_id' => $package->id,
                'user_id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'platform_type' => $request->platform_type,
                'currency_code' => $paymentConfigurations->currency_code,
                'country_code' => Auth::user()->country_code,
            ]);
            $paymentTransactionData->update(['order_id' => $paymentIntent['id']]);

            $paymentTransactionData = PaymentTransaction::findOrFail($paymentTransactionData->id);
            // Custom Array to Show as response
            $paymentGatewayDetails = [
                ...$paymentIntent,
                'payment_transaction_id' => $paymentTransactionData->id,
            ];

            DB::commit();
            ResponseService::successResponse('', ['payment_intent' => $paymentGatewayDetails, 'payment_transaction' => $paymentTransactionData]);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /** Get Payment Transactions */
    public function getPaymentTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latest_only' => 'nullable|boolean',
            'page' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $paymentTransactions = PaymentTransaction::where('user_id', Auth::user()->id)->orderBy('id', 'DESC');
            if ($request->latest_only) {
                $paymentTransactions->where('created_at', '>', Carbon::now()->subMinutes(30)->toDateTimeString());
            }
            $paymentTransactions = $paymentTransactions->paginate();

            $formatter = app(CurrencyFormatterService::class);

            $paymentTransactions->getCollection()->transform(function ($data) use ($formatter) {
                if ($data->payment_status == 'pending') {
                    try {
                        $paymentIntent = PaymentService::create($data->payment_gateway)->retrievePaymentIntent($data->order_id);
                        // dd($paymentIntent);
                    } catch (Throwable) {
                        //                        PaymentTransaction::find($data->id)->update(['payment_status' => "failed"]);
                    }

                    if (! empty($paymentIntent) && $paymentIntent['status'] != 'pending') {
                        PaymentTransaction::find($data->id)->update(['payment_status' => $paymentIntent['status'] ?? 'failed']);
                    }
                }
                $data->formatted_amount = $formatter->formatPrice($data->amount);

                return $data;
            });

            ResponseService::successResponse(__('Payment Transactions Fetched'), $paymentTransactions);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /** Get Payment Receipt */
    public function getPaymentReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_transaction_id' => 'required|exists:payment_transactions,id',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $payment = PaymentTransaction::with('user')->findOrFail($request->payment_transaction_id);

            if ($payment->user_id != Auth::user()->id) {
                ResponseService::errorResponse(__('You are not authorized to view this receipt'));
            }

            if (!in_array($payment->payment_status, ['succeed', 'failed'])) {
                ResponseService::errorResponse(__('Receipt is only available for completed transactions'));
            }

            return PaymentReceiptService::getHtmlOutput($payment);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /** In-App Purchase */
    public function inAppPurchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_token' => 'required',
            'payment_method' => 'required|in:google,apple',
            'package_id' => 'required|integer',
            'refer_points_used' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $package = Package::where('status', 1)->findOrFail($request->package_id);
            if(collect($package)->isEmpty()) {
                ResponseService::errorResponse(__('Package not found'));
            }
            $purchasedPackage = UserPurchasedPackage::onlyActive()->where(['package_id' => $request->package_id, 'user_id' => Auth::user()->id])->first();
            if (! empty($purchasedPackage)) {
                ResponseService::errorResponse(__('You already have purchased this package'));
            }

            // Handle refer points
            $referPointsUsed = (int) ($request->refer_points_used ?? 0);
            $finalAmount = $package->final_price;

            if ($referPointsUsed > 0) {
                $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
                if ($referEarnEnabled != '1') {
                    ResponseService::errorResponse(__('Refer & Earn is not enabled'));
                }

                $user = Auth::user();
                if ($user->refer_points < $referPointsUsed) {
                    ResponseService::errorResponse(__('Insufficient refer points'));
                }

                $maxPercentage = $package->refer_max_points_usage_percentage
                    ?? (int) (CachingService::getSystemSettings('refer_max_points_usage_percentage') ?: 10);
                $minPoints = $package->refer_min_points_to_use
                    ?? (int) (CachingService::getSystemSettings('refer_min_points_to_use') ?: 5);
                $maxPoints = $package->refer_max_points_to_use
                    ?? (int) (CachingService::getSystemSettings('refer_max_points_to_use') ?: 50);

                $maxFromPercentage = (int) floor($package->final_price * $maxPercentage / 100);
                $effectiveMax = min($maxFromPercentage, $maxPoints);

                if ($referPointsUsed < $minPoints) {
                    ResponseService::errorResponse(__('Minimum') . ' ' . $minPoints . ' ' . __('points required to use'));
                }
                if ($referPointsUsed > $effectiveMax) {
                    ResponseService::errorResponse(__('Maximum') . ' ' . $effectiveMax . ' ' . __('points can be used for this package'));
                }

                $finalAmount = $package->final_price - $referPointsUsed;
            }

            $paymentTransaction = PaymentTransaction::create([
                'user_id' => Auth::user()->id,
                'package_id' => $request->package_id,
                'amount' => max(0, $finalAmount),
                'original_price' => $package->price,
                'discount_price' => $package->price - $package->final_price,
                'payment_gateway' => $request->payment_method,
                'order_id' => Str::random(20),
                'payment_status' => 'succeed',
                'refer_points_used' => $referPointsUsed,
            ]);

            UserPurchasedPackage::create([
                'user_id' => Auth::user()->id,
                'package_id' => $request->package_id,
                'start_date' => Carbon::now(),
                'total_limit' => $package->item_limit == 'unlimited' ? null : $package->item_limit,
                'end_date' => is_null($package->duration) ? null : Carbon::now()->addDays($package->duration),
                'payment_transactions_id' => $paymentTransaction->id,
            ]);

            // Deduct refer points used
            if ($referPointsUsed > 0) {
                $user = User::lockForUpdate()->find(Auth::user()->id);
                $user->decrement('refer_points', $referPointsUsed);
                $user->refresh();

                ReferPointTransaction::create([
                    'user_id' => $user->id,
                    'points' => $referPointsUsed,
                    'transaction_type' => 'debit',
                    'type' => 'used_for_purchase',
                    'remark' => 'Used ' . $referPointsUsed . ' points for ' . $package->name . ' purchase',
                    'package_original_price' => $package->price,
                    'package_discounted_price' => $package->final_price,
                    'points_used' => $referPointsUsed,
                    'points_remaining_after' => $user->refer_points,
                    'final_payment_amount' => max(0, $finalAmount),
                    'reference_id' => $paymentTransaction->id,
                    'reference_type' => 'payment_transaction',
                ]);
            }

            // Award referral points
            $this->awardReferralPointsForUser(Auth::user()->id, $package);

            DB::commit();
            ResponseService::successResponse(__('Package Purchased Successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'API Controller -> inAppPurchase');
            ResponseService::errorResponse();
        }
    }

    /** Bank Transfer Update */
    public function bankTransferUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_transaction_id' => 'required|integer',
                'payment_receipt' => 'required|file|mimes:jpg,jpeg,png|max:7048',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }
            $transaction = PaymentTransaction::where('user_id', Auth::user()->id)->findOrFail($request->payment_transaction_id);

            if (! $transaction) {
                return ResponseService::errorResponse(__('Transaction not found.'));
            }
            $receiptPath = ! empty($request->file('payment_receipt'))
                ? FileService::upload($request->file('payment_receipt'), 'bank-transfer')
                : '';
            $transaction->update([
                'payment_receipt' => $receiptPath,
                'payment_status' => 'under review',
            ]);

            return ResponseService::successResponse(__('Payment transaction updated successfully.'), $transaction);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> bankTransferUpdate');

            return ResponseService::errorResponse();
        }
    }


    /** Make Payment Transaction Fail */
    public function makePaymentTransactionFail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_transaction_id' => 'required',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }
            $transaction = PaymentTransaction::where(['user_id' => Auth::user()->id, 'payment_status' => 'pending', 'id' => $request->payment_transaction_id])->first();
            if (collect($transaction)->isEmpty()) {
                return ResponseService::errorResponse(__('Transaction not found.'));
            }
            $transaction->update([
                'payment_status' => 'failed',
            ]);

            return ResponseService::successResponse(__('Payment transaction updated successfully.'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> makePaymentTransactionFail');

            return ResponseService::errorResponse();
        }
    }
}
