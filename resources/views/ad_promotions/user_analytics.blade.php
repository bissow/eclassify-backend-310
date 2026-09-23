@extends('layouts.main')

@section('title')
    {{ __('User Promotions & Sales Analytics') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Select a user to inspect real-time promotions performance, sales metrics, and boost history.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                <a href="{{ route('ad-promotions.index') }}" class="btn btn-secondary">
                    <i class="ph ph-arrow-left me-1"></i> {{ __('Back to Promoted Ads') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <!-- User Selection Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-6 col-lg-5">
                        <label for="select_user_id" class="form-label fw-bold text-uppercase small text-muted">
                            <i class="ph ph-user me-1 text-primary"></i> {{ __('Select User / Seller') }}
                        </label>
                        <select id="select_user_id" class="form-select select2 w-100" data-placeholder="{{ __('Choose a user...') }}">
                            <option value="">{{ __('Select User...') }}</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ (string)$u->id === (string)$selectedUserId ? 'selected' : '' }}>
                                    {{ $u->name }} (ID: {{ $u->id }}{{ $u->mobile ? ' • ' . $u->mobile : ($u->email ? ' • ' . $u->email : '') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-7 d-flex justify-content-md-end align-items-end gap-2 pt-md-4">
                        <button type="button" class="btn btn-primary" id="btn_refresh_analytics">
                            <i class="ph ph-arrows-clockwise me-1"></i> {{ __('Refresh Data') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row mb-4 g-3" id="analytics_kpi_row">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 mb-0 h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-fire fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Active Promotions') }}</p>
                            <h4 class="mb-0 fw-bold" id="kpi_active_promotions">0</h4>
                            <span class="text-muted small" id="kpi_active_sub">0 sales • 0 boosts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 mb-0 h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-info text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-shopping-bag-open fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Units Sold / Claimed') }}</p>
                            <h4 class="mb-0 fw-bold text-info" id="kpi_units_sold">0</h4>
                            <span class="text-muted small">{{ __('across all promo events') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 mb-0 h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-currency-dollar fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Generated Revenue') }}</p>
                            <h4 class="mb-0 fw-bold text-success" id="kpi_revenue">0.00</h4>
                            <span class="text-muted small">{{ __('discounted purchases') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 mb-0 h-100">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-warning text-dark rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-sparkle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Active Boosts') }}</p>
                            <h4 class="mb-0 fw-bold text-warning" id="kpi_active_boosts">0</h4>
                            <span class="text-muted small" id="kpi_boosts_sub">0 bump • 0 top • 0 spot</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Filter & Table Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="ph ph-clock-counter-clockwise me-1 text-primary"></i> {{ __('Promotions & Boosts History') }}
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small fw-bold text-muted">{{ __('Filter Type:') }}</label>
                    <select id="history_filter_type" class="form-select form-select-sm" style="width: 200px;">
                        <option value="all">{{ __('All Promotions & Boosts') }}</option>
                        <option value="sales">{{ __('All Sales (Discounts)') }}</option>
                        <option value="boosts">{{ __('All Boosts (Bump/Top/Spot)') }}</option>
                        <option value="flash_sale">{{ __('Flash Sale') }}</option>
                        <option value="clearance_sale">{{ __('Clearance Sale') }}</option>
                        <option value="deal_of_the_day">{{ __('Deal of the Day') }}</option>
                        <option value="daily_bump_up">{{ __('Daily Bump Up') }}</option>
                        <option value="top_ad">{{ __('Top Ad') }}</option>
                        <option value="spotlight">{{ __('Spotlight') }}</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <table
                    id="user_promotions_table"
                    data-toggle="table"
                    data-url="{{ route('ad-promotions.user-analytics.history') }}"
                    data-side-pagination="server"
                    data-pagination="true"
                    data-page-list="[10, 20, 50, 100]"
                    data-search="false"
                    data-show-refresh="false"
                    data-query-params="userPromosQueryParams"
                    data-response-handler="userPromosResponseHandler"
                    class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="false">{{ __('ID') }}</th>
                            <th data-field="item_info">{{ __('Advertisement') }}</th>
                            <th data-field="type_badge">{{ __('Type / Promotion') }}</th>
                            <th data-field="pricing">{{ __('Pricing / Value') }}</th>
                            <th data-field="performance">{{ __('Units / Claimed') }}</th>
                            <th data-field="duration">{{ __('Duration Active') }}</th>
                            <th data-field="timing">{{ __('Dates & Bump') }}</th>
                            <th data-field="status_badge">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
@endsection

@section('js')
<script>
    function userPromosQueryParams(params) {
        var page = 1;
        if (params.limit > 0) {
            page = Math.floor((params.offset || 0) / params.limit) + 1;
        }
        return {
            user_id: $('#select_user_id').val(),
            filter_type: $('#history_filter_type').val(),
            page: page,
            limit: params.limit,
            offset: params.offset,
            search: params.search
        };
    }

    function userPromosResponseHandler(res) {
        if (!res) {
            return { total: 0, rows: [] };
        }
        var list = [];
        var total = 0;

        if (res.data && Array.isArray(res.data.data)) {
            list = res.data.data;
            total = res.data.total || list.length;
        } else if (Array.isArray(res.rows)) {
            list = res.rows;
            total = res.total !== undefined ? res.total : list.length;
        } else if (res.data && Array.isArray(res.data)) {
            list = res.data;
            total = list.length;
        } else if (Array.isArray(res)) {
            list = res;
            total = list.length;
        }

        var rows = list.map(function(item) {
            var itemHtml = '<div class="d-flex align-items-center gap-2">' +
                '<img src="' + (item.item_image || '/assets/images/no_image_available.png') + '" class="rounded" style="width:40px;height:40px;object-fit:cover;" onerror="handleImageError(this)">' +
                '<div>' +
                    '<div class="fw-bold text-truncate" style="max-width:220px;">' + (item.item_name || '-') + '</div>' +
                    '<div class="text-muted small">Views: ' + (item.views || 0) + '</div>' +
                '</div>' +
            '</div>';

            var typeBadge = '';
            if (item.record_type === 'boost') {
                if (item.promotion_type === 'daily_bump_up') {
                    typeBadge = '<span class="badge bg-info"><i class="ph ph-rocket-launch me-1"></i>' + (item.type_title || 'Daily Bump Up') + '</span>';
                } else if (item.promotion_type === 'top_ad') {
                    typeBadge = '<span class="badge bg-warning text-dark"><i class="ph ph-arrow-fat-line-up me-1"></i>' + (item.type_title || 'Top Ad') + '</span>';
                } else if (item.promotion_type === 'spotlight') {
                    typeBadge = '<span class="badge bg-success"><i class="ph ph-sparkle me-1"></i>' + (item.type_title || 'Spotlight') + '</span>';
                } else {
                    typeBadge = '<span class="badge bg-secondary">' + (item.type_title || item.promotion_type) + '</span>';
                }
            } else {
                var camp = (item.promotion && item.promotion.campaign) ? ' <span class="badge bg-purple ms-1" style="background:#8b5cf6;">' + item.promotion.campaign.title + '</span>' : '';
                typeBadge = '<span class="badge bg-primary"><i class="ph ph-lightning me-1"></i>' + (item.type_title || (item.promotion ? item.promotion.title : 'Sale')) + '</span>' + camp;
            }

            var pricingHtml = '-';
            if (item.record_type === 'sale') {
                pricingHtml = '<div><strong class="text-primary">' + item.promotional_price + '</strong></div>' +
                    (item.original_price > item.promotional_price ? '<del class="text-muted small">' + item.original_price + '</del>' : '') +
                    (item.discount_percentage ? ' <span class="badge bg-danger">-' + item.discount_percentage + '%</span>' : '');
            } else {
                pricingHtml = '<span class="text-muted small">Boost Placement</span>';
            }

            var perfHtml = '-';
            if (item.record_type === 'sale') {
                perfHtml = '<div><strong>' + item.claimed_units + '</strong> / ' + item.stock_quantity + '</div>' +
                    '<div class="text-muted small">Rev: <span class="text-success fw-bold">' + (item.generated_revenue || 0) + '</span></div>';
            } else {
                perfHtml = '<span class="text-muted small">' + (item.views || 0) + ' views</span>';
            }

            var durationHtml = '<span class="badge bg-info border">' + (item.days_active || 1) + ' {{ __("days") }}</span>';

            var timingHtml = '<div class="small">' +
                '<div><i class="ph ph-calendar me-1 text-muted"></i>' + (item.start_date ? item.start_date.substring(0, 10) : '-') + '</div>' +
                (item.last_bumped_at ? '<div class="text-info"><i class="ph ph-rocket-launch me-1"></i>Bump: ' + item.last_bumped_at.substring(0, 16) + '</div>' : '') +
            '</div>';

            var statusBadge = '';
            if (item.is_currently_active) {
                statusBadge = '<span class="badge bg-success">{{ __("Active") }}</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">' + (item.status ? item.status.toUpperCase() : 'ENDED') + '</span>';
            }

            return {
                id: item.id,
                item_info: itemHtml,
                type_badge: typeBadge,
                pricing: pricingHtml,
                performance: perfHtml,
                duration: durationHtml,
                timing: timingHtml,
                status_badge: statusBadge
            };
        });

        return { total: total, rows: rows };
    }

    function loadUserAnalytics(userId) {
        if (!userId) return;

        // 1. Fetch KPI metrics via AJAX
        $.ajax({
            url: '{{ route("ad-promotions.user-analytics.data") }}',
            type: 'GET',
            data: { user_id: userId },
            success: function(res) {
                if (res.error === false && res.data) {
                    var sum = res.data.summary || {};
                    var boosts = res.data.breakdown_by_boost_type || {};

                    $('#kpi_active_promotions').text(sum.active_promoted_ads || 0);
                    $('#kpi_active_sub').text((sum.active_sales_items || 0) + ' sales • ' + (sum.active_boosts || 0) + ' boosts');
                    $('#kpi_units_sold').text(sum.total_units_sold || 0);
                    $('#kpi_revenue').text(sum.total_promo_revenue || '0.00');
                    $('#kpi_active_boosts').text(sum.active_boosts || 0);

                    var bumpCount = boosts.daily_bump_up ? (boosts.daily_bump_up.active_count || 0) : 0;
                    var topCount = boosts.top_ad ? (boosts.top_ad.active_count || 0) : 0;
                    var spotCount = boosts.spotlight ? (boosts.spotlight.active_count || 0) : 0;
                    $('#kpi_boosts_sub').text(bumpCount + ' bump • ' + topCount + ' top • ' + spotCount + ' spot');
                }
            }
        });

        // 2. Refresh Table using web route
        var tableUrl = '{{ route("ad-promotions.user-analytics.history") }}';
        $('#user_promotions_table').bootstrapTable('refresh', {
            url: tableUrl,
            query: {
                user_id: userId,
                filter_type: $('#history_filter_type').val()
            }
        });
    }

    $(document).ready(function() {
        if ($.fn.select2) {
            $('#select_user_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }

        var initialUserId = $('#select_user_id').val();
        if (initialUserId) {
            loadUserAnalytics(initialUserId);
        }

        $('#select_user_id').on('change', function() {
            var uid = $(this).val();
            loadUserAnalytics(uid);
        });

        $('#history_filter_type').on('change', function() {
            $('#user_promotions_table').bootstrapTable('refresh');
        });

        $('#btn_refresh_analytics').on('click', function() {
            var uid = $('#select_user_id').val();
            loadUserAnalytics(uid);
        });
    });
</script>
@endsection