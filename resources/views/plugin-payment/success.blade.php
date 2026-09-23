<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Payment Successful</h2>
        <p>{{ __('Your payment has been completed successfully.') }}</p>
        @isset($transaction)
            <p style="color: #666; font-size: 12px;">{{ __('Transaction ID') }}: {{ $transaction->id }}</p>
        @endisset
    </div>
    <script>
        if (window.opener) {
            window.opener.postMessage({
                status: 'success',
                paymentTransactionId: {{ $transaction->id ?? 'null' }},
                gateway: '{{ $transaction->payment_gateway ?? '' }}',
            }, '*');
            setTimeout(() => window.close(), 2000);
        }
    </script>
</body>
</html>
