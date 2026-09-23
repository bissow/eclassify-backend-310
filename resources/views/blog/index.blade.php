@extends('layouts.main')
@section('title')
    {{__("Blogs")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can('blog-create')
                    <a class="btn btn-primary" href="{{ route('blog.create') }}">+ {{__("Add blog")}} </a>
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
                        <div id="filters row">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label for="filter_category_id">{{ __("Category") }}</label>
                                    <select id="filter_category_id" class="form-control select-search" data-placeholder="{{ __('All') }}">
                                        <option value="">{{ __("All") }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                ['field'=>'title','title'=>__('Title'),'align'=>'center','sortable'=>true],
                                ['field'=>'category.name','title'=>__('Category'),'align'=>'center','sortable'=>true],
                                ['field'=>'slug','title'=>__('Slug'),'align'=>'center','sortable'=>true],
                                ['field'=>'description','title'=>__('Description'),'align'=>'center','escape'=>false,'formatter'=>'truncateDescription'],
                                ['field'=>'image','title'=>__('Image'),'align'=>'center','formatter'=>'imageFormatter'],
                                ['field'=>'tags','title'=>__('Tags'),'align'=>'center'],
                                ['field'=>'views','title'=>__('Views'),'align'=>'center', 'sortable' => true],
                                ['field'=>'useful_count','title'=>__('Useful'),'align'=>'center','sortable'=>true],
                                ['field'=>'not_useful_count','title'=>__('Not Useful'),'align'=>'center','sortable'=>true],
                                ['field'=>'useful_percentage','title'=>__('Useful %'),'align'=>'center','sortable'=>false,'formatter'=>'usefulPercentageFormatter', 'visible' => false],
                                ['field'=>'created_at','title'=>__('Date'),'align'=>'center'],
                            ];
                            if(auth()->user()->canany(['blog-update','blog-delete'])) {
                                $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('blog.show', 0)"
                            toolbar-id="filters"
                            click-to-select
                            table-name="blogs"
                            class="translatable-table"
                            :extra="['data-search-align'=>'right','data-query-params'=>'blogQueryParams','data-use-row-attr-func'=>'true']"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
    function blogQueryParams(p) {
        return {
            ...p,
            category_id: $('#filter_category_id').val()
        };
    }

    function usefulPercentageFormatter(value, row) {
        return value + '%';
    }

    $('#filter_category_id').on('change', function() {
        $('#table_list').bootstrapTable('refresh');
    });
</script>
@endsection
