@extends('layouts.main')
@section('title')
    {{__("User Queries")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body">
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                ['field'=>'name','title'=>__('Name'),'align'=>'center','sortable'=>true],
                                ['field'=>'email','title'=>__('Email'),'align'=>'center'],
                                ['field'=>'subject','title'=>__('Subject'),'align'=>'center','sortable'=>true],
                                ['field'=>'message','title'=>__('Message'),'align'=>'center','sortable'=>true,'formatter'=>'descriptionFormatter'],
                            ];
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('contact-us.show')"
                            click-to-select
                            show-export
                            export-file-name="contact-us-list"
                            :extra="['data-search-align'=>'right','data-query-params'=>'queryParams','data-use-row-attr-func'=>'true']"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
