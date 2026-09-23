@extends('layouts.main')

@section('title')
    {{__("Custom Fields")}} / {{__("Sub Category")}}
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
        <div class="row">
            <div class="col-md-10">
                <div class="buttons text-start">
                    <a href="{{ route('category.index', $p_id) }}" class="btn btn-primary">< {{__("Back To Category")}} </a>
                    <a href="{{ route('custom-fields.create', ['id' => $cat_id]) }}" class="btn btn-primary">+ {{__("Create Custom Field")}} / {{ $category_name }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    @php
                        $cols = [
                            ['field'=>'state','title'=>'','checkbox'=>true],
                            ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                            ['field'=>'image','title'=>__('Image'),'align'=>'center','formatter'=>'imageFormatter'],
                            ['field'=>'name','title'=>__('Custom Field'),'align'=>'center','sortable'=>true],
                            ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false],
                        ];
                    @endphp
                    <x-data-table
                        id="table_list"
                        :url="route('category.custom-fields.show', $cat_id)"
                        click-to-select
                        fixed-columns
                        :extra="['data-search-align'=>'right','data-query-params'=>'queryParams']"
                        :columns="$cols"
                    />
                </div>
            </div>
        </div>
    </section>
@endsection
