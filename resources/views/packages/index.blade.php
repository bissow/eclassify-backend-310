@extends('layouts.main')

@section('title')
    {{ __('Subscription Packages') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 text-end">
                @can('advertisement-listing-package-create')
                    <a class="btn btn-primary me-2" href="{{ route('package.create') }}">{{ __('Create Subscription Package') }}</a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('js')
@can('user-package-list')
<script>
var currentSubscriberPackageId = null;

function subscribersQueryParams(p) {
    return $.extend({}, p, { package_id: currentSubscriberPackageId, status: 'active' });
}

function loadSubscribers(el) {
    currentSubscriberPackageId = $(el).data('package-id');
    $('#subscribersModalLabel').text('{{ __('Active Subscribers') }} — ' + $(el).data('package-name'));
    $('#subscribers_table').bootstrapTable('refresh', { silent: false });
}

$('#subscribersModal').on('hidden.bs.modal', function () {
    currentSubscriberPackageId = null;
    $('#subscribers_table').bootstrapTable('load', { total: 0, rows: [] });
});
</script>
@endcan

<script>
var currentPackageView = 'active'; // 'all' | 'active' | 'discontinued'

function packageQueryParams(p) {
    var params = $.extend({}, p);
    params.status = null;
    if (currentPackageView === 'active') {
        params.is_discontinued = 0;
    } else if (currentPackageView === 'discontinued') {
        params.is_discontinued = 1;
    } else if (currentPackageView === 'inactive'){
        params.is_discontinued = 0;
        params.status = 0;
    }
    var typeVal = $('#type').val();
    if (typeVal) {
        params.filter = JSON.stringify({ type: typeVal });
    }
    return params;
}

$(function () {
    function setActiveBtn(view) {
        $('#btn-all-packages, #btn-active-packages, #btn-discontinued-packages, #btn-inactive-packages').removeClass('active');
        if (view === 'all') $('#btn-all-packages').addClass('active');
        else if (view === 'active') $('#btn-active-packages').addClass('active');
        else if (view === 'discontinued') $('#btn-discontinued-packages').addClass('active');
        else if (view === 'inactive') $('#btn-inactive-packages').addClass('active');
    }

    function applyColumnVisibility(view) {
        var showDiscontinued = (view === 'discontinued' || view === 'all');
        var showActiveOnly   = (view === 'active' || view === 'all');
        var showInactiveOnly = (view === 'inactive' || view === 'all');

        if (showDiscontinued) {
            $('#table_list').bootstrapTable('showColumn', 'discontinued_at');
            $('#table_list').bootstrapTable('showColumn', 'active_subscribers');
        } else {
            $('#table_list').bootstrapTable('hideColumn', 'discontinued_at');
            $('#table_list').bootstrapTable('hideColumn', 'active_subscribers');
        }

        if (showActiveOnly || showInactiveOnly) {
            $('#table_list').bootstrapTable('showColumn', 'total_revenue');
            $('#table_list').bootstrapTable('showColumn', 'total_subscribers');
        } else {
            $('#table_list').bootstrapTable('hideColumn', 'total_revenue');
            $('#table_list').bootstrapTable('hideColumn', 'total_subscribers');
        }

        // package_status column always visible
    }

    $('#btn-active-packages').addClass('active');
    applyColumnVisibility('active');

    $('#btn-all-packages').on('click', function () {
        currentPackageView = 'all';
        setActiveBtn('all');
        applyColumnVisibility('all');
        $('#table_list').bootstrapTable('refresh');
    });

    $('#btn-active-packages').on('click', function () {
        currentPackageView = 'active';
        setActiveBtn('active');
        applyColumnVisibility('active');
        $('#table_list').bootstrapTable('refresh');
    });

    $('#btn-discontinued-packages').on('click', function () {
        currentPackageView = 'discontinued';
        setActiveBtn('discontinued');
        applyColumnVisibility('discontinued');
        $('#table_list').bootstrapTable('refresh');
    });

    $('#btn-inactive-packages').on('click', function () {
        currentPackageView = 'inactive';
        setActiveBtn('inactive');
        applyColumnVisibility('inactive');
        $('#table_list').bootstrapTable('refresh');
    });

    $('#type').on('change', function () {
        $('#table_list').bootstrapTable('refresh');
    });
});

function packageStatusFormatter(value, row) {
    if (value == 1) {
        return '<span class="badge bg-danger">{{ __('Discontinued') }}</span>';
    }
    if(row.status == 0){
        return '<span class="badge bg-warning">{{ __('Inactive') }}</span>';
    }
    return '<span class="badge bg-success">{{ __('Active') }}</span>';
}

function activeSubscribersBadgeFormatter(value) {
    if (value > 0) {
        return '<span class="badge bg-warning text-dark">' + value + ' {{ __('active') }}</span>';
    }
    return '<span class="badge bg-secondary">{{ __('None') }}</span>';
}

$(document).on('click', '.discontinue-btn', function () {
    var btn = $(this);
    var name = btn.data('name');
    var subscribers = parseInt(btn.data('subscribers')) || 0;
    var url = btn.data('url');

    $('#discontinuePackageName').text(name);
    var msg = '{{ __("This package will stop appearing for new purchases. Existing subscribers will keep access until their subscription expires. This action cannot be undone.") }}';
    if (subscribers > 0) {
        msg += ' <strong>{{ __("There are currently") }} ' + subscribers + ' {{ __("active subscriber(s).") }}</strong>';
    }
    $('#discontinueWarningMsg').html(msg);
    $('#confirmDiscontinueBtn').data('url', url);
    $('#discontinueModal').modal('show');
});

$('#confirmDiscontinueBtn').on('click', function () {
    var url = $(this).data('url');
    $.ajax({
        url: url,
        type: 'PUT',
        data: { _token: '{{ csrf_token() }}' },
        success: function (res) {
            $('#discontinueModal').modal('hide');
            $('#table_list').bootstrapTable('refresh');
            if (res.status) {
                toastMessage(res.message || '{{ __("Package discontinued successfully.") }}', 'success');
            } else {
                toastMessage(res.message || '{{ __("Something went wrong.") }}', 'error');
            }
        },
        error: function () {
            $('#discontinueModal').modal('hide');
            toastMessage('{{ __("Something went wrong.") }}', 'error');
        }
    });
});
</script>
@endsection

@section('content')
    @canany(['advertisement-listing-package-update','featured-advertisement-package-update'])
    <div class="modal fade" id="discontinueModal" tabindex="-1" aria-labelledby="discontinueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="discontinueModalLabel">
                        <i class="ph ph-warning me-1"></i> {{ __('Discontinue Package') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-semibold mb-2">{{ __('You are about to discontinue:') }} <span id="discontinuePackageName" class="text-danger"></span></p>
                    <div class="alert alert-warning" id="discontinueWarningMsg"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirmDiscontinueBtn">{{ __('Yes, Discontinue') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endcanany

    @can('user-package-list')
    <div class="modal fade" id="subscribersModal" tabindex="-1" aria-labelledby="subscribersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscribersModalLabel">{{ __('Active Subscribers') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $subCols = [
                            ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                            ['field'=>'user.name','title'=>__('User Name'),'align'=>'center'],
                            ['field'=>'start_date','title'=>__('Start Date'),'align'=>'center','sortable'=>true],
                            ['field'=>'end_date','title'=>__('End Date'),'align'=>'center','formatter'=>'unlimitedBadgeFormatter','sortable'=>true],
                            ['field'=>'status','title'=>__('Status'),'align'=>'center','formatter'=>'userPackageStatusBadgeFormatter','escape'=>false],
                            ['field'=>'total_limit','title'=>__('Total Limit'),'align'=>'center','formatter'=>'unlimitedBadgeFormatter','sortable'=>true],
                            ['field'=>'used_limit','title'=>__('Used Limit'),'align'=>'center','sortable'=>true],
                        ];
                    @endphp
                    <x-data-table
                        id="subscribers_table"
                        :url="route('package.users.show')"
                        :columns="$subCols"
                        table-name="subscribers"
                        :show-columns="false"
                        :show-refresh="false"
                        :show-export="false"
                        :extra="['data-query-params'=>'subscribersQueryParams']"
                    />
                </div>
            </div>
        </div>
    </div>
    @endcan

    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-primary all w-100" id="btn-all-packages">
                                    {{ __('All Packages') }}
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-success w-100" id="btn-active-packages">
                                    {{ __('Active Packages') }}
                                </button>
                            </div>

                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-warning w-100" id="btn-inactive-packages">
                                    {{ __('Inactive Packages') }}
                                </button>
                            </div>

                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-danger w-100" id="btn-discontinued-packages">
                                    {{ __('Discontinued Packages') }}
                                </button>
                            </div>
                        </div>

                        <div id="filters">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label for="type">{{ __('Package Type') }}</label>
                                    <select name="type" class="form-control" aria-label="type" id="type">
                                        <option value="">{{ __('All') }}</option>
                                        <option value="item_listing">{{ __('Item Listing (Ads)') }}</option>
                                        <option value="advertisement">{{ __('Advertisement (Featured Ads)') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        @php
                            $pkgCols = [
                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                ['field'=>'icon','title'=>__('Image'),'align'=>'center','formatter'=>'imageFormatter'],
                                ['field'=>'name','title'=>__('Name'),'align'=>'center','escape'=>true],
                                ['field'=>'type','title'=>__('Type'),'align'=>'center','formatter'=>'packageTypeFormatter'],
                                ['field'=>'category_names','title'=>__('Categories'),'align'=>'center','formatter'=>'categoryNamesFormatter'],
                                ['field'=>'price','title'=>__('Price'),'align'=>'center','sortable'=>true],
                                ['field'=>'discount_in_percentage','title'=>__('Discount (%)'),'align'=>'center','sortable'=>true],
                                ['field'=>'final_price','title'=>__('Final Price'),'align'=>'center','sortable'=>true],
                                ['field'=>'duration','title'=>__('Package Duration'),'align'=>'center','sortable'=>true],
                                ['field'=>'total_revenue','title'=>__('Total Revenue'),'align'=>'center','sortable'=>true, 'visible' => false],
                                ['field'=>'total_subscribers','title'=>__('Active Subscribers'),'align'=>'center','sortable'=>true, 'visible' => false],
                                ['field'=>'active_subscribers','title'=>__('Remaining Subscribers'),'align'=>'center','sortable'=>true,'visible'=>false,'formatter'=>'activeSubscribersBadgeFormatter','escape'=>false],
                                ['field'=>'discontinued_at','title'=>__('Discontinued On'),'align'=>'center','sortable'=>true, 'visible'=>false],
                                ['field'=>'is_discontinued','title'=>__('Package Status'),'align'=>'center','sortable'=>false, 'formatter'=>'packageStatusFormatter','escape'=>false],
                                ['field'=>'is_reel_allowed','title'=>__('Is Video Ads Allowed ?'),'align'=>'center','visible'=>false,'sortable'=>true,'formatter'=>'yesNoFormatter'],
                            ];
                            if(auth()->user()->canany(['advertisement-listing-package-update','featured-advertisement-package-update'])) {
                                $pkgCols[] = ['field'=>'status','title'=>__('Status'),'align'=>'center','sortable'=>true,'formatter'=>'statusSwitchFormatter','escape'=>false];
                            }
                            if(auth()->user()->canany(['advertisement-listing-package-update','advertisement-listing-package-delete','featured-advertisement-package-update','featured-advertisement-package-delete','user-package-list'])) {
                                $pkgCols[] = ['field'=>'operate','title'=>__('Action'),'align'=>'center','escape'=>false,'sortable'=>false];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('package.show', 1)"
                            toolbar-id="filters"
                            fixed-columns
                            show-export
                            export-file-name="package-list"
                            table-name="packages"
                            :extra="['data-search-align'=>'right','data-query-params'=>'packageQueryParams']"
                            :columns="$pkgCols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
