<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Models\Referral;
use App\Models\ReferPointTransaction;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Services\CachingService;
use App\Services\NotificationService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayPalPayment;
use App\Services\Payment\WebhookVerifiable;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
use PhonePe\PhonePe;
use Throwable;


class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        try {
            Log::channel('webhook')->info("Stripe Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("Stripe Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            $payload = $request->getContent();

            $signatureHeader = $request->header('Stripe-Signature');
            if (empty($signatureHeader)) {
                Log::channel('webhook')->error("Stripe Webhook : Missing Stripe-Signature header");
                return response()->json(['error' => 'Missing signature'], 400);
            }

            $paymentConfiguration = PaymentConfiguration::select('webhook_secret_key')
                ->where('payment_method', 'Stripe')
                ->first();
            $endpointSecret = $paymentConfiguration->webhook_secret_key ?? null;
            if (empty($endpointSecret)) {
                Log::channel('webhook')->error("Stripe Webhook : Webhook secret not configured");
                return response()->json(['error' => 'Webhook secret not configured'], 500);
            }

            // Verify signature and construct the event.
            // Throws SignatureVerificationException / UnexpectedValueException on failure.
            // See https://stripe.com/docs/webhooks/signatures for more information.
            $event = Webhook::constructEvent($payload, $signatureHeader, $endpointSecret);

            $metadata = $event->data->object->metadata ?? null;
            $paymentTransactionId = $metadata['payment_transaction_id'] ?? null;
            $userId = $metadata['user_id'] ?? null;
            $packageId = $metadata['package_id'] ?? null;

            switch ($event->type) {
                case 'payment_intent.created':
                    break;

                case 'payment_intent.succeeded':
                    if (empty($paymentTransactionId) || empty($userId) || empty($packageId)) {
                        Log::channel('webhook')->error("Stripe Webhook : Missing metadata for succeeded event");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->assignPackage($paymentTransactionId, $userId, $packageId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Stripe Webhook : ", [$response['message']]);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    if (empty($paymentTransactionId) || empty($userId)) {
                        Log::channel('webhook')->error("Stripe Webhook : Missing metadata for failed event");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->failedTransaction($paymentTransactionId, $userId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Stripe Webhook : ", [$response['message']]);
                    }
                    break;

                default:
                    Log::channel('webhook')->error('Stripe Webhook : Received unknown event type', [$event->type]);
                    break;
            }
            return response()->json(['status' => 'success'], 200);
        } catch (UnexpectedValueException $e) {
            Log::channel('webhook')->error("Stripe Webhook : Invalid payload", [$e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::channel('webhook')->error("Stripe Webhook : Signature verification failed", [$e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (Throwable $e) {
            Log::channel('webhook')->error("Stripe Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function razorpay(Request $request)
    {
        try {
            Log::channel('webhook')->info("Razorpay Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("Razorpay Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            $signatureHeader = $request->header('X-Razorpay-Signature');
            if (empty($signatureHeader)) {
                Log::channel('webhook')->error("Razorpay Webhook : Missing X-Razorpay-Signature header");
                return response()->json(['error' => 'Missing signature'], 400);
            }

            // Raw request body — required as-is: re-encoding would break the HMAC.
            $webhookBody = $request->getContent();

            // Razorpay uses a DEDICATED webhook secret (configured in the Razorpay
            // dashboard), stored in payment_configurations.webhook_secret_key.
            $paymentConfiguration = PaymentConfiguration::select('api_key', 'secret_key', 'webhook_secret_key')
                ->where('payment_method', 'Razorpay')
                ->first();
            $apiKey = $paymentConfiguration->api_key ?? null;
            $apiSecret = $paymentConfiguration->secret_key ?? null;
            $webhookSecret = $paymentConfiguration->webhook_secret_key ?? null;
            if (empty($webhookSecret)) {
                Log::channel('webhook')->error("Razorpay Webhook : Webhook secret not configured");
                return response()->json(['error' => 'Webhook secret not configured'], 500);
            }

            // Verify signature using the official SDK. It computes
            // hash_hmac('sha256', body, webhookSecret), compares it to the header value
            // via hash_equals, and throws SignatureVerificationError on mismatch.
            // https://razorpay.com/docs/webhooks/validate-test/
            $api = new Api($apiKey, $apiSecret);
            $api->utility->verifyWebhookSignature($webhookBody, $signatureHeader, $webhookSecret);
            $data = json_decode($webhookBody, false, 512, JSON_THROW_ON_ERROR);
            $event = $data->event ?? null;
            $metadata = $data->payload->payment->entity->notes ?? null;
            $paymentTransactionId = $metadata->payment_transaction_id ?? null;
            $userId = $metadata->user_id ?? null;
            $packageId = $metadata->package_id ?? null;

            switch ($event) {
                case 'payment.captured':
                    if (empty($paymentTransactionId) || empty($userId) || empty($packageId)) {
                        Log::channel('webhook')->error("Razorpay Webhook : Missing metadata for payment.captured");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->assignPackage($paymentTransactionId, $userId, $packageId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Razorpay Webhook : ", [$response['message']]);
                    }
                    break;

                case 'payment.failed':
                    if (empty($paymentTransactionId) || empty($userId)) {
                        Log::channel('webhook')->error("Razorpay Webhook : Missing metadata for payment.failed");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->failedTransaction($paymentTransactionId, $userId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Razorpay Webhook : ", [$response['message']]);
                    }
                    break;

                case 'payment.authorized':
                    // Payment authorized but not yet captured — no action required.
                    break;

                default:
                    Log::channel('webhook')->error('Razorpay Webhook : Received unknown event type', [$event ?? 'null']);
                    break;
            }

            return response()->json(['status' => 'success'], 200);
        } catch (SignatureVerificationError $e) {
            Log::channel('webhook')->error("Razorpay Webhook : Signature verification failed", [$e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\JsonException $e) {
            Log::channel('webhook')->error("Razorpay Webhook : Invalid JSON payload", [$e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (Throwable $e) {
            Log::channel('webhook')->error("Razorpay Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function paystack(Request $request)
    {
        try {
            Log::channel('webhook')->info("Paystack Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("Paystack Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            $signatureHeader = $request->header('x-paystack-signature');
            if (empty($signatureHeader)) {
                Log::channel('webhook')->error("Paystack Webhook : Missing x-paystack-signature header");
                return response()->json(['error' => 'Missing signature'], 400);
            }

            $payload = $request->getContent();
            $secretKey = PaymentConfiguration::where('payment_method', 'Paystack')->value('secret_key');

            if (empty($secretKey)) {
                Log::channel('webhook')->error("Paystack Webhook : Secret key not configured");
                return response()->json(['error' => 'Secret key not configured'], 500);
            }

            // Verify signature: HMAC-SHA512 of the raw body using the secret key.
            // https://paystack.com/docs/payments/webhooks/#verify-event-origin
            $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
            if (!hash_equals($expectedSignature, $signatureHeader)) {
                Log::channel('webhook')->error("Paystack Webhook : Signature verification failed");
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            $metadata = $event->data->metadata ?? null;
            $paymentTransactionId = $metadata->payment_transaction_id ?? null;
            $userId = $metadata->user_id ?? null;
            $packageId = $metadata->package_id ?? null;

            switch ($event->event ?? null) {
                case 'charge.success':
                    if (empty($paymentTransactionId) || empty($userId) || empty($packageId)) {
                        Log::channel('webhook')->error("Paystack Webhook : Missing metadata for charge.success");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->assignPackage($paymentTransactionId, $userId, $packageId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Paystack Webhook : ", [$response['message']]);
                    }
                    break;

                case 'charge.failed':
                    if (empty($paymentTransactionId) || empty($userId)) {
                        Log::channel('webhook')->error("Paystack Webhook : Missing metadata for charge.failed");
                        return response()->json(['error' => 'Missing metadata'], 400);
                    }
                    $response = $this->failedTransaction($paymentTransactionId, $userId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("Paystack Webhook : ", [$response['message']]);
                    }
                    break;

                default:
                    Log::channel('webhook')->error('Paystack Webhook : Received unknown event type', [$event->event ?? 'null']);
                    break;
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\JsonException $e) {
            Log::channel('webhook')->error("Paystack Webhook : Invalid JSON payload", [$e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (Throwable $e) {
            Log::channel('webhook')->error("Paystack Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function paystackSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function phonePe(Request $request)
    {
        try {
            Log::channel('webhook')->info("PhonePe Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("PhonePe Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            // Raw payload
            $content = $request->getContent();
            $jsonInput = json_decode($content, true);
            if (!is_array($jsonInput) || empty($jsonInput)) {
                Log::channel('webhook')->error("PhonePe Webhook : Invalid JSON payload");
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            // --- Verify Authorization header ---
            // PhonePe signs the callback with Authorization = SHA256(username:password),
            // where username/password are the webhook credentials set in the PhonePe
            // dashboard and stored in payment_configurations.
            $authHeader = $request->header('Authorization');
            if (empty($authHeader)) {
                Log::channel('webhook')->error("PhonePe Webhook : Missing Authorization header");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $paymentConfiguration = PaymentConfiguration::where('payment_method', 'PhonePe')->first();
            $username = $paymentConfiguration->username ?? '';
            $password = $paymentConfiguration->password ?? '';

            // Fail closed if credentials are not configured — otherwise the expected hash
            // would be SHA256(":"), a fixed value an attacker could compute and forge.
            if (empty($username) || empty($password)) {
                Log::channel('webhook')->error("PhonePe Webhook : Webhook credentials not configured");
                return response()->json(['error' => 'Webhook credentials not configured'], 500);
            }

            $expectedHash = hash('sha256', $username . ':' . $password);
            if (!hash_equals($expectedHash, $authHeader)) {
                Log::channel('webhook')->error("PhonePe Webhook : Authorization header mismatch");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // --- Extract order info ---
            // eClassify persists PhonePe's own orderId in payment_transactions.order_id,
            // so the payload's orderId is the correct key to look up the transaction.
            $payload = $jsonInput['payload'] ?? [];
            $orderId = $payload['orderId'] ?? null;
            $state = $payload['state'] ?? null;
            if (empty($orderId)) {
                Log::channel('webhook')->error("PhonePe Webhook : Missing orderId in payload");
                return response()->json(['error' => 'Missing orderId'], 400);
            }

            $paymentTransaction = PaymentTransaction::where('order_id', $orderId)
                ->whereRaw('LOWER(payment_gateway) = ?', ['phonepe'])
                ->first();
            if (!$paymentTransaction) {
                Log::channel('webhook')->error("PhonePe Webhook : Transaction not found for orderId {$orderId}");
                // Acknowledge so PhonePe does not keep retrying for an unknown order.
                return response()->json(['status' => 'transaction_not_found'], 200);
            }

            if ($state === "COMPLETED" || $state === "SUCCESS") {
                if ($paymentTransaction->payment_status === 'succeed') {
                    return response()->json(['status' => 'already_processed'], 200);
                }
                $response = $this->assignPackage(
                    $paymentTransaction->id,
                    $paymentTransaction->user_id,
                    $paymentTransaction->package_id
                );
                if ($response['error']) {
                    Log::channel('webhook')->error("PhonePe Webhook : ", [$response['message']]);
                }
            } else {
                Log::channel('webhook')->warning("PhonePe Webhook : Payment not completed", ['state' => $state, 'orderId' => $orderId]);
                $response = $this->failedTransaction($paymentTransaction->id, $paymentTransaction->user_id);
                if ($response['error']) {
                    Log::channel('webhook')->error("PhonePe Webhook : ", [$response['message']]);
                }
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PhonePe Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function flutterwave(Request $request)
    {
        try {
            Log::channel('webhook')->info("Flutterwave Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("Flutterwave Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            $content = $request->getContent();
            $payload = json_decode($content, true);
            if (!is_array($payload) || empty($payload)) {
                Log::channel('webhook')->error("Flutterwave Webhook : Invalid payload");
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            $data = $payload['data'] ?? $payload;
            $txRef = $data['tx_ref'] ?? $payload['txRef'] ?? null;
            $flwTransactionId = $data['id'] ?? $payload['id'] ?? null;
            if (empty($txRef) || empty($flwTransactionId)) {
                Log::channel('webhook')->error("Flutterwave Webhook : Missing tx_ref or transaction id");
                return response()->json(['error' => 'Invalid payload structure'], 400);
            }

            $parts = explode('-', $txRef);
            $paymentTransactionId = $parts[1] ?? null;
            $packageId = $parts[3] ?? null;
            if (empty($paymentTransactionId) || empty($packageId)) {
                Log::channel('webhook')->error("Flutterwave Webhook : Unexpected tx_ref format", [$txRef]);
                return response()->json(['error' => 'Invalid tx_ref'], 400);
            }

            $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
            if (!$paymentTransaction) {
                Log::channel('webhook')->error("Flutterwave Webhook : Transaction not found for {$paymentTransactionId}");
                return response()->json(['status' => 'transaction_not_found'], 200);
            }

            // --- Authenticate the webhook ---
            // Flutterwave webhooks are not HMAC-signed, and this panel stores no dashboard
            // "Secret Hash" (its webhook_secret_key field holds the encryption key), so a
            // verif-hash comparison has no secret to check against. Instead we authoritatively
            // re-verify the transaction with Flutterwave using the secret key: a forged
            // webhook cannot pass because Flutterwave itself must confirm the charge.
            // https://developer.flutterwave.com/docs/verify-payments
            $secretKey = PaymentConfiguration::whereRaw('LOWER(payment_method) = ?', ['flutterwave'])->value('secret_key');
            if (empty($secretKey)) {
                Log::channel('webhook')->error("Flutterwave Webhook : Secret key not configured");
                return response()->json(['error' => 'Secret key not configured'], 500);
            }

            $verifyResponse = Http::withToken($secretKey)
                ->acceptJson()
                ->get("https://api.flutterwave.com/v3/transactions/{$flwTransactionId}/verify");

            if (!$verifyResponse->successful()) {
                Log::channel('webhook')->error("Flutterwave Webhook : Verification request failed", [$verifyResponse->status()]);
                return response()->json(['error' => 'Verification failed'], 400);
            }

            $verified = $verifyResponse->json();
            $verifiedData = $verified['data'] ?? [];
            $apiCallOk = ($verified['status'] ?? null) === 'success';
            $verifiedStatus = $verifiedData['status'] ?? null;

            $isSuccessful = $apiCallOk
                && $verifiedStatus === 'successful'
                && (string) ($verifiedData['tx_ref'] ?? '') === (string) $txRef
                && (float) ($verifiedData['amount'] ?? 0) >= (float) $paymentTransaction->amount;

            if ($isSuccessful) {
               
                if ($paymentTransaction->payment_status === 'succeed') {
                    return response()->json(['status' => 'already_processed'], 200);
                }
                $response = $this->assignPackage($paymentTransaction->id, $paymentTransaction->user_id, $packageId);
                if ($response['error']) {
                    Log::channel('webhook')->error("Flutterwave Webhook : ", [$response['message']]);
                }
                return response()->json(['status' => 'success'], 200);
            }

            // Only mark failed when Flutterwave explicitly reports a failed charge — never
            // fail a still-pending transaction on an unverified/forged event.
            if ($apiCallOk && $verifiedStatus === 'failed') {
                $response = $this->failedTransaction($paymentTransaction->id, $paymentTransaction->user_id);
                if ($response['error']) {
                    Log::channel('webhook')->error("Flutterwave Webhook : ", [$response['message']]);
                }
                return response()->json(['status' => 'failed'], 200);
            }

            Log::channel('webhook')->warning("Flutterwave Webhook : Transaction not confirmed successful", ['tx_ref' => $txRef, 'verified_status' => $verifiedStatus]);
            return response()->json(['status' => 'ignored'], 200);
        } catch (Throwable $e) {
            Log::channel('webhook')->error("Flutterwave Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function phonePeSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function paypal(Request $request)
    {
        try {
            Log::channel('webhook')->info("PayPal Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("PayPal Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            $content = $request->getContent();
            $jsonInput = json_decode($content, true);
            if (!is_array($jsonInput) || empty($jsonInput)) {
                Log::channel('webhook')->error("PayPal Webhook : Invalid JSON payload");
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            $eventType = $jsonInput['event_type'] ?? null;
            $resource  = $jsonInput['resource'] ?? [];

            // Gateway config is stored as 'Paypal'; match case-insensitively.
            $paymentConfiguration = PaymentConfiguration::whereRaw('LOWER(payment_method) = ?', ['paypal'])->first();
            if (!$paymentConfiguration) {
                Log::channel('webhook')->error("PayPal Webhook : Gateway not configured");
                return response()->json(['error' => 'Gateway not configured'], 500);
            }

            $paypal = new PayPalPayment(
                $paymentConfiguration->api_key,
                $paymentConfiguration->secret_key,
                $paymentConfiguration->currency_code,
                $paymentConfiguration->payment_mode
            );

            switch ($eventType) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $captureId = $resource['id'] ?? null;
                    $customId  = $resource['custom_id'] ?? null;
                    if (empty($captureId) || empty($customId)) {
                        Log::channel('webhook')->error("PayPal Webhook : Missing capture id or custom_id");
                        return response()->json(['error' => 'Missing data'], 400);
                    }

                    $parts = explode('-', $customId);
                    $paymentTransactionId = $parts[1] ?? null;
                    $packageId = $parts[3] ?? null;
                    if (empty($paymentTransactionId) || empty($packageId)) {
                        Log::channel('webhook')->error("PayPal Webhook : Unexpected custom_id format", [$customId]);
                        return response()->json(['error' => 'Invalid custom_id'], 400);
                    }

                    $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                    if (!$paymentTransaction) {
                        Log::channel('webhook')->error("PayPal Webhook : Transaction not found for {$paymentTransactionId}");
                        return response()->json(['status' => 'transaction_not_found'], 200);
                    }

                    // --- Authenticate the webhook ---
                    // This panel stores no PayPal Webhook ID, so verify-webhook-signature
                    // cannot be used. Instead we authoritatively re-fetch the capture from
                    // PayPal with our client credentials and confirm it: a forged webhook
                    // cannot pass because PayPal itself must report the capture as COMPLETED.
                    // https://developer.paypal.com/docs/api/payments/v2/#captures_get
                    $capture = $paypal->getCapture($captureId);
                    $confirmed = is_array($capture)
                        && ($capture['status'] ?? null) === 'COMPLETED'
                        && (string) ($capture['custom_id'] ?? '') === (string) $customId
                        && (float) ($capture['amount']['value'] ?? 0) >= (float) $paymentTransaction->amount;

                    if (!$confirmed) {
                        Log::channel('webhook')->warning("PayPal Webhook : Capture not confirmed", ['capture_id' => $captureId, 'custom_id' => $customId]);
                        return response()->json(['status' => 'unverified'], 200);
                    }

                    if ($paymentTransaction->payment_status === 'succeed') {
                        return response()->json(['status' => 'already_processed'], 200);
                    }
                    $response = $this->assignPackage($paymentTransaction->id, $paymentTransaction->user_id, $packageId);
                    if ($response['error']) {
                        Log::channel('webhook')->error("PayPal Webhook : ", [$response['message']]);
                    }
                    return response()->json(['status' => 'success'], 200);

                case 'PAYMENT.CAPTURE.DENIED':
                    $customId = $resource['custom_id'] ?? null;
                    if (!empty($customId)) {
                        $parts = explode('-', $customId);
                        $paymentTransactionId = $parts[1] ?? null;
                        if (!empty($paymentTransactionId)) {
                            $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                            if ($paymentTransaction) {
                                $response = $this->failedTransaction($paymentTransaction->id, $paymentTransaction->user_id);
                                if ($response['error']) {
                                    Log::channel('webhook')->error("PayPal Webhook : ", [$response['message']]);
                                }
                            }
                        }
                    }
                    return response()->json(['status' => 'failed'], 200);

                default:
                    Log::channel('webhook')->info("PayPal Webhook : Unhandled event type", [$eventType]);
                    return response()->json(['status' => 'ignored'], 200);
            }
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PayPal Webhook : Error occurred", [$e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function handlePaypalCapture($orderId)
    {
        $payment = PaymentConfiguration::where([
            'payment_method' => 'paypal',
            'status' => 1
        ])->first();

        if (!$payment) {
            throw new \Exception("PayPal payment configuration not found.");
        }

        $paypal = new PayPalPayment(
            $payment->api_key,
            $payment->secret_key,
            $payment->currency_code,
            $payment->payment_mode
        );

        return $paypal->capturePayment($orderId);
    }

    public function paypalPaymentSuccess(Request $request)
    {
        try {

            $orderId = $request->query('token');

            if (!$orderId) {
                return view('payment.paypal', [
                    'trxref'    => null,
                    'reference' => null
                ]);
            }

            $paymentResult = $this->handlePaypalCapture($orderId);


            return view('payment.paypal', [
                'trxref'    => $orderId,
                'reference' => $paymentResult['id'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PayPal Success Handler Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return view('payment.paypal', [
                'trxref'    => null,
                'reference' => null
            ]);
        }
    }

    public function paypalSuccessCallback(Request $request)
    {
        try {
            $orderId = $request->query('token');
            if (!$orderId) {
                return ResponseService::errorResponse("Missing PayPal order ID.");
            }

            $paymentResult = $this->handlePaypalCapture($orderId);
            return ResponseService::successResponse("Payment done successfully.");
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PayPal Success Callback Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return ResponseService::errorResponse("Something went wrong during PayPal payment.");
        }
    }

    public function paypalCancelCallback()
    {
        return ResponseService::successResponse("Payment Cancelled successfully.");
    }
    public function paypalCancelCallbackWeb(Request $request)
    {
        try {
            $orderId = $request->query('token');
            return view('payment.paypal', [
                'trxref'    => $orderId ?? null,
                'reference' => null
            ]);
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PayPal Success Handler Error", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return view('payment.paypalcancle', [
                'trxref'    => null,
                'reference' => null
            ]);
        }
    }

    /**
     * Shared return endpoint for every installed plugin gateway. Never
     * trusts the request itself — always re-confirms status server-to-server
     * via retrievePaymentIntent().
     */
    public function pluginPaymentSuccess($paymentTransactionId)
    {
        return $this->handlePluginPaymentReturn($paymentTransactionId, isWeb: false);
    }

    public function pluginPaymentSuccessWeb($paymentTransactionId)
    {
        return $this->handlePluginPaymentReturn($paymentTransactionId, isWeb: true);
    }

    public function pluginPaymentFailed($paymentTransactionId)
    {
        return $this->handlePluginPaymentReturn($paymentTransactionId, isWeb: false);
    }

    public function pluginPaymentFailedWeb($paymentTransactionId)
    {
        return $this->handlePluginPaymentReturn($paymentTransactionId, isWeb: true);
    }

    private function handlePluginPaymentReturn($paymentTransactionId, bool $isWeb)
    {
        $transaction = PaymentTransaction::find($paymentTransactionId);

        if (!$transaction) {
            if ($isWeb) {
                return view('plugin-payment.failed', ['message' => 'Transaction not found.']);
            }
            ResponseService::errorResponse('Transaction not found.');

            return;
        }

        // Gateways with a working webhook are the sole source of truth for
        // fulfillment, same as every built-in gateway — the webhook alone
        // calls assignPackage()/failedTransaction(). Only reconcile here for
        // gateways with no webhook support (e.g. PayU), since nothing else
        // will ever process them otherwise.
        $webhookCapable = false;

        try {
            $webhookCapable = PaymentService::create($transaction->payment_gateway) instanceof WebhookVerifiable;
        } catch (Throwable $e) {
            // Gateway unresolvable — fall through and attempt reconciliation
            // anyway; reconcilePluginTransaction() has its own error handling.
        }

        if (!$webhookCapable) {
            $this->reconcilePluginTransaction($transaction);
        }

        $succeeded = $transaction->fresh()->payment_status === 'succeed';

        if ($isWeb) {
            return view($succeeded ? 'plugin-payment.success' : 'plugin-payment.failed', [
                'transaction' => $transaction,
            ]);
        }

        if ($succeeded) {
            ResponseService::successResponse('Payment successful.', ['payment_transaction' => $transaction]);
        } else {
            ResponseService::errorResponse('Payment failed or is still processing.', ['payment_transaction' => $transaction]);
        }
    }

    // Shared by the return endpoints and the webhook handler. Returns false
    // only when the re-confirm call itself failed, so the webhook handler
    // can return a retry-able status instead of reporting false success.
    private function reconcilePluginTransaction(PaymentTransaction $transaction): bool
    {
        if ($transaction->payment_status !== 'pending') {
            return true;
        }

        try {
            $verified = PaymentService::create($transaction->payment_gateway)->retrievePaymentIntent($transaction->order_id);
            $status = $verified['status'] ?? 'pending';

            if ($status === 'succeed') {
                $this->assignPackage($transaction->id, $transaction->user_id, $transaction->package_id);
            } elseif ($status === 'failed') {
                $this->failedTransaction($transaction->id, $transaction->user_id);
            }
            $transaction->refresh();

            return true;
        } catch (Throwable $e) {
            Log::channel('webhook')->error('Plugin transaction reconcile failed', [
                'payment_transaction_id' => $transaction->id,
                'gateway' => $transaction->payment_gateway,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generic webhook endpoint, shared by every plugin gateway that
     * supports one — {gateway} is its slug. Verification is delegated to
     * the gateway's own WebhookVerifiable class; everything after that is
     * the same generic path the return URLs use.
     */
    public function pluginWebhook(string $gateway, Request $request)
    {
        try {
            $paymentGateway = PaymentService::create($gateway);
        } catch (Throwable $e) {
            Log::channel('webhook')->warning('Plugin webhook: gateway not resolvable', ['gateway' => $gateway, 'message' => $e->getMessage()]);

            return response()->json(['error' => 'Unknown or unavailable gateway'], 404);
        }

        if (!$paymentGateway instanceof WebhookVerifiable) {
            Log::channel('webhook')->warning('Plugin webhook: gateway does not support webhooks', ['gateway' => $gateway]);

            return response()->json(['error' => 'Webhook not supported for this gateway'], 400);
        }

        try {
            $result = $paymentGateway->verifyWebhook($request);
        } catch (Throwable $e) {
            Log::channel('webhook')->warning('Plugin webhook verification failed', ['gateway' => $gateway, 'message' => $e->getMessage()]);

            return response()->json(['error' => 'Verification failed'], 400);
        }

        $transaction = PaymentTransaction::find($result['payment_transaction_id'] ?? null);

        if (!$transaction) {
            Log::channel('webhook')->warning('Plugin webhook: transaction not found', ['gateway' => $gateway, 'result' => $result]);

            return response()->json(['error' => 'Transaction not found'], 404);
        }

        if (!$this->reconcilePluginTransaction($transaction)) {
            // Re-confirm failed (likely transient) — ask the gateway to retry.
            return response()->json(['error' => 'Reconciliation failed, please retry'], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Success Business Login
     * @param $payment_transaction_id
     * @param $user_id
     * @param $package_id
     * @return array
     */
    private function assignPackage($payment_transaction_id, $user_id, $package_id)
    {
        try {
            DB::beginTransaction();

            $paymentTransactionData = PaymentTransaction::where('id', $payment_transaction_id)->lockForUpdate()->first();
            if ($paymentTransactionData == null) {
                DB::rollBack();
                Log::channel('webhook')->error("Payment Transaction id not found");
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            if ($paymentTransactionData->payment_status == "succeed") {
                DB::rollBack();
                return [
                    'error'   => true,
                    'message' => 'Transaction Already Succeed'
                ];
            }

            $paymentTransactionData->update(['payment_status' => "succeed"]);

            $package = Package::find($package_id);

            if (!empty($package)) {
                // create purchased package record
                $userPackage = UserPurchasedPackage::create([
                    'package_id'  => $package_id,
                    'user_id'     => $user_id,
                    'start_date'  => Carbon::now(),
                    'end_date'    => is_null($package->duration) ? null : Carbon::now()->addDays($package->duration),
                    'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                    'listing_duration_type' => $package->listing_duration_type,
                    'listing_duration_days' => $package->listing_duration_days
                ]);
            }

            // Deduct refer points used (if any)
            if ($paymentTransactionData->refer_points_used > 0) {
                $user = User::lockForUpdate()->find($user_id);
                if ($user) {
                    $pointsToDeduct = $paymentTransactionData->refer_points_used;
                    $user->decrement('refer_points', $pointsToDeduct);
                    $user->refresh();

                    ReferPointTransaction::create([
                        'user_id' => $user_id,
                        'points' => $pointsToDeduct,
                        'transaction_type' => 'debit',
                        'type' => 'used_for_purchase',
                        'remark' => 'Used ' . $pointsToDeduct . ' points for ' . ($package->name ?? 'package') . ' purchase',
                        'package_original_price' => $package->price ?? null,
                        'package_discounted_price' => $package->final_price ?? null,
                        'points_used' => $pointsToDeduct,
                        'points_remaining_after' => $user->refer_points,
                        'final_payment_amount' => $paymentTransactionData->amount,
                        'reference_id' => $paymentTransactionData->id,
                        'reference_type' => 'payment_transaction',
                    ]);
                }
            }

            // Award referral points if applicable
            $this->awardReferralPoints($user_id, $package);

            $title = "Package Purchased";
            $body = 'Amount :- ' . $paymentTransactionData->amount;
            if (!empty($user_id)) {
                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $body,
                    'payment',
                    ['id' => $paymentTransactionData->id],
                    false,
                    array($user_id),
                    true
                );
            }
            DB::commit();

            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::channel('webhook')->error($th->getMessage() . "WebhookController -> assignPackage");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }

    private function awardReferralPoints($user_id, $package)
    {
        try {
            $referEarnEnabled = CachingService::getSystemSettings('refer_earn_enabled');
            if ($referEarnEnabled != '1') {
                return;
            }

            // Only award for paid packages
            if (empty($package) || $package->final_price <= 0) {
                return;
            }

            // Find unrewarded referral for this user
            $referral = Referral::where('referred_id', $user_id)
                ->where('is_rewarded', false)
                ->first();

            if (empty($referral)) {
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

                // Notify referrer
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
            $referredUser = User::lockForUpdate()->find($user_id);
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

            // Mark referral as rewarded
            $referral->update([
                'is_rewarded' => true,
                'rewarded_at' => now(),
            ]);
        } catch (Throwable $th) {
            Log::channel('webhook')->error('Error awarding referral points: ' . $th->getMessage());
        }
    }

    public function paytabs(Request $request)
    {
        try {
            Log::channel('webhook')->info("PayTabs Webhook called");

            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error("PayTabs Webhook : Invalid request method");
                return response()->json(['error' => 'Invalid request method'], 405);
            }
           
            $rawBody = $request->getContent();
            if (empty($rawBody)) {
                Log::channel('webhook')->error("PayTabs Webhook : Empty payload");
                return response()->json(['error' => 'Empty payload'], 400);
            }

            $receivedSignature = $request->header('signature');
            if (empty($receivedSignature)) {
                Log::channel('webhook')->error("PayTabs Webhook : Missing signature header");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // PayTabs signs the callback with HMAC-SHA256 of the raw body using your
            // Server Key (stored in secret_key). Gateway is stored as 'Paytabs'; match
            // case-insensitively so verification is collation independent.
            $paymentConfiguration = PaymentConfiguration::whereRaw('LOWER(payment_method) = ?', ['paytabs'])->first();
            if (!$paymentConfiguration || empty($paymentConfiguration->secret_key)) {
                Log::channel('webhook')->critical("PayTabs Webhook : Secret key not configured");
                return response()->json(['error' => 'Server configuration error'], 500);
            }

            $serverKey = $paymentConfiguration->secret_key;

            $expectedSignature = hash_hmac('sha256', $rawBody, $serverKey);
            if (!hash_equals($expectedSignature, $receivedSignature)) {
                Log::channel('webhook')->error("PayTabs Webhook : Signature verification failed");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payload = json_decode($rawBody, true);

            if (!$payload || empty($payload)) {
                Log::channel('webhook')->error("PayTabs Invalid JSON payload");
                return response()->json(['error' => 'Invalid payload'], 400);
            }


            /**
             * Expected PayTabs payload structure
             * ----------------------------------
             * tran_ref
             * cart_id
             * payment_result.response_status
             * metadata (optional)
             */

            $tranRef = $payload['tran_ref'] ?? null;
            $cartId  = $payload['cart_id'] ?? null;
            $status  = $payload['payment_result']['response_status'] ?? null;
            $metadata = $payload['metadata'] ?? [];

            $transaction_id = $metadata['payment_transaction_id'] ?? null;
            $package_id     = $metadata['package_id'] ?? null;
            $user_id        = $metadata['user_id'] ?? null;

            if ((!$transaction_id || !$package_id) && $cartId) {
                $parts = explode('-', $cartId);
                $transaction_id = $parts[1] ?? null;
                $package_id     = $parts[3] ?? null;
            }

            if ($transaction_id && !$user_id) {
                $paymentTransaction = PaymentTransaction::find($transaction_id);
                $user_id = $paymentTransaction->user_id ?? null;
            }

            if (!$transaction_id || !$package_id || !$user_id) {
                Log::channel('webhook')->error("PayTabs Missing transaction metadata", [
                    'tran_ref' => $tranRef,
                    'cart_id' => $cartId,
                    'metadata' => $metadata
                ]);

                return response()->json(['status' => 'ignored'], 200);
            }

            $paymentTransaction = PaymentTransaction::find($transaction_id);

            if (!$paymentTransaction) {
                Log::channel('webhook')->error("PayTabs PaymentTransaction not found", [$transaction_id]);
                return response()->json(['status' => 'ignored'], 200);
            }

            if ($paymentTransaction->payment_status === 'succeed') {
                return response()->json(['status' => 'already_processed'], 200);
            }

            if ($status === 'A') {
                // ✅ Approved
                $response = $this->assignPackage($transaction_id, $user_id, $package_id);

                if ($response['error']) {
                    Log::channel('webhook')->error("PayTabs assignPackage failed", [$response['message']]);
                }

                return response()->json(['status' => 'success'], 200);
            }

            if (in_array($status, ['D', 'E', 'C'])) {
                // ❌ Declined / Error / Cancelled
                $response = $this->failedTransaction($transaction_id, $user_id);

                if ($response['error']) {
                    Log::channel('webhook')->error("PayTabs failedTransaction failed", [$response['message']]);
                }

                return response()->json(['status' => 'failed'], 200);
            }

            Log::channel('webhook')->warning("PayTabs Payment pending/hold", [
                'status' => $status,
                'tran_ref' => $tranRef
            ]);

            return response()->json(['status' => 'pending'], 200);
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("PayTabs Webhook Fatal Error", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function paytabsSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }

    public function dpoSuccessCallback(Request $request)
    {
        $transactionToken = $request->query('TransactionToken') ?? $request->query('token');
        $companyRef = $request->query('CompanyRef');

        try {
            if (!$transactionToken) {
                Log::channel('webhook')->warning("DPO Success Callback: Transaction token not found", [
                    'request' => $request->all()
                ]);
                ResponseService::successResponse("Payment received. Your transaction is being processed.");
                return;
            }
            $dpoConfig = PaymentConfiguration::where(['payment_method' => 'DPO', 'status' => 1])->first();
            $paymentMode = $dpoConfig->payment_mode ?? 'UAT';

          
            if ($paymentMode === 'UAT') {
                try {
                    $dpo = PaymentService::create('dpo');
                    $verified = $dpo->retrievePaymentIntent($transactionToken);

                    if ($verified['status'] === 'succeeded') {
                        // Find payment transaction by order_id (token)
                        $paymentTransaction = PaymentTransaction::where('order_id', $transactionToken)->first();
                        
                        if ($paymentTransaction && $paymentTransaction->payment_status !== 'succeed') {
                            // Assign package immediately in test mode
                            $result = $this->assignPackage(
                                $paymentTransaction->id,
                                $paymentTransaction->user_id,
                                $paymentTransaction->package_id
                            );
                            
                            if (isset($result['error']) && $result['error']) {
                                Log::channel('webhook')->error("DPO Success Callback: Package assignment failed", [
                                    'transaction_id' => $paymentTransaction->id,
                                    'error' => $result['message'] ?? 'Unknown error'
                                ]);
                            }
                            
                            ResponseService::successResponse("Payment verified and package assigned successfully.");
                        } elseif ($paymentTransaction && $paymentTransaction->payment_status === 'succeed') {
                            ResponseService::successResponse("Payment already processed successfully.");
                        } else {
                            Log::channel('webhook')->warning("DPO Success Callback: Payment transaction not found", [
                                'transaction_token' => $transactionToken
                            ]);
                            ResponseService::successResponse("Payment received. Your transaction is being processed.");
                        }
                    } else {
                        Log::channel('webhook')->warning("DPO Success Callback: Payment verification status not succeeded", [
                            'transaction_token' => $transactionToken,
                            'status' => $verified['status'] ?? 'unknown'
                        ]);
                        ResponseService::successResponse("Payment received. Your transaction is being processed.");
                    }
                } catch (\Throwable $e) {
                    Log::channel('webhook')->error("DPO Success Callback Verify Error (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    ResponseService::successResponse("Payment received. Your transaction is being processed.");
                }
            } else {
             // Live mode (PROD) - webhook will handle package assignment in background
                ResponseService::successResponse("Payment received. Your package will be activated shortly.");
            }
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("DPO Success Callback Handler Error", [
                'transaction_token' => $transactionToken,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            ResponseService::successResponse("Payment received. Your transaction is being processed.");
        }
    }

    public function dpoPaymentSuccess(Request $request)
    {
        $transactionToken = $request->query('TransactionToken') ?? $request->query('token');
        $companyRef = $request->query('CompanyRef');

        try {

            if (!$transactionToken) {
                Log::channel('webhook')->warning("DPO Success Redirect: Transaction token not found", [
                    'request' => $request->all()
                ]);
                return view('payment.dpo', [
                    'transactionToken' => null,
                    'companyRef' => null,
                    'message' => 'Payment received. Your transaction is being processed.'
                ]);
            }

            $dpoConfig = PaymentConfiguration::where(['payment_method' => 'DPO', 'status' => 1])->first();
            $paymentMode = $dpoConfig->payment_mode ?? 'UAT';

            if ($paymentMode === 'UAT') {
                try {
                    $dpo = PaymentService::create('dpo');
                    $verified = $dpo->retrievePaymentIntent($transactionToken);

                    if ($verified['status'] === 'succeeded') {
                        $paymentTransaction = PaymentTransaction::where('order_id', $transactionToken)->first();
                        
                        if ($paymentTransaction && $paymentTransaction->payment_status !== 'succeed') {
                            // Assign package immediately in test mode
                            $result = $this->assignPackage(
                                $paymentTransaction->id,
                                $paymentTransaction->user_id,
                                $paymentTransaction->package_id
                            );
                            
                            if (isset($result['error']) && $result['error']) {
                                Log::channel('webhook')->error("DPO Success Redirect: Package assignment failed", [
                                    'transaction_id' => $paymentTransaction->id,
                                    'error' => $result['message'] ?? 'Unknown error'
                                ]);
                            }
                            
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment verified and package assigned successfully.'
                            ]);
                        } elseif ($paymentTransaction && $paymentTransaction->payment_status === 'succeed') {
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment already processed successfully.'
                            ]);
                        } else {
                            Log::channel('webhook')->warning("DPO Success Redirect: Payment transaction not found", [
                                'transaction_token' => $transactionToken
                            ]);
                            return view('payment.dpo', [
                                'transactionToken' => $transactionToken,
                                'companyRef' => $companyRef,
                                'message' => 'Payment received. Your transaction is being processed.'
                            ]);
                        }
                    } else {
                        Log::channel('webhook')->warning("DPO Success Redirect: Payment verification status not succeeded", [
                            'transaction_token' => $transactionToken,
                            'status' => $verified['status'] ?? 'unknown'
                        ]);
                        return view('payment.dpo', [
                            'transactionToken' => $transactionToken,
                            'companyRef' => $companyRef,
                            'message' => 'Payment received. Your transaction is being processed.'
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::channel('webhook')->error("DPO Success Verify Error (UAT Mode)", [
                        'transaction_token' => $transactionToken,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    return view('payment.dpo', [
                        'transactionToken' => $transactionToken,
                        'companyRef' => $companyRef,
                        'message' => 'Payment received. Your transaction is being processed.'
                    ]);
                }
            } else {
                // Live mode (PROD) - webhook will handle package assignment in background
                return view('payment.dpo', [
                    'transactionToken' => $transactionToken,
                    'companyRef' => $companyRef,
                    'message' => 'Payment received. Your package will be activated shortly.'
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('webhook')->error("DPO Success Handler Error", [
                'transaction_token' => $transactionToken,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('payment.dpo', [
                'transactionToken' => $transactionToken,
                'companyRef' => $companyRef,
                'message' => 'Payment received. Your transaction is being processed.'
            ]);
        }
    }

    public function dpo(Request $request)
    {
        try {
            Log::channel('webhook')->info('DPO Webhook called');

            // Only accept POST requests
            if ($request->method() !== 'POST') {
                Log::channel('webhook')->error('DPO Webhook : Invalid request method');
                return response()->json(['error' => 'Invalid request method'], 405);
            }

            // Raw body — DPO posts an XML notification.
            $rawBody = $request->getContent();
            if (empty($rawBody)) {
                Log::channel('webhook')->error('DPO Webhook : Empty payload');
                return response()->json(['ignored' => true], 200);
            }

            // Extract the DPO TransactionToken (XML), falling back to a posted field.
            preg_match('/TransactionToken>(.*?)<\/TransactionToken>/', $rawBody, $matches);
            $token = $matches[1] ?? $request->input('TransactionToken');
            if (empty($token)) {
                Log::channel('webhook')->error('DPO Webhook : TransactionToken missing');
                return response()->json(['ignored' => true], 200);
            }

            // Resolve the transaction (DPO's TransactionToken is stored in order_id).
            $paymentTransaction = PaymentTransaction::where('order_id', $token)->first();
            if (!$paymentTransaction) {
                Log::channel('webhook')->warning('DPO Webhook : Transaction not found', [$token]);
                return response()->json(['ignored' => true], 200);
            }

            // Idempotency: assignPackage marks payment_status as "succeed".
            if ($paymentTransaction->payment_status === 'succeed') {
                return response()->json(['already_processed' => true], 200);
            }

            // Authenticate by re-verifying the token with DPO (verifyToken + CompanyToken).
            // This is the authoritative check: a forged notification cannot pass because DPO
            // must return Result 000 for our CompanyToken.
            $dpo = PaymentService::create('dpo');
            $verified = $dpo->retrievePaymentIntent($token);

            if (($verified['status'] ?? null) === 'succeeded') {
                $response = $this->assignPackage(
                    $paymentTransaction->id,
                    $paymentTransaction->user_id,
                    $paymentTransaction->package_id
                );
                if ($response['error']) {
                    Log::channel('webhook')->error('DPO Webhook : ', [$response['message']]);
                }
                return response()->json(['status' => 'success'], 200);
            }

            $response = $this->failedTransaction(
                $paymentTransaction->id,
                $paymentTransaction->user_id
            );
            if ($response['error']) {
                Log::channel('webhook')->error('DPO Webhook : ', [$response['message']]);
            }

            return response()->json(['status' => 'failed'], 200);
        } catch (\Throwable $e) {
            Log::channel('webhook')->error('DPO Webhook Fatal', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => true], 500);
        }
    }

    public function flutterWaveSuccessCallback()
    {
        ResponseService::successResponse("Payment done successfully.");
    }
    /**
     * Failed Business Logic
     * @param $payment_transaction_id
     * @param $user_id
     * @return array
     */
    private function failedTransaction($payment_transaction_id, $user_id)
    {
        try {
            $paymentTransactionData = PaymentTransaction::find($payment_transaction_id);
            if (!$paymentTransactionData) {
                return [
                    'error'   => true,
                    'message' => 'Payment Transaction id not found'
                ];
            }

            $paymentTransactionData->update(['payment_status' => "failed"]);

            $body = 'Amount :- ' . $paymentTransactionData->amount;
            // Dispatch chunked notification jobs using centralized service
            NotificationService::dispatchChunkedNotifications(
                'Package Payment Failed',
                $body,
                'payment',
                ['id' => $paymentTransactionData->id],
                false,
                array($user_id),
                true
            );
            // NotificationService::sendFcmNotification($userTokens, 'Package Payment Failed', $body, 'payment');
            return [
                'error'   => false,
                'message' => 'Transaction Verified Successfully'
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::channel('webhook')->error($th->getMessage() . "WebhookController -> failedTransaction");
            return [
                'error'   => true,
                'message' => 'Error Occurred'
            ];
        }
    }
}
