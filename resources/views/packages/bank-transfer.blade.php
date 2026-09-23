@extends('layouts.main')

@section('title')
    {{ __('Bank Transfers') }}
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
                                        ['field'=>'user.name','title'=>__('User Name'),'align'=>'center','sortable'=>false,'formatter' => 'userProfileFormatter'],
                                        ['field'=>'amount','title'=>__('Amount'),'align'=>'center','sortable'=>false],
                                        ['field'=>'payment_status','title'=>__('Payment Status'),'align'=>'center','sortable'=>true],
                                        ['field'=>'payment_receipt','title'=>__('Payment Reciept'),'align'=>'center','sortable'=>false,'formatter'=>'imageFormatter'],
                                        ['field'=>'created_at','title'=>__('Created At'),'align'=>'center','sortable'=>true],
                                        ['field'=>'operate','title'=>__('Action'),'escape'=>false,'align'=>'center','sortable'=>false],
                                    ];
                                @endphp
                                <x-data-table
                                    id="table_list"
                                    :url="route('package.bank-transfer.show')"
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
        <div id="editStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{ __('Status') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="edit-form" action="" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <select name="payment_status" class="form-select" id="verification_status" aria-label="status">
                                        <option value="succeed">{{ __("Approved") }}</option>
                                        <option value="rejected">{{ __("Rejected") }}</option>
                                    </select>
                                </div>
                            </div>
                            <input type="submit" value="{{ __("Save") }}" class="btn btn-primary mt-3">
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </section>
@endsection
