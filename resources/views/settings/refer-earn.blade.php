@extends('layouts.main')

@section('title')
    {{ __('Refer & Earn Settings') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>{{ __('Refer & Earn Settings') }}</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form class="create-form-without-reset" action="{{ route('settings.store') }}" method="post"
            data-parsley-validate data-success-function="successFunction">
            @csrf
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Refer & Earn Configuration') }}</h6>
                            </div>

                            {{-- Enable/Disable --}}
                            <div class="form-group mt-3">
                                <label class="form-label" for="refer_earn_enabled">{{ __('Enable Refer & Earn') }}</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="refer_earn_enabled" id="refer_earn_enabled" value="1"
                                           {{ ($settings['refer_earn_enabled'] ?? 0) == 1 ? 'checked' : '' }}
                                           onchange="toggleReferSettings()">
                                    <input type="hidden" name="refer_earn_enabled" value="0" id="refer_earn_enabled_hidden">
                                    <label class="form-check-label" for="refer_earn_enabled" id="refer-enabled-label">
                                        {{ ($settings['refer_earn_enabled'] ?? 0) == 1 ? __('Enabled') : __('Disabled') }}
                                    </label>
                                </div>
                            </div>

                            <div id="refer-settings-group" class="row">
                                {{-- Points for Referrer --}}
                                <div class="form-group mt-3 col-lg-6">
                                    <label for="refer_points_for_referrer" class="form-label">{{ __('Points for Referrer') }}</label>
                                    <input type="number" name="refer_points_for_referrer" id="refer_points_for_referrer"
                                           class="form-control" min="0"
                                           value="{{ $settings['refer_points_for_referrer'] ?? 10 }}"
                                           placeholder="{{ __('Points given to user whose code is used') }}">
                                    <small class="text-muted">{{ __('Points awarded to the user whose referral code is used (when referred user purchases a paid plan)') }}</small>
                                </div>

                                {{-- Points for Referred User --}}
                                <div class="form-group mt-3 col-lg-6">
                                    <label for="refer_points_for_referred" class="form-label">{{ __('Points for Referred User') }}</label>
                                    <input type="number" name="refer_points_for_referred" id="refer_points_for_referred"
                                           class="form-control" min="0"
                                           value="{{ $settings['refer_points_for_referred'] ?? 5 }}"
                                           placeholder="{{ __('Points given to user who enters the code') }}">
                                    <small class="text-muted">{{ __('Points awarded to the user who used a referral code (when they purchase a paid plan)') }}</small>
                                </div>
                            </div>

                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Global Points Usage Limits') }}</h6>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i>
                                {{ __('1 Point = 1') }} {{ $settings['currency_symbol'] ?? '$' }}.
                                {{ __('These are global defaults. You can override them per package in the package settings.') }}
                            </div>

                            <div id="refer-limits-group">
                                {{-- Max Points Usage Percentage --}}
                                <div class="form-group mt-3">
                                    <label for="refer_max_points_usage_percentage" class="form-label">{{ __('Max Points Usage Percentage') }} (%)</label>
                                    <input type="number" name="refer_max_points_usage_percentage" id="refer_max_points_usage_percentage"
                                            class="form-control" min="1" max="100"
                                            value="{{ $settings['refer_max_points_usage_percentage'] ?? 10 }}"
                                            placeholder="10">
                                    <small class="text-muted">{{ __('Maximum percentage of the discounted price that can be paid using refer points') }}</small>
                                </div>

                                {{-- Minimum Points to Use --}}
                                <div class="form-group mt-3">
                                    <label for="refer_min_points_to_use" class="form-label">{{ __('Minimum Points to Use') }}</label>
                                    <input type="number" name="refer_min_points_to_use" id="refer_min_points_to_use"
                                            class="form-control" min="1"
                                            value="{{ $settings['refer_min_points_to_use'] ?? 5 }}"
                                            placeholder="5">
                                    <small class="text-muted">{{ __('User must have at least this many points to use them. If user has fewer points, they cannot use points at all.') }}</small>
                                </div>

                                {{-- Maximum Points to Use --}}
                                <div class="form-group mt-3">
                                    <label for="refer_max_points_to_use" class="form-label">{{ __('Maximum Points to Use') }}</label>
                                    <input type="number" name="refer_max_points_to_use" id="refer_max_points_to_use"
                                            class="form-control" min="1"
                                            value="{{ $settings['refer_max_points_to_use'] ?? 50 }}"
                                            placeholder="50">
                                    <small class="text-muted">{{ __('Maximum number of points a user can use per purchase, regardless of available points') }} (1 {{ __('point') }} = 1 {{ $settings['currency_symbol'] ?? '$' }}). {{ __('Only applies when package has no specific refer settings.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-1 mb-1">{{ __('Save Settings') }}</button>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('script')
<script>
    function toggleReferSettings() {
        const enabled = document.getElementById('refer_earn_enabled').checked;
        const label = document.getElementById('refer-enabled-label');
        const settingsGroup = document.getElementById('refer-settings-group');
        const limitsGroup = document.getElementById('refer-limits-group');

        label.textContent = enabled ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}';
        document.getElementById('refer_earn_enabled_hidden').value = enabled ? '1' : '0';

        settingsGroup.style.opacity = enabled ? '1' : '0.5';
        limitsGroup.style.opacity = enabled ? '1' : '0.5';

        const inputs = document.querySelectorAll('#refer-settings-group input, #refer-limits-group input');
        inputs.forEach(input => {
            input.disabled = !enabled;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleReferSettings();
    });

    function successFunction(response) {
        if(!response.error && !response.warning){
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    }
</script>
@endsection
