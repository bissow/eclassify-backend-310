@extends('layouts.main')

@section('title')
    {{ __("Blog Category") }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end">
                @can('blog-create')
                    <a class="btn btn-primary" href="{{ route('blog-category.create') }}">+ {{__("Add Blog Category")}}</a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @php
                                        $cols = [
                                            ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                            ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                            ['field'=>'slug','title'=>__('Slug'),'sortable'=>true],
                                        ];
                                        if(auth()->user()->can('blog-update')) {
                                            $cols[] = ['field'=>'is_active','title'=>__('Active'),'width'=>100,'sortable'=>false,'formatter'=>'statusSwitchFormatter'];
                                        }
                                        if(auth()->user()->canany(['blog-update','blog-delete'])) {
                                            $cols[] = ['field'=>'operate','title'=>__('Action'),'width'=>100,'escape'=>false];
                                        }
                                    @endphp
                                    <x-data-table
                                        id="table_list"
                                        :url="route('blog-category.show', 1)"
                                        click-to-select
                                        fixed-columns
                                        show-export
                                        export-file-name="blog-categories-list"
                                        :extra="['data-query-params'=>'queryParams', 'data-table'=>'blog_categories', 'data-status-column'=>'is_active']"
                                        :columns="$cols"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
