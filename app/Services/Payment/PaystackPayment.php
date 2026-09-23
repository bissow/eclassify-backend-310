<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PaystackPayment implements PaymentInterface {
    private string $currencyCode;
    private string $secretKey;
    private string $baseUrl = 'https://api.paystack.co';

    /**
     * PaystackPayment constructor.
     * @param $currencyCode
     */
    public function __construct($currencyCode) {
        $this->currencyCode = $currencyCode;
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     */
    public function createPaymentIntent($amount, $customMetaData) {

        try {

            if (empty($customMetaData['email'])) {
                throw new RuntimeException("Email cannot be empty");
            }
            if($customMetaData['platform_type'] == 'app') {
                $callbackUrl = route('paystack.success') ;
            }else{
                $callbackUrl = route('paystack.success.web');
            }

            $finalAmount = $amount * 100;
            $reference = uniqid('T', true);

            $data = [
                'amount'   => $finalAmount,
                'currency' => $this->currencyCode,
                'email'    => $customMetaData['email'],
                'metadata' => $customMetaData,
                'reference' => $reference,
                'callback_url' => $callbackUrl
            ];

            $response = Http::withToken($this->secretKey)
                ->baseUrl($this->baseUrl)
                ->post('/transaction/initialize', $data)
                ->throw()
                ->json();

            return $response;

        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return array
     */
    public function createAndFormatPaymentIntent($amount, $customMetaData): array {
        $response = $this->createPaymentIntent($amount, $customMetaData);
        return $this->format($response, $amount, $this->currencyCode, $customMetaData);
    }

    /**
     * @param $paymentId
     * @return array
     * @throws Throwable
     */
    public function retrievePaymentIntent($paymentId): array {
        try {
            $response = Http::withToken($this->secretKey)
                ->baseUrl($this->baseUrl)
                ->get("/transaction/verify/{$paymentId}")
                ->throw()
                ->json();

            return $this->format($response['data'], $response['data']['amount'], $response['data']['currency'], $response['data']['metadata']);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param $currency
     * @param $amount
     */
    public function minimumAmountValidation($currency, $amount) {
        // TODO: Implement minimumAmountValidation() method.
    }

    /**
     * @param $paymentIntent
     * @param $amount
     * @param $currencyCode
     * @param $metadata
     * @return array
     */
    public function format($paymentIntent, $amount, $currencyCode, $metadata) {
        return $this->formatPaymentIntent($paymentIntent['data']['reference'], $amount, $currencyCode, $paymentIntent['status'], $metadata, $paymentIntent);
    }

    /**
     * @param $id
     * @param $amount
     * @param $currency
     * @param $status
     * @param $metadata
     * @param $paymentIntent
     * @return array
     */
    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array {
        return [
            'id'                       => $id,
            'amount'                   => $amount,
            'currency'                 => $currency,
            'metadata'                 => $metadata,
            'status'                   => match ($status) {
                "abandoned" => "failed",
                "succeed" => "succeed",
                default => $status ?? true
            },
            'payment_gateway_response' => $paymentIntent
        ];
    }


}
