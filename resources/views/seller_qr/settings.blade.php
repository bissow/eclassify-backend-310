@extends('layouts.main')

@section('title')
    {{ __('Seller QR Code & Standee Settings') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Customize default templates, brand frames, colors, and permissions for Seller QR standees.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                <a href="{{ route('seller-qr.index') }}" class="btn btn-outline-secondary">
                    <i class="ph ph-arrow-left me-1"></i> {{ __('Back to QR List') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form action="{{ route('seller-qr.settings.update') }}" method="POST" enctype="multipart/form-data" id="qrSettingsForm">
            @csrf
            <div class="row">
                <!-- Settings Form -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="ph ph-sliders text-primary me-2"></i> {{ __('General & Permission Controls') }}
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Feature Toggle -->
                            <div class="p-3 mb-3 rounded border" style="background-color: #f8fafc;">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="seller_qr_enabled" id="seller_qr_enabled" value="1" {{ $settings['enabled'] ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="seller_qr_enabled">
                                        {{ __('Enable Seller QR Code Feature Globally') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('When enabled, eligible sellers with active packages can generate and download shop QR standees.') }}</small>
                            </div>

                            <!-- Allow User Logo -->
                            <div class="p-3 mb-3 rounded border" style="background-color: #f8fafc;">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="seller_qr_allow_user_logo" id="seller_qr_allow_user_logo" value="1" {{ $settings['allow_user_logo'] ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="seller_qr_allow_user_logo">
                                        {{ __('Allow Sellers to Embed Their Own Store Logo into the QR Code') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('If disabled, QR codes will either be clean or embed the default platform logo.') }}</small>
                            </div>

                            <!-- Allow User Customization -->
                            <div class="p-3 mb-3 rounded border" style="background-color: #f8fafc;">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="seller_qr_allow_user_customization" id="seller_qr_allow_user_customization" value="1" {{ $settings['allow_user_customization'] ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="seller_qr_allow_user_customization">
                                        {{ __('Allow Sellers to Customize Brand Colors & Headings') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('If disabled, all seller standees will enforce the default admin template & colors configured below.') }}</small>
                            </div>

                            <!-- Geofence Distance Warning Threshold -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_warning_distance_km" class="form-label fw-bold">{{ __('Location Discrepancy Warning Radius (Kilometers)') }}</label>
                                <div class="input-group">
                                    <input type="number" step="1" min="1" max="1000" name="seller_qr_warning_distance_km" id="seller_qr_warning_distance_km" class="form-control" value="{{ $settings['warning_distance_km'] }}">
                                    <span class="input-group-text">{{ __('km') }}</span>
                                </div>
                                <small class="text-muted">{{ __('If a customer scans or views a store beyond this distance from their device GPS or selected location, a warning notice is displayed.') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Template & Branding Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="ph ph-paint-brush text-primary me-2"></i> {{ __('Default Template & Standee Branding') }}
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Standee Top Badge Text -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_badge_text" class="form-label fw-bold">{{ __('Standee Top Pill Badge Text') }}</label>
                                <input type="text" name="seller_qr_badge_text" id="seller_qr_badge_text" class="form-control" value="{{ $settings['badge_text'] ?? 'DIGITAL STORE & CATALOG' }}" placeholder="e.g. DIGITAL STORE & CATALOG">
                                <small class="text-muted">{{ __('Appears at the very top of all generated standees.') }}</small>
                            </div>

                            <!-- Default Standee Title -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_default_title" class="form-label fw-bold">{{ __('Default Standee Header Title') }}</label>
                                <input type="text" name="seller_qr_default_title" id="seller_qr_default_title" class="form-control" value="{{ $settings['default_title'] }}" required>
                            </div>

                            <!-- Default Standee Tagline -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_default_tagline" class="form-label fw-bold">{{ __('Default Standee Tagline') }}</label>
                                <input type="text" name="seller_qr_default_tagline" id="seller_qr_default_tagline" class="form-control" value="{{ $settings['default_tagline'] }}">
                            </div>

                            <!-- Catalog Page Top Banner Text -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_catalog_banner_text" class="form-label fw-bold">{{ __('Public Catalog Page App Banner Text') }}</label>
                                <input type="text" name="seller_qr_catalog_banner_text" id="seller_qr_catalog_banner_text" class="form-control" value="{{ $settings['catalog_banner_text'] ?? 'Browse this store catalog on our mobile app' }}" placeholder="e.g. Browse this store catalog on our mobile app">
                                <small class="text-muted">{{ __('Displayed in the header banner when buyers scan and open a seller\'s store in a mobile web browser.') }}</small>
                            </div>

                            <!-- Public Catalog Base URL -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_catalog_base_url" class="form-label fw-bold">{{ __('Public Store Catalog Base URL') }}</label>
                                <input type="url" name="seller_qr_catalog_base_url" id="seller_qr_catalog_base_url" class="form-control" value="{{ $settings['catalog_base_url'] ?? '' }}" placeholder="e.g. https://myclassified.com">
                                <small class="text-muted">{{ __('Base URL used to generate public QR code links (e.g. {BaseURL}/store-qr/{slug}). If left empty, the system web_url or root URL will be used.') }}</small>
                            </div>

                            <!-- Colors Row -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="seller_qr_primary_color" class="form-label fw-bold">{{ __('Standee Primary Brand Color') }}</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color w-25" id="color_picker_primary" value="{{ $settings['primary_color'] }}">
                                        <input type="text" name="seller_qr_primary_color" id="seller_qr_primary_color" class="form-control" value="{{ $settings['primary_color'] }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="seller_qr_secondary_color" class="form-label fw-bold">{{ __('Standee Accent / Button Color') }}</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color w-25" id="color_picker_secondary" value="{{ $settings['secondary_color'] }}">
                                        <input type="text" name="seller_qr_secondary_color" id="seller_qr_secondary_color" class="form-control" value="{{ $settings['secondary_color'] }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Default Center Logo Embedded in QR -->
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">{{ __('Default Logo Embedded Inside QR Code') }}</label>
                                <div class="d-flex flex-wrap gap-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seller_qr_default_center_logo_type" id="logo_type_platform" value="platform_logo" {{ ($settings['default_center_logo_type'] ?? 'platform_logo') === 'platform_logo' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="logo_type_platform">
                                            <i class="ph ph-shield-check text-primary me-1"></i> {{ __('Platform Brand Logo (Default)') }}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seller_qr_default_center_logo_type" id="logo_type_store" value="store_logo" {{ ($settings['default_center_logo_type'] ?? '') === 'store_logo' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="logo_type_store">
                                            <i class="ph ph-storefront text-info me-1"></i> {{ __('Seller Store / Shop Logo') }}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="seller_qr_default_center_logo_type" id="logo_type_none" value="none" {{ ($settings['default_center_logo_type'] ?? '') === 'none' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="logo_type_none">
                                            <i class="ph ph-x-circle text-muted me-1"></i> {{ __('No Embedded Logo') }}
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">{{ __('When sellers generate QR standees, this logo will be automatically centered inside their QR code.') }}</small>
                            </div>

                            <!-- Admin Center Logo Upload -->
                            <div class="form-group mb-3 p-3 rounded border bg-light">
                                <label for="seller_qr_center_logo" class="form-label fw-bold d-flex align-items-center justify-content-between">
                                    <span><i class="ph ph-image text-primary me-1"></i> {{ __('Admin Platform QR Center Logo') }}</span>
                                    <span class="badge bg-secondary">{{ __('Square / Icon') }}</span>
                                </label>
                                <input type="file" name="seller_qr_center_logo" id="seller_qr_center_logo" class="form-control" accept="image/*">
                                <small class="text-muted">{{ __('Icon or emblem displayed inside the center of QR codes when "Platform Brand Logo" is selected. Transparent PNG or SVG recommended. Max: 3MB.') }}</small>
                                @if(!empty($settings['center_logo_url']))
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <div class="p-2 border rounded bg-white" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                            <img src="{{ $settings['center_logo_url'] }}" alt="Current Center Logo" style="max-height: 36px; max-width: 36px; object-fit: contain;" id="currentCenterLogoPreview">
                                        </div>
                                        <small class="text-muted">{{ __('Current QR Center Logo') }}</small>
                                    </div>
                                @endif
                            </div>

                            <!-- Footer Text -->
                            <div class="form-group mb-3">
                                <label for="seller_qr_default_footer_text" class="form-label fw-bold">{{ __('Standee Footer Branding Text') }}</label>
                                <input type="text" name="seller_qr_default_footer_text" id="seller_qr_default_footer_text" class="form-control" value="{{ $settings['default_footer_text'] }}" placeholder="e.g. Powered by Bissow.com">
                            </div>

                            <!-- Footer Logo Upload -->
                            <div class="form-group mb-3 p-3 rounded border bg-light">
                                <label for="seller_qr_footer_logo" class="form-label fw-bold d-flex align-items-center justify-content-between">
                                    <span><i class="ph ph-file-image text-primary me-1"></i> {{ __('Footer Platform Horizontal Logo') }}</span>
                                    <span class="badge bg-secondary">{{ __('Wide / Full Logo') }}</span>
                                </label>
                                <input type="file" name="seller_qr_footer_logo" id="seller_qr_footer_logo" class="form-control" accept="image/*">
                                <small class="text-muted">{{ __('Appears at the bottom footer of standees. Recommended: Transparent PNG or SVG. Max: 3MB.') }}</small>
                                @if(!empty($settings['footer_logo_url']))
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <div class="p-2 border rounded bg-white">
                                            <img src="{{ $settings['footer_logo_url'] }}" alt="Current Footer Logo" style="max-height: 32px;" id="currentFooterLogoPreview">
                                        </div>
                                        <small class="text-muted">{{ __('Current Footer Logo') }}</small>
                                    </div>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary px-4 py-2 mt-2">
                                <i class="ph ph-floppy-disk me-1"></i> {{ __('Save QR Settings') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Live Standee Preview -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="ph ph-device-mobile text-primary me-2"></i> {{ __('Live Standee Mockup') }}
                            </h5>
                            <span class="badge bg-light-primary text-primary">{{ __('Google Pay / UPI Style') }}</span>
                        </div>
                        <div class="card-body p-4 text-center bg-light">
                            <!-- Mock Standee Frame -->
                            <div id="mockStandee" style="background: #ffffff; border-radius: 16px; border-top: 8px solid {{ $settings['primary_color'] }}; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 22px 16px; max-width: 320px; margin: 0 auto; text-align: center;">
                                <div style="display: inline-block; background: rgba(0,178,202,0.12); color: {{ $settings['primary_color'] }}; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;" id="mockBadge">
                                    {{ $settings['badge_text'] ?? __('DIGITAL STORE & CATALOG') }}
                                </div>
                                <h6 class="fw-bold mb-1" id="mockTitle" style="color: #0f172a !important; font-size: 16px;">
                                    {{ $settings['default_title'] }}
                                </h6>
                                <p class="text-muted mb-3 small" id="mockTagline" style="font-size: 11px; color: #64748b !important;">
                                    {{ $settings['default_tagline'] }}
                                </p>

                                <div style="border: 2px solid {{ $settings['primary_color'] }}; border-radius: 14px; padding: 12px; background: #fff; display: inline-block; margin-bottom: 12px;" id="mockQrBorder">
                                    <div style="position: relative; width: 140px; height: 140px; margin: 0 auto;">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=https://bissow.com/store/demo-store" style="width: 140px; height: 140px; display: block;" alt="Demo QR Code">
                                        <div id="mockCenterLogo" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 34px; height: 34px; background: #fff; border-radius: 50%; border: 2px solid {{ $settings['primary_color'] }}; display: {{ ($settings['default_center_logo_type'] ?? 'platform_logo') === 'none' ? 'none' : 'flex' }}; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                            @php
                                                $previewLogo = !empty($settings['center_logo_url']) ? $settings['center_logo_url'] : ($settings['footer_logo_url'] ?? '');
                                                $centerType = $settings['default_center_logo_type'] ?? 'platform_logo';
                                            @endphp
                                            @if($centerType === 'store_logo')
                                                <i class="ph ph-storefront" style="font-size: 16px; color: {{ $settings['primary_color'] }};" id="mockCenterStoreIcon"></i>
                                                <img src="{{ $previewLogo }}" style="width: 22px; height: 22px; object-fit: contain; display: none;" alt="Center Logo" id="mockCenterLogoImg">
                                            @elseif(!empty($previewLogo))
                                                <img src="{{ $previewLogo }}" style="width: 22px; height: 22px; object-fit: contain;" alt="Center Logo" id="mockCenterLogoImg">
                                            @else
                                                <i class="ph ph-qr-code" style="font-size: 16px; color: {{ $settings['primary_color'] }};" id="mockCenterDefaultIcon"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="background: {{ $settings['secondary_color'] }}; color: #fff; font-size: 9px; font-weight: bold; border-radius: 12px; padding: 4px 8px; margin-top: 8px;" id="mockScanBadge">
                                        {{ __('SCAN TO VIEW ALL ADS & OFFERS') }}
                                    </div>
                                </div>

                                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; text-align: left; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <div style="width: 34px; height: 34px; border-radius: 8px; background: {{ $settings['primary_color'] }}15; color: {{ $settings['primary_color'] }}; display: flex; align-items: center; justify-content: center; font-size: 18px; shrink: 0;" id="mockStoreIcon">
                                        <i class="ph ph-storefront"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="color: #0f172a !important; font-size: 13px; font-weight: 700; line-height: 1.2;">
                                            {{ __('Example Store Name') }} <span style="color: #0284c7; font-weight: bold;">✓</span>
                                        </div>
                                        <div style="color: #64748b !important; font-size: 10.5px; margin-top: 2px;">
                                            {{ __('City Center Market, Mumbai') }}
                                        </div>
                                    </div>
                                </div>

                                <div style="border-top: 1px dashed #cbd5e1; padding-top: 8px; font-size: 10px; color: #94a3b8; font-weight: 600;" id="mockFooter">
                                    <span id="mockFooterText">{{ $settings['default_footer_text'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('script')
<script>
    // Live update mockup preview
    $('#seller_qr_badge_text').on('input', function () {
        $('#mockBadge').text($(this).val() || "{{ __('DIGITAL STORE & CATALOG') }}");
    });

    $('#seller_qr_default_title').on('input', function () {
        $('#mockTitle').text($(this).val() || "{{ __('Scan to Browse Store & Catalog') }}");
    });

    $('#seller_qr_default_tagline').on('input', function () {
        $('#mockTagline').text($(this).val() || "{{ __('Explore all verified ads, items and exclusive offers') }}");
    });

    $('#seller_qr_default_footer_text').on('input', function () {
        $('#mockFooterText').text($(this).val() || "{{ __('Powered by Bissow.com') }}");
    });

    // Live update center logo preview when changing radio button
    $('input[name="seller_qr_default_center_logo_type"]').on('change', function () {
        var val = $(this).val();
        var primaryColor = $('#seller_qr_primary_color').val() || '#00B2CA';
        var currentImgSrc = $('#currentCenterLogoPreview').attr('src') || "{{ $previewLogo }}";

        if (val === 'none') {
            $('#mockCenterLogo').hide();
        } else if (val === 'store_logo') {
            $('#mockCenterLogo').css('display', 'flex').html(
                '<i class="ph ph-storefront" style="font-size: 16px; color: ' + primaryColor + ';"></i>'
            );
        } else if (val === 'platform_logo') {
            if (currentImgSrc && currentImgSrc.length > 0) {
                $('#mockCenterLogo').css('display', 'flex').html(
                    '<img src="' + currentImgSrc + '" style="width: 22px; height: 22px; object-fit: contain;" alt="Center Logo" id="mockCenterLogoImg">'
                );
            } else {
                $('#mockCenterLogo').css('display', 'flex').html(
                    '<i class="ph ph-shield-check" style="font-size: 16px; color: ' + primaryColor + ';"></i>'
                );
            }
        }
    });

    // Live update center logo preview when uploading a new file
    $('#seller_qr_center_logo').on('change', function () {
        var input = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var newSrc = e.target.result;
                if ($('#currentCenterLogoPreview').length) {
                    $('#currentCenterLogoPreview').attr('src', newSrc);
                }
                // If platform_logo is selected, update mockup immediately
                var selectedType = $('input[name="seller_qr_default_center_logo_type"]:checked').val();
                if (selectedType === 'platform_logo' || !selectedType) {
                    $('#mockCenterLogo').css('display', 'flex').html(
                        '<img src="' + newSrc + '" style="width: 22px; height: 22px; object-fit: contain;" alt="Center Logo" id="mockCenterLogoImg">'
                    );
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    $('#color_picker_primary').on('input', function () {
        var color = $(this).val();
        $('#seller_qr_primary_color').val(color);
        updatePrimaryColor(color);
    });

    $('#seller_qr_primary_color').on('input', function () {
        var color = $(this).val();
        $('#color_picker_primary').val(color);
        updatePrimaryColor(color);
    });

    $('#color_picker_secondary').on('input', function () {
        var color = $(this).val();
        $('#seller_qr_secondary_color').val(color);
        updateSecondaryColor(color);
    });

    $('#seller_qr_secondary_color').on('input', function () {
        var color = $(this).val();
        $('#color_picker_secondary').val(color);
        updateSecondaryColor(color);
    });

    function updatePrimaryColor(color) {
        $('#mockStandee').css('border-top-color', color);
        $('#mockQrBorder').css('border-color', color);
        $('#mockBadge').css('color', color);
    }

    function updateSecondaryColor(color) {
        $('#mockScanBadge').css('background-color', color);
    }

    $('#qrSettingsForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        formData.set('seller_qr_enabled', $('#seller_qr_enabled').is(':checked') ? 1 : 0);
        formData.set('seller_qr_allow_user_logo', $('#seller_qr_allow_user_logo').is(':checked') ? 1 : 0);
        formData.set('seller_qr_allow_user_customization', $('#seller_qr_allow_user_customization').is(':checked') ? 1 : 0);

        $.ajax({
            url: $(form).attr('action'),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (!response.error) {
                    showSuccessToast(response.message);
                } else {
                    showErrorToast(response.message);
                }
            },
            error: function (xhr) {
                var msg = "{{ __('An error occurred while saving settings.') }}";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showErrorToast(msg);
            }
        });
    });
</script>
@endsection
