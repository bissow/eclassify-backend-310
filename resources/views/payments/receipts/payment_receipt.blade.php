<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar','he','fa','ur']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>{{ __('Payment Receipt') }}</title>

    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            padding-left: 10px;
            padding-right: 10px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .logo {
            height: 80px;
            max-width: 200px;
            margin: 0 auto 10px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .receipt-number {
            font-size: 16px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-row {
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f8f8f8;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            margin-bottom: 5px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(!empty($settings['company_logo']))
                <img src="{{ $settings['company_logo'] }}" alt="{{ __('Company Logo') }}" class="logo"><br>
            @endif
            <div class="receipt-title">{{ __('Payment Receipt') }}</div>
            <div class="receipt-number">{{ __('Receipt') }} #{{ $payment->id }}</div>
        </div>

        <div class="info-section">
            <h3>{{ __('Customer Information') }}</h3>
            @if($payment->user)
                <div class="info-row">
                    <strong>{{ __('Name') }}:</strong> {{ $payment->user->name }}
                </div>
                @if($payment->user->email)
                    <div class="info-row">
                        <strong>{{ __('Email') }}:</strong> {{ \App\Services\HelperService::maskDemoEmail($payment->user->email) }}
                    </div>
                @endif
                @if($payment->user->mobile)
                    <div class="info-row">
                        <strong>{{ __('Mobile') }}:</strong> {{ \App\Services\HelperService::maskDemoMobile($payment->user->mobile) }}
                    </div>
                @endif
            @endif
        </div>

        <div class="info-section">
            <h3>{{ __('Payment Information') }}</h3>
            <div class="info-row">
                <strong>{{ __('Payment Date') }}:</strong> {{ $payment->created_at->format('d M Y') }}
            </div>
            @if($payment->order_id)
                <div class="info-row">
                    <strong>{{ __('Order ID') }}:</strong> {{ $payment->order_id }}
                </div>
            @endif
            <div class="info-row">
                <strong>{{ __('Payment Gateway') }}:</strong> {{ __(ucfirst($payment->payment_gateway)) }}
            </div>
            <div class="info-row">
                <strong>{{ __('Payment Status') }}:</strong>
                <span class="payment-status status-{{ $payment->payment_status }}">{{ __($payment->payment_status_upper) }}</span>
            </div>
        </div>

        @php
            $currencySymbol = $settings['currency_symbol'] ?? '$';
            $package = $payment->package_id ? \App\Models\Package::find($payment->package_id) : null;
        @endphp

        @if($package)
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Duration') }}</th>
                        <th>{{ __('Item Limit') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>
                            @if(is_null($package->duration) || $package->type == 'unlimited')
                                {{ __('Unlimited') }}
                            @else
                                {{ $package->duration }} {{ __('Days') }}
                            @endif
                        </td>
                        <td>
                            @if($package->item_limit == 'unlimited' || $package->item_limit == 0)
                                {{ __('Unlimited') }}
                            @else
                                {{ $package->item_limit }}
                            @endif
                        </td>
                        <td>{{ $currencySymbol }} {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Payment Transaction') }}</td>
                        <td>{{ $currencySymbol }} {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if(($payment->original_price && $payment->original_price > 0) || ($payment->discount_price && $payment->discount_price > 0) || ($payment->refer_points_used && $payment->refer_points_used > 0))
            <table class="table">
                <tbody>
                    @if($payment->original_price && $payment->original_price > 0)
                        <tr>
                            <td><strong>{{ __('Original Price') }}</strong></td>
                            <td>{{ $currencySymbol }} {{ number_format($payment->original_price, 2) }}</td>
                        </tr>
                    @endif
                    @if($payment->discount_price && $payment->discount_price > 0)
                        <tr>
                            <td><strong>{{ __('Discount') }}</strong></td>
                            <td>- {{ $currencySymbol }} {{ number_format($payment->discount_price, 2) }}</td>
                        </tr>
                    @endif
                    @if($payment->refer_points_used && $payment->refer_points_used > 0)
                        <tr>
                            <td><strong>{{ __('Refer Points Used') }}</strong></td>
                            <td>{{ $payment->refer_points_used }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        <div class="total-section">
            <div class="total-row">
                <span class="total-label">{{ __('Total Amount') }}:</span>
                <span class="total-amount">{{ $currencySymbol }} {{ number_format($payment->amount, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            @if($payment->payment_status === 'succeed')
                <p>{{ __('Thank you for your purchase!') }}</p>
            @endif
            <p>{{ $settings['company_name'] ?? '' }}@if(!empty($settings['company_address'])) | {{ $settings['company_address'] }}@endif</p>
            @php
                $phone = $settings['company_tel1'] ?? ($settings['company_tel2'] ?? '');
            @endphp
            <p>@if(!empty($settings['company_email'])){{ $settings['company_email'] }}@endif @if(!empty($phone))| {{ $phone }}@endif</p>
            <p>{{ __('Receipt generated on') }} {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>
</body>
</html>
