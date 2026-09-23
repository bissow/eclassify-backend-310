<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ $favicon ?? url('assets/images/logo/logo.png') }}" type="image/x-icon">
    {{-- Toastify --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/toastify-js/toastify.css') }}">
    <title>{{ __('Forgot Password') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/main/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/extensions/toastify-js/toastify.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/parsley.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/pages/parsley.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/custom/function.js') }}"></script>
    <style>
        :root {
            --bs-primary: {{ $theme_color }} !important;
        }
    </style>
</head>
<body>
<div id="auth" class="login_bg" style="background-image: url('{{$login_bg_image??''}}');">
    <img src="{{$login_bg_image ?? ''}}" data-custom-image="{{asset('assets/images/bg/login.jpg')}}" alt="" style="display: none" id="bg_image">
    <div class="justify-content-md-end justify-content-sm-center login-box d-flex align-items-center">
        <div class="col-lg-4 col-12 card" id="auth-box">
            <div class="auth-logo mb-5 d-block">
                <img id="company_logo" src="{{ $company_logo ?? '' }}" data-custom-image="{{asset('assets/images/logo/sidebar_logo.png')}}" alt="Logo">
            </div>
            <div class="center mtop-75">
                <div class="login_heading mb-4">
                    <h3>{{ __('Forgot Password') }}</h3>
                    <p>{{ __('Reset password works only on a valid admin email. OTP will be sent to the valid admin email, so SMTP details must be configured before using this option.') }}</p>
                </div>

                <div id="login-reset-email-section" class="{{ empty($otpResetState['sent']) ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('change-password.send-reset-otp') }}" id="send-reset-otp-form">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="admin_reset_email" class="form-label">{{ __('Admin Email') }}</label>
                            <input type="email" id="admin_reset_email" name="email" class="form-control" value="{{ $otpResetState['email'] ?? '' }}" placeholder="{{ __('Admin Email') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="send-reset-otp-btn">{{ __('Send OTP') }}</button>
                    </form>
                </div>

                <div id="login-reset-otp-section" class="mt-4 {{ !empty($otpResetState['sent']) && empty($otpResetState['verified']) ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('change-password.verify-reset-otp') }}" id="verify-reset-otp-form">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="admin_reset_otp" class="form-label">{{ __('OTP') }}</label>
                            <input type="text" id="admin_reset_otp" name="otp" class="form-control" maxlength="6" placeholder="{{ __('Enter OTP') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="verify-reset-otp-btn">{{ __('Verify OTP') }}</button>
                    </form>
                </div>

                <div id="login-reset-password-section" class="mt-4 {{ !empty($otpResetState['verified']) ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('change-password.update-with-otp') }}" id="update-reset-password-form" data-parsley-validate>
                        @csrf
                        <div class="form-group position-relative has-icon-right mb-3">
                            <label for="reset_new_password" class="form-label">{{ __('New Password') }}</label>
                            <input type="password" id="reset_new_password" name="new_password" class="form-control" placeholder="{{ __('New Password') }}" data-parsley-minlength="8" data-parsley-uppercase="1" data-parsley-lowercase="1" data-parsley-number="1" data-parsley-special="1" data-parsley-required required>
                            <div class="form-control-icon icon-right">
                                <i class="bi bi-eye login-toggle-password" data-target="#reset_new_password"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-right mb-3">
                            <label for="reset_confirm_password" class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password" id="reset_confirm_password" name="confirm_password" class="form-control" placeholder="{{ __('Confirm Password') }}" data-parsley-equalto="#reset_new_password" required>
                            <div class="form-control-icon icon-right">
                                <i class="bi bi-eye login-toggle-password" data-target="#reset_confirm_password"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="update-reset-password-btn">{{ __('Reset Password') }}</button>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-primary text-decoration-none">{{ __('Back to Login') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function showResetStep(step) {
        if(step == 'password_updated'){
            window.location.href = "{{ route('login') }}";
        }
        $('#login-reset-email-section').toggleClass('d-none', step !== 'email');
        $('#login-reset-otp-section').toggleClass('d-none', step !== 'otp');
        $('#login-reset-password-section').toggleClass('d-none', step !== 'password');
    }

    function toggleLoginPassword(targetSelector, iconElement) {
        let input = $(targetSelector);
        let isPassword = input.attr("type") === "password";
        input.attr("type", isPassword ? "text" : "password");
        $(iconElement).toggleClass("bi-eye bi-eye-slash");
    }

    function submitResetForm(formSelector, buttonSelector, successHandler) {
        let formElement = $(formSelector);
        let submitButtonElement = $(buttonSelector);
        let data = new FormData(formElement[0]);

        formAjaxRequest(
            'POST',
            formElement.attr('action'),
            data,
            formElement,
            submitButtonElement,
            function (response) {
                if (typeof successHandler === 'function') {
                    successHandler(response);
                }
            },
        );
    }

    $(document).on('click', '.login-toggle-password', function () {
        toggleLoginPassword($(this).data('target'), this);
    });

    $('#send-reset-otp-form').on('submit', function (e) {
        e.preventDefault();
        submitResetForm('#send-reset-otp-form', '#send-reset-otp-btn', function () {
            showResetStep('otp');
        });
    });

    $('#verify-reset-otp-form').on('submit', function (e) {
        e.preventDefault();
        submitResetForm('#verify-reset-otp-form', '#verify-reset-otp-btn', function () {
            showResetStep('password');
        });
    });

    $('#update-reset-password-form').on('submit', function (e) {
        e.preventDefault();
        submitResetForm('#update-reset-password-form', '#update-reset-password-btn', function () {
            setTimeout(function () {
                showResetStep('password_updated');
            }, 1000);
            $('#admin_reset_otp').val('');
            $('#reset_new_password').val('');
            $('#reset_confirm_password').val('');
            $('#send-reset-otp-form')[0].reset();
        });
    });

    $('#bg_image').on('error', function () {
        this.src = $(this).data('custom-image');
        $('.login_bg').css('background-image', "url(" + $(this).data('custom-image') + ")");
    });

    $('#company_logo').on('error', function () {
        this.src = $(this).data('custom-image');
    });
</script>
</body>
</html>
