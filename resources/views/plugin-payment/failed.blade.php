<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Payment Failed</h2>
        <p>{{ $message ?? __('Your payment could not be completed.') }}</p>
        @isset($transaction)
            <p style="color: #666; font-size: 12px;">{{ __('Transaction ID') }}: {{ $transaction->id }}</p>
        @endisset
    </div>
    <script>
        if (window.opener) {
            window.opener.postMessage({
                status: 'failed',
                paymentTransactionId: {{ $transaction->id ?? 'null' }},
                gateway: '{{ $transaction->payment_gateway ?? '' }}',
            }, '*');
            setTimeout(() => window.close(), 2000);
        }
    </script>
</body>
</html>
