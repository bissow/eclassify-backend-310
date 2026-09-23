@extends('layouts.main')

@section('title')
    {{ __('States') }}
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
        <div class="row m-3">
            <div class="col-12 text-end">
                @can('state-update')
                    <a href="{{ route('states.translation') }}" class="btn btn-primary">
                        <i class="fa fa-language me-2"></i> {{ __('Translate States') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @php
                            $filtersHtml = '<div class="row"><div class="col-12 col-md-12">
                                <label for="filter_country">'.__("Country").'</label>
                                <select class="form-control bootstrap-table-filter-control-country_name" id="filter_country">
                                    <option value="">'.__("All").'</option>';
                            foreach($countries as $country) {
                                $filtersHtml .= '<option value="'.$country->id.'">'.$country->name.'</option>';
                            }
                            $filtersHtml .= '</select></div></div>';
                        @endphp

                        <x-data-table
                            id="table_list"
                            :url="route('states.show',1)"
                            toolbar-id="filters"
                            :toolbar-slot="$filtersHtml"
                            click-to-select
                            filter-control
                            fixed-columns
                            show-export
                            export-file-name="state-list"
                            table-name="states"
                            status-column="status"
                            :columns="[
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                ['field'=>'country_name','title'=>__('Country'),'sortable'=>true,'filterName'=>'country_id','filterControl'=>'select','filterData'=>''],
                                ['field'=>'country.emoji','title'=>__('Flag')],
                                ['field'=>'status','title'=>__('Status'),'sortable'=>true,'escape'=>false,'formatter'=>'statusSwitchFormatter', 'align' => 'center'],
                                ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'escape'=>false],
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
