@extends('layouts.main')
@section('title')
    {{__("Custom Fields")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 text-end mt-2 mt-md-0">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    @can('custom-field-create')
                        <a href="{{ route('custom-fields.create', ['id' => 0]) }}" class="btn btn-primary mb-0">
                            <span class="d-none d-sm-inline">+ {{__("Create Custom Field")}}</span>
                            <span class="d-sm-none">+ {{__("Create")}}</span>
                        </a>
                        <a href="{{ route('custom-fields.bulk-upload') }}" class="btn btn-success mb-0">
                            <i class="fas fa-upload"></i> <span class="d-none d-sm-inline">{{__("Bulk Upload")}}</span><span class="d-sm-none">{{__("Upload")}}</span>
                        </a>
                    @endcan
                    @can('custom-field-update')
                        <a href="{{ route('custom-fields.bulk-update') }}" class="btn btn-warning mb-0">
                            <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">{{__("Bulk Update")}}</span><span class="d-sm-none">{{__("Update")}}</span>
                        </a>
                    @endcan
                </div>
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
                        <div id="filters">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label for="filter">{{ __("Category") }}</label>
                                    <select name="category" class="form-control select2 bootstrap-table-filter-control-category_names" aria-label="category">
                                        <option value="">{{ __("All") }}</option>
                                        @include('category.dropdowntree', ['categories' => $categories])
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="type">{{ __("Type") }}</label>
                                    <select name="type" class="form-select form-control bootstrap-table-filter-control-type" id="type">
                                        <option value="">{{ __("All") }}</option>
                                        <option value="number">{{ __("Number Input") }}</option>
                                        <option value="textbox">{{ __("Text Input") }}</option>
                                        <option value="fileinput">{{ __("File Input") }}</option>
                                        <option value="radio">{{ __("Radio") }}</option>
                                        <option value="dropdown">{{ __("Dropdown") }}</option>
                                        <option value="checkbox">{{ __("Checkboxes") }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        @php
                            $cfCols = [
                                ['field'=>'state','title'=>'','checkbox'=>true],
                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                ['field'=>'image','title'=>__('Image'),'align'=>'center','formatter'=>'imageFormatter'],
                                ['field'=>'name','title'=>__('Name'),'align'=>'center','escape'=>true,'sortable'=>true],
                                ['field'=>'category_names','title'=>__('Category'),'align'=>'center','filterName'=>'category_id','filterControl'=>'select','filterData'=>''],
                                ['field'=>'type','title'=>__('Type'),'align'=>'center','sortable'=>true,'filterName'=>'type','filterControl'=>'select','filterData'=>''],
                            ];
                            if(auth()->user()->canany(['custom-field-update','custom-field-delete'])) {
                                $cfCols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('custom-fields.show',1)"
                            toolbar-id="filters"
                            click-to-select
                            filter-control
                            fixed-columns
                            show-export
                            export-file-name="custom-field-list"
                            :extra="['data-search-align'=>'right']"
                            :columns="$cfCols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
