<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Route;

class PayTabsPayment implements PaymentInterface
{
    private string $profileId;
    private string $serverKey;
    private string $baseUrl;
    private string $callbackUrl;

    /**
     * PayTabs is region-locked: a Server Key only authenticates against its own region's
     * endpoint. The region is configured per profile in Payment Settings and mapped to the
     * correct base URL here.
     */
    private const REGION_ENDPOINTS = [
        'global' => 'https://secure-global.paytabs.com',
        'uae'    => 'https://secure.paytabs.com',
        'ksa'    => 'https://secure.paytabs.sa',
        'egypt'  => 'https://secure-egypt.paytabs.com',
        'oman'   => 'https://secure-oman.paytabs.com',
        'jordan' => 'https://secure-jordan.paytabs.com',
        'kuwait' => 'https://secure-kuwait.paytabs.com',
        'iraq'   => 'https://secure-iraq.paytabs.com',
    ];

    public function __construct(string $serverKey, string $profileId, bool $isLive, ?string $region = null)
    {
        $this->serverKey = trim($serverKey);
        $this->profileId = trim($profileId);

        // Select the region endpoint; default to Global (most broadly compatible).
        // Note: PayTabs has no separate sandbox URL — test vs live is set on the profile,
        // so $isLive does not change the endpoint.
        $regionKey = strtolower(trim((string) $region));
        $this->baseUrl = self::REGION_ENDPOINTS[$regionKey] ?? self::REGION_ENDPOINTS['global'];

        // Safely resolves the named route for your IPN webhook
        $this->callbackUrl = route('paytabs.webhook');

        Log::info('PayTabs initialized', [
            'profileId' => $this->profileId,
            'region'    => $regionKey ?: 'global',
            'baseUrl'   => $this->baseUrl,
        ]);
    }

    /**
     * Create payment page link
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        $redirectUrl = (($customMetaData['platform_type'] ?? 'web') === 'app')
            ? route('paytabs.success')
            : route('paytabs.success.web');

        $validatedAmount = $this->minimumAmountValidation(
            $customMetaData['currency_code'] ?? 'USD',
            $amount
        );

        $transactionId = 't-' . ($customMetaData['payment_transaction_id'] ?? uniqid())
            . '-p-' . ($customMetaData['package_id'] ?? '0');

        // PayTabs requires an ISO 3166-1 alpha-2 country code (e.g. AE, IN, SA).
        $country = strtoupper(trim($customMetaData['country'] ?? $customMetaData['country_code'] ?? ''));
        if (strlen($country) !== 2) {
            $country = 'AE';
        }

        $payload = [
            'profile_id' => (int) $this->profileId, // PayTabs expects an integer
            'tran_type'  => 'sale',
            'tran_class' => 'ecom',

            'cart_id'          => $transactionId,
            'cart_currency'    => $customMetaData['currency_code'] ?? 'USD',
            'cart_amount'      => round($validatedAmount, 2),
            'cart_description' => 'Payment Description',

            'customer_details' => [
                'name'    => $customMetaData['customer_name'] ?? 'Customer',
                'email'   => $customMetaData['customer_email'] ?? 'test@test.com',
                'phone'   => $customMetaData['customer_phone'] ?? '0000000000',
                'street1' => 'Street address',
                'city'    => 'City',
                'state'   => 'State',
                'country' => $country, // ISO 3166-1 alpha-2
                'zip'     => '00000',
            ],

            'callback' => $this->callbackUrl, // Your IPN webhook listener endpoint
            'return'   => $redirectUrl,   // Where the client returns after clicking "Done"

            // Stashing custom variables in standard user_defined metadata
            'user_defined' => [
                'metadata' => $customMetaData
            ]
        ];

        // Perform the API POST request
        $response = Http::withHeaders([
            'Authorization' => $this->serverKey, // Capitalized key
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . '/payment/request', $payload);

        if ($response->failed()) {
            Log::error('PayTabs API error response', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
        }

        return $response->json();
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);

        $transactionId =
            't-' . $customMetaData['payment_transaction_id']
            . '-p-' . $customMetaData['package_id'];

        return $this->formatPaymentIntent(
            $transactionId,
            $amount,
            $customMetaData['currency_code'] ?? 'USD',
            'PENDING',
            $customMetaData,
            $paymentIntent
        );
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function retrievePaymentIntent($transactionId): array
    {
        $response = Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->post($this->baseUrl . '/payment/query', [
            'profile_id' => $this->profileId,
            'tran_ref'   => $transactionId,
        ]);

        $data = $response->json();

        if (!isset($data['payment_result'])) {
            throw new Exception('Invalid PayTabs verification response');
        }

        return $this->formatPaymentIntent(
            $transactionId,
            $data['cart_amount'] ?? 0,
            $data['cart_currency'] ?? 'USD',
            $data['payment_result']['response_status'] ?? 'FAILED',
            $data['metadata'] ?? [],
            $data
        );
    }

    /** --------------------------------
     * Interface method (FIXED)
     * --------------------------------*/
    public function formatPaymentIntent(
        $id,
        $amount,
        $currency,
        $status,
        $metadata,
        $paymentIntent
    ): array {
        $formatted = [
            'id'       => $id,
            'amount'   => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
            'status'   => match ($status) {
                'A', 'SUCCESS' => 'succeeded',
                'P', 'PENDING' => 'pending',
                default => 'failed'
            },
            'payment_gateway_response' => $paymentIntent
        ];

        // Add payment URL for webview (same format as DPO)
        // PayTabs returns redirect_url in the payment gateway response
        if (is_array($paymentIntent)) {
            if (isset($paymentIntent['redirect_url'])) {
                $formatted['payment_url'] = $paymentIntent['redirect_url'];
            }
        }

        return $formatted;
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function minimumAmountValidation($currency, $amount)
    {
        return match ($currency) {
            'USD', 'SAR', 'AED' => max($amount, 1.00),
            default => max($amount, 0.50),
        };
    }
}
