@extends('layouts.main')
@section('title')
    {{__("Verification Fields")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 text-end">
                @can('seller-verification-field-create')
                    <a href="{{ route('seller-verification.create') }}" class="btn btn-primary mb-0">+ {{__("Create Verification Field")}} </a>
                @endcan
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
                                ['field'=>'name','title'=>__('Name'),'align'=>'center','sortable'=>true],
                                ['field'=>'min_length','title'=>__('Min Length'),'align'=>'center','sortable'=>true],
                                ['field'=>'max_length','title'=>__('Max Length'),'align'=>'center','sortable'=>true],
                                ['field'=>'values','title'=>__('Values'),'align'=>'center','sortable'=>true,'formatter'=>'rejectedReasonFormatter'],
                            ];
                            if(auth()->user()->canany(['seller-verification-field-update','seller-verification-field-delete'])) {
                                $cols[] = ['field'=>'operate','title'=>__('Action'),'align'=>'center','sortable'=>false,'escape'=>false,'events'=>'verificationfeildEvents'];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('verification-field.show')"
                            click-to-select
                            fixed-columns
                            filter-control
                            toolbar-id="filters"
                            show-export
                            export-file-name="verification-fields-list"
                            :extra="['data-search-align'=>'right']"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

