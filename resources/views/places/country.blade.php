@extends('layouts.main')

@section('title')
    {{ __('Countries') }}
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
       <div class="buttons d-flex justify-content-end">
            @can('country-create')
            <a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#countryModal">
                + {{ __("Import Countries") }}
            </a>
            @endcan
            @can('country-update')
            <a class="btn btn-primary ms-2" href="{{ route('countries.translation') }}">
                 <i class="fa fa-language me-2"></i> {{ __("Translate Countries") }}</a>
            @endcan
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                ['field'=>'emoji','title'=>__('Flag')],
                                ['field'=>'status','title'=>__('Status'),'sortable'=>true,'escape'=>false,'formatter'=>'statusSwitchFormatter', 'align' => 'center'],
                                ['field'=>'operate','title'=>__('Action'),'escape'=>false],
                            ];
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('countries.show',1)"
                            click-to-select
                            fixed-columns
                            filter-control
                            toolbar-id="filters"
                            table-name="countries"
                            status-column="status"
                            show-export
                            export-file-name="country-list"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div id="countryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{ __('Import Country Data') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="create-form" action="{{route('countries.import')}}" method="POST" data-success-function="successFunction">
                            @csrf

                            <div class="row">
                                <div class="col-12 mb-3">
                                    <input type="text" id="countrySearchInput" class="form-control" placeholder="{{ __('Search countries...') }}">
                                </div>
                                <div class="col-12 mb-2">
                                    <input type="checkbox" id="selectAllCountries" class="form-check-input">
                                    <label for="selectAllCountries" class="form-label">{{ __('Select All') }}</label>
                                </div>

                                @foreach($countries as $country)
                                    <div class="col-md-3">
                                        <input type="checkbox" id="{{$country['id']}}" name="countries[]" value="{{$country['id']}}" {{$country['is_already_exists'] ? "checked disabled" : ""}} class="form-check-input">
                                        <label for="{{$country['id']}}" class="form-label">{{$country['name'].' '.$country['emoji']}}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="text-end">
                                <input type="submit" value="{{__("Save")}}" class="btn btn-primary mt-3">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </section>
@endsection
@section('js')
    <script>
        function successFunction() {
            $('#countryModal').modal('hide');
        }
    </script>
@endsection
