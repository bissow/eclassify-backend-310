<!DOCTYPE html>
<html>
<head>
    <title>DPO Payment Success</title>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Payment Processing</h2>
        <p id="message">{{ $message ?? 'Processing your payment...' }}</p>
        @if(isset($transactionToken))
        <p style="color: #666; font-size: 12px;">Transaction Token: {{ $transactionToken }}</p>
        @endif
    </div>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const transactionToken = urlParams.get('TransactionToken') || urlParams.get('token') || '{{ $transactionToken ?? '' }}';
        const companyRef = urlParams.get('CompanyRef') || '{{ $companyRef ?? '' }}';

        // If opened in popup/iframe, send message to parent
        if (window.opener) {
            window.opener.postMessage({
                status: 'success',
                transactionToken: transactionToken,
                companyRef: companyRef,
                message: '{{ $message ?? "Payment processed successfully" }}'
            }, '*');
            setTimeout(() => window.close(), 2000);
        }
    </script>
</body>
</html>
