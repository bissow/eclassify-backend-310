@extends('layouts.main')
@section('title')
    {{ __('Home Screen Sections') }}
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
        {{-- Section Toggles --}}
        <div class="card">
            <div class="card-header">
                <h4>{{ __('Section Visibility') }}</h4>
            </div>
            <div class="card-body mt-3">
                <div class="row">
                    @php
                        $sectionLabels = [
                            'all_categories' => __('All Categories'),
                            'slider' => __('Slider'),
                            'popular_categories' => __('Popular Categories'),
                            'featured_section' => __('Featured Section'),
                            'all_ads' => __('All Advertisement')
                        ];
                    @endphp
                    @foreach ($sections as $section)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border shadow-sm h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <strong>{{ $sectionLabels[$section->section_type] ?? $section->section_type }}</strong>
                                    @can('home-screen-section-update')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input section-toggle" type="checkbox" role="switch"
                                                data-id="{{ $section->id }}"
                                                {{ $section->is_active ? 'checked' : '' }}>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Popular Categories Management --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>{{ __('Popular Categories') }}</h4>
                <div>
                    @can('home-screen-section-update')
                        @if ($popularCategories->count() > 1)
                            <a href="{{ route('home-screen-section.popular-categories.order') }}"
                                class="btn btn-sm btn-info">
                                <i class="fas fa-sort"></i> {{ __('Change Order') }}
                            </a>
                        @endif
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @can('home-screen-section-update')
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light mt-3">
                                <h6 class="mb-1">
                                    <i class="fas fa-plus-circle text-primary me-1"></i>
                                    {{ __('Add Category') }}
                                </h6>
                                <p class="text-muted small mb-3">{{ __('Search and select a category to add to the popular list') }}</p>
                                <form id="add-popular-category-form" action="{{ route('home-screen-section.popular-categories.store') }}" method="POST">
                                    @csrf
                                    <div class="d-flex row">
                                        <div class="col-md-6">
                                            <label for="p_category">{{ __('Category') }}</label>
                                            <select name="category_id" id="p_category" class="form-control select2 bootstrap-table-filter-control-category" aria-label="category" data-placeholder="{{ __('All') }}">
                                                <option value="">{{ __('All') }}</option>
                                                @include('category.dropdowntree', ['categories' => $categories])
                                            </select>
                                        </div>
                                        <div class="col-md-3 mt-4">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-plus me-1"></i> {{ __('Add') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan

                @php
                    $cols = [
                        ['field'=>'sequence','title'=>__('Sequence'),'sortable'=>true],
                        ['field'=>'image','title'=>__('Image'),'escape'=>false],
                        ['field'=>'name','title'=>__('Name')],
                        ['field'=>'type','title'=>__('Type'),'escape'=>false],
                    ];
                    if(auth()->user()->can('home-screen-section-update')) {
                        $cols[] = ['field'=>'action','title'=>__('Action'),'escape'=>false];
                    }
                @endphp
                <x-data-table
                    id="popular_categories_table"
                    :url="route('home-screen-section.popular-categories.list')"
                    sort-name="sequence"
                    sort-order="asc"
                    :columns="$cols"
                />
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        // Section toggle
        $('.section-toggle').on('change', function() {
            let id = $(this).data('id');
            let isActive = $(this).is(':checked') ? 1 : 0;

            ajaxRequest('POST', '{{ route("home-screen-section.toggle") }}', JSON.stringify({
                id: id,
                is_active: isActive,
                _token: '{{ csrf_token() }}'
            }), null, function(response) {
                showSuccessToast(response.message);
            }, function(response) {
                showErrorToast(response.message);
            });
        });

        // Add popular category form
        $('#add-popular-category-form').on('submit', function(e) {
            e.preventDefault();
            let formElement = $(this);
            let submitButtonElement = $(this).find(':submit');
            let url = $(this).attr('action');
            let data = new FormData(this);

            function successCallback(response) {
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }

            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
        });

    </script>
@endsection
