@extends('layouts.main')

@section('title')
    {{ __('Stores & Shops') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
                <p class="text-subtitle text-muted">{{ __('Manage user registered stores, shops, and locations.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">{{ __('Status Filter') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">{{ __('Verification Filter') }}</label>
                        <select id="filter_verified" class="form-select">
                            <option value="">{{ __('All Verification') }}</option>
                            <option value="1">{{ __('Verified') }}</option>
                            <option value="0">{{ __('Unverified') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" id="reset_filters">
                            <i class="ph ph-arrow-counter-clockwise me-1"></i> {{ __('Reset Filters') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="apply_filters">
                            <i class="ph ph-funnel me-1"></i> {{ __('Apply') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-body">
                @php
                    $cols = [
                        ['field' => 'no', 'title' => __('No.'), 'sortable' => false, 'align' => 'center'],
                        ['field' => 'name', 'title' => __('Store Name'), 'sortable' => true, 'formatter' => 'storeProfileFormatter'],
                        ['field' => 'owner_name', 'title' => __('Owner'), 'sortable' => false, 'formatter' => 'storeOwnerFormatter'],
                        ['field' => 'contact', 'title' => __('Contact'), 'sortable' => false, 'formatter' => 'storeContactFormatter'],
                        ['field' => 'formatted_location', 'title' => __('Location'), 'sortable' => false],
                        ['field' => 'items_count', 'title' => __('Active Items'), 'sortable' => true, 'align' => 'center'],
                        ['field' => 'status_switch', 'title' => __('Active Status'), 'sortable' => false, 'align' => 'center', 'escape' => false],
                        ['field' => 'verify_switch', 'title' => __('Verified Badge'), 'sortable' => false, 'align' => 'center', 'escape' => false],
                    ];

                    if (auth()->user()->can('store-delete')) {
                        $cols[] = ['field' => 'operate', 'title' => __('Action'), 'escape' => false, 'align' => 'center', 'sortable' => false];
                    }
                @endphp

                <x-data-table
                    id="table_list"
                    :url="route('stores.show', 1)"
                    click-to-select
                    fixed-columns
                    show-export
                    export-file-name="stores-list"
                    table-name="stores"
                    status-column="deleted_at"
                    :extra="['data-query-params' => 'storeQueryParams']"
                    :columns="$cols"
                />
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        function storeQueryParams(p) {
            return {
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                limit: p.limit,
                search: p.search,
                status: $('#filter_status').val(),
                is_verified: $('#filter_verified').val(),
            };
        }

        $('#apply_filters').on('click', function () {
            $('#table_list').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function () {
            $('#filter_status').val('');
            $('#filter_verified').val('');
            $('#table_list').bootstrapTable('refresh');
        });

        function storeProfileFormatter(value, row) {
            var logo = row.logo ? row.logo : '{{ asset("assets/images/default-profile-icon.svg") }}';
            return '<div class="d-flex align-items-center gap-2">' +
                '<img src="' + logo + '" alt="' + (row.name || '') + '" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">' +
                '<div>' +
                '<div class="font-weight-bold text-dark">' + (row.name || '-') + '</div>' +
                '<small class="text-muted"><i class="ph ph-link"></i> ' + (row.slug || '') + '</small>' +
                '</div>' +
                '</div>';
        }

        function storeOwnerFormatter(value, row) {
            return '<div>' +
                '<div class="font-weight-bold">' + (row.owner_name || '-') + '</div>' +
                '<small class="text-muted">' + (row.owner_email || '') + '</small>' +
                '</div>';
        }

        function storeContactFormatter(value, row) {
            var phone = row.contact ? (row.country_code ? row.country_code + ' ' : '') + row.contact : '-';
            var email = row.email ? row.email : '-';
            return '<div>' +
                '<div><i class="ph ph-phone text-primary me-1"></i>' + phone + '</div>' +
                '<small class="text-muted"><i class="ph ph-envelope text-secondary me-1"></i>' + email + '</small>' +
                '</div>';
        }

        // Toggle Status via AJAX
        $(document).on('change', '.toggle-store-status', function () {
            var storeId = $(this).data('id');
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var url = "{{ url('stores') }}/" + storeId;

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    status: isChecked
                },
                success: function (response) {
                    if (response.error === false) {
                        toastr.success(response.message || "{{ __('Status updated successfully') }}");
                    } else {
                        toastr.error(response.message || "{{ __('Failed to update status') }}");
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function (xhr) {
                    toastr.error("{{ __('Error updating status') }}");
                    $('#table_list').bootstrapTable('refresh');
                }
            });
        });

        // Toggle Verification via AJAX
        $(document).on('change', '.toggle-store-verify', function () {
            var storeId = $(this).data('id');
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var url = "{{ url('stores') }}/" + storeId;

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    is_verified: isChecked
                },
                success: function (response) {
                    if (response.error === false) {
                        toastr.success(response.message || "{{ __('Verification status updated') }}");
                    } else {
                        toastr.error(response.message || "{{ __('Failed to update verification') }}");
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function (xhr) {
                    toastr.error("{{ __('Error updating verification') }}");
                    $('#table_list').bootstrapTable('refresh');
                }
            });
        });
    </script>
@endsection
