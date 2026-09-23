@extends('layouts.main')

@section('title')
    {{ __('Currencies') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h4>@yield('title')</h4>
            </div>
            <div class="buttons col-12 col-md-6 d-flex justify-content-end">
                <a class="btn btn-primary" href="{{ route('currency.create') }}">{{ __('Create Currency') }}</a>
                {{-- <div class="col-12 col-md-6 d-flex justify-content-end">
                <a class="btn btn-primary me-2" href="{{ route('currency.create') }}">{{ __('Create Currency') }}</a>
            </div> --}}
            </div>
        </div>
    @endsection

    @section('content')
        <section class="section">
            {{-- <div class="buttons d-flex justify-content-end">

        </div> --}}

            {{-- {{ print_r($currencies) }} --}}
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            @php
                                $cols = [
                                    ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                    ['field'=>'iso_code','title'=>__('ISO Code'),'sortable'=>true],
                                    ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                    ['field'=>'symbol','title'=>__('Symbol'),'sortable'=>true],
                                    ['field'=>'symbol_position','title'=>__('Symbol Position'),'sortable'=>true],
                                    ['field'=>'country.name','title'=>__('Country'),'sortable'=>true,'attrs'=>['data-sort-name'=>'country_name']],
                                ];
                                if(auth()->user()->canany(['currency-update','currency-delete'])) {
                                    $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false];
                                }
                            @endphp
                            <x-data-table
                                id="table_list"
                                :url="route('currency.show', 1)"
                                click-to-select
                                fixed-columns
                                filter-control
                                toolbar-id="filters"
                                table-name="currencies"
                                status-column="deleted_at"
                                show-export
                                export-file-name="currency-list"
                                :columns="$cols"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endsection
