@extends('layouts.main')

@section('title')
    {{__("Staff Management")}}
@endsection

@section('content')
    <div class="page-header">
        <h3 class="page-title">
            {{__('Staff Management')}}
        </h3>
        @can('role-create')
            <div class="buttons">
                <a class="btn btn-primary" href="{{ route('staff.create') }}"> {{ __('Create New Staff') }}</a>
            </div>
        @endcan
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-12">
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true,'align'=>'center'],
                                ['field'=>'name','title'=>__('Name'),'sortable'=>true,'align'=>'center'],
                                ['field'=>'email','title'=>__('Email'),'sortable'=>true,'align'=>'center'],
                            ];
                            if(auth()->user()->can('staff-update')) {
                                $cols[] = ['field'=>'status','title'=>__('Status'),'formatter'=>'statusSwitchFormatter','sortable'=>false,'align'=>'center'];
                            }
                            if(auth()->user()->canany(['staff-update','staff-delete'])) {
                                $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false,'events'=>'staffEvents','align'=>'center'];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('staff.show',1)"
                            click-to-select
                            fixed-columns
                            table-name="users"
                            status-column="deleted_at"
                            show-export
                            export-file-name="staff-list"
                            :extra="['data-query-params'=>'queryParams']"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    @can('staff-update')
        <!-- EDIT USER MODEL MODEL -->
        <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{__("Edit Staff")}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="form-horizontal edit-form" method="POST" data-parsley-validate>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_role" class="form-label col-12 ">{{__("Role")}}</label>
                                        <select name="role_id" id="edit_role" class="form-control" data-parsley-required="true">
                                            <option value="">--{{__("Select Role")}}--</option>
                                            @foreach ($roles as $role)
                                                <option value="{{$role->id}}">{{$role->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_name" class="form-label text-center">{{__("Name")}}</label>
                                        <input type="text" id="edit_name" class="form-control col-12" placeholder="Name" name="name" data-parsley-required="true">
                                    </div>
                                </div>

                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_email" class="form-label text-center">{{__("Email")}}</label>
                                        <input type="email" id="edit_email" class="form-control col-12" placeholder="email" name="email" data-parsley-required="true">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">{{__("Close")}}</button>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">{{__("Save")}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RESET PASSWORD MODEL -->
        <div id="resetPasswordModel" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{__("Password Reset")}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="form-horizontal edit-form" data-parsley-validate role="form" method="post">
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group mandatory">
                                    <label for="new_password" class="form-label">{{__("New Password")}}</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="{{__("New Password")}}" data-parsley-minlength="8" data-parsley-uppercase="1" data-parsley-lowercase="1" data-parsley-number="1" data-parsley-special="1" data-parsley-required="true">
                                </div>
                                <div class="form-group mandatory">
                                    <label for="confirm_password" class="form-label">{{__("Confirm Password")}}</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="{{__("Confirm Password")}}" data-parsley-equalto="#new_password" minlength="4" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">{{__("Close")}}</button>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">{{__("Save")}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
