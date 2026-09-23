@extends('layouts.main')

@section('title')
    {{ __('User Subscriptions') }}
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">

                        {{-- <div class="row " id="toolbar"> --}}

                        <div class="row">
                            <div class="col-12">

                                @php
                                    $cols = [
                                        ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                        ['field'=>'user.name','title'=>__('User Name'),'align'=>'center','sortable'=>false, 'formatter' => 'userProfileFormatter'],
                                        ['field'=>'package.name','title'=>__('Package Name'),'align'=>'center','sortable'=>false],
                                        ['field'=>'start_date','title'=>__('Start Date'),'align'=>'center'],
                                        ['field'=>'end_date','title'=>__('End Date'),'align'=>'center','formatter'=>'unlimitedBadgeFormatter','sortable'=>true],
                                        ['field'=>'status','title'=>__('Status'),'align'=>'center','formatter'=>'userPackageStatusBadgeFormatter'],
                                        ['field'=>'total_limit','title'=>__('Total Limit'),'align'=>'center','formatter'=>'unlimitedBadgeFormatter','sortable'=>true],
                                        ['field'=>'used_limit','title'=>__('Used Limit'),'align'=>'center','sortable'=>true],
                                    ];
                                @endphp
                                @php
                                    $selectedStatus = request('status');
                                    $statusFilter = '<div class="col-lg-2">
                                        <label for="filter_status">'.__('Status').'</label>
                                        <select id="filter_status" class="form-select" onchange="$(\'#table_list\').bootstrapTable(\'refresh\')">
                                            <option value="">'.__('All').'</option>
                                            <option value="active"'.($selectedStatus === 'active' ? ' selected' : '').'>'.__('Active').'</option>
                                            <option value="expired"'.($selectedStatus === 'expired' ? ' selected' : '').'>'.__('Expired').'</option>
                                        </select>
                                    </div>';
                                @endphp
                                <x-data-table
                                    id="table_list"
                                    :url="route('package.users.show')"
                                    click-to-select
                                    fixed-columns
                                    table-name="packages"
                                    show-export
                                    export-file-name="user-package-list"
                                    toolbar-id="user-package-toolbar"
                                    :toolbar-slot="$statusFilter"
                                    :extra="['data-search-align'=>'right','data-query-params'=>'userPackageQueryParams']"
                                    :columns="$cols"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
