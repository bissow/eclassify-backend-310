@extends('layouts.main')
@section('title')
    {{__("Create Feature Section")}}
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
        @can('feature-section-create')
            <div class="row">
                <form action="{{ route('feature-section.store') }}" class="create-form" method="POST" enctype="multipart/form-data" data-parsley-validate data-success-function="successFunction">
                    @csrf
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">{{__("Add Feature Section")}}</div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mt-2" id="langTabs" role="tablist">
                                    @foreach($languages as $key => $lang)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if($key == 0) active @endif" id="tab-{{ $lang->id }}" data-bs-toggle="tab" data-bs-target="#lang-{{ $lang->id }}" type="button" role="tab">
                                                {{ $lang->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content mt-3">
                                    @foreach($languages as $key => $lang)
                                        <div class="tab-pane fade @if($key == 0) show active @endif" id="lang-{{ $lang->id }}" role="tabpanel">
                                            <input type="hidden" name="languages[]" value="{{ $lang->id }}">

                                            <div class="form-group">
                                                <label>{{ __('Title') }} ({{ $lang->name }})</label>
                                                <input type="text" 
                                                    name="title[{{ $lang->id }}]" 
                                                    class="form-control @if($lang->id == 1) feature-section-name @endif" 
                                                    placeholder="{{ __('Title') }}"
                                                    value=""
                                                    @if($lang->id == 1) data-parsley-required="true" @endif>
                                            </div>

                                            @if($lang->id == 1)
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="slug" class="mandatory form-label">{{ __('Slug') }}</label>
                                                            <input type="text" name="slug" id="slug" class="form-control feature-section-slug" data-parsley-required="true">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 form-group mandatory">
                                                        <label for="filter" class=" form-label">{{ __('Filters') }}</label>
                                                        <select id="filter" name="filter" class="form-control select-search" data-placeholder="{{ __('Select Filters') }}">
                                                            <option value=""></option>
                                                            <option value="most_liked">{{__("Most Liked")}}</option>  
                                                            <option value="most_viewed">{{__("Most Viewed")}}</option>
                                                            <option value="price_criteria">{{__("Price Criteria")}}</option>
                                                            <option value="category_criteria">{{__("Category Criteria")}}</option>
                                                            <option value="featured_ads">{{__("Featured Ads")}}</option>
                                                            <option value="item_selection">{{__("Item Selection")}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div id="category_criteria" class="form-group mandatory" style="display: none;">
                                                            <label for="category_id" class="form-label fw-semibold">{{ __('Category') }}</label>
                                                            <small class="text-muted d-block mb-1">{{ __('Items from selected categories will appear in this section') }}</small>
                                                            <select name="category_id[]" class="form-control select-search" multiple id="category_id" data-placeholder="{{__("Select Category")}}" style="width : 100%" required>
                                                                @include('category.dropdowntree', ['categories' => $categories])
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div id="item_selection_criteria" class="col-md-12 mt-2" style="display: none;">
                                                        <div class="row align-items-start">
                                                            <div class="col-md-5 form-group">
                                                                <label class="form-label fw-semibold">{{ __('Filter by Category') }}</label>
                                                                <small class="text-muted d-block mb-1">{{ __('Select categories to load their items') }}</small>
                                                                <select id="item_filter_category" class="form-control select-search" multiple data-placeholder="{{ __('Select Category') }}" style="width:100%">
                                                                </select>
                                                            </div>
                                                            <div class="col-md-1 d-flex align-items-center justify-content-center" style="padding-top:48px">
                                                                <span id="item_loading_spinner" style="display:none"><i class="fa fa-spinner fa-spin text-primary"></i></span>
                                                                <span id="item_arrow" class="text-muted"><i class="fa fa-arrow-right"></i></span>
                                                            </div>
                                                            <div class="col-md-6 form-group">
                                                                <label class="form-label fw-semibold">{{ __('Select Items') }} <span id="item_count_badge" class="badge bg-primary ms-1" style="display:none">0</span></label>
                                                                <small class="text-muted d-block mb-1">{{ __('Choose specific items to show in this section') }}</small>
                                                                <select name="item_id[]" id="item_id" class="form-control select-search" multiple data-placeholder="{{ __('Select Items') }}" style="width:100%">
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="price_criteria" style="display:none;">
                                                        <div class="row align-items-end">
                                                            <div class="col-md-4">
                                                                <div class="col-md-12 form-group mandatory">
                                                                    <label for="min_price" class="form-label fw-semibold">{{ __('Minimum Price') }}</label>
                                                                    <small class="text-muted d-block mb-1">{{ __('Items priced above this value') }}</small>
                                                                    <input type="number" name="min_price" id="min_price" class="form-control" required min="1">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1 d-flex align-items-center justify-content-center pb-2">
                                                                <span class="text-muted"><i class="fa fa-arrows-h"></i></span>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="col-md-12 form-group mandatory">
                                                                    <label for="max_price" class="form-label fw-semibold">{{ __('Maximum Price') }}</label>
                                                                    <small class="text-muted d-block mb-1">{{ __('Items priced below this value') }}</small>
                                                                    <input type="number" name="max_price" id="max_price" class="form-control" required min="1" data-parsley-gt="#min_price" data-parsley-error-message="Max Price should be Greater than Min Price">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row form-group mandatory mt-3">
                                                    <label for="Field Name" class=" form-label">{{ __('Select Style for APP Section') }}</label>
                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_1" required/>
                                                            <img src="{{asset('/images/app_styles/style_1.png')}}" height="115px" width="130px" alt="style_1" class="style_image">
                                                        </label>
                                                    </div>
                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_2"/>
                                                            <img src="{{asset('/images/app_styles/style_2.png')}}" height="115px" width="130px" alt="style_2" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_3"/>
                                                            <img src="{{asset('/images/app_styles/style_3.png')}}" height="115px" width="130px" alt="style_3" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_4"/>
                                                            <img src="{{asset('/images/app_styles/style_4.png')}}" height="115px" width="130px" alt="style_4" class="style_image">
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit" name="submit">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @endcan

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <small class="text-danger">* {{__("To change the order, Drag the Table column Up & Down")}}</small>
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'style','title'=>__('Style'),'formatter'=>'styleImageFormatter'],
                                ['field'=>'title','title'=>__('Title'),'sortable'=>true],
                                ['field'=>'filter','title'=>__('Filters'),'sortable'=>true,'formatter'=>'filterTextFormatter','escape'=>false],
                                ['field'=>'sequence','title'=>__('Sequence'),'sortable'=>true],
                                ['field'=>'min_price','title'=>__('Min Price'),'sortable'=>true,'visible'=>false],
                                ['field'=>'max_price','title'=>__('Max price'),'sortable'=>true,'visible'=>false],
                                ['field'=>'values_text','title'=>__('Value'),'sortable'=>false,'formatter'=>'featureSectionValueFormatter','escape'=>false],
                            ];
                            if(auth()->user()->canany(['feature-section-update','feature-section-delete'])) {
                                $cols[] = ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false,'events'=>'featuredSectionEvents'];
                            }
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('feature-section.show',1)"
                            click-to-select
                            fixed-columns
                            table-name="feature_sections"
                            show-export
                            sort-name="sequence"
                            sort-order="asc"
                            export-file-name="featured-section-list"
                            :extra="['data-search-align'=>'right','data-query-params'=>'queryParams','data-reorderable-rows'=>'true','data-use-row-attr-func'=>'true']"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>

        @can('feature-section-update')
        <!-- EDIT MODEL MODEL -->
            <div id="editModal" class="modal fade modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="" class="form-horizontal edit-form" enctype="multipart/form-data" method="POST" novalidate>
                            <div class="modal-header">
                                <h5 class="modal-title" id="myModalLabel1">{{ __('Edit feature Section') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <ul class="nav nav-tabs" id="editLangTabs" role="tablist">
                                    @foreach($languages as $key => $lang)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if($key == 0) active @endif" id="edit-tab-{{ $lang->id }}" data-bs-toggle="tab" data-bs-target="#edit-lang-{{ $lang->id }}" type="button" role="tab">
                                                {{ $lang->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content mt-3">
                                    @foreach($languages as $key => $lang)
                                        <div class="tab-pane fade @if($key == 0) show active @endif" id="edit-lang-{{ $lang->id }}" role="tabpanel">
                                            <input type="hidden" name="languages[]" value="{{ $lang->id }}">

                                            <div class="form-group">
                                                <label>{{ __('Title') }} ({{ $lang->name }})</label>
                                                <input type="text" 
                                                    name="title[{{ $lang->id }}]" 
                                                    class="form-control @if($lang->id == 1) edit-feature-section-name @endif" 
                                                    placeholder="{{ __('Title') }}"
                                                    id="edit_title_{{$lang->id}}"
                                                    value=""
                                                    @if($lang->id == 1) data-parsley-required="true" @endif>
                                            </div>

                                            @if($lang->id == 1)
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="slug" class="mandatory form-label">{{ __('Slug') }}</label>
                                                            <input type="text" name="slug" id="edit_slug" class="form-control edit-feature-section-slug" data-parsley-required="true">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 form-group mandatory">
                                                        <label for="edit_filter" class="form-label">{{ __('Filters') }}</label>
                                                        <select id="edit_filter" name="filter" class="form-control select-search">
                                                            <option value="most_liked">{{__("Most Liked")}}</option>
                                                            <option value="most_viewed">{{__("Most Viewed")}}</option>
                                                            <option value="price_criteria">{{__("Price Criteria")}}</option>
                                                            <option value="category_criteria">{{__("Category Criteria")}}</option>
                                                            <option value="featured_ads">{{__("Featured Ads")}}</option>
                                                            <option value="item_selection">{{__("Item Selection")}}</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="edit_price_criteria" class="row" style="display: none;">
                                                    <div class="col-md-12 mb-2">
                                                        <div class="alert alert-info py-2 px-3" style="font-size:0.85rem">
                                                            <i class="fa fa-lock me-1"></i> {{ __('Price range cannot be changed after creation.') }}
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="edit_min_price" class="form-label fw-semibold">{{ __('Minimum Price') }}</label>
                                                            <input type="number" name="min_price" id="edit_min_price" class="form-control" required min="1" readonly>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="edit_max_price" class="form-label fw-semibold">{{ __('Maximum Price') }}</label>
                                                            <input type="number" name="max_price" id="edit_max_price" class="form-control" required min="1" data-parsley-gt="#edit_min_price" data-parsley-error-message="Max Price should be Greater than Min Price" readonly>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div id="edit_category_criteria" class="form-group mandatory" style="display: none;">
                                                        <div class="alert alert-info py-2 px-3 mb-2" style="font-size:0.85rem">
                                                            <i class="fa fa-lock me-1"></i> {{ __('Category selection cannot be changed after creation.') }}
                                                        </div>
                                                        <label for="edit_category_id" class="form-label fw-semibold">{{ __('Category') }}</label>
                                                        <select name="category_id[]" class="select2" id="edit_category_id" data-placeholder="{{__("Select Category")}}" multiple style="width:100%">
                                                            @include('category.dropdowntree', ['categories' => $categories])
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="edit_item_selection_criteria" class="col-md-12 mt-2" style="display: none;">
                                                    <div class="alert alert-info py-2 px-3 mb-2" style="font-size:0.85rem">
                                                        <i class="fa fa-lock me-1"></i> {{ __('Item selection cannot be changed after creation.') }}
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12 form-group">
                                                            <label class="form-label fw-semibold">{{ __('Selected Items') }} <span id="edit_item_count_badge" class="badge bg-primary ms-1" style="display:none">0</span></label>
                                                            <select name="item_id[]" id="edit_item_id" class="select2" multiple data-placeholder="{{ __('Loading...') }}" style="width:100%">
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row form-group mandatory mt-3">
                                                    <label for="Field Name" class=" form-label">{{ __('Select Style for APP Section') }}</label>
                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_1" required/>
                                                            <img src="{{asset('/images/app_styles/style_1.png')}}" height="115px" width="130px" alt="style_1" class="style_image">
                                                        </label>
                                                    </div>
                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_2" required/>
                                                            <img src="{{asset('/images/app_styles/style_2.png')}}" height="115px" width="130px" alt="style_2" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_3" required/>
                                                            <img src="{{asset('/images/app_styles/style_3.png')}}" height="115px" width="130px" alt="style_3" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_4" required/>
                                                            <img src="{{asset('/images/app_styles/style_4.png')}}" height="115px" width="130px" alt="style_4" class="style_image">
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
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
        @endcan
    </section>
@endsection
@section('js')
    <script>
        {{--TODO: @include was not loading data 2nd time. So added this temporary solution here--}}
        let category_options = $('#category_id option').clone();
        $('#edit_category_id').append(category_options);

        const categoryItemCounts = @json($categoryItemCounts);

        function appendCategoryOptionsWithCounts($select) {
            const opts = $('#category_id option').clone();
            opts.each(function () {
                const catId = $(this).val();
                if (catId && categoryItemCounts[catId] !== undefined) {
                    $(this).text($(this).text() + ' (' + categoryItemCounts[catId] + ')');
                }
            });
            $select.append(opts);
        }

        appendCategoryOptionsWithCounts($('#item_filter_category'));
        appendCategoryOptionsWithCounts($('#edit_item_filter_category'));

        const itemsByCategoryUrl = "{{ route('feature-section.items-by-category') }}";
        const itemsByIdsUrl = "{{ route('feature-section.items-by-ids') }}";

        function loadItemsForCategories(categoryIds, $itemSelect, selectedIds) {
            $itemSelect.empty().trigger('change');
            if (!categoryIds || categoryIds.length === 0) {
                $('#item_count_badge').hide();
                return;
            }

            $('#item_loading_spinner').show();
            $('#item_arrow').hide();

            $.get(itemsByCategoryUrl, { category_ids: categoryIds.join(',') }, function (res) {
                $.each(res.data || [], function (i, item) {
                    var isSelected = selectedIds && selectedIds.includes(String(item.id));
                    $itemSelect.append(new Option(item.name, item.id, isSelected, isSelected));
                });
                $itemSelect.trigger('change');
            }).always(function () {
                $('#item_loading_spinner').hide();
                $('#item_arrow').show();
            });
        }

        function updateItemCountBadge($select, $badge) {
            var count = $select.val() ? $select.val().length : 0;
            $badge.text(count).toggle(count > 0);
        }

        // Create form: category change → reload items
        $('#item_filter_category').on('change', function () {
            var selected = $(this).val() || [];
            var currentSelected = $('#item_id').val() || [];
            loadItemsForCategories(selected, $('#item_id'), currentSelected);
        });

        $('#item_id').on('change', function () {
            updateItemCountBadge($(this), $('#item_count_badge'));
        });

        $('#edit_item_id').on('change', function () {
            updateItemCountBadge($(this), $('#edit_item_count_badge'));
        });

        // Filter change — create form
        $('#filter').on('change', function () {
            const val = $(this).val();
            $('#item_selection_criteria').toggle(val === 'item_selection');
            $('#category_criteria').toggle(val === 'category_criteria');
            $('#price_criteria').toggle(val === 'price_criteria');
        });

        // Filter change — edit modal
        $('#edit_filter').on('change', function () {
            const val = $(this).val();
            $('#edit_item_selection_criteria').toggle(val === 'item_selection');
            $('#edit_category_criteria').toggle(val === 'category_criteria');
            $('#edit_price_criteria').toggle(val === 'price_criteria');
        });

        function successFunction(response) {
            window.location.reload();
        }
    </script>
@endsection
