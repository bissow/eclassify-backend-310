@extends('layouts.main')

@section('title')
    {{ __('Slider') }}
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
        <div class="row">
            @can('slider-create')
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <form method="POST" action="{{ route('slider.store') }}" enctype="multipart/form-data" class="create-form" id="slider-form" data-pre-submit-function="customValidation">
                                @csrf
                                <div class="row mt-1">
                                    <div class="form-group col-md-12 col-sm-12 mandatory">
                                        <label for="image" class="col-md-12 col-sm-12 col-12 form-label">{{ __('Image') }}</label>
                                        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png" data-parsley-required="true">
                                        @if (count($errors) > 0)
                                            @foreach ($errors->all() as $error)
                                                <div class="alert alert-danger error-msg">{{ $error }}</div>
                                            @endforeach
                                        @endif
                                    </div>

                                    <label for="items" class="col-md-12 col-sm-12 col-form-label">{{ __('Item') }}</label>
                                    <div class="col-md-12 col-sm-12">
                                        <select name="item" class="form-select form-control-sm select2" id="items" aria-label="items" data-parsley-errors-messages-disabled>
                                            @if (isset($items))
                                                <option value="" selected>{{__("Select Advertisement")}}</option>
                                                @foreach ($items as $row)
                                                    <option value="{{ $row->id }}">{{ $row->name }} </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center align-items-center mt-3">
                                        <h6 class="mb-0">{{__("OR")}}</h6>

                                    </div>
                                    <div class="col-md-12">
                                        <div class="col-md-12 form-group">
                                            <label for="category" class="form-label">{{ __('Category') }}</label>
                                            <select name="category_id" id="category" class="form-select form-control select2" data-placeholder="{{__("Select Category")}}">
                                                <option value="">{{__("Select a Category")}}</option>
                                                @include('category.dropdowntree', ['categories' => $categories])
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center align-items-center mt-3">
                                        <h6 class="mb-0">{{__("OR")}}</h6>

                                    </div>
                                    <div class="col-md-12 col-sm-12">
                                        <label for="link" class="col-md-12 col-sm-12 col-form-label ">{{ __('Third Party Link') }}</label>
                                        <input type="text" name="link" id="link" value="{{ old('link', '') }}" class="form-control " placeholder="{{ __('link') }}" data-parsley-errors-messages-disabled>
                                    </div>
        
                                    <div class="col-md-12 form-group mt-3">
                                        
                                            <label for="country" class="mandatory form-label">{{ __('Country') }}</label>
                                            <select class="form-control select2" id="country" name="country_id" >
                                                <option value="">{{ __('--Select Country--') }}</option>
                                                @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                       
                                    </div>
                                    <div class="col-md-12 form-group mt-3">
                                            <label for="state" class="mandatory form-label">{{ __('State') }}</label>
                                            <select class="form-control select2" id="state" name="state_id" >
                                                <option value="">{{ __('--Select State--') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 form-group mt-3">
                                            <label for="city" class="mandatory form-label">{{ __('City') }}</label>
                                            <select class="form-control select2" id="city" name="city_id" >
                                                <option value="">{{ __('--Select City--') }}</option>
                                        </select>
                                    </div>
                                    <div class="invalid-form-error-message"></div>
                                    <div class="col-12 d-flex justify-content-end mt-2" style="padding: 1% 2%;">
                                        <button type="submit" class="btn btn-primary me-1 mb-1">{{ __('Save') }}</button>
                                    </div>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan


            <div class="{{\Illuminate\Support\Facades\Auth::user()->can('slider-create') ? "col-md-8" : "col-md-12"}}">
                <div class="card">
                    <div class="card-content">
                        <div class="row mt-1">
                            <div class="card-body">
                                <div class="form-group row ">
                                    <div class="col-12">
                                        @php
                                            $cols = [
                                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                                ['field'=>'image','title'=>__('Image'),'align'=>'center','sortable'=>false,'formatter'=>'imageFormatter'],
                                                ['field'=>'model_type','title'=>__('Type'),'align'=>'center','sortable'=>true,'formatter'=>'typeFormatter'],
                                                ['field'=>'model.name','title'=>__('Name'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'model_name']],
                                                ['field'=>'country.name','title'=>__('Country'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'country_name']],
                                                ['field'=>'state.name','title'=>__('State'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'state_name']],
                                                ['field'=>'city.name','title'=>__('City'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'city_name']],
                                                ['field'=>'third_party_link','title'=>__('Third Party Link'),'align'=>'center','sortable'=>true],
                                            ];
                                            if(auth()->user()->can('slider-delete')) {
                                                $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false,'align'=>'center','sortable'=>false];
                                            }
                                        @endphp
                                        <x-data-table
                                            id="table_list"
                                            :url="route('slider.show',1)"
                                            click-to-select
                                            fixed-columns
                                            show-export
                                            export-file-name="slider-list"
                                            :extra="['data-query-params'=>'queryParams','data-id-field'=>'id']"
                                            :columns="$cols"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


