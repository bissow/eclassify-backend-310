@extends('layouts.main')
@section('title')
    {{__("Seller Verification")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                ['field'=>'user_name','title'=>__('User'),'align'=>'center','sortable'=>true,'formatter' => 'userProfileFormatter'],
                                ['field'=>'status','title'=>__('Status'),'align'=>'center','sortable'=>true,'filterControl'=>'select','formatter'=>'sellerverificationStatusFormatter'],
                            ];
                            if(auth()->user()->canany(['seller-verification-request-update'])) {
                                $cols[] = ['field'=>'operate','title'=>__('Action'),'align'=>'center','sortable'=>false,'escape'=>false,'events'=>'verificationEvents'];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('verification_requests.show')"
                            click-to-select
                            fixed-columns
                            filter-control
                            toolbar-id="filters"
                            show-export
                            export-file-name="verification_requests-list"
                            :extra="['data-search-align'=>'right']"
                            :columns="$cols"
                        />
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
                                    <select name="status" class="form-select" id="verification_status" aria-label="status">
                                        <option value="pending">{{ __("Pending") }}</option>
                                        <option value="approved">{{ __("Approved") }}</option>
                                        <option value="rejected">{{ __("Rejected") }}</option>
                                    </select>
                                </div>
                                <div class="form-group " id="rejectionReasonField" style="display: none;">
                                    <label for="rejection_reason">{{ __("Rejection Reason") }} </label><span class="text-danger">*</span>
                                    <textarea id="rejection_reason" name="rejection_reason" class="form-control"></textarea>
                                </div>
                            </div>
                            <input type="submit" value="{{ __("Save") }}" class="btn btn-primary mt-3">
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{ __('Verification Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="center" id="verification_fields"></div>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>

    </section>
@endsection
