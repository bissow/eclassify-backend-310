@extends('layouts.main')

@section('title')
    {{ __('Payment Gateways Settings') }}
@endsection

@section('content')
    <section class="section pg-page">
        <form id="pgForm" class="create-form-without-reset" action="{{ route('settings.payment-gateway.store') }}" method="post"
            enctype="multipart/form-data" data-success-function="pgReloadAfterSave">
            <div class="row d-flex mb-3 pg-page-inner">
@php
$gwMeta = [
    ['id'=>'gw_stripe','title'=>'Stripe','color'=>'#635BFF','icon'=>'ph ph-credit-card','status'=>($paymentGateway['Stripe']['status'] ?? 0)],
    ['id'=>'gw_razorpay','title'=>'Razorpay','color'=>'#0B5CFF','icon'=>'ph ph-lightning','status'=>($paymentGateway['Razorpay']['status'] ?? 0)],
    ['id'=>'gw_paystack','title'=>'Paystack','color'=>'#11B981','icon'=>'ph ph-stack','status'=>($paymentGateway['Paystack']['status'] ?? 0)],
    ['id'=>'gw_paytabs','title'=>'PayTabs','color'=>'#E10E1B','icon'=>'ph ph-wallet','status'=>($paymentGateway['Paytabs']['status'] ?? 0)],
    ['id'=>'gw_dpo','title'=>'DPO','color'=>'#0072BC','icon'=>'ph ph-globe','status'=>($paymentGateway['DPO']['status'] ?? 0)],
    ['id'=>'gw_phonepe','title'=>'PhonePe','color'=>'#5F259F','icon'=>'ph ph-device-mobile','status'=>($paymentGateway['PhonePe']['status'] ?? 0)],
    ['id'=>'gw_flutterwave','title'=>'Flutterwave','color'=>'#FB8C00','icon'=>'ph ph-butterfly','status'=>($paymentGateway['flutterwave']['status'] ?? 0)],
    ['id'=>'gw_paypal','title'=>'PayPal','color'=>'#0070BA','icon'=>'ph ph-paypal-logo','status'=>($paymentGateway['Paypal']['status'] ?? 0)],
    ['id'=>'gw_bank','title'=>'Bank Transfer','color'=>'#0EA5A4','icon'=>'ph ph-bank','status'=>($settings['bank_transfer_status'] ?? 0)],
];
// Installed plugins that declared themselves type=payment (PayU today, any
// future one later) — added to the same sidebar list generically, no
// per-plugin entry needed here.
foreach ($pluginGateways ?? [] as $plugin) {
    $gwMeta[] = [
        'id' => 'gw_'.strtolower($plugin->plugin_slug),
        'title' => $plugin->name,
        'color' => '#6C5CE7',
        'icon' => 'ph ph-puzzle-piece',
        'status' => $paymentGateway[$plugin->plugin_slug]['status'] ?? 0,
        'is_plugin' => true,
        'is_enabled' => $plugin->is_enabled,
    ];
}
@endphp
            <div class="col-12">
                <div class="pg-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="mb-1">{{ __('Payment Gateways') }}</h4>
                        <p class="text-muted mb-0">{{ __('Manage and configure your payment gateways') }}</p>
                    </div>
                </div>
                <div class="pg-note" role="alert"><i class="ph ph-info"></i><span>{{ __('Note: Some currencies are not supported by payment gateways with decimal values. If your subscription price contains a decimal value, please ensure the selected payment gateway and its currency support decimal amounts to avoid payment failures.') }}</span></div>
            </div>

            <div id="pgUnsavedBanner" class="alert alert-warning d-none align-items-center justify-content-between" style="display:flex;">
                <span><i class="ph ph-warning me-1"></i> {{ __('You have unsaved changes.') }}</span>
                <button type="button" class="btn btn-sm btn-warning" onclick="document.getElementById('pgForm').querySelector('[type=submit]').click();">{{ __('Save Now') }}</button>
            </div>


                

                            <div class="col-12">
                <div class="card pg-wrap-card">
                    <div class="card-body">
                        <div class="pg-toolbar">
                            <div class="btn-group pg-filter" role="group" aria-label="{{ __('Filter gateways') }}">
                                <button type="button" class="btn active" data-filter="all">{{ __('All Gateways') }}</button>
                                <button type="button" class="btn" data-filter="active">{{ __('Active') }}</button>
                                <button type="button" class="btn" data-filter="inactive">{{ __('Inactive') }}</button>
                            </div>
                        </div>
                        <div class="row pg-body g-4">
                            <div class="col-12 col-lg-4 pg-left">
                                <ul class="pg-list" id="gwGrid">
                                    @foreach($gwMeta as $gw)
                                        <li class="pg-list-item {{ $loop->first ? 'selected' : '' }} {{ $gw['status'] ? 'is-active' : 'is-inactive' }}" data-target="detail_{{ $gw['id'] }}" role="button" tabindex="0">
                                            <span class="pg-list-icon" style="--pg:{{ $gw['color'] }}"><i class="{{ $gw['icon'] }}"></i></span>
                                            <span class="pg-list-body">
                                                <span class="pg-list-name" style="display:inline-flex;align-items:center;flex-wrap:wrap;gap:4px;">
                                                    {{ __($gw['title']) }}
                                                    @if ($gw['is_plugin'] ?? false)
                                                        <span style="display:inline-flex;align-items:center;padding:1px 7px;border-radius:999px;font-size:9px;font-weight:600;line-height:1.5;white-space:nowrap;background:#e0f2fe;color:#0369a1;">{{ __('Plugin') }}</span>
                                                        @if (!($gw['is_enabled'] ?? true))
                                                            <span style="display:inline-flex;align-items:center;padding:1px 7px;border-radius:999px;font-size:9px;font-weight:600;line-height:1.5;white-space:nowrap;background:#f1f5f9;color:#64748b;">{{ __('Disabled') }}</span>
                                                        @endif
                                                    @endif
                                                </span>
                                                <span class="pg-list-status">
                                                    <span class="pg-dot"></span>
                                                    <span class="pg-on-label">{{ __('Active') }}</span>
                                                    <span class="pg-off-label">{{ __('Inactive') }}</span>
                                                </span>
                                            </span>
                                            <span class="pg-list-gear"><i class="ph ph-gear-six"></i></span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="pg-empty text-center text-muted py-5 d-none" id="gwEmpty">{{ __('No gateways match this filter.') }}</div>
                            </div>
                            <div class="col-12 col-lg-8 pg-right">
                                <div class="pg-detail-panel" id="pgDetailPanel">
{{-- Stripe Payment Gateway START --}}
<div class="pg-detail active" id="detail_gw_stripe"><div class="pg-detail-head"><h5 class="mb-0">Stripe {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Stripe Setting') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="stripe_currency_code"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Stripe Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[Stripe][currency_code]" id="stripe_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="USD">USD</option>
                                        <option value="AED">AED</option>
                                        <option value="AFN">AFN</option>
                                        <option value="ALL">ALL</option>
                                        <option value="AMD">AMD</option>
                                        <option value="ANG">ANG</option>
                                        <option value="AOA">AOA</option>
                                        <option value="ARS">ARS</option>
                                        <option value="AUD">AUD</option>
                                        <option value="AWG">AWG</option>
                                        <option value="AZN">AZN</option>
                                        <option value="BAM">BAM</option>
                                        <option value="BBD">BBD</option>
                                        <option value="BDT">BDT</option>
                                        <option value="BGN">BGN</option>
                                        <option value="BMD">BMD</option>
                                        <option value="BND">BND</option>
                                        <option value="BOB">BOB</option>
                                        <option value="BRL">BRL</option>
                                        <option value="BSD">BSD</option>
                                        <option value="BWP">BWP</option>
                                        <option value="BYN">BYN</option>
                                        <option value="BZD">BZD</option>
                                        <option value="CAD">CAD</option>
                                        <option value="CDF">CDF</option>
                                        <option value="CHF">CHF</option>
                                        <option value="CNY">CNY</option>
                                        <option value="COP">COP</option>
                                        <option value="CRC">CRC</option>
                                        <option value="CVE">CVE</option>
                                        <option value="CZK">CZK</option>
                                        <option value="DKK">DKK</option>
                                        <option value="DOP">DOP</option>
                                        <option value="DZD">DZD</option>
                                        <option value="EGP">EGP</option>
                                        <option value="ETB">ETB</option>
                                        <option value="EUR">EUR</option>
                                        <option value="FJD">FJD</option>
                                        <option value="FKP">FKP</option>
                                        <option value="GBP">GBP</option>
                                        <option value="GEL">GEL</option>
                                        <option value="GIP">GIP</option>
                                        <option value="GMD">GMD</option>
                                        <option value="GTQ">GTQ</option>
                                        <option value="GYD">GYD</option>
                                        <option value="HKD">HKD</option>
                                        <option value="HNL">HNL</option>
                                        <option value="HTG">HTG</option>
                                        <option value="HUF">HUF</option>
                                        <option value="IDR">IDR</option>
                                        <option value="ILS">ILS</option>
                                        <option value="INR">INR</option>
                                        <option value="ISK">ISK</option>
                                        <option value="JMD">JMD</option>
                                        <option value="KES">KES</option>
                                        <option value="KGS">KGS</option>
                                        <option value="KHR">KHR</option>
                                        <option value="KYD">KYD</option>
                                        <option value="KZT">KZT</option>
                                        <option value="LAK">LAK</option>
                                        <option value="LBP">LBP</option>
                                        <option value="LKR">LKR</option>
                                        <option value="LRD">LRD</option>
                                        <option value="LSL">LSL</option>
                                        <option value="MAD">MAD</option>
                                        <option value="MDL">MDL</option>
                                        <option value="MKD">MKD</option>
                                        <option value="MMK">MMK</option>
                                        <option value="MNT">MNT</option>
                                        <option value="MOP">MOP</option>
                                        <option value="MUR">MUR</option>
                                        <option value="MVR">MVR</option>
                                        <option value="MWK">MWK</option>
                                        <option value="MXN">MXN</option>
                                        <option value="MYR">MYR</option>
                                        <option value="MZN">MZN</option>
                                        <option value="NAD">NAD</option>
                                        <option value="NGN">NGN</option>
                                        <option value="NIO">NIO</option>
                                        <option value="NOK">NOK</option>
                                        <option value="NPR">NPR</option>
                                        <option value="NZD">NZD</option>
                                        <option value="PAB">PAB</option>
                                        <option value="PEN">PEN</option>
                                        <option value="PGK">PGK</option>
                                        <option value="PHP">PHP</option>
                                        <option value="PKR">PKR</option>
                                        <option value="PLN">PLN</option>
                                        <option value="QAR">QAR</option>
                                        <option value="RON">RON</option>
                                        <option value="RSD">RSD</option>
                                        <option value="RUB">RUB</option>
                                        <option value="SAR">SAR</option>
                                        <option value="SBD">SBD</option>
                                        <option value="SCR">SCR</option>
                                        <option value="SEK">SEK</option>
                                        <option value="SGD">SGD</option>
                                        <option value="SHP">SHP</option>
                                        <option value="SLE">SLE</option>
                                        <option value="SOS">SOS</option>
                                        <option value="SRD">SRD</option>
                                        <option value="STD">STD</option>
                                        <option value="SZL">SZL</option>
                                        <option value="THB">THB</option>
                                        <option value="TJS">TJS</option>
                                        <option value="TOP">TOP</option>
                                        <option value="TRY">TRY</option>
                                        <option value="TTD">TTD</option>
                                        <option value="TWD">TWD</option>
                                        <option value="TZS">TZS</option>
                                        <option value="UAH">UAH</option>
                                        <option value="UYU">UYU</option>
                                        <option value="UZS">UZS</option>
                                        <option value="WST">WST</option>
                                        <option value="XAF">XAF</option>
                                        <option value="XCD">XCD</option>
                                        <option value="YER">YER</option>
                                        <option value="ZAR">ZAR</option>
                                        <option value="ZMW">ZMW</option>
                                    </select>
                                </div>

                                <label for="stripe_secret_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Stripe Secret key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="stripe_secret_key" name="gateway[Stripe][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('Stripe Secret key') }}"
                                        value="{{ $paymentGateway['Stripe']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="stripe_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Stripe Publishable key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="stripe_publishable_key" name="gateway[Stripe][api_key]" type="password"
                                        class="form-control" placeholder="{{ __('Stripe Publishable key') }}"
                                        value="{{ $paymentGateway['Stripe']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="stripe_webhook_secret"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Stripe Webhook Secret') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="stripe_webhook_secret" name="gateway[Stripe][webhook_secret_key]"
                                        type="password" class="form-control"
                                        placeholder="{{ __('Stripe Webhook Secret') }}"
                                        value="{{ $paymentGateway['Stripe']['webhook_secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="stripe_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Stripe Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="stripe_webhook_url" name="gateway[Stripe][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('Stripe Webhook URL') }}"
                                        value="{{ url('/webhook/stripe') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[Stripe][status]" id="stripe_gateway"
                                            value="{{ $paymentGateway['Stripe']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Stripe']['status']) && $paymentGateway['Stripe']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_stripe_gateway" aria-label="switch_stripe_gateway">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- Stripe Payment Gateway END --}}

                {{-- Razorpay Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_razorpay"><div class="pg-detail-head"><h5 class="mb-0">Razorpay {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Razorpay Setting') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="razorpay_currency_code"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Razorpay Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[Razorpay][currency_code]" id="razorpay_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="AED">AED</option>
                                        <option value="ALL">ALL</option>
                                        <option value="AMD">AMD</option>
                                        <option value="ARS">ARS</option>
                                        <option value="AUD">AUD</option>
                                        <option value="AWG">AWG</option>
                                        <option value="AZN">AZN</option>
                                        <option value="BAM">BAM</option>
                                        <option value="BBD">BBD</option>
                                        <option value="BDT">BDT</option>
                                        <option value="BGN">BGN</option>
                                        <option value="BHD">BHD</option>
                                        <option value="BIF">BIF</option>
                                        <option value="BMD">BMD</option>
                                        <option value="BND">BND</option>
                                        <option value="BOB">BOB</option>
                                        <option value="BRL">BRL</option>
                                        <option value="BSD">BSD</option>
                                        <option value="BTN">BTN</option>
                                        <option value="BWP">BWP</option>
                                        <option value="BZD">BZD</option>
                                        <option value="CAD">CAD</option>
                                        <option value="CHF">CHF</option>
                                        <option value="CLP">CLP</option>
                                        <option value="CNY">CNY</option>
                                        <option value="COP">COP</option>
                                        <option value="CRC">CRC</option>
                                        <option value="CUP">CUP</option>
                                        <option value="CVE">CVE</option>
                                        <option value="CZK">CZK</option>
                                        <option value="DJF">DJF</option>
                                        <option value="DKK">DKK</option>
                                        <option value="DOP">DOP</option>
                                        <option value="DZD">DZD</option>
                                        <option value="EGP">EGP</option>
                                        <option value="ETB">ETB</option>
                                        <option value="EUR">EUR</option>
                                        <option value="FJD">FJD</option>
                                        <option value="GBP">GBP</option>
                                        <option value="GHS">GHS</option>
                                        <option value="GIP">GIP</option>
                                        <option value="GMD">GMD</option>
                                        <option value="GNF">GNF</option>
                                        <option value="GTQ">GTQ</option>
                                        <option value="GYD">GYD</option>
                                        <option value="HKD">HKD</option>
                                        <option value="HNL">HNL</option>
                                        <option value="HRK">HRK</option>
                                        <option value="HTG">HTG</option>
                                        <option value="HUF">HUF</option>
                                        <option value="IDR">IDR</option>
                                        <option value="ILS">ILS</option>
                                        <option value="INR">INR</option>
                                        <option value="IQD">IQD</option>
                                        <option value="ISK">ISK</option>
                                        <option value="JMD">JMD</option>
                                        <option value="JOD">JOD</option>
                                        <option value="JPY">JPY</option>
                                        <option value="KES">KES</option>
                                        <option value="KGS">KGS</option>
                                        <option value="KHR">KHR</option>
                                        <option value="KMF">KMF</option>
                                        <option value="KRW">KRW</option>
                                        <option value="KWD">KWD</option>
                                        <option value="KYD">KYD</option>
                                        <option value="KZT">KZT</option>
                                        <option value="LAK">LAK</option>
                                        <option value="LKR">LKR</option>
                                        <option value="LRD">LRD</option>
                                        <option value="LSL">LSL</option>
                                        <option value="MAD">MAD</option>
                                        <option value="MDL">MDL</option>
                                        <option value="MGA">MGA</option>
                                        <option value="MKD">MKD</option>
                                        <option value="MMK">MMK</option>
                                        <option value="MNT">MNT</option>
                                        <option value="MOP">MOP</option>
                                        <option value="MUR">MUR</option>
                                        <option value="MVR">MVR</option>
                                        <option value="MWK">MWK</option>
                                        <option value="MXN">MXN</option>
                                        <option value="MYR">MYR</option>
                                        <option value="MZN">MZN</option>
                                        <option value="NAD">NAD</option>
                                        <option value="NGN">NGN</option>
                                        <option value="NIO">NIO</option>
                                        <option value="NOK">NOK</option>
                                        <option value="NPR">NPR</option>
                                        <option value="NZD">NZD</option>
                                        <option value="OMR">OMR</option>
                                        <option value="PEN">PEN</option>
                                        <option value="PGK">PGK</option>
                                        <option value="PHP">PHP</option>
                                        <option value="PKR">PKR</option>
                                        <option value="PLN">PLN</option>
                                        <option value="PYG">PYG</option>
                                        <option value="QAR">QAR</option>
                                        <option value="RON">RON</option>
                                        <option value="RSD">RSD</option>
                                        <option value="RUB">RUB</option>
                                        <option value="RWF">RWF</option>
                                        <option value="SAR">SAR</option>
                                        <option value="SCR">SCR</option>
                                        <option value="SEK">SEK</option>
                                        <option value="SGD">SGD</option>
                                        <option value="SLL">SLL</option>
                                        <option value="SOS">SOS</option>
                                        <option value="SSP">SSP</option>
                                        <option value="SVC">SVC</option>
                                        <option value="SZL">SZL</option>
                                        <option value="THB">THB</option>
                                        <option value="TND">TND</option>
                                        <option value="TRY">TRY</option>
                                        <option value="TTD">TTD</option>
                                        <option value="TWD">TWD</option>
                                        <option value="TZS">TZS</option>
                                        <option value="UAH">UAH</option>
                                        <option value="UGX">UGX</option>
                                        <option value="USD">USD</option>
                                        <option value="UYU">UYU</option>
                                        <option value="UZS">UZS</option>
                                        <option value="VND">VND</option>
                                        <option value="VUV">VUV</option>
                                        <option value="XAF">XAF</option>
                                        <option value="XCD">XCD</option>
                                        <option value="XOF">XOF</option>
                                        <option value="XPF">XPF</option>
                                        <option value="YER">YER</option>
                                        <option value="ZAR">ZAR</option>
                                        <option value="ZMW">ZMW</option>

                                    </select>
                                </div>

                                <label for="razorpay_secret_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Razorpay Secret key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="razorpay_secret_key" name="gateway[Razorpay][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('Razorpay Secret key') }}"
                                        value="{{ $paymentGateway['Razorpay']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="razorpay_public_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Razorpay Public key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="razorpay_public_key" name="gateway[Razorpay][api_key]" type="password"
                                        class="form-control" placeholder="{{ __('Razorpay Publishable key') }}"
                                        value="{{ $paymentGateway['Razorpay']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="razorpay_webhook_secret"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Razorpay Webhook Secret') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="razorpay_webhook_secret" name="gateway[Razorpay][webhook_secret_key]"
                                        type="password" class="form-control"
                                        placeholder="{{ __('Razorpay Webhook Secret') }}"
                                        value="{{ $paymentGateway['Razorpay']['webhook_secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="razorpay_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Razorpay Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="razorpay_webhook_url" name="gateway[Razorpay][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('Razorpay Webhook URL') }}"
                                        value="{{ url('/webhook/razorpay') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[Razorpay][status]" id="razorpay_gateway"
                                            value="{{ $paymentGateway['Razorpay']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Razorpay']['status']) && $paymentGateway['Razorpay']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_razorpay_gateway" aria-label="switch_razorpay_gateway">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- Razorpay Payment Gateway END --}}

                {{-- Paystack Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_paystack"><div class="pg-detail-head"><h5 class="mb-0">Paystack {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Paystack Setting') }}</h6>
                            </div>
                            <div class="form-group row mt-3">
                                <label for="paystack_currency_code"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paystack Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[Paystack][currency_code]" id="paystack_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="USD">USD</option>
                                        <option value="GHS">GHS</option>
                                        <option value="KES">KES</option>
                                        <option value="NGN">NGN</option>
                                        <option value="ZAR">ZAR</option>
                                    </select>
                                </div>

                                <label for="paystack_secret_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paystack Secret key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paystack_secret_key" name="gateway[Paystack][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('Paystack Secret key') }}"
                                        value="{{ $paymentGateway['Paystack']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paystack_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paystack Public key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paystack_publishable_key" name="gateway[Paystack][api_key]" type="password"
                                        class="form-control" placeholder="{{ __('Paystack Public key') }}"
                                        value="{{ $paymentGateway['Paystack']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paystack_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paystack Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paystack_webhook_url" name="gateway[Paystack][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('Paystack Webhook URL') }}"
                                        value="{{ url('/webhook/paystack') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[Paystack][status]" id="paystack_gateway"
                                            value="{{ $paymentGateway['Paystack']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Paystack']['status']) && $paymentGateway['Paystack']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_paystack_gateway" aria-label="switch_paystack_gateway">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- Paystack Payment Gateway END --}}

                {{-- PaysTabs Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_paytabs"><div class="pg-detail-head"><h5 class="mb-0">PayTabs {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Paytabs Setting') }}</h6>
                            </div>
                            <div class="form-group row mt-3">
                                <label for="paytabs_currency_code"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[Paytabs][currency_code]" id="paytabs_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="SAR">SAR</option>
                                        <option value="AED">AED</option>
                                        <option value="BHD">BHD</option>
                                        <option value="EGP">EGP</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="HKD">HKD</option>
                                        <option value="IDR">IDR</option>
                                        <option value="INR">INR</option>
                                        <option value="IQD">IQD</option>
                                        <option value="JOD">JOD</option>
                                        <option value="JPY">JPY</option>
                                        <option value="KWD">KWD</option>
                                        <option value="MAD">MAD</option>
                                        <option value="OMR">OMR</option>
                                        <option value="PKR">PKR</option>
                                        <option value="QAR">QAR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>

                                <label for="paytabs_region"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Region') }}</label>
                                <div class="col-sm-12 mt-2">
                                    @php($paytabsRegion = $paymentGateway['Paytabs']['payment_mode'] ?? 'global')
                                    <select name="gateway[Paytabs][payment_mode]" id="paytabs_region"
                                        class="select2 form-select form-control">
                                        <option value="global" {{ $paytabsRegion === 'global' ? 'selected' : '' }}>Global</option>
                                        <option value="uae" {{ $paytabsRegion === 'uae' ? 'selected' : '' }}>UAE (secure.paytabs.com)</option>
                                        <option value="ksa" {{ $paytabsRegion === 'ksa' ? 'selected' : '' }}>Saudi Arabia (secure.paytabs.sa)</option>
                                        <option value="egypt" {{ $paytabsRegion === 'egypt' ? 'selected' : '' }}>Egypt</option>
                                        <option value="oman" {{ $paytabsRegion === 'oman' ? 'selected' : '' }}>Oman</option>
                                        <option value="jordan" {{ $paytabsRegion === 'jordan' ? 'selected' : '' }}>Jordan</option>
                                        <option value="kuwait" {{ $paytabsRegion === 'kuwait' ? 'selected' : '' }}>Kuwait</option>
                                        <option value="iraq" {{ $paytabsRegion === 'iraq' ? 'selected' : '' }}>Iraq</option>
                                    </select>
                                </div>

                                <label for="paytabs_secret_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Secret key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paytabs_secret_key" name="gateway[Paytabs][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('Paytabs Secret key') }}"
                                        value="{{ $paymentGateway['Paytabs']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paytabs_profile_id"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Profile ID') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paytabs_profile_id" name="gateway[Paytabs][additional_data_1]"
                                        type="text" class="form-control" placeholder="{{ __('Paytabs Profile ID') }}"
                                        value="{{ $paymentGateway['Paytabs']['additional_data_1'] ?? '' }}">
                                </div>

                                <label for="paytabs_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Public key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paytabs_publishable_key" name="gateway[Paytabs][api_key]" type="password"
                                        class="form-control" placeholder="{{ __('Paytabs Public key') }}"
                                        value="{{ $paymentGateway['Paytabs']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paytabs_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paytabs_webhook_url" name="gateway[Paytabs][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('Paytabs Webhook URL') }}"
                                        value="{{ url('/webhook/paytabs') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[Paytabs][status]" id="paytabs_gateway"
                                            value="{{ $paymentGateway['Paytabs']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Paytabs']['status']) && $paymentGateway['Paytabs']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_paytabs_gateway" aria-label="switch_paytabs_gateway">
                                    </div>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Is Live') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[Paytabs][additional_data_2]"
                                            id="paytabs_is_live"
                                            value="{{ $paymentGateway['Paytabs']['additional_data_2'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Paytabs']['additional_data_2']) && $paymentGateway['Paytabs']['additional_data_2'] == '1' ? 'checked' : '' }}
                                            id="switch_paytabs_is_live" aria-label="switch_paytabs_is_live">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- Paytabs Payment Gateway END --}}

                {{-- DPO Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_dpo"><div class="pg-detail-head"><h5 class="mb-0">DPO {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('DPO Setting') }}</h6>
                            </div>
                            <div class="form-group row mt-3">
                                <label for="DPO_currency_code"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('DPO Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[DPO][currency_code]" id="DPO_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="AED">AED</option>
                                        <option value="USD" selected>USD</option>
                                    </select>
                                </div>

                                <label for="DPO_company_token"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('DPO Company Token') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="DPO_company_token" name="gateway[DPO][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('DPO Company Token') }}"
                                        value="{{ $paymentGateway['DPO']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="DPO_service_id"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('DPO Service ID') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="DPO_service_id" name="gateway[DPO][additional_data_1]" type="text"
                                        class="form-control" placeholder="{{ __('DPO Service ID') }}"
                                        value="{{ $paymentGateway['DPO']['additional_data_1'] ?? '' }}">
                                </div>

                                {{-- <label for="paytabs_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('Paytabs Public key') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paytabs_publishable_key" name="gateway[Paytabs][api_key]" type="text"
                                        class="form-control" placeholder="{{ __('Paytabs Public key') }}"
                                        value="{{ $paymentGateway['Paytabs']['api_key'] ?? '' }}">
                                </div> --}}

                                <label for="DPO_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('DPO Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="DPO_webhook_url" name="gateway[DPO][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('DPO Webhook URL') }}"
                                        value="{{ url('/webhook/dpo') }}" disabled>
                                </div>

                                <label for="dpo_payment_mode"
                                    class="col-sm-12 form-check-label mt-2">{{ __('DPO Payment Mode') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select id="dpo_payment_mode" name="gateway[DPO][payment_mode]"
                                        class="form-control">
                                        <option value="UAT"
                                            {{ isset($paymentGateway['DPO']['payment_mode']) && $paymentGateway['DPO']['payment_mode'] == 'UAT' ? 'selected' : '' }}>
                                            UAT</option>
                                        <option value="PROD"
                                            {{ isset($paymentGateway['DPO']['payment_mode']) && $paymentGateway['DPO']['payment_mode'] == 'PROD' ? 'selected' : '' }}>
                                            PROD</option>
                                    </select>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[DPO][status]" id="DPO_gateway"
                                            value="{{ $paymentGateway['DPO']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['DPO']['status']) && $paymentGateway['DPO']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_DPO_gateway" aria-label="switch_DPO_gateway">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- DPO Payment Gateway END --}}

                {{-- phonePe Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_phonepe"><div class="pg-detail-head"><h5 class="mb-0">PhonePe {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('PhonePe Setting') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="paystack_secret_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Client Secret') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paystack_secret_key" name="gateway[PhonePe][secret_key]" type="password"
                                        class="form-control phonepe-required"
                                        placeholder="{{ __('PhonePe Client Secret') }}"
                                        value="{{ $paymentGateway['PhonePe']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paystack_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Client ID') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paystack_publishable_key" name="gateway[PhonePe][api_key]" type="password"
                                        class="form-control phonepe-required" placeholder="{{ __('PhonePe Client ID') }}"
                                        value="{{ $paymentGateway['PhonePe']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>
                                <label for="paystack_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Client Version') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paystack_publishable_key" name="gateway[PhonePe][additional_data_1]"
                                        type="text" class="form-control phonepe-required"
                                        placeholder="{{ __('PhonePe Client Version') }}"
                                        value="{{ $paymentGateway['PhonePe']['additional_data_1'] ?? '' }}">
                                </div>
                                <label for="paystack_publishable_key"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Merchant ID') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paystack_publishable_key" name="gateway[PhonePe][additional_data_2]"
                                        type="text" class="form-control phonepe-required"
                                        placeholder="{{ __('PhonePe Merchant ID') }}"
                                        value="{{ $paymentGateway['PhonePe']['additional_data_2'] ?? '' }}">
                                </div>
                                <label for="phonepe_username"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Username') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="phonepe_username_key" name="gateway[PhonePe][username]" type="password"
                                        class="form-control phonepe-required" placeholder="{{ __('PhonePe Username') }}"
                                        value="{{ $paymentGateway['PhonePe']['username'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>
                                <label for="phonepe_password"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Password') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paystack_publishable_key" name="gateway[PhonePe][password]" type="password"
                                        class="form-control phonepe-required" placeholder="{{ __('PhonePe Password') }}"
                                        value="{{ $paymentGateway['PhonePe']['password'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="phonepe_mode"
                                    class="col-sm-12 form-check-label mt-2">{{ __('PhonePe Payment Mode') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select id="phonepe_mode" name="gateway[PhonePe][payment_mode]"
                                        class="form-control phonepe-required">
                                        <option value="UAT"
                                            {{ isset($paymentGateway['PhonePe']['payment_mode']) && $paymentGateway['PhonePe']['payment_mode'] == 'UAT' ? 'selected' : '' }}>
                                            UAT</option>
                                        <option value="PROD"
                                            {{ isset($paymentGateway['PhonePe']['payment_mode']) && $paymentGateway['PhonePe']['payment_mode'] == 'PROD' ? 'selected' : '' }}>
                                            PROD</option>
                                    </select>
                                </div>

                                <label for="paystack_webhook_url"
                                    class="col-sm-12 form-check-label  mt-2">{{ __('PhonePe Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="phonePe_webhook_url" name="gateway[PhonePe][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('PhonePe Webhook URL') }}"
                                        value="{{ url('/webhook/phonePe') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label  mt-2"
                                    id='lbl_stripe'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12  mt-2">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="gateway[PhonePe][status]" id="paystack_gateway"
                                            value="{{ $paymentGateway['PhonePe']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['PhonePe']['status']) && $paymentGateway['PhonePe']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_phonepe_gateway" aria-label="switch_paystack_gateway">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- phonePe Payment Gateway END --}}
{{-- Flutterwave START --}}
<div class="pg-detail" id="detail_gw_flutterwave"><div class="pg-detail-head"><h5 class="mb-0">Flutterwave {{ __('Settings') }}</h5></div><div class="pg-detail-body">

                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Flutterwave Setting') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="flutterwave_currency_code" class="col-sm-12 form-check-label mt-2">
                                    {{ __('Flutterwave Currency') }}
                                </label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[flutterwave][currency_code]" id="flutterwave_currency_code"
                                        class="select2 form-select form-control-sm">
                                        <option value="NGN">NGN</option>
                                        <option value="USD">USD</option>
                                        <option value="GHS">GHS</option>
                                        <option value="KES">KES</option>
                                        <option value="UGX">UGX</option>
                                        <option value="TZS">TZS</option>
                                        <option value="ZAR">ZAR</option>
                                        <option value="XOF">XOF</option>
                                    </select>
                                </div>

                                <label for="flutterwave_secret_key" class="col-sm-12 form-check-label mt-2">
                                    {{ __('Flutterwave Secret Key') }}
                                </label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="flutterwave_secret_key" name="gateway[flutterwave][secret_key]"
                                        type="password" class="form-control"
                                        placeholder="{{ __('Flutterwave Secret Key') }}"
                                        value="{{ $paymentGateway['flutterwave']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="flutterwave_public_key" class="col-sm-12 form-check-label mt-2">
                                    {{ __('Flutterwave Public Key') }}
                                </label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="flutterwave_public_key" name="gateway[flutterwave][api_key]"
                                        type="password" class="form-control"
                                        placeholder="{{ __('Flutterwave Public Key') }}"
                                        value="{{ $paymentGateway['flutterwave']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="flutterwave_encryption_key" class="col-sm-12 form-check-label mt-2">
                                    {{ __('Flutterwave Encryption Key') }}
                                </label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="flutterwave_encryption_key" name="gateway[flutterwave][webhook_secret_key]"
                                        type="password" class="form-control"
                                        placeholder="{{ __('Flutterwave Encryption Key') }}"
                                        value="{{ $paymentGateway['flutterwave']['webhook_secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="flutterwave_webhook_url" class="col-sm-12 form-check-label mt-2">
                                    {{ __('Flutterwave Webhook URL') }}
                                </label>
                                <div class="col-sm-12 mt-2">
                                    <input id="flutterwave_webhook_url" name="gateway[flutterwave][webhook_url]"
                                        type="text" class="form-control"
                                        placeholder="{{ __('Flutterwave Webhook URL') }}"
                                        value="{{ url('/webhook/flutterwave') }}" disabled>
                                </div>

                                <label class="col-sm-12 form-check-label mt-2"
                                    id='lbl_flutterwave'>{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12 mt-2">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="gateway[flutterwave][status]"
                                            id="flutterwave_gateway"
                                            value="{{ $paymentGateway['flutterwave']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['flutterwave']['status']) && $paymentGateway['flutterwave']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_flutterwave_gateway" aria-label="switch_flutterwave_gateway">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                </div></div>
{{-- Flutterwave END --}}
{{-- paypal Payment Gateway START --}}
<div class="pg-detail" id="detail_gw_paypal"><div class="pg-detail-head"><h5 class="mb-0">PayPal {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('PayPal Setting') }}</h6>
                            </div>
                            <div class="form-group row mt-3">

                                <label for="paypal_currency_code"
                                    class="col-sm-12 form-check-label mt-2">{{ __('PayPal Currency Symbol') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select name="gateway[Paypal][currency_code]" id="paypal_currency_code"
                                        class="select2 form-select form-control">
                                        <option value="USD"
                                            {{ ($paymentGateway['Paypal']['currency_code'] ?? '') == 'USD' ? 'selected' : '' }}>
                                            USD</option>
                                        <option value="EUR"
                                            {{ ($paymentGateway['Paypal']['currency_code'] ?? '') == 'EUR' ? 'selected' : '' }}>
                                            EUR</option>
                                        <option value="GBP"
                                            {{ ($paymentGateway['Paypal']['currency_code'] ?? '') == 'GBP' ? 'selected' : '' }}>
                                            GBP</option>
                                        <option value="AUD"
                                            {{ ($paymentGateway['Paypal']['currency_code'] ?? '') == 'AUD' ? 'selected' : '' }}>
                                            AUD</option>
                                        <option value="CAD"
                                            {{ ($paymentGateway['Paypal']['currency_code'] ?? '') == 'CAD' ? 'selected' : '' }}>
                                            CAD</option>
                                    </select>
                                </div>

                                <label for="paypal_client_id"
                                    class="col-sm-12 form-check-label mt-2">{{ __('PayPal Client ID') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paypal_client_id" name="gateway[Paypal][api_key]" type="password"
                                        class="form-control" placeholder="{{ __('PayPal Client ID') }}"
                                        value="{{ $paymentGateway['Paypal']['api_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paypal_secret_key"
                                    class="col-sm-12 form-check-label mt-2">{{ __('PayPal Secret Key') }}</label>
                                <div class="col-sm-12 mt-2 position-relative has-icon-right">
                                    <input id="paypal_secret_key" name="gateway[Paypal][secret_key]" type="password"
                                        class="form-control" placeholder="{{ __('PayPal Secret Key') }}"
                                        value="{{ $paymentGateway['Paypal']['secret_key'] ?? '' }}">
                                    <div class="form-control-icon lh-1 top-0 mt-2">
                                        <i class="bi bi-eye toggle-password"></i>
                                    </div>
                                </div>

                                <label for="paypal_webhook_url"
                                    class="col-sm-12 form-check-label mt-2">{{ __('PayPal Webhook URL') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <input id="paypal_webhook_url" name="gateway[Paypal][webhook_url]" type="text"
                                        class="form-control" placeholder="{{ __('PayPal Webhook URL') }}"
                                        value="{{ url('/webhook/paypal') }}" disabled>
                                </div>

                                <label for="phonepe_mode"
                                    class="col-sm-12 form-check-label mt-2">{{ __('Paypal Payment Mode') }}</label>
                                <div class="col-sm-12 mt-2">
                                    <select id="phonepe_mode" name="gateway[Paypal][payment_mode]"
                                        class="form-control phonepe-required">
                                        <option value="UAT"
                                            {{ isset($paymentGateway['Paypal']['payment_mode']) && $paymentGateway['Paypal']['payment_mode'] == 'UAT' ? 'selected' : '' }}>
                                            UAT</option>
                                        <option value="PROD"
                                            {{ isset($paymentGateway['Paypal']['payment_mode']) && $paymentGateway['Paypal']['payment_mode'] == 'PROD' ? 'selected' : '' }}>
                                            PROD</option>
                                    </select>
                                </div>

                                <label class="col-sm-12 form-check-label mt-2">{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12 mt-2">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="gateway[Paypal][status]" id="paypal_gateway"
                                            value="{{ $paymentGateway['Paypal']['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch" type="checkbox"
                                            role="switch" name='op'
                                            {{ isset($paymentGateway['Paypal']['status']) && $paymentGateway['Paypal']['status'] == '1' ? 'checked' : '' }}
                                            id="switch_paypal_gateway" aria-label="switch_paypal_gateway">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div></div>
{{-- paypal Payment Gateway END --}}
{{-- Installed plugin gateways (type=payment) — rendered from each plugin's
     settings_fields, no per-plugin markup needed here --}}
@foreach ($pluginGateways ?? [] as $plugin)
<div class="pg-detail" id="detail_gw_{{ strtolower($plugin->plugin_slug) }}"><div class="pg-detail-head" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;"><h5 class="mb-0">{{ $plugin->name }} {{ __('Settings') }}</h5>
    <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;line-height:1.6;white-space:nowrap;background:#e0f2fe;color:#0369a1;">{{ __('Plugin') }}</span>
    @if ($plugin->is_enabled)
        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;line-height:1.6;white-space:nowrap;background:#dcfce7;color:#15803d;">{{ __('Enabled') }}</span>
    @else
        <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;line-height:1.6;white-space:nowrap;background:#f1f5f9;color:#64748b;">{{ __('Disabled') }}</span>
    @endif
</div><div class="pg-detail-body">
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ $plugin->name }} {{ __('Setting') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                @foreach ($plugin->settings_fields as $field)
                                    <label for="{{ $plugin->plugin_slug }}_{{ $field['column'] }}"
                                        class="col-sm-12 form-check-label mt-2">{{ $field['label'] }}</label>
                                    <div class="col-sm-12 mt-2">
                                        @if (($field['type'] ?? 'text') === 'select')
                                            <select name="gateway[{{ $plugin->plugin_slug }}][{{ $field['column'] }}]"
                                                id="{{ $plugin->plugin_slug }}_{{ $field['column'] }}"
                                                class="select2 form-select form-control">
                                                @foreach ($field['options'] ?? [] as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ ($paymentGateway[$plugin->plugin_slug][$field['column']] ?? '') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif (($field['type'] ?? 'text') === 'password')
                                            <div class="position-relative has-icon-right">
                                                <input id="{{ $plugin->plugin_slug }}_{{ $field['column'] }}"
                                                    name="gateway[{{ $plugin->plugin_slug }}][{{ $field['column'] }}]"
                                                    type="password"
                                                    class="form-control" placeholder="{{ $field['label'] }}"
                                                    value="{{ $paymentGateway[$plugin->plugin_slug][$field['column']] ?? '' }}">
                                                <div class="form-control-icon lh-1 top-0 mt-2">
                                                    <i class="bi bi-eye toggle-password"></i>
                                                </div>
                                            </div>
                                        @else
                                            <input id="{{ $plugin->plugin_slug }}_{{ $field['column'] }}"
                                                name="gateway[{{ $plugin->plugin_slug }}][{{ $field['column'] }}]"
                                                type="text"
                                                class="form-control" placeholder="{{ $field['label'] }}"
                                                value="{{ $paymentGateway[$plugin->plugin_slug][$field['column']] ?? '' }}">
                                        @endif
                                    </div>
                                @endforeach

                                @if (!empty($plugin->webhook_url))
                                    <label for="{{ $plugin->plugin_slug }}_webhook_url"
                                        class="col-sm-12 form-check-label mt-2">{{ __('Webhook URL') }}</label>
                                    <div class="col-sm-12 mt-2">
                                        <input id="{{ $plugin->plugin_slug }}_webhook_url" type="text"
                                            class="form-control" value="{{ $plugin->webhook_url }}" disabled>
                                    </div>
                                @endif

                                <label class="col-sm-12 form-check-label mt-2">{{ __('Status') }}</label>
                                <div class="col-sm-2 col-md-12 col-xs-12 mt-2">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="gateway[{{ $plugin->plugin_slug }}][status]"
                                            value="{{ $paymentGateway[$plugin->plugin_slug]['status'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch plugin-gateway-switch" type="checkbox"
                                            role="switch" name="op"
                                            data-plugin-slug="{{ $plugin->plugin_slug }}"
                                            data-plugin-enabled="{{ $plugin->is_enabled ? 1 : 0 }}"
                                            data-plugin-toggle-url="{{ route('plugin-manager.toggle', $plugin->plugin_slug) }}"
                                            {{ isset($paymentGateway[$plugin->plugin_slug]['status']) && $paymentGateway[$plugin->plugin_slug]['status'] == '1' ? 'checked' : '' }}
                                            aria-label="switch_{{ strtolower($plugin->plugin_slug) }}_gateway">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
</div></div>
@endforeach
{{-- Bank START --}}
<div class="pg-detail" id="detail_gw_bank"><div class="pg-detail-head"><h5 class="mb-0">Bank Transfer {{ __('Settings') }}</h5></div><div class="pg-detail-body">
                {{-- Bank Account Details --}}
                <div class="col-md-6 mt-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Manage Bank Account Details') }}</h6>
                            </div>

                            <div class="form-group">
                                <label for="account_holder_name"
                                    class="form-label">{{ __('Account Holder Name') }}</label>
                                <input class="form-control" type="text" name="bank[account_holder_name]"
                                    id="account_holder_name" value="{{ $settings['account_holder_name'] ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label for="bank_name" class="form-label">{{ __('Bank Name') }}</label>
                                <input class="form-control" type="text" name="bank[bank_name]" id="bank_name"
                                    value="{{ $settings['bank_name'] ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label for="account_number" class="form-label">{{ __('Account Number') }}</label>
                                <input class="form-control" type="number" name="bank[account_number]"
                                    id="account_number" value="{{ $settings['account_number'] ?? '' }}">
                            </div>

                            <div class="form-group">
                                <label for="ifsc_swift_code" class="form-label">{{ __('IFSC/SWIFT Code') }}</label>
                                <input class="form-control" type="text" name="bank[ifsc_swift_code]"
                                    id="ifsc_swift_code" value="{{ $settings['ifsc_swift_code'] ?? '' }}">
                            </div>

                            <label class="form-check-label mt-2">{{ __('Status') }}</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="bank[bank_transfer_status]" value="0">
                                <input class="form-check-input" type="checkbox" name="bank[bank_transfer_status]"
                                    value="1"
                                    {{ isset($settings['bank_transfer_status']) && $settings['bank_transfer_status'] == '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            <div class="col-12 d-flex justify-content-end">
                </div></div>
{{-- Bank END --}}
</div></div></div>
                        <div class="pg-card-footer">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </div></div></div>
            </div>
        </form>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        $('#stripe_currency_code').val("{{ $paymentGateway['Stripe']['currency_code'] ?? '' }}").trigger("change");
        $('#switch_stripe_gateway').val("{{ $paymentGateway['Stripe']['status'] ?? false }}").trigger("change");

        $('#razorpay_currency_code').val("{{ $paymentGateway['Razorpay']['currency_code'] ?? '' }}").trigger("change");
        $('#switch_razorpay_gateway').val("{{ $paymentGateway['Stripe']['status'] ?? false }}").trigger("change");

        $('#paystack_currency_code').val("{{ $paymentGateway['Paystack']['currency_code'] ?? '' }}").trigger("change");
        $('#switch_paystack_gateway').val("{{ $paymentGateway['Stripe']['status'] ?? false }}").trigger("change");
    </script>
    <script>
        $(document).ready(function() {
            function togglePhonePeRequiredFields() {
                if ($('#switch_phonepe_gateway').is(':checked')) {
                    $('.phonepe-required').attr('required', true);
                } else {
                    $('.phonepe-required').removeAttr('required');
                }
            }

            // Initial check on page load
            togglePhonePeRequiredFields();

            // On switch toggle
            $('#switch_phonepe_gateway').on('change', function() {
                togglePhonePeRequiredFields();
            });

            // Plugin-backed gateways must not stay on while their plugin is disabled.
            // Returns a Promise resolving once the user has enabled the plugin or accepted turning the gateway off.
            function resolveDisabledPluginSwitch($switch) {
                var $hidden = $switch.siblings('input[type="hidden"]');
                $switch.prop('checked', false);
                $hidden.val(0);

                return Swal.fire({
                    icon: 'warning',
                    title: "{{ __('Plugin Disabled') }}",
                    text: "{{ __('This payment gateway is provided by a plugin that is currently disabled. Enable the plugin first to activate this gateway.') }}",
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Enable Plugin') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }
                    return $.ajax({
                        url: $switch.data('plugin-toggle-url'),
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}' }
                    }).then(function(response) {
                        var pluginNowEnabled = !!(response && response.data && response.data.is_enabled);
                        if (pluginNowEnabled) {
                            $switch.data('plugin-enabled', 1);
                            $switch.prop('checked', true);
                            $hidden.val(1);
                            showSuccessToast(response.message || "{{ __('Plugin enabled successfully') }}");
                        } else {
                            showErrorToast("{{ __('Plugin could not be enabled') }}");
                        }
                    }, function(xhr) {
                        showErrorToast((xhr.responseJSON && xhr.responseJSON.message) || "{{ __('Something went wrong') }}");
                    });
                });
            }

            $('.plugin-gateway-switch').on('change', function() {
                var $switch = $(this);
                var isEnabled = $switch.data('plugin-enabled') == 1;
                if ($switch.is(':checked') && !isEnabled) {
                    resolveDisabledPluginSwitch($switch);
                }
            });

            // Also catch gateways already saved as "on" whose plugin got disabled afterwards —
            // ask the same enable/turn-off question when the settings form is saved.
            var $paymentGatewayForm = $('.plugin-gateway-switch').closest('form');
            $paymentGatewayForm.on('submit', function(e) {
                var $offending = $('.plugin-gateway-switch').filter(function() {
                    return $(this).is(':checked') && $(this).data('plugin-enabled') != 1;
                });
                if (!$offending.length) {
                    return;
                }
                e.preventDefault();
                var chain = Promise.resolve();
                $offending.each(function() {
                    var $switch = $(this);
                    chain = chain.then(function() {
                        return resolveDisabledPluginSwitch($switch);
                    });
                });
                chain.then(function() {
                    $paymentGatewayForm.off('submit').trigger('submit');
                });
            });

            // Warn about unsaved changes: an inline banner immediately, plus a native
            // confirm if the admin navigates away / closes the tab without saving.
            // Reverting every field back to its original value clears the warning again.
            var formIsDirty = false;
            var formIsSubmitting = false;
            var $pgUnsavedBanner = $('#pgUnsavedBanner');
            var pgInitialFormState = $paymentGatewayForm.serialize();
            $paymentGatewayForm.find('input, select, textarea').on('change input', function() {
                var isNowDirty = $paymentGatewayForm.serialize() !== pgInitialFormState;
                if (isNowDirty && !formIsDirty) {
                    showWarningToast("{{ __('You have unsaved changes. Click Save to save the changes.') }}");
                }
                formIsDirty = isNowDirty;
                $pgUnsavedBanner.toggleClass('d-none', !isNowDirty);
            });
            $paymentGatewayForm.on('submit', function() {
                formIsSubmitting = true;
            });
            $(window).on('beforeunload', function(e) {
                if (formIsDirty && !formIsSubmitting) {
                    var message = "{{ __('You have unsaved changes. Click Save to save the changes.') }}";
                    e.preventDefault();
                    e.returnValue = message;
                    return message;
                }
            });

            window.pgReloadAfterSave = function() {
                formIsDirty = false;
                $pgUnsavedBanner.addClass('d-none');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            };
        });
    </script>
@endsection
