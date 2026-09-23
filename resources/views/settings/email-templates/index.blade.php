@extends('layouts.main')

@section('title')
    {{ __('Email Templates') }}
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
        <div class="card">
            <div class="card-body">
                @php
                    $cols = [
                        ['field'=>'display_name','title'=>__('Template Name'),'sortable'=>true],
                        ['field'=>'description','title'=>__('Description'),'sortable'=>true],
                        ['field'=>'status','title'=>__('Status'),'sortable'=>true,'escape'=>false],
                        ['field'=>'operate','title'=>__('Action'),'escape'=>false],
                    ];
                @endphp
                <x-data-table
                    id="table_list"
                    :url="route('settings.email-templates.list')"
                    sort-name="display_name"
                    sort-order="asc"
                    :columns="$cols"
                />
            </div>
        </div>
    </section>
@endsection
