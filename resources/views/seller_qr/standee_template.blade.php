<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - QR Standee</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #ffffff;
            color: #0f172a;
            width: 100%;
            height: 100%;
            position: relative;
        }
        .standee-container {
            width: 100%;
            height: 100%;
            padding: 22px 18px 16px 18px;
            background: #ffffff;
            text-align: center;
            border-top: 8px solid {{ $primaryColor }};
        }
        .brand-badge {
            display: inline-block;
            background: {{ $primaryColor }}18;
            color: {{ $primaryColor }};
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .header-title {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 5px;
        }
        .header-tagline {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 14px;
            line-height: 1.35;
        }
        .qr-card-wrapper {
            background: #ffffff;
            border: 2.5px solid {{ $primaryColor }};
            border-radius: 18px;
            padding: 14px;
            margin: 0 auto 14px auto;
            width: 78%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
        }
        .qr-image {
            width: 190px;
            height: 190px;
            display: block;
            margin: 0 auto;
        }
        .scan-action-badge {
            margin-top: 10px;
            display: inline-block;
            background: {{ $secondaryColor }};
            color: #ffffff;
            padding: 5px 14px;
            border-radius: 16px;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .store-info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            margin: 0 auto 12px auto;
            width: 88%;
            text-align: left;
        }
        .store-avatar-box {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: {{ $primaryColor }}15;
            overflow: hidden;
            text-align: center;
            line-height: 36px;
        }
        .store-avatar-img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            vertical-align: middle;
        }
        .store-name-row {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 2px;
        }
        .verified-tick {
            color: #0284c7;
            font-weight: bold;
            font-size: 12px;
            margin-left: 4px;
        }
        .store-location {
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.3;
        }
        .store-contact {
            font-size: 10px;
            color: #475569;
            font-weight: 600;
            margin-top: 3px;
        }
        .standee-footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
            font-size: 9.5px;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .footer-logo {
            max-height: 18px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 6px;
        }
    </style>
</head>
<body>
    <div class="standee-container">
        <!-- Top Tag / Category -->
        <div class="brand-badge">
            {{ $badgeText ?? __('DIGITAL STORE & CATALOG') }}
        </div>

        <!-- Heading & Tagline -->
        <h1 class="header-title">{{ $title }}</h1>
        <p class="header-tagline">{{ $tagline }}</p>

        <!-- QR Code Standee Card -->
        <div class="qr-card-wrapper">
            <img src="{{ $qrBase64 }}" class="qr-image" alt="Store QR Code" />
            <div class="scan-action-badge">
                {{ __('SCAN TO VIEW ALL ADS & OFFERS') }}
            </div>
        </div>

        <!-- Store Information -->
        @if ($store)
        <div class="store-info-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 44px; vertical-align: middle; padding-right: 10px;">
                        <div class="store-avatar-box">
                            @if (!empty($storeLogoUrl))
                                <img src="{{ $storeLogoUrl }}" class="store-avatar-img" alt="{{ $store->name }}" />
                            @else
                                <span style="font-size: 18px; color: {{ $primaryColor }}; line-height: 36px;">🏪</span>
                            @endif
                        </div>
                    </td>
                    <td style="vertical-align: middle; text-align: left;">
                        <div class="store-name-row">
                            {{ $store->name }}
                            @if ($isVerified)
                                <span class="verified-tick" title="{{ __('Verified Store') }}">✓ {{ __('Verified') }}</span>
                            @endif
                        </div>
                        @if ($storeAddress)
                            <div class="store-location">{{ $storeAddress }}</div>
                        @endif
                        @if ($storeContact)
                            <div class="store-contact">{{ __('Call / WhatsApp:') }} {{ $storeContact }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Footer Notice -->
        <div class="standee-footer">
            <span>{{ $footerText }}</span>
            @if (!empty($footerLogoUrl))
                <img src="{{ $footerLogoUrl }}" class="footer-logo" alt="Platform Logo" />
            @endif
        </div>
    </div>
</body>
</html>
