@extends('layouts.main')

@section('title')
    {{ __('Change Password') }}
@endsection

@section('content')
    <section class="section row">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div class="divider">
                        <div class="divider-text">
                            <h4 class="mb-0">{{ __('Change Password') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <form method="POST" action="{{ route('change-password.update') }}" class="create-form" data-parsley-validate>
                    @csrf
                    <div class="card-body">
                        <label for="old_password" class="form-label">{{ __('Current Password')}}</label>
                        <div class="form-group position-relative has-icon-right mb-4 mandatory">
                            <input type="password" name="old_password" id="old_password" class="form-control form-control-solid mb-2" placeholder="{{ __('Current Password') }}" required/>
                            <div class="form-control-icon lh-1 top-0 mt-2">
                                <i class="bi bi-eye toggle-password"></i>
                            </div>
                        </div>

                        <label for="new_password" class="form-label">{{ __('New Password')}}</label>
                        <div class="form-group position-relative has-icon-right mb-4 mandatory">
                            <input type="password" name="new_password" id="new_password" class="form-control form-control-solid" placeholder="{{ __('New Password') }}" data-parsley-minlength="8" data-parsley-uppercase="1" data-parsley-lowercase="1" data-parsley-number="1" data-parsley-special="1" data-parsley-required/>
                            <div class="form-control-icon lh-1 top-0 mt-2">
                                <i class="bi bi-eye toggle-password"></i>
                            </div>
                        </div>

                        <label for="confirm_password" class="form-label">{{ __('Confirm Password')}}</label>
                        <div class="form-group position-relative has-icon-right mb-4 mandatory">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-solid" placeholder="{{ __('Confirm Password') }}" data-parsley-equalto="#new_password" required/>
                            <div class="form-control-icon lh-1 top-0 mt-2">
                                <i class="bi bi-eye toggle-password"></i>
                            </div>
                        </div>

                        <div class="form-group text-end">
                            <button type="submit" class="btn btn-primary">{{ __('Change') }}</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div class="divider">
                        <div class="divider-text">
                            <h4 class="mb-0">{{ __('Reset Password') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="alert alert-light-primary mb-4">
                            <strong>{{ __('Note') }}:</strong>
                            {{ __('Reset password works only on a valid admin email. OTP will be sent to the valid admin email, so SMTP details must be configured before using this option.') }}
                        </div>

                        <div id="admin-reset-email-section" class="{{ $otpResetState['sent'] ? 'd-none' : '' }}">
                            <form method="POST" action="{{ route('change-password.send-reset-otp') }}" class="create-form-without-reset" data-parsley-validate data-success-function="handleAdminResetOtpSent">
                            @csrf
                            <label for="admin_reset_email" class="form-label">{{ __('Admin Email') }}</label>
                            <div class="form-group mb-4 mandatory">
                                <input
                                    type="email"
                                    id="admin_reset_email"
                                    name="email"
                                    class="form-control"
                                    value="{{ $otpResetState['email'] }}"
                                    placeholder="{{ __('Admin Email') }}"
                                    required
                                />
                            </div>
                            <div class="form-group text-end">
                                <button type="submit" class="btn btn-outline-primary">{{ __('Send OTP') }}</button>
                            </div>
                            </form>
                        </div>

                        <div id="admin-reset-otp-section" class="{{ $otpResetState['sent'] && ! $otpResetState['verified'] ? '' : 'd-none' }}">
                            <hr>
                            <h6>{{ __('Verify OTP') }}</h6>
                            <p class="text-muted mb-3">{{ __('Enter the OTP sent to your valid admin email to continue with password reset.') }}</p>
                            <form method="POST" action="{{ route('change-password.verify-reset-otp') }}" class="create-form-without-reset" data-parsley-validate data-success-function="handleAdminResetOtpVerified">
                            @csrf
                            <div class="form-group mb-4 mandatory">
                                <label for="admin_reset_otp" class="form-label">{{ __('OTP') }}</label>
                                <input
                                    type="text"
                                    id="admin_reset_otp"
                                    name="otp"
                                    class="form-control"
                                    maxlength="6"
                                    placeholder="{{ __('Enter OTP') }}"
                                    data-parsley-type="digits"
                                    data-parsley-length="[6, 6]"
                                    required
                                />
                            </div>
                            <div class="form-group text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Verify OTP') }}</button>
                            </div>
                            </form>
                        </div>

                        <div id="admin-reset-password-section" class="{{ $otpResetState['verified'] ? '' : 'd-none' }}">
                            <hr>
                            <h6>{{ __('Set New Password') }}</h6>
                            <form method="POST" action="{{ route('change-password.update-with-otp') }}" class="create-form-without-reset" data-parsley-validate data-success-function="handleAdminResetPasswordUpdated">
                            @csrf
                            <label for="reset_new_password" class="form-label">{{ __('New Password')}}</label>
                            <div class="form-group position-relative has-icon-right mb-4 mandatory">
                                <input type="password" name="new_password" id="reset_new_password" class="form-control form-control-solid" placeholder="{{ __('New Password') }}" data-parsley-minlength="8" data-parsley-uppercase="1" data-parsley-lowercase="1" data-parsley-number="1" data-parsley-special="1" data-parsley-required/>
                                <div class="form-control-icon lh-1 top-0 mt-2">
                                    <i class="bi bi-eye toggle-password"></i>
                                </div>
                            </div>

                            <label for="reset_confirm_password" class="form-label">{{ __('Confirm Password')}}</label>
                            <div class="form-group position-relative has-icon-right mb-4 mandatory">
                                <input type="password" id="reset_confirm_password" name="confirm_password" class="form-control form-control-solid" placeholder="{{ __('Confirm Password') }}" data-parsley-equalto="#reset_new_password" required/>
                                <div class="form-control-icon lh-1 top-0 mt-2">
                                    <i class="bi bi-eye toggle-password"></i>
                                </div>
                            </div>

                            <div class="form-group text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Reset Password') }}</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

    @section('js')
    <script>
        function showAdminResetStep(step) {
            document.getElementById('admin-reset-email-section').classList.toggle('d-none', step !== 'email');
            document.getElementById('admin-reset-otp-section').classList.toggle('d-none', step !== 'otp');
            document.getElementById('admin-reset-password-section').classList.toggle('d-none', step !== 'password');
        }

        function handleAdminResetOtpSent() {
            showAdminResetStep('otp');
        }

        function handleAdminResetOtpVerified() {
            showAdminResetStep('password');
        }

        function handleAdminResetPasswordUpdated() {
            showAdminResetStep('email');
            document.getElementById('admin_reset_otp').value = '';
            document.getElementById('admin_reset_email').value = '';
            document.getElementById('reset_new_password').value = '';
            document.getElementById('reset_confirm_password').value = '';
        }
    </script>
@endsection
