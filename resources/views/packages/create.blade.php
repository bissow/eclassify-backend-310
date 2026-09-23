@extends('layouts.main')

@section('title')
    {{ __('Create Package') }}
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
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('package.index') }}">
                < {{ __('Back to Packages') }} </a>
        </div>
        <form action="{{ route('package.store') }}" method="POST" class="create-form"
            data-success-function="afterPackageCreationSuccess" data-parsley-validate enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">{{ __('Create Package') }}</div>
                        <div class="card-body mt-2">

                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                @foreach ($languages as $key => $lang)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if ($key == 0) active @endif"
                                            id="tab-{{ $lang->id }}" data-bs-toggle="tab"
                                            data-bs-target="#lang-{{ $lang->id }}" type="button" role="tab">
                                            {{ $lang->name }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content mt-3">
                                @foreach ($languages as $key => $lang)
                                    <div class="tab-pane fade @if ($key == 0) show active @endif"
                                        id="lang-{{ $lang->id }}" role="tabpanel">
                                        <input type="hidden" name="languages[]" value="{{ $lang->id }}">
                                        @if ($lang->id == 1)
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('Package Name') }} ({{ $lang->name }}) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="name[{{ $lang->id }}]"
                                                        class="form-control" data-parsley-required="true">
                                                </div>
                                                {{-- Row 1: IOS Product ID (Name already shown above) --}}
                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('IOS Product ID') }}</label>
                                                    <input type="text" name="ios_product_id" class="form-control">
                                                </div>
                                            </div>
                                        @else
                                            <div class="form-group">
                                                <label>{{ __('Package Name') }} ({{ $lang->name }}) <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="name[{{ $lang->id }}]"
                                                    class="form-control">
                                            </div>
                                        @endif
                                        @if ($lang->id == 1)
                                            {{-- Row 2: Price and Discount Percentage --}}
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('Price') }} ({{ $currency_symbol }}) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="price" class="form-control"
                                                        data-parsley-required="true" min="0" step="0.01">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('Discount') }} (%) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="discount_in_percentage" class="form-control"
                                                        data-parsley-required="true" min="0" max="100"
                                                        step="0.01">
                                                </div>
                                            </div>

                                            {{-- Row 3: Final Price --}}
                                            <div class="row">
                                                <div class="col-md-12 form-group">
                                                    <label>{{ __('Final Price') }} ({{ $currency_symbol }}) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="final_price" class="form-control"
                                                        data-parsley-required="true" min="0" step="0.01">
                                                </div>
                                            </div>

                                            {{-- Package Duration Type --}}
                                            <div class="row">
                                                <div class="col-lg-4 form-group">
                                                    <label class="d-block">{{ __('Package Duration Type') }} <span class="text-danger">*</span></label>
                                                    <div class="form-check form-check-inline mt-2">
                                                        <input class="form-check-input package-duration-type" type="radio"
                                                            name="package_duration_type" id="package_duration_limited"
                                                            value="limited" checked>
                                                        <label class="form-check-label"
                                                            for="package_duration_limited">{{ __('Limited') }}</label>
                                                    </div>
                                                    <div class="form-check form-check-inline mt-2">
                                                        <input class="form-check-input package-duration-type" type="radio"
                                                            name="package_duration_type" id="package_duration_unlimited"
                                                            value="unlimited">
                                                        <label class="form-check-label"
                                                            for="package_duration_unlimited">{{ __('Unlimited') }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-lg-8 form-group">
                                                    <div id="package_duration_input" class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label>{{ __('Duration (Days)') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="duration" class="form-control" data-parsley-required="true" min="1" placeholder="{{ __('Enter duration in days') }}">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            {{-- Package Type Radio Buttons --}}
                                            <div class="row form-group">
                                                <label>{{ __('Package Type') }} <span class="text-danger">*</span></label>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input package-type-radio"
                                                            type="radio" name="type" id="type_item_listing"
                                                            value="item_listing" checked>
                                                        <label class="form-check-label" for="type_item_listing">
                                                            {{ __('Ad Listing Package') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input package-type-radio"
                                                            type="radio" name="type"
                                                            id="type_advertisement" value="advertisement">
                                                        <label class="form-check-label" for="type_advertisement">
                                                            {{ __('Featured Ads Package') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input package-type-radio"
                                                            type="radio" name="type"
                                                            id="type_promotional" value="promotional">
                                                        <label class="form-check-label" for="type_promotional">
                                                            {{ __('Promotional / Offer Package') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Ad Listing Package Section --}}
                                            <div id="ad_listing_section" style="display: none;"
                                                class="border rounded p-3 mb-3">
                                                <h6 class="mb-3">{{ __('Ad Listing Package Settings') }}</h6>

                                                {{-- Item Limit --}}
                                                <div class="form-group mb-3">
                                                    <label>{{ __('Item Limit') }}</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input ads-item-limit-type" type="radio"
                                                            name="ads_item_limit_type" id="ads_limit_limited"
                                                            value="limited">
                                                        <label class="form-check-label"
                                                            for="ads_limit_limited">{{ __('Limited') }}</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input ads-item-limit-type" type="radio"
                                                            name="ads_item_limit_type" id="ads_limit_unlimited"
                                                            value="unlimited" checked>
                                                        <label class="form-check-label"
                                                            for="ads_limit_unlimited">{{ __('Unlimited') }}</label>
                                                    </div>
                                                    <div id="ads_item_limit_input" style="display: none;" class="mt-2">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text myDivClass"
                                                                    style="height: 42px;">
                                                                    <span class="mySpanClass">{{ __('Number') }}</span>
                                                                </div>
                                                            </div>
                                                            <input type="number" name="ads_item_limit"
                                                                class="form-control" min="1"
                                                                placeholder="{{ __('Enter item limit') }}">
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Listing Duration Type --}}
                                                <div class="form-group mb-3">
                                                    <label>{{ __('Listing Duration Type') }}</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_standard" value="standard" checked>
                                                        <label class="form-check-label"
                                                            for="ads_listing_standard">{{ __('Standard (30 days)') }}</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_package" value="package">
                                                        <label class="form-check-label"
                                                            for="ads_listing_package">{{ __('Package') }}</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_custom" value="custom">
                                                        <label class="form-check-label"
                                                            for="ads_listing_custom">{{ __('Custom') }}</label>
                                                    </div>
                                                    <div id="ads_listing_duration_days_input" style="display: none;"
                                                        class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label>{{ __('Days') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="ads_listing_duration_days" class="form-control" data-parsley-required="true" min="1" placeholder="{{ __('Enter days') }}">
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Allow Reel Upload --}}
                                                <div class="form-group mb-0">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="is_reel_allowed" id="is_reel_allowed" value="1">
                                                        <label class="form-check-label" for="is_reel_allowed">
                                                            {{ __('Allow Video Ads Upload') }}
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">{{ __('Users with this package can upload a Video Ad for their listings.') }}</small>
                                                </div>
                                            </div>

                                            {{-- Featured Ads Package Section --}}
                                            <div id="featured_ads_section" style="display: none;"
                                                class="border rounded p-3 mb-3">
                                                <h6 class="mb-3">{{ __('Featured Ads Package Settings') }}</h6>

                                                {{-- Item Limit --}}
                                                <div class="form-group mb-3">
                                                    <label>{{ __('Item Limit') }}</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input featured-item-limit-type"
                                                            type="radio" name="featured_item_limit_type"
                                                            id="featured_limit_limited" value="limited">
                                                        <label class="form-check-label"
                                                            for="featured_limit_limited">{{ __('Limited') }}</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input featured-item-limit-type"
                                                            type="radio" name="featured_item_limit_type"
                                                            id="featured_limit_unlimited" value="unlimited" checked>
                                                        <label class="form-check-label"
                                                            for="featured_limit_unlimited">{{ __('Unlimited') }}</label>
                                                    </div>
                                                    <div id="featured_item_limit_input" style="display: none;"
                                                        class="mt-2">
                                                        <input type="number" name="featured_item_limit"
                                                            class="form-control" min="1"
                                                            placeholder="{{ __('Enter item limit') }}">
                                                    </div>
                                                </div>

                                                {{-- Featured Ads Duration Type --}}
                                                <div class="form-group mb-3">
                                                    <label>{{ __('Featured Ads Duration Type') }}</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_standard" value="standard" checked>
                                                        <label class="form-check-label"
                                                            for="featured_ads_standard">{{ __('Standard (30 days)') }}</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_package" value="package">
                                                        <label class="form-check-label"
                                                            for="featured_ads_package">{{ __('Package') }}</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_custom" value="custom">
                                                        <label class="form-check-label"
                                                            for="featured_ads_custom">{{ __('Custom') }}</label>
                                                    </div>
                                                    <div id="featured_ads_duration_days_input" style="display: none;"
                                                        class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label>{{ __('Days') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="featured_ads_duration_days" class="form-control" data-parsley-required="true" min="1" placeholder="{{ __('Enter days') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Promotional & Marketing Features Section --}}
                                            <div id="promotional_features_section" class="border rounded p-3 mb-3 border-primary">
                                                <h6 class="mb-3 text-primary"><i class="bi bi-tag-fill me-2"></i>{{ __('Promotions & Ad Marketing Features') }}</h6>
                                                <small class="text-muted d-block mb-3">{{ __('Enable special promotional permissions, ad bump-ups, top ads, and spotlight badges for subscribers of this package.') }}</small>

                                                {{-- Allow Promotions --}}
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="allows_promotions" id="allows_promotions" value="1">
                                                            <label class="form-check-label fw-bold" for="allows_promotions">
                                                                {{ __('Allow Sales & Campaign Promotions') }}
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">{{ __('Allows users to submit items to Flash Sales, Clearance Sales, and Deals of the Day.') }}</small>
                                                    </div>
                                                    <div class="col-md-6" id="promotional_item_limit_group" style="display: none;">
                                                        <label class="form-label">{{ __('Max Promotional Items Allowed') }}</label>
                                                        <input type="number" name="promotion_item_limit" class="form-control" min="0" value="0" placeholder="{{ __('0 for unlimited, or specific quota') }}">
                                                        <small class="text-muted">{{ __('Set to 0 for unlimited promotional submissions during package validity.') }}</small>
                                                    </div>
                                                </div>

                                                <hr class="my-3">

                                                {{-- Daily Bump Up --}}
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="allows_daily_bump_up" id="allows_daily_bump_up" value="1">
                                                            <label class="form-check-label fw-bold" for="allows_daily_bump_up">
                                                                {{ __('Allow Daily Bump Up') }}
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">{{ __('Renews the ad creation date to push it back to the top of standard search results once every 24 hours.') }}</small>
                                                    </div>
                                                    <div class="col-md-6" id="daily_bump_limit_group" style="display: none;">
                                                        <label class="form-label">{{ __('Max Bump Ups Allowed') }}</label>
                                                        <input type="number" name="daily_bump_up_limit" class="form-control" min="0" value="0" placeholder="{{ __('0 for unlimited, or specific quota') }}">
                                                        <small class="text-muted">{{ __('Total bump ups allowed during package lifetime (0 = unlimited).') }}</small>
                                                    </div>
                                                </div>

                                                <hr class="my-3">

                                                {{-- Top Ad and Spotlight --}}
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="allows_top_ad" id="allows_top_ad" value="1">
                                                            <label class="form-check-label fw-bold" for="allows_top_ad">
                                                                {{ __('Allow "Top Ad" Boost') }}
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">{{ __('Grants priority top ranking placement with high-visibility badges.') }}</small>
                                                        <div id="top_ad_limit_group" style="display: none;" class="mt-2">
                                                            <label class="form-label">{{ __('Max "Top Ad" Items Allowed') }}</label>
                                                            <input type="number" name="top_ad_limit" class="form-control" min="0" value="0" placeholder="{{ __('0 for unlimited, or specific quota') }}">
                                                            <small class="text-muted">{{ __('0 for unlimited during package validity.') }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="allows_spotlight" id="allows_spotlight" value="1">
                                                            <label class="form-check-label fw-bold" for="allows_spotlight">
                                                                {{ __('Allow "Spotlight" Carousel') }}
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">{{ __('Showcases the ad on the homepage spotlight carousel & offer page banner hero.') }}</small>
                                                        <div id="spotlight_limit_group" style="display: none;" class="mt-2">
                                                            <label class="form-label">{{ __('Max "Spotlight" Items Allowed') }}</label>
                                                            <input type="number" name="spotlight_limit" class="form-control" min="0" value="0" placeholder="{{ __('0 for unlimited, or specific quota') }}">
                                                            <small class="text-muted">{{ __('0 for unlimited during package validity.') }}</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Seller Store QR Code Feature --}}
                                                <div class="col-md-12 mb-3">
                                                    <div class="card p-3 border rounded shadow-sm" style="background-color: #f0fdfa; border-color: #99f6e4 !important;">
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input" type="checkbox" name="allows_seller_qr_code" id="allows_seller_qr_code" value="1">
                                                            <label class="form-check-label fw-bold text-dark" for="allows_seller_qr_code">
                                                                <i class="ph ph-qr-code text-primary me-1"></i> {{ __('Include Seller Store QR Code Feature') }}
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">{{ __('Allows verified store owners/sellers to generate custom QR standees & posters (UPI/Google Pay style) with location-based catalog discovery.') }}</small>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Key Points --}}
                                            <div class="form-group">
                                                <label>{{ __('Key Points') }} ({{ $lang->name }})</label>
                                                <div id="key_points_container_{{ $lang->id }}">
                                                    <div class="form-group key-point-item">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="key_points[{{ $lang->id }}][]"
                                                                class="form-control"
                                                                placeholder="{{ __('Enter key point') }}">
                                                            <button type="button" class="btn btn-danger remove-key-point"
                                                                style="display: none;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary mt-2 add-key-point"
                                                    data-lang-id="{{ $lang->id }}">
                                                    <i class="fas fa-plus me-1"></i> {{ __('Add Key Point') }}
                                                </button>
                                            </div>

                                            {{-- Image --}}
                                            <div class="form-group">
                                                <label for="icon" class="form-label">{{ __('Icon') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="icon" id="icon" class="form-control"
                                                    data-parsley-required="true" accept=".jpg, .jpeg, .png">
                                                {{ __('(use 256 x 256 size for better view)') }}
                                                <div class="img_error" style="color:#DC3545;"></div>
                                            </div>
                                        @else
                                            {{-- Key Points for other languages --}}
                                            <div class="form-group">
                                                <label>{{ __('Key Points') }} ({{ $lang->name }})</label>
                                                <div id="key_points_container_{{ $lang->id }}">
                                                    <div class="form-group key-point-item">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="key_points[{{ $lang->id }}][]"
                                                                class="form-control"
                                                                placeholder="{{ __('Enter key point') }}">
                                                            <button type="button" class="btn btn-danger remove-key-point"
                                                                style="display: none;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary mt-2 add-key-point"
                                                    data-lang-id="{{ $lang->id }}">
                                                    <i class="fas fa-plus me-1"></i> {{ __('Add Key Point') }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-sm-12">
                    @include('category.category-scope', [
                        'categories' => $categories,
                        'selected_categories' => $selected_categories ?? [],
                        'show_global' => true,
                        'global_id' => 'is_global',
                        'global_label' => __('Global (Apply to All Categories)'),
                        'selected_all_categories' => $selected_all_categories ?? [],
                        'wrapper_id' => 'category_selection_scope',
                        'subtitle' => __('Select the categories where this Package Should be Applied'),
                    ])
                </div>

                {{-- Refer Points Settings --}}
                {{-- <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">{{ __('Refer Points Settings') }}</div>
                        <div class="card-body mt-2">
                            <small class="text-muted d-block mb-3">{{ __('Leave empty to use global settings from Refer & Earn settings page.') }}</small>

                            <div class="form-group mb-3">
                                <label>{{ __('Max Points Usage Percentage') }} (%)</label>
                                <input type="number" name="refer_max_points_usage_percentage" class="form-control"
                                    min="1" max="100" placeholder="{{ __('Global default') }}">
                            </div>

                            <div class="form-group mb-3">
                                <label>{{ __('Minimum Points to Use') }}</label>
                                <input type="number" name="refer_min_points_to_use" class="form-control"
                                    min="1" placeholder="{{ __('Global default') }}">
                            </div>

                            <div class="form-group mb-3">
                                <label>{{ __('Maximum Points to Use') }}</label>
                                <input type="number" name="refer_max_points_to_use" class="form-control"
                                    min="1" placeholder="{{ __('Global default') }}">
                            </div>
                        </div>
                    </div>
                </div> --}}

                <div class="col-md-12 text-end">
                    <input type="submit" class="btn btn-primary" value="{{ __('Save and Back') }}">
                </div>

            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Package type radio buttons - only one can be selected
            $('.package-type-radio').on('change', function() {
                const selectedType = $(this).val();

                if (selectedType === 'item_listing') {
                    $('#ad_listing_section').show();
                    $('#featured_ads_section').hide();
                    $('#is_global').prop('checked', false).prop('disabled', false);
                    $('.category-checkbox').prop('disabled', false);
                    $('#category_selection').show();
                } else if (selectedType === 'advertisement') {
                    $('#ad_listing_section').hide();
                    $('#featured_ads_section').show();
                    $('#is_global').prop('checked', true).prop('disabled', true);
                    $('.category-checkbox').prop('checked', false).prop('disabled', true);
                    $('#category_selection').hide();
                } else if (selectedType === 'promotional') {
                    $('#ad_listing_section').hide();
                    $('#featured_ads_section').hide();
                    $('#is_global').prop('checked', true).prop('disabled', true);
                    $('.category-checkbox').prop('checked', false).prop('disabled', true);
                    $('#category_selection').hide();
                    // Auto-check allows_promotions
                    $('#allows_promotions').prop('checked', true).trigger('change');
                }
            });

            // Promotional toggles
            $('#allows_promotions').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#promotional_item_limit_group').show();
                } else {
                    $('#promotional_item_limit_group').hide();
                }
            });

            $('#allows_daily_bump_up').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#daily_bump_limit_group').show();
                } else {
                    $('#daily_bump_limit_group').hide();
                }
            });

            $('#allows_top_ad').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#top_ad_limit_group').show();
                } else {
                    $('#top_ad_limit_group').hide();
                }
            });

            $('#allows_spotlight').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#spotlight_limit_group').show();
                } else {
                    $('#spotlight_limit_group').hide();
                }
            });
            
            // Initialize on page load
            const initialType = $('.package-type-radio:checked').val();
            if (initialType) {
                $('.package-type-radio[value="' + initialType + '"]').trigger('change');
            }

            // Ad listing item limit toggle
            $('.ads-item-limit-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#ads_item_limit_input').show();
                } else {
                    $('#ads_item_limit_input').hide();
                }
            });

            // Ad listing duration type toggle
            $('.ads-listing-duration-type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#ads_listing_duration_days_input').show();
                } else {
                    $('#ads_listing_duration_days_input').hide();
                }
            });

            // Featured item limit toggle
            $('.featured-item-limit-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#featured_item_limit_input').show();
                } else {
                    $('#featured_item_limit_input').hide();
                }
            });

            // Featured ads duration type toggle
            $('.featured-ads-duration-type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#featured_ads_duration_days_input').show();
                } else {
                    $('#featured_ads_duration_days_input').hide();
                }
            });

            // Package duration type toggle
            $('.package-duration-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#package_duration_input').show();
                    $('#package_duration_input input[name="duration"]').attr('data-parsley-required',
                        'true');
                } else {
                    $('#package_duration_input').hide();
                    $('#package_duration_input input[name="duration"]').removeAttr('data-parsley-required');
                }
            });

            // Add key point
            $(document).on('click', '.add-key-point', function() {
                const langId = $(this).data('lang-id');
                const container = $('#key_points_container_' + langId);
                const newPoint = container.find('.key-point-item').first().clone();
                newPoint.find('input').val('');
                newPoint.find('.remove-key-point').show();
                container.append(newPoint);
                updateRemoveButtons();
            });

            // Remove key point
            $(document).on('click', '.remove-key-point', function() {
                const container = $(this).closest('#key_points_container_' + $(this).closest('.tab-pane')
                    .find('input[name="languages[]"]').val());
                if ($(this).closest('.key-point-item').siblings('.key-point-item').length > 0) {
                    $(this).closest('.key-point-item').remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                $('.key-point-item').each(function() {
                    const container = $(this).closest('#key_points_container_' + $(this).closest(
                        '.tab-pane').find('input[name="languages[]"]').val());
                    const count = container.find('.key-point-item').length;
                    container.find('.remove-key-point').toggle(count > 1);
                });
            }

            // Global package toggle
            $('#is_global').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#category_selection').hide();
                    $('.category-checkbox').prop('checked', false);
                } else {
                    $('#category_selection').show();
                }
            });

            $(document).on('click', '.category-header label', function(e) {

                if ($(e.target).is('input[type="checkbox"]')) {
                    e.stopPropagation();
                }
            });
            
            // Prevent category header from triggering toggle except when clicking the toggle button
            $(document).on('click', '.category-header', function(e) {
                // If clicking on toggle button, let it handle
                if ($(e.target).hasClass('toggle-button') || $(e.target).closest('.toggle-button').length) {
                    return;
                }
                // If clicking on checkbox or label, let it handle
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label') || $(e.target).closest('label').length) {
                    return;
                }
                // Otherwise, prevent any action
                e.stopPropagation();
            });
        });

        function afterPackageCreationSuccess() {
            setTimeout(function() {
                window.location.href = "{{ route('package.index') }}";
            }, 1000)
        }

        // Auto-calculate final price based on price and discount
        function calculateFinalPrice() {
            const price = parseFloat($('input[name="price"]').val()) || 0;
            const discount = parseFloat($('input[name="discount_in_percentage"]').val()) || 0;
            
            if (price > 0 && discount >= 0 && discount <= 100) {
                const discountAmount = (price * discount) / 100;
                const finalPrice = price - discountAmount;
                $('input[name="final_price"]').val(finalPrice.toFixed(2));
            }
        }

        $('input[name="price"], input[name="discount_in_percentage"]').on('input', calculateFinalPrice);
    </script>
@endsection
