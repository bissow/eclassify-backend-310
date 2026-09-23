<?php

namespace App\Services\Payment;

use App\Models\PaymentConfiguration;
use App\Models\PluginLicense;
use InvalidArgumentException;

class PaymentService
{
    /**
     * @param string $paymentGateway - Stripe
     * @param array $context - Optional, only used for plugin gateways:
     *   'payment_transaction_id' and 'platform_type', used to build the
     *   success/failed return URLs. Built-in gateways ignore this entirely —
     *   they still pick their own redirect route internally off
     *   $customMetaData['platform_type'] when createAndFormatPaymentIntent()
     *   is called, unchanged.
     * @return StripePayment
     */
    public static function create(string $paymentGateway, array $context = [])
    {
        $paymentGateway = strtolower($paymentGateway);
        $payment = PaymentConfiguration::where(['payment_method' => $paymentGateway, 'status' => 1])->first();

        if (!$payment) {
            throw new InvalidArgumentException('Invalid Payment Gateway.');
        }

        return match ($paymentGateway) {
            'stripe' => new StripePayment($payment->secret_key, $payment->currency_code),
            'paystack' => new PaystackPayment($payment->currency_code),
            'razorpay' => new RazorpayPayment($payment->secret_key, $payment->api_key, $payment->currency_code),
            'phonepe' => new PhonePePayment($payment->secret_key, $payment->api_key, $payment->additional_data_1, $payment->additional_data_2, $payment->payment_mode),
            'flutterwave' => new FlutterWavePayment($payment->secret_key, $payment->api_key, $payment->webhook_secret_key, $payment->currency_code),
            'paypal' => new PayPalPayment($payment->api_key, $payment->secret_key, $payment->currency_code, $payment->payment_mode),
            'paytabs' => new PayTabsPayment(
                $payment->secret_key,
                $payment->additional_data_1, // profile_id
                (bool) $payment->additional_data_2, // is_live
                $payment->payment_mode // region key: global/uae/ksa/egypt/oman/jordan/kuwait/iraq
            ),
            'dpo' => new DpoPayment(
                $payment->secret_key,            // CompanyToken
                $payment->additional_data_1,  // ServiceType
                strtoupper($payment->payment_mode ?? 'UAT') === 'PROD' // is_live
            ),
            'google,apple' => null,
            // Anything not built in falls through to an installed type=payment
            // plugin, resolved by naming convention — see resolvePluginGateway().
            default => self::resolvePluginGateway($paymentGateway, $payment, $context),
        };
    }

    // Plugin gateways name their own payment class via module.json's
    // "main_class" — the same field PluginManagerController's install-time
    // check reads, so both agree on where the class actually lives.
    protected static function resolvePluginGateway(string $paymentGateway, PaymentConfiguration $payment, array $context = [])
    {
        // Hyphens must match literally — the caller must pass the real slug.
        $license = PluginLicense::where('plugin_slug', $paymentGateway)->where('type', 'payment')->first();

        if (!$license) {
            throw new InvalidArgumentException('Invalid Payment Gateway.');
        }

        if (!$license->isUsable()) {
            throw new InvalidArgumentException('This payment gateway is currently unavailable.');
        }

        $class = $license->manifest()['main_class'] ?? null;

        // Licensed and enabled, but its class is missing/unnamed/broken
        // (files deleted, bad reinstall) — auto-disable so this doesn't
        // silently fail every future attempt too.
        if (!$class || !class_exists($class) || !is_subclass_of($class, PaymentInterface::class)) {
            logger()->error("Plugin payment gateway [{$license->plugin_slug}] is enabled but its main_class (".($class ?: 'not set in module.json').") is missing or invalid — disabling it.");

            if ($license->is_enabled) {
                $license->update(['is_enabled' => false]);
                PaymentConfiguration::where('payment_method', $license->plugin_slug)->update(['status' => 0]);
            }

            throw new InvalidArgumentException('This payment gateway is currently unavailable.');
        }

        // Product-owned return endpoints, shared by every plugin gateway.
        [$successUrl, $failedUrl] = self::pluginReturnUrls($context);

        return new $class($payment, $successUrl, $failedUrl);
    }

    protected static function pluginReturnUrls(array $context): array
    {
        $paymentTransactionId = $context['payment_transaction_id'] ?? null;

        if (!$paymentTransactionId) {
            return [null, null];
        }

        $isApp = ($context['platform_type'] ?? null) === 'app';

        return [
            route($isApp ? 'plugin-payment.success' : 'plugin-payment.success.web', $paymentTransactionId),
            route($isApp ? 'plugin-payment.failed' : 'plugin-payment.failed.web', $paymentTransactionId),
        ];
    }

    /***
     * @param string $paymentGateway
     * @param $paymentIntentData
     * @return array
     * Stripe Payment Intent : https://stripe.com/docs/api/payment_intents/object
     */
    //    public static function formatPaymentIntent(string $paymentGateway, $paymentIntentData) {
    //        $paymentGateway = strtolower($paymentGateway);
    //        return match ($paymentGateway) {
    //            'stripe' => [
    //                'id'                       => $paymentIntentData->id,
    //                'amount'                   => $paymentIntentData->amount,
    //                'currency'                 => $paymentIntentData->currency,
    //                'metadata'                 => $paymentIntentData->metadata,
    //                'status'                   => match ($paymentIntentData->status) {
    //                    "canceled" => "failed",
    //                    "succeeded" => "succeed",
    //                    "processing", "requires_action", "requires_capture", "requires_confirmation", "requires_payment_method" => "pending",
    //                },
    //                'payment_gateway_response' => $paymentIntentData
    //            ],
    //
    //            'paystack' => [
    //                'id'                       => $paymentIntentData['data']['reference'],
    //                'amount'                   => $paymentIntentData->amount,
    //                'currency'                 => $paymentIntentData->currency,
    //                'metadata'                 => $paymentIntentData->metadata,
    //                'status'                   => match ($paymentIntentData['data']['status']) {
    //                    "abandoned" => "failed",
    //                    "succeed" => "succeed",
    //                    default => $paymentIntentData['data']['status'] ?? true
    //                },
    //                'payment_gateway_response' => $paymentIntentData
    //            ],
    //            // any other payment processor implementations
    //            default => $paymentIntentData,
    //        };
    //    }
}
