<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DpoPayment implements PaymentInterface
{
    private string $companyToken;
    private string $serviceType;
    private string $baseUrl;
    private string $callbackUrl;
    private string $paymentMode;

    public function __construct(
        string $companyToken,
        string $serviceType,
        bool $isLive
    ) {
        $this->companyToken = trim($companyToken);
        $this->serviceType  = trim($serviceType);
        $this->paymentMode = $isLive ? 'PROD' : 'UAT';
        // Validate required parameters
        if (empty($this->companyToken)) {
            throw new Exception('DPO CompanyToken is required but was not provided. Please configure it in Payment Configuration.');
        }

        if (empty($this->serviceType)) {
            throw new Exception('DPO ServiceType is required but was not provided. Please configure it in Payment Configuration.');
        }

        // DPO uses same base for sandbox & live (mode is controlled by token)
        $this->baseUrl = 'https://secure.3gdirectpay.com';

        $this->callbackUrl = route('dpo.webhook');
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function createPaymentIntent($amount, $customMetaData)
    {
        $transactionId =
            't-' . $customMetaData['payment_transaction_id']
            . '-p-' . $customMetaData['package_id'];

        // Format ServiceDate with time (required by DPO API)
        $serviceDate = date('Y/m/d H:i');
        
        // DPO RedirectURL: Where user goes after successful payment (web)
        $redirectUrl = ($customMetaData['platform_type'] == 'app')
            ? route('dpo.success')
            : route('dpo.success.web');
        
        // DPO BackURL: Where user goes if payment is cancelled or fails (webhook/callback)
        // This is used as the callback URL
           
        $xmlData = '<?xml version="1.0" encoding="utf-8"?><API3G>' .
            '<CompanyToken>' . htmlspecialchars($this->companyToken, ENT_XML1, 'UTF-8') . '</CompanyToken>' .
            '<Request>createToken</Request>' .
            '<Transaction>' .
            '<PaymentAmount>' . number_format((float)$amount, 2, '.', '') . '</PaymentAmount>' .
            '<PaymentCurrency>' . htmlspecialchars($customMetaData['currency_code'] ?? 'USD', ENT_XML1, 'UTF-8') . '</PaymentCurrency>' .
            '<CompanyRef>' . htmlspecialchars($transactionId, ENT_XML1, 'UTF-8') . '</CompanyRef>' .
            '<RedirectURL>' . htmlspecialchars($redirectUrl, ENT_XML1, 'UTF-8') . '</RedirectURL>' .
            '<BackURL>' . htmlspecialchars($this->callbackUrl, ENT_XML1, 'UTF-8') . '</BackURL>' .
            '<CompanyRefUnique>0</CompanyRefUnique>' .
            '<PTL>10</PTL>' .
            '</Transaction>' .
            '<Services>' .
            '<Service>' .
            '<ServiceType>' . htmlspecialchars($this->serviceType, ENT_XML1, 'UTF-8') . '</ServiceType>' .
            '<ServiceDescription>Package Payment</ServiceDescription>' .
            '<ServiceDate>' . $serviceDate . '</ServiceDate>' .
            '</Service>' .
            '</Services>' .
            '</API3G>';

        $endpoint = $this->baseUrl . '/API/v6/';
        $ch = curl_init();
        
        if (!$ch) {
            throw new Exception("Couldn't initialize a cURL handle");
        }
        
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        if ($curlError) {
            Log::error('DPO cURL error', ['error' => $curlError]);
            throw new Exception('DPO API request failed: ' . $curlError);
        }
        // Check if response contains TransToken
        if (!str_contains($responseBody, '<TransToken>')) {
            // Try to extract error message from response
            $errorMessage = 'Unable to create DPO payment';
            if (preg_match('/<ResultExplanation>(.*?)<\/ResultExplanation>/', $responseBody, $errorMatches)) {
                $errorMessage = 'DPO Error: ' . trim($errorMatches[1]);
            } elseif (preg_match('/<Result>(.*?)<\/Result>/', $responseBody, $resultMatches)) {
                $errorMessage = 'DPO Error Code: ' . trim($resultMatches[1]);
            }
            Log::error('DPO createToken failed', [
                'response' => $responseBody,
                'http_code' => $httpCode,
                'error' => $errorMessage,
            ]);
            throw new Exception($errorMessage);
        }

        // Extract TransToken from response
        preg_match('/<TransToken>(.*?)<\/TransToken>/', $responseBody, $matches);

        if (empty($matches[1])) {
            Log::error('DPO TransToken not found in response', ['response' => $responseBody]);
            throw new Exception('TransToken not found in DPO response');
        }

        $transToken = trim($matches[1]);

        // Extract TransRef if available
        $transRef = null;
        if (preg_match('/<TransRef>(.*?)<\/TransRef>/', $responseBody, $refMatches)) {
            $transRef = trim($refMatches[1]);
        }

        return [
            'transaction_id' => $transactionId,
            'token' => $transToken,
            'trans_ref' => $transRef,
            'payment_url' => $this->baseUrl . '/payv3.php?ID=' . $transToken, // For webview
            'metadata' => $customMetaData,
            'raw_response' => $responseBody,
        ];
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);

        return $this->formatPaymentIntent(
            $paymentIntent['token'],
            $amount,
            $customMetaData['currency_code'],
            'PENDING',
            $customMetaData,
            $paymentIntent
        );
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function retrievePaymentIntent($transactionToken): array
    {
        // Build XML with proper declaration (single line as per DPO requirements)
        $xmlData = '<?xml version="1.0" encoding="utf-8"?><API3G>' .
            '<CompanyToken>' . htmlspecialchars($this->companyToken, ENT_XML1, 'UTF-8') . '</CompanyToken>' .
            '<Request>verifyToken</Request>' .
            '<TransactionToken>' . htmlspecialchars($transactionToken, ENT_XML1, 'UTF-8') . '</TransactionToken>' .
            '</API3G>';

        // Use cURL for consistency
        $endpoint = $this->baseUrl . '/API/v6/';
        
        $ch = curl_init();
        
        if (!$ch) {
            throw new Exception("Couldn't initialize a cURL handle");
        }
        
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        if ($curlError) {
            Log::error('DPO verifyToken cURL error', ['error' => $curlError]);
            throw new Exception('DPO API request failed: ' . $curlError);
        }

        Log::info('DPO verifyToken response', [
            'http_code' => $httpCode,
            'response_length' => strlen($responseBody),
        ]);

        // Check for Result code 000 (success) or other success indicators
        $status = 'FAILED';
        if (preg_match('/<Result>(.*?)<\/Result>/', $responseBody, $resultMatches)) {
            $resultCode = trim($resultMatches[1]);
            // Result code 000 means success according to DPO documentation
            $status = ($resultCode === '000') ? 'SUCCESS' : 'FAILED';
        }

        // Extract amount and currency if available
        $amount = 0;
        $currency = 'USD';
        if (preg_match('/<PaymentAmount>(.*?)<\/PaymentAmount>/', $responseBody, $amountMatches)) {
            $amount = (float)trim($amountMatches[1]);
        }
        if (preg_match('/<PaymentCurrency>(.*?)<\/PaymentCurrency>/', $responseBody, $currencyMatches)) {
            $currency = trim($currencyMatches[1]);
        }

        return $this->formatPaymentIntent(
            $transactionToken,
            $amount,
            $currency,
            $status,
            [],
            $responseBody
        );
    }

    /** --------------------------------
     * Interface method
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
                'SUCCESS' => 'succeeded',
                'PENDING' => 'pending',
                default   => 'failed'
            },
            'payment_gateway_response' => $paymentIntent
        ];

        // Add payment URL for webview (DPO format: PaymentURL=XXXX)
        // If paymentIntent is an array with payment_url or redirect_url, include it
        if (is_array($paymentIntent)) {
            if (isset($paymentIntent['payment_url'])) {
                $formatted['payment_url'] = $paymentIntent['payment_url'];   // Also add in DPO format: PaymentURL=XXXX
            }elseif (isset($paymentIntent['token'])) { // Build payment URL from token
                $paymentUrl = $this->baseUrl . '/payv3.php?ID=' . $paymentIntent['token'];
                $formatted['payment_url'] = $paymentUrl;
            }
        }

        return $formatted;
    }

    /** --------------------------------
     * Interface method
     * --------------------------------*/
    public function minimumAmountValidation($currency, $amount)
    {
        return max($amount, 1.00);
    }
}