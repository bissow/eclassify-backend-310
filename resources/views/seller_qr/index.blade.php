@extends('layouts.main')

@section('title')
    {{ __('Seller QR Codes') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Manage generated seller store QR codes, standees, and scan metrics.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                @can('seller-qr-setting')
                    <a href="{{ route('seller-qr.settings') }}" class="btn btn-outline-primary">
                        <i class="ph ph-sliders me-1"></i> {{ __('QR Settings & Standee Customization') }}
                    </a>
                @endcan
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
                            <i class="ph ph-qr-code fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total QR Codes') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalQrCodes ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-check-circle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Active QRs') }}</p>
                            <h4 class="mb-0 fw-bold text-success">{{ $activeQrCodes ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-info text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-scan fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total Scans') }}</p>
                            <h4 class="mb-0 fw-bold text-info">{{ number_format($totalScans ?? 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-trophy fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Top Scanned') }}</p>
                            <h5 class="mb-0 fw-bold text-truncate" style="max-width: 140px;" title="{{ $topScanned?->store?->name ?? 'N/A' }}">
                                {{ $topScanned?->store?->name ?? __('None yet') }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="statusFilter" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active Only') }}</option>
                            <option value="inactive">{{ __('Inactive Only') }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="table_list"
                        data-toggle="table"
                        data-url="{{ route('seller-qr.show') }}"
                        data-click-to-select="true"
                        data-side-pagination="server"
                        data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100]"
                        data-search="true"
                        data-toolbar="#toolbar"
                        data-show-columns="true"
                        data-show-refresh="true"
                        data-trim-on-search="false"
                        data-sort-name="id"
                        data-sort-order="desc"
                        data-mobile-responsive="true"
                        data-query-params="queryParams">
                        <thead>
                            <tr>
                                <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                <th data-field="store" data-sortable="false">{{ __('Store / Seller') }}</th>
                                <th data-field="token" data-sortable="false">{{ __('QR Token') }}</th>
                                <th data-field="scans" data-sortable="true" data-align="center">{{ __('Scans') }}</th>
                                <th data-field="last_scanned" data-sortable="true">{{ __('Last Scanned') }}</th>
                                <th data-field="status" data-sortable="false" data-align="center">{{ __('Status') }}</th>
                                <th data-field="created_at" data-sortable="true">{{ __('Created') }}</th>
                                <th data-field="operate" data-sortable="false" data-align="center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
    function queryParams(p) {
        return {
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            limit: p.limit,
            search: p.search,
            status: $('#statusFilter').val()
        };
    }

    $('#statusFilter').on('change', function () {
        $('#table_list').bootstrapTable('refresh');
    });

    $(document).on('click', '.toggle-status-btn', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var url = "{{ url('seller-qr/toggle-status') }}/" + id;

        Swal.fire({
            title: "{{ __('Are you sure?') }}",
            text: "{{ __('You want to change the status of this QR code?') }}",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "{{ __('Yes, update it!') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        if (!response.error) {
                            showSuccessToast(response.message);
                            $('#table_list').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function () {
                        showErrorToast("{{ __('An error occurred') }}");
                    }
                });
            }
        });
    });
</script>
@endsection
