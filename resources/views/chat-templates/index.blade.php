@extends('layouts.main')
@section('title')
    {{ __('Chat Template') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-muted mb-0">{{ __('Manage and Create Pre-Defined Chat Templates') }}</p>
            </div>
            <div class="col-12 col-md-6 text-end mt-2 mt-md-0">
                @can('chat-template-create')
                    <a href="{{ route('chat-templates.create') }}" class="btn btn-primary mb-0">
                        <span class="d-none d-sm-inline">+ {{ __('Create Chat Template') }}</span>
                        <span class="d-sm-none">+ {{ __('Create') }}</span>
                    </a>
                @endcan
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
                                    <label for="filter">{{ __('Category') }}</label>
                                    <select name="category" class="form-control select2 bootstrap-table-filter-control-category_id" aria-label="category">
                                        <option value="">{{ __('All') }}</option>
                                        @include('category.dropdowntree', ['categories' => $categories])
                                    </select>
                                </div>
                            </div>
                        </div>
                        @php
                            $ctCols = [
                                ['field' => 'id', 'title' => __('ID'), 'align' => 'center', 'sortable' => true, 'visible' => false],
                                ['field' => 'name', 'title' => __('Name'), 'sortable' => true, 'escape' => true],
                                ['field' => 'category_names', 'title' => __('Categories'), 'formatter' => 'chatTemplateCategoryFormatter', 'escape' => false, 'filterName' => 'category_id', 'filterControl' => 'select', 'filterData' => ''],
                                ['field' => 'customer_questions_count', 'title' => __('Matrices and Timeline'), 'formatter' => 'chatTemplateMetricsFormatter', 'escape' => false],
                                ['field' => 'status', 'title' => __('Activity'), 'align' => 'center', 'sortable' => true, 'formatter' => 'statusSwitchFormatter', 'escape' => false],
                            ];
                            if (auth()->user()->canany(['chat-template-update', 'chat-template-delete'])) {
                                $ctCols[] = ['field' => 'operate', 'title' => __('Action'), 'align' => 'center', 'escape' => false, 'sortable' => false];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('chat-templates.show', 1)"
                            toolbar-id="filters"
                            filter-control
                            fixed-columns
                            show-export
                            export-file-name="chat-template-list"
                            table-name="chat_templates"
                            :extra="['data-search-align' => 'right']"
                            :columns="$ctCols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        function chatTemplateCategoryFormatter(value, row) {
            if (!Array.isArray(value) || value.length === 0) {
                return '-';
            }
            var cls = row.is_global ? 'ct-pill ct-pill-success' : 'ct-pill';
            return value.map(function (name) {
                return '<span class="' + cls + '">' + $('<div>').text(name).html() + '</span>';
            }).join(' ');
        }

        function chatTemplateMetricsFormatter(value, row) {
            return '<span class="ct-metrics">' + row.customer_questions_count + ' ' + '{{ __('Messages') }}' +
                '<span class="ct-dot"></span>' + row.seller_questions_count + ' ' + '{{ __('Replies') }}' + '</span>';
        }
    </script>
@endsection
