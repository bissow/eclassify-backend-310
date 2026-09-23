@extends('layouts.main')

@section('title')
    {{ __('Send Notification') }}
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
    <div class="row">
        <section class="section">
            @can('notification-create')
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <form action="{{ route('notification.store') }}" class="create-form needs-validation" method="post" data-parsley-validate enctype="multipart/form-data" data-success-function="successFunction">
                                <div class="card-body">
                                    <textarea id="user_id" name="user_id" style="visibility: hidden;position: absolute;" aria-label="user_id"></textarea>
                                    <div class="form-group row">
                                        <div class="col-md-12 col-sm-12">
                                            <label for="send_to" class="form-label">{{ __('Select User') }}</label> <span class="text-danger">*</span>
                                            <select id="send_to" name="send_to" class="form-control w-100" required>
                                                <option value="all">{{ __('All') }}</option>
                                                <option value="selected">{{ __('Selected Only') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-12 col-sm-12">
                                            <label for="title" class="form-label">{{ __('Title') }} </label> <span class="text-danger">*</span>
                                            <input name="title" id="title" type="text" class="form-control" placeholder={{ __('Title') }} required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <label for="message" class="form-label">{{ __('Message') }}</label> <span class="text-danger">*</span>
                                            <textarea id="message" name="message" class="form-control" placeholder={{ __('Message') }} required></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-12 col-sm-12">
                                            <div class="form-check">
                                                <input id="include_image" name="include_image" type="checkbox" class="form-check-input">
                                                <label for="include_image" class="form-check-label">{{ __('Include Image') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row" id="show_image" style="display: none">
                                        <div class="col-md-12 col-sm-12">
                                            <label class="form-label">{{ __('Image') }}</label>
                                            <input id="file" name="file" type="file" accept="image/*" class="form-control">
                                            <p style="display: none" id="img_error_msg" class="badge rounded-pill bg-danger"></p>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-12 col-sm-12">
                                            <label for="item_id" class="form-label">{{ __('Advertisement') }} </label>
                                            <select name="item_id" class="select2 form-select form-control-sm" data-parsley-minSelect='1' id="item_id">
                                                <option value=""> {{ __('Select Advertisement') }} </option>
                                                @foreach ($item_list as $row)
                                                    <option value="{{ $row->id }}" data-parametertypes='{{ $row->name }}'>{{ $row->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12 d-flex justify-content-end">
                                        <button class="btn btn-primary" type="submit" name="submit">{{ __('Submit') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        @php
                                            $userCols = [
                                                ['field'=>'state','title'=>'','checkbox'=>true],
                                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                                ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                                ['field'=>'mobile','title'=>__('Number'),'sortable'=>true],
                                            ];
                                        @endphp
                                        <x-data-table
                                            id="user_notification_list"
                                            :url="route('customer.show',1)"
                                            click-to-select
                                            fixed-columns
                                            :extra="['data-query-params'=>'notificationUserList']"
                                            :columns="$userCols"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="toolbar">
                                @can('notification-delete')
                                    <a href="{{route('notification.batch.delete')}}" class="btn btn-danger btn-sm btn-icon text-white" id="delete_multiple" title="Delete Notification"><em class='fa fa-trash'></em></a>
                                @endcan
                            </div>
                            @php
                                $notifCols = [];
                                if(auth()->user()->can('notification-delete')) {
                                    $notifCols[] = ['field'=>'state','title'=>'','checkbox'=>true];
                                }
                                $notifCols[] = ['field'=>'id','title'=>__('ID'),'sortable'=>true];
                                $notifCols[] = ['field'=>'title','title'=>__('Title'),'sortable'=>true];
                                $notifCols[] = ['field'=>'message','title'=>__('Message'),'sortable'=>true];
                                $notifCols[] = ['field'=>'image','title'=>__('Image'),'formatter'=>'imageFormatter'];
                                $notifCols[] = ['field'=>'send_to','title'=>__('Send To'),'sortable'=>true];
                                if(auth()->user()->can('notification-delete')) {
                                    $notifCols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false];
                                }
                            @endphp
                            <x-data-table
                                id="table_list"
                                :url="route('notification.show',1)"
                                toolbar-id="toolbar"
                                click-to-select
                                fixed-columns
                                show-export
                                export-file-name="notification-list"
                                :columns="$notifCols"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function successFunction(response) {
            setTimeout(function () {
                window.location.reload();
            }, 1500);
        }
        // function responseHandler(res) {
        //     $.each(res.rows, function (i, row) {
        //         row.state = $.inArray(row.id, selections) !== -1
        //     })
        //     return res;
        // }
    </script>
@endsection
