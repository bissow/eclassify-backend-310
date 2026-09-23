@extends('layouts.main')
@section('title')
    {{__("Tips")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can('tip-create')
                    <a class="btn btn-primary" href="{{ route('tips.create') }}">+ {{__("Add Tip")}} </a>
                @endcan
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
                        <div id="toolbar">
                            <small class="text-danger">* {{ __("To change the order, Drag the Table column Up & Down") }}</small>
                        </div>
                        <div class="table-responsive">
                            @php
                                $cols = [
                                    ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                    ['field'=>'description','title'=>__('Description'),'sortable'=>true,'formatter'=>'descriptionFormatter'],
                                ];
                                if(auth()->user()->can('tip-update')) {
                                    $cols[] = ['field'=>'status','title'=>__('Active'),'width'=>5,'sortable'=>false,'formatter'=>'statusSwitchFormatter'];
                                }
                                if(auth()->user()->canany(['tip-update','tip-delete'])) {
                                    $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false];
                                }
                            @endphp
                            <x-data-table
                                id="table_list"
                                :url="route('tips.show', 0)"
                                toolbar-id="toolbar"
                                click-to-select
                                sort-name="sequence"
                                sort-order="asc"
                                table-name="tips"
                                status-column="deleted_at"
                                show-export
                                export-file-name="tips-list"
                                :extra="['data-search-align'=>'right','data-query-params'=>'queryParams','data-reorderable-rows'=>'true','data-use-row-attr-func'=>'true','data-detail-view'=>'true','data-detail-formatter'=>'detailFormatter']"
                                :columns="$cols"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Description Modal -->
    <div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="descriptionModalLabel">{{ __('Full Description') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Full description will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
