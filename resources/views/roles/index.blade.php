@extends('layouts.main')

@section('title')
    {{__('Role Management')}}
@endsection

@section('content')

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{__('Role Management')}}
            </h3>
            @can('role-create')
                <div class="buttons">
                    <a class="btn btn-primary" href="{{ route('roles.create') }}"> {{ __('Create New Role') }}</a>
                </div>
            @endcan
        </div>

        @can('role-list')
            <div class="row grid-margin">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @php
                                $cols = [
                                    ['field'=>'id','title'=>__('ID'),'sortable'=>true,'visible'=>false],
                                    ['field'=>'no','title'=>__('No.')],
                                    ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                    ['field'=>'operate','title'=>__('Action'),'escape'=>false],
                                ];
                            @endphp
                            <x-data-table
                                id="table_list"
                                :url="route('roles.list')"
                                click-to-select
                                :fixed-number="2"
                                fixed-columns
                                show-export
                                :export-file-name="'roles-list-' . date('d-m-y')"
                                :extra="['data-maintain-selected'=>'true','data-export-data-type'=>'all','data-query-params'=>'queryParams']"
                                :columns="$cols"
                            />
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

@endsection
