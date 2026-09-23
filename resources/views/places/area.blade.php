@extends('layouts.main')
@section('title')
    {{__("Area")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        @can('area-create')
         <div class="row m-3">
            <div class="col-12 text-end">
                <a href="{{ route('areas.translation') }}" class="btn btn-primary">
                    <i class="fa fa-language me-2"></i> {{ __('Translate Areas') }}
                </a>
            </div>
        </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('Add Area') }}</h3>
                        </div>
                        <div class="card-body">
                <form class="create-form" action="{{route('area.create')}}" method="POST" data-parsley-validate enctype="multipart/form-data">
                    @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="country" class="mandatory form-label">{{ __('Country') }}</label> <span class="text-danger">*</span>
                                            <select class="form-control select2" id="country" name="country_id" required>
                                                <option value="">{{ __('--Select Country--') }}</option>
                                                @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="state" class="mandatory form-label">{{ __('State') }}</label> <span class="text-danger">*</span>
                                            <select class="form-control select2" id="state" name="state_id" required>
                                                <option value="">{{ __('--Select State--') }}</option>
                                        </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="city" class="mandatory form-label">{{ __('City') }}</label> <span class="text-danger">*</span>
                                            <select class="form-control select2" id="city" name="city_id" required>
                                                <option value="">{{ __('--Select City--') }}</option>
                                        </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="areas-container">
                                    <div class="row area-input-group mb-3">
                                        <div class="col-md-4 form-group">
                                            <label for="name" class="mandatory form-label mt-2">{{ __("Area Name") }}</label> <span class="text-danger">*</span>
                                        <div class="d-flex">
                                                <input type="text" name="name[]" class="form-control me-2" placeholder="{{ __("Enter Area name") }}" required>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label for="latitude" class="mandatory form-label mt-2">{{ __("Latitude") }}</label>
                                            <div class="d-flex mb-2">
                                                <input type="text" name="latitude[]" class="form-control me-2" placeholder="{{ __("Enter Latitude") }}">
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label for="longitude" class="mandatory form-label mt-2">{{ __("Longitude") }}</label>
                                            <div class="d-flex mb-2">
                                                <input type="text" name="longitude[]" class="form-control me-2" placeholder="{{ __("Enter Longitude") }}">
                                                <button type="button" class="btn btn-danger remove-area-button ms-2">-</button>
                                                <button type="button" class="btn btn-secondary add-area-button ms-2">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                         <div class="alert alert-warning" role="alert">
                                           {{ __('Please select the correct location on the map. Do not place the marker for any other location.') }}
                                        </div>
                                        <div id="map"></div>
                                    </div>
                                </div>

                                <div class="form-group mt-3 text-end">
                                    <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                                    <a href="{{ route('area.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        @php
                            $filtersHtml = '<div class="row">
                                <div class="col-12 col-md-4">
                                    <label for="filter_country">'.__("Country").'</label>
                                    <select class="form-control bootstrap-table-filter-control-country.name" id="filter_country">
                                        <option value="">'.__("All").'</option>';
                            foreach($countries as $country) {
                                $filtersHtml .= '<option value="'.$country->id.'">'.$country->name.'</option>';
                            }
                            $filtersHtml .= '</select></div>
                                <div class="col-12 col-md-4">
                                    <label for="filter_state">'.__("State").'</label>
                                    <select class="form-control bootstrap-table-filter-control-state.name" id="filter_state">
                                        <option value="">'.__("All").'</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="filter_city">'.__("City").'</label>
                                    <select name="city_id" class="form-control bootstrap-table-filter-control-city.name" id="filter_city">
                                        <option value="">'.__("All").'</option>
                                    </select>
                                </div></div>';
                        @endphp

                        <x-data-table
                            id="table_list"
                            :url="route('area.show',1)"
                            toolbar-id="filters"
                            :toolbar-slot="$filtersHtml"
                            click-to-select
                            filter-control
                            fixed-columns
                            table-name="areas"
                            status-column="status"
                            :columns="[
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'name','title'=>__('Name'),'sortable'=>false],
                                ['field'=>'country.name','title'=>__('Country'),'sortable'=>false,'filterName'=>'country_id','filterControl'=>'select','filterData'=>''],
                                ['field'=>'state.name','title'=>__('State'),'sortable'=>false,'filterName'=>'state_id','filterControl'=>'select','filterData'=>''],
                                ['field'=>'city.name','title'=>__('City'),'sortable'=>false,'filterName'=>'city_id','filterControl'=>'select','filterData'=>''],
                                ['field'=>'longitude','title'=>__('Longitude'),'sortable'=>false],
                                ['field'=>'latitude','title'=>__('Latitude'),'sortable'=>false],
                                ['field'=>'status','title'=>__('Status'),'sortable'=>true,'escape'=>false,'formatter'=>'statusSwitchFormatter', 'align' => 'center'],
                                ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'escape'=>false,'events'=>'areaEvents'],
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
        @can('area-update')
        <!-- EDIT MODEL MODEL -->
            <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myModalLabel1">{{ __('Edit Area') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-form" class="edit-form" action="" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label for="country" class="mandatory form-label">{{__("Country")}}</label>
                                        <select name="country_id" id="edit_country" class="form-control country form-select" data-placeholder="{{__("Select Country")}}">
                                            <option value="">Select Country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4 form-group">
                                        <label for="state" class="mandatory form-label">{{__("State")}}</label>
                                        <select name="state_id" id="edit_state" class="form-control form-select" data-placeholder="{{__("Select State")}}">
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="city" class="mandatory form-label">{{__("City")}}</label>
                                        <select name="city_id" id="edit_city" class="form-control form-select" data-placeholder="{{__("Select City")}}">
                                            <option value="">{{ __('--Select City--') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="edit_name" class="mandatory form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" id="edit_name" class="form-control" data-parsley-required="true">
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="latitude" class="mandatory form-label">Latitude</label>
                                        <div class="d-flex mb-2">
                                            <input type="text" name="latitude" id="edit_latitude" class="form-control me-2" placeholder="{{__("Enter Latitude")}}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="longitude" class="mandatory form-label">Longitude</label>
                                        <div class="d-flex mb-2">
                                            <input type="text" name="longitude" id="edit_longitude" class="form-control me-2" placeholder="{{__("Enter Longitude")}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div id="edit_map" style="height: 400px;"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">{{ __('Close') }}</button>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('Save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </section>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        // Initialize map with default coordinates
        const map = window.mapUtils.initializeMap('map', 23.245131597411042, 69.66773986816408);

        // Function to update area coordinates
        window.updateAreaCoordinates = function(lat, lng) {
            // Get the last area input group (the one being edited)
            const $areaGroup = $('.area-input-group').last();

            // Update latitude and longitude fields
            $areaGroup.find('input[name="latitude[]"]').val(lat);
            $areaGroup.find('input[name="longitude[]"]').val(lng);
        };
    });
</script>
@endsection
