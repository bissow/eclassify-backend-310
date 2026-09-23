@extends('layouts.main')

@section('title')
    {{ __('OTP Provider Settings') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form class="create-form-without-reset" action="{{ route('settings.store') }}" method="post"
            data-success-function="successFunction" data-parsley-validate>
            @csrf

            <div class="card">
                <div class="card-body">

                    <div class="divider pt-3">
                        <h6 class="divider-text">{{ __('OTP Provider Settings') }}</h6>
                    </div>

                    {{-- OTP Provider --}}
                    <div class="form-group row mt-3">
                        <label class="col-sm-12 form-label-mandatory">
                            {{ __('OTP Service Provider') }}
                        </label>
                        <div class="col-md-6 col-sm-12">
                            <select name="otp_service_provider" id="otp_service_provider" class="form-select">
                                <option value="firebase"
                                    {{ ($settings['otp_service_provider'] ?? '') == 'firebase' ? 'selected' : '' }}>
                                    {{ __('Firebase') }}
                                </option>
                                <option value="twilio"
                                    {{ ($settings['otp_service_provider'] ?? '') == 'twilio' ? 'selected' : '' }}>
                                    {{ __('Twilio') }}
                                </option>
                                <option value="2factor"
                                    {{ ($settings['otp_service_provider'] ?? '') == '2factor' ? 'selected' : '' }}>
                                    {{ __('2Factor') }}
                                </option>
                            </select>
                        </div>
                    </div>

                    {{-- ================= TWILIO SETTINGS ================= --}}
                    <div class="col-12 mt-4 p-4 row bg-light d-none" id="twilio-settings">
                        <h5>{{ __('Twilio SMS Settings') }}</h5>

                        <div class="form-group row mt-3">
                            <div class="col-md-6">
                                <label>{{ __('Account SID') }}</label>
                                <input type="text" name="twilio_account_sid" class="form-control"
                                    value="{{ $settings['twilio_account_sid'] ?? '' }}">
                            </div>

                            <div class="col-md-6">
                                <label>{{ __('Auth Token') }}</label>
                                <input type="text" name="twilio_auth_token" class="form-control"
                                    value="{{ $settings['twilio_auth_token'] ?? '' }}">
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <div class="col-md-6">
                                <label>{{ __('Twilio Phone Number') }}</label>
                                <input type="text" name="twilio_my_phone_number" class="form-control"
                                    value="{{ $settings['twilio_my_phone_number'] ?? '' }}">
                            </div>
                        </div>
                    </div>

                    {{-- ================= 2FACTOR SETTINGS ================= --}}
                    <div class="col-12 mt-4 p-4 row bg-light d-none" id="twofactor-settings">
                        <h5>{{ __('2Factor OTP Settings') }}</h5>

                        <div class="form-group row mt-3">
                            <div class="col-md-6">
                                <label class="form-label mandatory">
                                    {{ __('2Factor API Key') }}
                                </label>
                                <input type="text" name="twofactor_api_key" class="form-control"
                                    placeholder="xxxxxxxxxxxxxxxx" value="{{ $settings['twofactor_api_key'] ?? '' }}">
                            </div>
                        </div>

                        <div class="form-group row mt-3">
                            <div class="col-md-6">
                                <label>{{ __('DLT Sender ID (Optional)') }}</label>
                                <input type="text" name="twofactor_sender_id" class="form-control"
                                    value="{{ $settings['twofactor_sender_id'] ?? '' }}">
                            </div>

                            <div class="col-md-6">
                                <label>{{ __('DLT Template ID (Optional)') }}</label>
                                <input type="text" name="twofactor_template_id" class="form-control"
                                    value="{{ $settings['twofactor_template_id'] ?? '' }}">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">
                    {{ __('Save Settings') }}
                </button>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        function toggleOtpProviders() {
            let provider = document.getElementById('otp_service_provider').value;

            document.getElementById('twilio-settings').classList.add('d-none');
            document.getElementById('twofactor-settings').classList.add('d-none');

            if (provider === 'twilio') {
                document.getElementById('twilio-settings').classList.remove('d-none');
            }

            if (provider === '2factor') {
                document.getElementById('twofactor-settings').classList.remove('d-none');
            }
        }

        document.getElementById('otp_service_provider')
            .addEventListener('change', toggleOtpProviders);

        toggleOtpProviders();

        function successFunction() {
            window.location.reload();
        }
    </script>
@endsection
