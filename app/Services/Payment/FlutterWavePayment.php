<?php

namespace App\Services\Payment;

use App\Services\ResponseService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FlutterwavePayment implements PaymentInterface
{
    private string $currencyCode;
    private string $secretKey;
    private string $publicKey;
    private ?string $encryptionKey;
    protected string $baseUrl;

    /**
     * @param string      $secret_key     Flutterwave Secret Key — used as the API Bearer token.
     * @param string      $public_key     Flutterwave Public Key.
     * @param string|null $encryption_key Flutterwave Encryption Key. Only required for the
     *                                    direct-card charge API; the Standard (hosted) checkout
     *                                    used here does not need it, so it is optional.
     * @param string      $currencyCode
     */
    public function __construct($secret_key, $public_key, $encryption_key, $currencyCode)
    {
        $this->currencyCode  = $currencyCode;
        $this->secretKey     = $secret_key;
        $this->publicKey     = $public_key;
        $this->encryptionKey = $encryption_key ?: null;
        $this->baseUrl       = 'https://api.flutterwave.com/v3';
    }

    /**
     * Create a Flutterwave Standard (hosted) payment and return the checkout link.
     * The client reads the redirect link from payment_gateway_response.data.link.
     * Docs: https://developer.flutterwave.com/docs/collecting-payments/standard/
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            if (empty($customMetaData['email'])) {
                throw new Exception("Email cannot be empty");
            }

            $redirectUrl = ($customMetaData['platform_type'] == 'app')
                ? route('flutterwave.success')
                : route('flutterwave.success.web');

            $finalAmount    = $amount;
            $transactionRef = 't' . '-' . $customMetaData['payment_transaction_id'] . '-' . 'p' . '-' . $customMetaData['package_id'];

            $payload = [
                'tx_ref'          => $transactionRef,
                'amount'          => $finalAmount,
                'currency'        => $this->currencyCode,
                'redirect_url'    => $redirectUrl,
                'payment_options' => 'card,banktransfer',
                'customer'        => [
                    'email'       => $customMetaData['email'],
                    'phonenumber' => $customMetaData['phone'] ?? Auth::user()->mobile,
                    'name'        => $customMetaData['name'] ?? Auth::user()->name,
                ],
                'meta'            => [
                    'package_id' => $customMetaData['package_id'],
                    'user_id'    => $customMetaData['user_id'],
                ],
            ];

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post($this->baseUrl . '/payments', $payload);

            if (!$response->successful() || $response->json('status') !== 'success') {
                throw new Exception($response->json('message') ?? 'Unable to initialize Flutterwave payment');
            }

            return $this->formatPaymentIntent(
                $transactionRef,
                $finalAmount,
                $this->currencyCode,
                'pending',
                $customMetaData,
                $response->json()
            );
        } catch (Exception $e) {
            return ResponseService::errorResponse("Payment failed: " . $e->getMessage());
        }
    }

    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        return $this->createPaymentIntent($amount, $customMetaData);
    }

    /**
     * Verify a transaction by its merchant reference (tx_ref). We persist tx_ref as the
     * order_id, so callers pass that value here — hence verify_by_reference (not the
     * numeric-id verify endpoint).
     * Docs: https://developer.flutterwave.com/docs/verify-payments
     */
    public function retrievePaymentIntent($transactionReference): array
    {
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->get($this->baseUrl . '/transactions/verify_by_reference', [
                'tx_ref' => $transactionReference,
            ]);

        if (!$response->successful() || $response->json('status') !== 'success') {
            throw new Exception('Error verifying transaction: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json('data') ?? [];

        return $this->formatPaymentIntent(
            $data['tx_ref'] ?? $transactionReference,
            $data['amount'] ?? 0,
            $data['currency'] ?? $this->currencyCode,
            $data['status'] ?? 'failed',
            [],
            $response->json()
        );
    }

    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array
    {
        return [
            'id' => $id,
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'status' => match ($status) {
                'successful' => 'succeeded',
                'pending' => 'pending',
                'failed' => 'failed',
                default => 'unknown'
            },
            'payment_gateway_response' => $paymentIntent
        ];
    }

    public function minimumAmountValidation($currency, $amount)
    {
        $minimumAmount = match ($currency) {
            'NGN' => 50, // 50 Naira
            default => 1.00
        };

        return ($amount >= $minimumAmount) ? $amount : $minimumAmount;
    }
}
