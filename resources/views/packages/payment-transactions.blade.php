@extends('layouts.main')

@section('title')
    {{ __('Payment Transactions') }}
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
                                        ['field'=>'amount','title'=>__('Amount'),'align'=>'center','sortable'=>false],
                                        ['field'=>'payment_gateway','title'=>__('Payment Gateway'),'align'=>'center'],
                                        ['field'=>'payment_status','title'=>__('Payment Status'),'align'=>'center','sortable'=>true],
                                        ['field'=>'order_id','title'=>__('Order Id'),'visible'=>false,'align'=>'center','sortable'=>true],
                                        ['field'=>'created_at','title'=>__('Created At'),'align'=>'center','sortable'=>true],
                                        ['field'=>'operate','title'=>__('Action'),'align'=>'center','sortable'=>false,'escape'=>false],
                                    ];
                                @endphp
                                <x-data-table
                                    id="table_list"
                                    :url="route('package.payment-transactions.show')"
                                    click-to-select
                                    fixed-columns
                                    table-name="packages"
                                    show-export
                                    export-file-name="user-package-list"
                                    :extra="['data-search-align'=>'right','data-query-params'=>'queryParams']"
                                    :columns="$cols"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
