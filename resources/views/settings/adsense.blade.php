@extends('layouts.main')

@section('title')
    {{ __('AdSense') . " " . __("Settings") }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form class="create-form-without-reset" action="{{ route('settings.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row d-flex mb-3">

                {{-- AdSense Enable/Disable Card --}}
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Google AdSense Configuration') }}</h6>
                            </div>

                            <div class="form-group row mt-3 align-items-center">
                                <label for="adsense_enabled" class="col-sm-3 col-form-label fw-semibold">
                                    {{ __('Enable AdSense') }}
                                </label>
                                <div class="col-sm-9">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="adsense_enabled" id="adsense_enabled" value="{{ $settings['adsense_enabled'] ?? 0 }}">
                                        <input class="form-check-input switch-input status-switch"
                                               type="checkbox"
                                               role="switch"
                                               id="switch_adsense_enabled"
                                               aria-label="switch_adsense_enabled"
                                               {{ isset($settings['adsense_enabled']) && $settings['adsense_enabled'] == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="switch_adsense_enabled">
                                            {{ isset($settings['adsense_enabled']) && $settings['adsense_enabled'] == '1' ? __('Enabled') : __('Disabled') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- AdSense Mode (Automatic / Manual) - shown only when enabled --}}
                            <div id="adsense_mode_section" class="form-group row mt-3 align-items-center {{ (!isset($settings['adsense_enabled']) || $settings['adsense_enabled'] != '1') ? 'd-none' : '' }}">
                                <label class="col-sm-3 col-form-label fw-semibold">{{ __('AdSense Mode') }}</label>
                                <div class="col-sm-9">
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adsense_mode"
                                                   id="adsense_mode_auto" value="automatic"
                                                   {{ ($settings['adsense_mode'] ?? 'automatic') == 'automatic' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="adsense_mode_auto">
                                                <span class="fw-semibold">{{ __('Automatic') }}</span>
                                                <br>
                                                <small class="text-muted">{{ __('Only Client ID required. Google auto-places ads.') }}</small>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adsense_mode"
                                                   id="adsense_mode_manual" value="manual"
                                                   {{ ($settings['adsense_mode'] ?? '') == 'manual' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="adsense_mode_manual">
                                                <span class="fw-semibold">{{ __('Manual') }}</span>
                                                <br>
                                                <small class="text-muted">{{ __('Manually define ad slots for banner, vertical, and square.') }}</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Client ID Card (always shown when AdSense is enabled) --}}
                <div id="adsense_client_section" class="col-12 mt-4 {{ (!isset($settings['adsense_enabled']) || $settings['adsense_enabled'] != '1') ? 'd-none' : '' }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Publisher Details') }}</h6>
                            </div>

                            <div class="form-group row mt-3">
                                <label for="adsense_client_id" class="col-sm-3 col-form-label fw-semibold">
                                    {{ __('Client ID') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_client_id"
                                           name="adsense_client_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="{{ __('e.g. ca-pub-0000000000000000') }}"
                                           value="{{ $settings['adsense_client_id'] ?? '' }}">
                                    <small class="text-muted">{{ __('Your Google AdSense Publisher ID (ca-pub-XXXXXXXXXXXXXXXX)') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Manual Ad Slots Card (shown only when AdSense is enabled AND mode is manual) --}}
                <div id="adsense_manual_section" class="col-12 mt-4 {{ (($settings['adsense_enabled'] ?? 0) != '1' || ($settings['adsense_mode'] ?? 'automatic') != 'manual') ? 'd-none' : '' }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Manual Ad Slot IDs') }}</h6>
                            </div>

                            {{-- Banner Slot --}}
                            <div class="form-group row mt-3">
                                <label for="adsense_banner_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    {{ __('Banner Slot ID') }}
                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_banner_slot_id"
                                           name="adsense_banner_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="{{ __('e.g. 1234567890') }}"
                                           value="{{ $settings['adsense_banner_slot_id'] ?? '' }}">
                                    <small class="text-muted">{{ __('Ad slot ID for horizontal banner ads') }}</small>
                                </div>
                            </div>

                            {{-- Vertical Slot --}}
                            <div class="form-group row mt-3">
                                <label for="adsense_vertical_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    {{ __('Vertical Slot ID') }}
                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_vertical_slot_id"
                                           name="adsense_vertical_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="{{ __('e.g. 0987654321') }}"
                                           value="{{ $settings['adsense_vertical_slot_id'] ?? '' }}">
                                    <small class="text-muted">{{ __('Ad slot ID for vertical / skyscraper ads') }}</small>
                                </div>
                            </div>

                            {{-- Square Slot --}}
                            <div class="form-group row mt-3">
                                <label for="adsense_square_slot_id" class="col-sm-3 col-form-label fw-semibold">
                                    {{ __('Square Slot ID') }}
                                </label>
                                <div class="col-sm-9">
                                    <input id="adsense_square_slot_id"
                                           name="adsense_square_slot_id"
                                           type="text"
                                           class="form-control"
                                           placeholder="{{ __('e.g. 1122334455') }}"
                                           value="{{ $settings['adsense_square_slot_id'] ?? '' }}">
                                    <small class="text-muted">{{ __('Ad slot ID for square / rectangle ads') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-1 mb-3">{{ __('Save') }}</button>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        $(document).ready(function () {

            // Toggle sections based on adsense_enabled switch
            $('#switch_adsense_enabled').on('change', function () {
                var isEnabled = $(this).is(':checked');
                $('#adsense_enabled').val(isEnabled ? 1 : 0);
                $(this).next('label').text(isEnabled ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}');

                if (isEnabled) {
                    $('#adsense_mode_section').removeClass('d-none');
                    $('#adsense_client_section').removeClass('d-none');
                    // Show manual section only if manual mode is selected
                    if ($('#adsense_mode_manual').is(':checked')) {
                        $('#adsense_manual_section').removeClass('d-none');
                    }
                } else {
                    $('#adsense_mode_section').addClass('d-none');
                    $('#adsense_client_section').addClass('d-none');
                    $('#adsense_manual_section').addClass('d-none');
                }
            });

            // Toggle manual slot IDs section based on mode selection
            $('input[name="adsense_mode"]').on('change', function () {
                if ($(this).val() === 'manual') {
                    $('#adsense_manual_section').removeClass('d-none');
                } else {
                    $('#adsense_manual_section').addClass('d-none');
                }
            });

        });
    </script>
@endsection
