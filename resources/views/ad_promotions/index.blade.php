@extends('layouts.main')

@section('title')
    {{ __('Promoted Ads Management') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Track and manage advertisements promoted with Daily Bump Up, Top Ad, and Spotlight features.') }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <!-- Quick Stats Cards -->
        <div class="row mb-3 g-3">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-trend-up fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total Promoted') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalPromotions ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-info text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-rocket-launch fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Daily Bump Ups') }}</p>
                            <h4 class="mb-0 fw-bold text-info">{{ $totalDailyBumps ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-warning text-dark rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-arrow-fat-line-up fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Top Ads') }}</p>
                            <h4 class="mb-0 fw-bold text-warning">{{ $totalTopAds ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-sparkle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Spotlights') }}</p>
                            <h4 class="mb-0 fw-bold text-success">{{ $totalSpotlights ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('Promotion Type') }}</label>
                        <select id="filter_promo_type" class="form-select">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="daily_bump_up">{{ __('Daily Bump Up') }}</option>
                            <option value="top_ad">{{ __('Top Ad') }}</option>
                            <option value="spotlight">{{ __('Spotlight') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('Status') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="expired">{{ __('Expired') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="reset_filters">
                            <i class="ph ph-arrow-counter-clockwise me-1"></i> {{ __('Reset') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="apply_filters">
                            <i class="ph ph-funnel me-1"></i> {{ __('Apply Filter') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-body">
                <table
                    id="ad_promotions_table"
                    data-toggle="table"
                    data-url="{{ route('ad-promotions.show') }}"
                    data-side-pagination="server"
                    data-pagination="true"
                    data-page-list="[10, 25, 50, 100]"
                    data-search="true"
                    data-show-refresh="true"
                    data-query-params="adPromoQueryParams"
                    class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="item">{{ __('Advertisement') }}</th>
                            <th data-field="seller">{{ __('Seller') }}</th>
                            <th data-field="type">{{ __('Promotion Applied') }}</th>
                            <th data-field="start_date" data-sortable="true">{{ __('Start Date') }}</th>
                            <th data-field="end_date" data-sortable="true">{{ __('End Date') }}</th>
                            <th data-field="last_bumped_at">{{ __('Last Bumped') }}</th>
                            <th data-field="status">{{ __('Status') }}</th>
                            <th data-field="actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
@endsection

@section('js')
<script>
    function adPromoQueryParams(params) {
        return {
            limit: params.limit,
            offset: params.offset,
            sort: params.sort,
            order: params.order,
            search: params.search,
            promotion_type: $('#filter_promo_type').val(),
            status: $('#filter_status').val()
        };
    }

    $(document).ready(function() {
        $('#apply_filters').on('click', function() {
            $('#ad_promotions_table').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function() {
            $('#filter_promo_type').val('');
            $('#filter_status').val('');
            $('#ad_promotions_table').bootstrapTable('refresh');
        });

        // Delete Promoted Ad
        $(document).on('click', '.delete-ad-promotion', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: '{{ __("Remove Promotion?") }}',
                text: '{{ __("This advertisement promotion badge and priority will be removed.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, remove it!") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("ad-promotions") }}/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (res.error === false) {
                                showSuccessToast(res.message);
                                $('#ad_promotions_table').bootstrapTable('refresh');
                            } else {
                                showErrorToast(res.message);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
