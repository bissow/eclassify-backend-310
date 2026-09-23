@extends('layouts.main')

@section('title')
    {{ __('Advertisements') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#adTypeModal">
                    {{ __('Create Advertisement') }}
                </button>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* Deleted user row styling — overrides table-striped backgrounds */
        #table_list tbody tr.deleted-user-row,
        .table-striped tbody tr.deleted-user-row:nth-of-type(odd),
        .table-striped tbody tr.deleted-user-row:nth-of-type(even) {
            background-color: #fdf0f0 !important;
            border-left: 4px solid #dc3545 !important;
        }
        #table_list tbody tr.deleted-user-row td {
            color: #999 !important;
        }
        #table_list tbody tr.deleted-user-row img {
            filter: grayscale(100%);
            opacity: 0.5;
        }
        #table_list tbody tr.deleted-user-row .badge.bg-danger {
            filter: none;
            opacity: 1;
            color: #fff !important;
        }
        #table_list tbody tr.deleted-user-row .badge {
            opacity: 0.7;
        }
        #table_list tbody tr.deleted-user-row .btn {
            opacity: 0.8;
        }
        #adPreviewMapWrap {
            position: relative;
            height: 200px;
            overflow: hidden !important;
            isolation: isolate;
        }
        #adPreviewLeafletMap {
            height: 200px !important;
            width: 100%;
        }
        #adPreviewLeafletMap.leaflet-container {
            z-index: 1;
        }
        /* keep leaflet panes/controls clipped inside the wrap, off the button */
        #adPreviewMapWrap .leaflet-pane,
        #adPreviewMapWrap .leaflet-top,
        #adPreviewMapWrap .leaflet-bottom {
            z-index: 1;
        }
        /* Advertisement Rich Description Modal Styling */
        .ad-desc-container {
            position: relative;
            transition: max-height 0.35s ease;
        }
        .ad-desc-container.is-collapsed {
            max-height: 220px;
            overflow: hidden;
        }
        .ad-desc-container.is-collapsed::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(to bottom, rgba(255,255,255,0), #ffffff);
            pointer-events: none;
        }
        [data-bs-theme="dark"] .ad-desc-container.is-collapsed::after,
        .theme-dark .ad-desc-container.is-collapsed::after,
        body.dark .ad-desc-container.is-collapsed::after {
            background: linear-gradient(to bottom, rgba(30,41,59,0), #1e293b);
        }
        .ad-rich-content {
            line-height: 1.65;
            color: inherit;
            word-break: break-word;
        }
        .ad-rich-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 8px 0;
        }
        .ad-rich-content p {
            margin-bottom: 0.75rem;
        }
        .ad-rich-content p:last-child {
            margin-bottom: 0;
        }
        .ad-rich-content blockquote {
            border-left: 3px solid var(--bs-primary, #3b82f6);
            padding-left: 12px;
            color: #64748b;
            margin: 10px 0;
        }
        .ad-rich-content ul, .ad-rich-content ol {
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }
    </style>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-primary active w-100" id="btn-all-ads">
                                    {{ __('All Advertisements') }}
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-success w-100" id="btn-active-ads">
                                    {{ __('Active Advertisements') }}
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-warning w-100" id="btn-requested-ads">
                                    {{ __('Requested Advertisements') }}
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-secondary w-100" id="btn-admin-ads">
                                    {{ __("Admin Advertisements") }}
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-outline-danger w-100" id="btn-favorite-ads">
                                    {{ __("Favorite Ads") }}
                                </button>
                            </div>
                            @can('advertisement-update')
                            <div class="col-12 col-md-auto ms-md-auto" id="bulk-action-btns" style="display:none;">
                                <button type="button" class="btn btn-warning" id="btn-bulk-update-status">
                                    <i class="fas fa-toggle-on me-1"></i>
                                    {{ __('Update Status') }}
                                    (<span id="selected-count">0</span> {{ __('selected') }})
                                </button>
                            </div>
                            @endcan
                        </div>
                        @can('advertisement-update')
                        <div class="text-muted small mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ __('Select multiple rows using checkboxes to bulk update their status.') }}
                        </div>
                        @endcan

                        <div id="filters">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-md-5 col-lg-4" id="p_category_col">
                                    <label for="p_category">{{ __('Category') }}</label>
                                    <select name="category_id" id="p_category" class="form-control bootstrap-table-filter-control-category" aria-label="category" data-placeholder="{{ __('All') }}">
                                        <option value="">{{ __('All') }}</option>
                                        @include('category.dropdowntree', ['categories' => $categories])
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 col-lg-3">
                                    <label for="filter">{{ __('Status') }}</label>
                                    <select class="form-control" id="filter" data-field="status">
                                        <option value="">{{ __('All') }}</option>
                                        <option value="approved">{{ __('Approved') }}</option>
                                        <option value="review">{{ __('Under Review') }}</option>
                                        <option value="sold out">{{ __('Sold Out') }}</option>
                                        <option value="expired">{{ __('Expired') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                        <option value="soft rejected">{{ __('Soft Rejected') }}</option>
                                        <option value="permanent rejected">{{ __('Permanent Rejected') }}</option>
                                        <option value="resubmitted">{{ __('Resubmitted') }}</option>
                                        <option value="deleted_user">{{ __('Deleted User') }}</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-auto ms-auto d-flex align-items-end gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="btn-toggle-more-filters" data-bs-toggle="collapse" data-bs-target="#moreFiltersCollapse" aria-expanded="false">
                                        <i class="fas fa-sliders-h me-1"></i> {{ __('More Filters') }}
                                        <span class="badge bg-primary text-white ms-1 d-none" id="more-filters-badge">0</span>
                                        <i class="fas fa-chevron-down ms-1" id="more-filters-chevron" style="transition: transform 0.2s;"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-reset-filters" title="{{ __('Reset Filters') }}">
                                        <i class="fas fa-undo me-1"></i> {{ __('Reset') }}
                                    </button>
                                </div>
                            </div>

                            {{-- More Filters Collapsible Section --}}
                            <div class="collapse mb-2" id="moreFiltersCollapse">
                                <div class="card card-body bg-light border-0 shadow-none mb-0 p-3 rounded-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                            <label for="filter_ad_type">{{ __('Ad Type') }}</label>
                                            <select class="form-control" id="filter_ad_type">
                                                <option value="">{{ __('All') }}</option>
                                                <option value="reel">{{ __('Reel') }}</option>
                                                <option value="normal">{{ __('Normal') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                            <label for="filter_featured_premium">{{ __('Featured') }}</label>
                                            <select class="form-control bootstrap-table-filter-control-featured_status"
                                                id="filter_featured_premium">
                                                <option value="">{{ __('All') }}</option>
                                                <option value="featured">{{ __('Featured') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                            <label for="filter_country_item_test">{{ __('Country') }}</label>
                                            <select class="form-control bootstrap-table-filter-control-country"
                                                id="filter_country_item_test">
                                                <option value="">{{ __('All') }}</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->name }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                                            <label for="filter_state_item">{{ __('State') }}</label>
                                            <select name="state_id" class="form-control bootstrap-table-filter-control-state"
                                                id="filter_state_item">
                                                <option value="">{{ __('All') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                                            <label for="filter_city_item">{{ __('City') }}</label>
                                            <select name="city_id" class="form-control bootstrap-table-filter-control-city"
                                                id="filter_city_item">
                                                <option value="">{{ __('All') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            @php
                                $itemCols = [];
                                if(auth()->user()->can('advertisement-update')) {
                                    $itemCols[] = ['field'=>'state','title'=>'','checkbox'=>true];
                                }
                                $itemCols = array_merge($itemCols, [
                                    ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                    ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                                    ['field'=>'description','title'=>__('Description'),'sortable'=>true,'formatter'=>'descriptionFormatter'],
                                    ['field'=>'user_profile','title'=>__('User'),'formatter'=>'userProfileFormatter'],
                                    ['field'=>'price','title'=>__('Price'),'sortable'=>true],
                                    ['field'=>'min_salary','title'=>__('Min Salary'),'sortable'=>true,'visible'=>false],
                                    ['field'=>'max_salary','title'=>__('Max Salary'),'sortable'=>true,'visible'=>false],
                                    ['field'=>'category.name','title'=>__('Category'),'sortable'=>true],
                                    ['field'=>'reel.video','title'=>__('Video Ad'),'sortable'=>false,'formatter'=>'reelCircleFormatter','escape'=>false,'align'=>'center'],
                                    ['field'=>'gallery_images','title'=>__('Images'),'sortable'=>false,'formatter'=>'galleryImageFormatter','escape'=>false],
                                    ['field'=>'latitude','title'=>__('Latitude'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'longitude','title'=>__('Longitude'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'address','title'=>__('Address'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'contact','title'=>__('Contact'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'address','title'=>__('Address'),'sortable'=>true,'filterControl'=>'select','filterData'=>'','visible'=>true,'formatter'=>'addressFormatter'],
                                    ['field'=>'featured_status','title'=>__('Featured or Not'),'sortable'=>false,'visible'=>false,'filterControl'=>'select','filterData'=>'','formatter'=>'featuredItemStatusFormatter'],
                                    ['field'=>'status','title'=>__('Status'),'sortable'=>false,'escape'=>false,'formatter'=>'itemStatusFormatter'],
                                ]);
                                if(auth()->user()->can('advertisement-update')) {
                                    $itemCols[] = ['field'=>'active_status','title'=>__('Active'),'sortable'=>true,'visible'=>true,'escape'=>false,'formatter'=>'statusSwitchFormatter','attrs'=>['data-sort-name'=>'deleted_at']];
                                }
                                $itemCols = array_merge($itemCols, [
                                    ['field'=>'rejected_reason','title'=>__('Rejected Reason'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'created_at','title'=>__('Created At'),'sortable'=>true,'visible'=>false,'align'=>'center'],
                                    ['field'=>'published_at','title'=>__('Published Date'),'sortable'=>true,'visible'=>false,'align'=>'center'],
                                    ['field'=>'renewed_at','title'=>__('Renewed Date'),'sortable'=>true,'visible'=>false,'align'=>'center'],
                                    ['field'=>'expiry_date','title'=>__('Expiry Date'),'sortable'=>true,'visible'=>false,'align'=>'center'],
                                    ['field'=>'user_id','title'=>__('User ID'),'sortable'=>false,'visible'=>false,'switchable'=>false],
                                    ['field'=>'category_id','title'=>__('Category ID'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'likes','title'=>__('Likes'),'sortable'=>true,'visible'=>false,'switchable'=>false],
                                    ['field'=>'clicks','title'=>__('Clicks'),'sortable'=>true,'visible'=>false,'align'=>'center','formatter'=>'clicksFormatter'],
                                ]);
                                if(auth()->user()->canany(['advertisement-update','advertisement-delete'])) {
                                    $itemCols[] = ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'events'=>'itemEvents','escape'=>false];
                                }
                            @endphp
                            <x-data-table
                                id="table_list"
                                :url="route('advertisement.show', 'approved')"
                                click-to-select
                                fixed-columns
                                :fixed-number="2"
                                show-export
                                export-file-name="item-list"
                                table-name="items"
                                status-column="deleted_at"
                                sortName="updated_at"
                                sortOrder="desc"
                                :mobile-responsive="false"
                                toolbar-id="filters"
                                :extra="['data-card-view'=>'false','data-row-style'=>'itemRowStyle','data-query-params'=>'itemListQueryParams']"
                                :columns="$itemCols"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h4 class="modal-title fw-bold" id="myModalLabel1">{{ __('Advertisement Details') }}</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-4">
                            {{-- LEFT COLUMN --}}
                            <div class="col-lg-8">
                                {{-- Main Image / Video --}}
                                <div class="ad-preview-main-image mb-3" style="position:relative;">
                                    <img id="adPreviewMainImg" src="" alt="Advertisement" class="w-100 rounded-3" onerror="onErrorImage(event)">
                                    <video id="adPreviewMainVideo" src="" controls playsinline class="w-100 rounded-3" style="display:none;max-height:400px;background:#000;"></video>
                                    <iframe id="adPreviewMainEmbed" src="" frameborder="0" allowfullscreen allow="autoplay; encrypted-media" class="w-100 rounded-3" style="display:none;height:400px;"></iframe>
                                </div>
                                {{-- Thumbnail Gallery --}}
                                <div class="ad-preview-gallery position-relative mb-4" id="adPreviewGallery">
                                    <button type="button" class="ad-gallery-nav ad-gallery-prev" id="adGalleryPrev"><i class="fas fa-chevron-left"></i></button>
                                    <div class="ad-gallery-track d-flex gap-2 overflow-hidden" id="adGalleryTrack"></div>
                                    <button type="button" class="ad-gallery-nav ad-gallery-next" id="adGalleryNext"><i class="fas fa-chevron-right"></i></button>
                                </div>
                                {{-- Highlights (Custom Fields) --}}
                                <div id="adPreviewHighlights" class="mb-4 ad-preview-hidden">
                                    <span class="badge bg-primary border px-3 py-2 mb-3 fs-6"><i class="fas fa-lightbulb me-1"></i> {{ __('Highlights') }}</span>
                                    <div class="table-responsive">
                                        <table class="table table-borderless mb-0" id="adHighlightsTable"></table>
                                    </div>
                                </div>
                                {{-- Description --}}
                                <div id="adPreviewDescription" class="mb-4 ad-preview-hidden">
                                    <h5 class="fw-bold mb-3">{{ __('Description') }}</h5>
                                    <div id="adDescriptionContainer" class="ad-desc-container is-collapsed">
                                        <div id="adDescriptionText" class="ad-rich-content"></div>
                                    </div>
                                    <a href="javascript:void(0)" id="adDescToggle" class="text-primary mt-2 d-inline-flex align-items-center gap-1 ad-preview-hidden">
                                        <span class="ad-desc-toggle-text">{{ __('Show more') }}</span>
                                        <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                                    </a>
                                </div>
                            </div>
                            {{-- RIGHT COLUMN --}}
                            <div class="col-lg-4">
                                {{-- Item Info Card --}}
                                <div class="card shadow-sm border mb-3">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-1" id="adPreviewName"></h5>
                                        <h4 class="text-primary fw-bold mb-2" id="adPreviewPrice"></h4>
                                        <div class="d-flex justify-content-end mb-2">
                                            <small class="text-muted" id="adPreviewAdId"></small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 text-muted small mb-3" id="adPreviewMeta">
                                        </div>
                                        <div class="d-flex gap-2" id="adPreviewActions"></div>
                                    </div>
                                </div>
                                {{-- Change Status Card --}}
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewStatusCard">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">{{ __('Change Status') }}</h6>
                                        <form id="adPreviewStatusForm">
                                            @csrf
                                            <input type="hidden" name="id" id="adPreviewStatusId">
                                            <select name="status" class="form-select mb-2" id="adPreviewStatusSelect">
                                                <option value="review">{{ __('Under Review') }}</option>
                                                <option value="approved">{{ __('Approve') }}</option>
                                                <option value="soft rejected">{{ __('Soft Rejected') }}</option>
                                                <option value="permanent rejected">{{ __('Permanent Rejected') }}</option>
                                            </select>
                                            <div id="adPreviewRejectReasonWrap" class="mb-2 ad-preview-hidden">
                                                <label class="form-label mandatory">{{ __('Reason') }}</label>
                                                <textarea name="rejected_reason" id="adPreviewRejectReason" class="form-control" rows="2"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">{{ __('Save') }}</button>
                                        </form>
                                    </div>
                                </div>
                                {{-- Location Card --}}
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewLocationCard">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">{{ __('Location') }}</h6>
                                        <div class="d-flex align-items-start gap-2 mb-3">
                                            <i class="fas fa-map-marker-alt text-muted mt-1"></i>
                                            <span id="adPreviewAddress" class="small"></span>
                                        </div>
                                        <div id="adPreviewMapWrap" class="rounded overflow-hidden ad-preview-hidden" data-map-provider="{{ $mapProvider ?? 'free_api' }}" data-google-map-key="{{ $googleMapKey ?? '' }}">
                                            <div id="adPreviewLeafletMap"></div>
                                        </div>
                                        <a href="#" id="adPreviewMapLink" target="_blank" class="d-block btn btn-outline-secondary btn-sm w-100 mt-2 ad-preview-hidden">
                                            {{ __('Show on google map') }}
                                        </a>
                                    </div>
                                </div>
                                {{-- Seller Card --}}
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewSellerCard">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="adPreviewSellerImg" src="" class="rounded-circle">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <h6 class="mb-0 fw-bold text-break" id="adPreviewSellerName"></h6>
                                                <small class="text-muted text-break d-block" id="adPreviewSellerEmail"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="editStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{ __('Status') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="create-form" action="{{ route('advertisement.approval') }}" method="POST" data-success-function="updateApprovalSuccess">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="hidden" name="id" id="id">
                                    <select name="status" class="form-select" id="status" aria-label="status">
                                        <option value="review">{{ __('Under Review') }}</option>
                                        <option value="approved">{{ __('Approve') }}</option>
                                        <option value="soft rejected">{{ __('Soft Rejected') }}</option>
                                        <option value="permanent rejected">{{ __('Permanent Rejected') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div id="rejected_reason_container" class="col-md-12" style="display: none;">
                                <label for="rejected_reason" class="mandatory form-label">{{ __('Reason') }}</label>
                                <textarea name="rejected_reason" id="rejected_reason" class="form-control" placeholder={{ __('Reason') }}></textarea>
                                {{-- <input type="text" name="rejected_reason" id="rejected_reason" class="form-control"> --}}
                            </div>
                            <input type="submit" value="{{ __('Save') }}" class="btn btn-primary mt-3">
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </section>

        {{-- Bulk Status Confirmation Modal --}}
        @can('advertisement-update')
        <div id="bulkStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkStatusModalLabel">
                            <i class="fa fa-pen-to-square me-2"></i>{{ __('Update Status') }}
                            &mdash; <span id="bulk-modal-count-label" class="fw-normal fs-6 text-muted"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        {{-- Status selector --}}
                        <div class="mb-3">
                            <label for="bulk_status_select" class="form-label fw-semibold">{{ __('New Status') }}</label>
                            <select id="bulk_status_select" class="form-select">
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="review">{{ __('Under Review') }}</option>
                                <option value="soft rejected">{{ __('Soft Rejected') }}</option>
                                <option value="permanent rejected">{{ __('Permanent Rejected') }}</option>
                            </select>
                        </div>

                        {{-- Rejection reason (shown only for rejected statuses) --}}
                        <div id="bulk-rejected-reason-container" class="mb-3" style="display:none;">
                            <label for="bulk_rejected_reason" class="form-label mandatory">{{ __('Rejection Reason') }}</label>
                            <textarea id="bulk_rejected_reason" class="form-control" rows="3"
                                placeholder="{{ __('Enter reason for rejection...') }}"></textarea>
                            <div class="invalid-feedback">{{ __('Rejection reason is required.') }}</div>
                        </div>

                        {{-- Selected items preview --}}
                        <p class="fw-semibold mb-2">{{ __('Selected Advertisements') }}:</p>
                        <div class="table-responsive">
                            <table id="bulk-confirm-table" class="table table-sm table-bordered table-striped"
                                aria-describedby="bulk-confirm-table-desc">
                                <caption id="bulk-confirm-table-desc" class="visually-hidden">Selected advertisements</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('Current Status') }}</th>
                                        <th>{{ __('User') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bulk-confirm-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="button" class="btn btn-primary" id="btn-confirm-bulk-update">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="bulk-update-spinner" role="status"></span>
                            <i class="fa fa-check me-1" id="bulk-update-icon"></i>{{ __('Confirm & Update') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- Ad Type Selection Modal --}}
        <div class="modal fade" id="adTypeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="adTypeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold" id="adTypeModalLabel">{{ __('Select Ads Type') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-2">
                        <div class="row g-3 align-items-stretch">
                            <div class="col-lg-6">
                                <div class="card border ad-type-option selected border-primary flex-grow-1" data-type="normal" style="cursor:pointer;">
                                    <div class="card-body text-center py-4 d-flex flex-column align-items-center justify-content-center">
                                        <div class="ad-type-option-icon-bg"><i class="ph-bold ph-briefcase" style="font-size:32px;color:var(--bs-primary);"></i></div>
                                        <h6 class="fw-bold mb-1">{{ __('Regular Ad Listing') }}</h6>
                                        <small class="text-muted">{{ __('Standard classified advertisement') }}</small>
                                    </div>
                                </div>
                                <div class="mt-2 invisible"><small>x</small></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card border ad-type-option flex-grow-1" data-type="reel" style="cursor:pointer;">
                                    <div class="card-body text-center py-4 d-flex flex-column align-items-center justify-content-center">
                                        <div class="ad-type-option-icon-bg"><i class="ph-bold ph-play-circle" style="font-size:32px;color:var(--bs-primary);"></i></div>
                                        <h6 class="fw-bold mb-1">{{ __('Video Ad Listing') }}</h6>
                                        <small class="text-muted">{{ __('Create engaging short video advertisements.') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-2">
                        <button type="button" class="btn btn-primary w-100" id="adTypeNextBtn">{{ __('Next') }}</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Reel Video Modal --}}
        <div class="modal fade" id="reelVideoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
                <div class="modal-content bg-black border-0">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <video id="reelVideoModalPlayer" src="" controls playsinline
                               style="width:100%;max-height:640px;display:block;background:#000;border-radius:0 0 8px 8px;"></video>
                    </div>
                </div>
            </div>
        </div>
@endsection
@section('script')
    <script>
        function itemRowStyle(row, index) {
            if (row.is_user_deleted) {
                return { classes: 'deleted-user-row' };
            }
            return {};
        }

        function updateApprovalSuccess() {
            $('#editStatusModal').modal('hide');
        }

        // ── Direct delegated handlers for item action buttons ──────────────────
        // Bootstrap Table's data-events system can be unreliable when combined
        // with data-click-to-select or other plugins. Using document-level
        // delegation (same pattern as common.js) guarantees these always fire.

        // "View Custom Fields" / Advertisement Preview button
        $(document).on('click', '.editdata', function (e) {
            e.preventDefault();
            let $table = $('#table_list');
            let $row = $(this).closest('tr');
            let row = $table.bootstrapTable('getData', { useCurrentPage: true })
                         .find(function(r) { return String(r.id) === String($row.find('td').eq(1).text().trim()); });

            if (!row) {
                let rowIndex = $table.find('tbody tr').index($row);
                let allData = $table.bootstrapTable('getData', { useCurrentPage: true });
                row = allData[rowIndex];
            }

            if (!row) return;

            // --- Main Image ---
            let mainImg = row.image || '';
            $('#adPreviewMainImg').attr('src', mainImg);

            // --- Gallery Thumbnails ---
            let allImages = [];
            if (mainImg) allImages.push(mainImg);
            if (row.gallery_images && row.gallery_images.length) {
                $.each(row.gallery_images, function(i, img) {
                    if (img.image && img.image !== mainImg) allImages.push(img.image);
                });
            }
            let thumbHtml = '';
            $.each(allImages, function(i, src) {
                thumbHtml += `<img src="${src}" class="ad-gallery-thumb ${i === 0 ? 'active' : ''}" onerror="onErrorImage(event)">`;
            });
            // Reel thumb in slideshow
            if (row.reel && row.reel.video) {
                const reelThumb = row.reel.thumbnail || '';
                thumbHtml += `<div class="ad-gallery-thumb ad-gallery-reel-thumb position-relative"
                                   data-video="${row.reel.video}"
                                   style="cursor:pointer;background:#111;display:inline-flex;align-items:center;justify-content:center;">
                                ${reelThumb ? `<img src="${reelThumb}" style="width:100%;height:100%;object-fit:cover;" onerror="onErrorImage(event)">` : ''}
                                <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);">
                                    <i class="fa fa-play" style="color:#fff;font-size:16px;margin-left:3px;"></i>
                                </span>
                              </div>`;
            }
            // Product Video thumb in slideshow
            const iv = row.item_video;
            if (iv) {
                const isFile = iv.video_type === 'file' && iv.video_file;
                const isLink = ['youtube_link','vimeo_link','other_link'].includes(iv.video_type) && iv.video_link;
                if (isFile) {
                    thumbHtml += `<div class="ad-gallery-thumb ad-gallery-product-video-thumb position-relative"
                                       data-video-file="${iv.video_file}"
                                       style="cursor:pointer;background:#1a1a2e;display:inline-flex;align-items:center;justify-content:center;">
                                    <span style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);">
                                        <i class="fa fa-film" style="color:#fff;font-size:14px;"></i>
                                        <span style="color:#ccc;font-size:9px;margin-top:3px;">Video</span>
                                    </span>
                                  </div>`;
                } else if (isLink) {
                    thumbHtml += `<div class="ad-gallery-thumb ad-gallery-product-link-thumb position-relative"
                                       data-video-link="${iv.video_link}" data-video-type="${iv.video_type}"
                                       style="cursor:pointer;background:#1a1a2e;display:inline-flex;align-items:center;justify-content:center;">
                                    <span style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);">
                                        <i class="fa fa-link" style="color:#fff;font-size:14px;"></i>
                                        <span style="color:#ccc;font-size:9px;margin-top:3px;">${iv.video_type === 'youtube_link' ? '{{ __("YouTube") }}' : iv.video_type === 'vimeo_link' ? '{{ __("Vimeo") }}' : '{{ __("Video") }}'}</span>
                                    </span>
                                  </div>`;
                }
            }
            $('#adGalleryTrack').html(thumbHtml);
            $('#adPreviewGallery').toggle(allImages.length > 1 || (row.reel && row.reel.video) || !!iv);

            // --- Item Info ---
            let escapedName = $('<span>').text(row.name || '').html();
            $('#adPreviewName').html(escapedName);

            let currencySymbol = row.currency && row.currency.symbol ? row.currency.symbol : '$';
            let priceDisplay = row.price ? currencySymbol + parseFloat(row.price).toFixed(2) : '';
            if (row.min_salary && row.max_salary) {
                priceDisplay = currencySymbol + parseFloat(row.min_salary).toFixed(2) + ' - ' + currencySymbol + parseFloat(row.max_salary).toFixed(2);
            }
            $('#adPreviewPrice').text(priceDisplay);
            $('#adPreviewAdId').text('Ad id #' + row.id);

            // Meta info
            let metaHtml = '';
            if (row.published_at || row.created_at) metaHtml += `<span><i class="far fa-calendar-alt me-1"></i> ${trans("Listed on")}: ${row.published_at || row.created_at}</span>`;
            if (row.clicks !== undefined) metaHtml += `<span><i class="far fa-eye me-1"></i> ${trans("Views")}: ${row.clicks || 0}</span>`;
            if (row.likes !== undefined) metaHtml += `<span><i class="far fa-heart me-1"></i> ${trans("Favorites")}: ${row.likes || 0}</span>`;
            $('#adPreviewMeta').html(metaHtml);

            // Action buttons
            let actionsHtml = '';
            if (!row.is_user_deleted) {
                @can('advertisement-update')
                    actionsHtml += `<a href="${"{{ route('advertisement.edit', ':id') }}".replace(':id', row.id)}" class="btn btn-primary flex-fill">{{ __('Edit') }}</a>`;
                @endcan
            }
            @can('advertisement-delete')
                actionsHtml += `<button type="button" class="btn flex-fill ad-preview-delete-btn" data-id="${row.id}">{{ __('Delete') }}</button>`;
            @endcan
            $('#adPreviewActions').html(actionsHtml);

            // --- Change Status ---
            @can('advertisement-update')
            if (!row.is_user_deleted && row.status !== 'sold out' && row.status !== 'expired' && !row.is_admin_listing) {
                $('#adPreviewStatusCard').show();
                $('#adPreviewStatusId').val(row.id);
                $('#adPreviewStatusSelect').val(row.status === 'approved' ? 'approved' : row.status).trigger('change');
                $('#adPreviewRejectReason').val(row.rejected_reason || '');
                adPreviewToggleRejectReason();
            } else {
                $('#adPreviewStatusCard').hide();
            }
            @endcan

            // --- Highlights (Custom Fields) ---
            if (row.custom_fields && row.custom_fields.length > 0) {
                let highlightHtml = '';
                $.each(row.custom_fields, function(i, field) {
                    let val = '';

                    if (field.type === 'fileinput') {
                        // Controller converts fileinput value to a plain string URL
                        let fileUrl = (typeof field.value === 'string') ? field.value : (field.value?.value || '');
                        if (fileUrl) {
                            if (fileUrl.match(/\.(jpg|jpeg|png|svg)$/i)) {
                                val = `<img src="${fileUrl}" alt="" class="rounded ad-highlight-file-img" onerror="onErrorImage(event)">`;
                            } else {
                                val = `<a href="${fileUrl}" target="_blank" class="text-primary">${trans("View File")}</a>`;
                            }
                        }
                    } else {
                        // For other types, value is an object { value: "..." } or null
                        let rawVal = (typeof field.value === 'string') ? field.value : (field.value?.value || '');
                        val = rawVal ? $('<span>').text(rawVal).html() : '';
                    }

                    // Only show fields that have a value
                    if (val) {
                        let fieldIcon = field.image ? `<span class="ad-highlight-icon-wrap me-2"><img src="${field.image}" class="ad-highlight-icon" onerror="onErrorImage(event)"></span>` : '';
                        highlightHtml += `<tr>
                            <td class="fw-semibold text-nowrap ps-0 ad-highlight-name">${fieldIcon}${$('<span>').text(field.name).html()}</td>
                            <td class="text-muted px-2">:</td>
                            <td class="text-break">${val}</td>
                        </tr>`;
                    }
                });
                if (highlightHtml) {
                    $('#adHighlightsTable').html(highlightHtml);
                    $('#adPreviewHighlights').show();
                } else {
                    $('#adPreviewHighlights').hide();
                }
            } else {
                $('#adPreviewHighlights').hide();
            }

            // --- Description: Prioritize description_json / formatted_description, fallback to description ---
            let descHtml = '';
            let rawJson = row.description_json || row.descriptionJson || '';
            let formattedDesc = row.formatted_description || '';

            if (formattedDesc && typeof formattedDesc === 'string' && formattedDesc.trim() !== '') {
                descHtml = formattedDesc;
            } else if (rawJson) {
                if (typeof rawJson === 'object' && rawJson !== null) {
                    let ops = rawJson.ops || (Array.isArray(rawJson) ? rawJson : null);
                    if (ops) {
                        descHtml = parseQuillDeltaToHtml(ops);
                    }
                } else if (typeof rawJson === 'string' && rawJson.trim() !== '') {
                    try {
                        let parsed = JSON.parse(rawJson);
                        if (parsed && (parsed.ops || Array.isArray(parsed))) {
                            descHtml = parseQuillDeltaToHtml(parsed.ops || parsed);
                        } else if (typeof parsed === 'string') {
                            descHtml = parsed;
                        }
                    } catch (e) {
                        descHtml = rawJson;
                    }
                }
            }

            // Fallback to plain text description
            if (!descHtml && row.description) {
                let escaped = $('<span>').text(row.description).html();
                descHtml = `<p class="text-muted mb-0" style="white-space: pre-wrap;">${escaped}</p>`;
            }

            if (descHtml && descHtml.trim() !== '') {
                $('#adDescriptionText').html(descHtml);
                $('#adDescriptionContainer').addClass('is-collapsed');
                $('#adDescToggle .ad-desc-toggle-text').text("{{ __('Show more') }}");
                $('#adDescToggle i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $('#adPreviewDescription').show();

                setTimeout(function() {
                    let containerEl = document.getElementById('adDescriptionText');
                    let height = containerEl ? containerEl.scrollHeight : 0;
                    if (height > 220) {
                        $('#adDescToggle').removeClass('ad-preview-hidden').show();
                    } else {
                        $('#adDescriptionContainer').removeClass('is-collapsed');
                        $('#adDescToggle').addClass('ad-preview-hidden').hide();
                    }
                }, 80);
            } else {
                $('#adPreviewDescription').hide();
            }

            // --- Location ---
            if (row.address) {
                let fullAddress = [row.address, row.city, row.state, row.country].filter(Boolean).join(', ');
                $('#adPreviewAddress').text(fullAddress);
                if (row.latitude && row.longitude) {
                    $('#adPreviewMapWrap').show();
                    $('#adPreviewMapLink').attr('href', `https://www.google.com/maps?q=${row.latitude},${row.longitude}`).show();
                    // Leaflet map will be initialized after modal is shown (needs visible container)
                    window._adPreviewMapCoords = { lat: parseFloat(row.latitude), lng: parseFloat(row.longitude) };
                } else {
                    $('#adPreviewMapWrap').hide();
                    $('#adPreviewMapLink').hide();
                    window._adPreviewMapCoords = null;
                }
                $('#adPreviewLocationCard').show();
            } else {
                $('#adPreviewLocationCard').hide();
                window._adPreviewMapCoords = null;
            }

            // --- Seller ---
            if (row.user) {
                $('#adPreviewSellerImg').attr('src', row.user.profile || '').attr('data-name', row.user.name || '').attr('onerror','onErrorUserAvatar(event, 40)');
                let sellerNameHtml = $('<span>').text(row.user.name || '').html();
                if (row.is_user_deleted) {
                    sellerNameHtml += ' <span class="badge bg-danger" style="font-size: 0.7em;">{{ __("Deleted") }}</span>';
                    $('#adPreviewSellerImg').css({'filter': 'grayscale(100%)', 'opacity': '0.6'});
                } else {
                    $('#adPreviewSellerImg').css({'filter': '', 'opacity': ''});
                }
                $('#adPreviewSellerName').html(sellerNameHtml);
                $('#adPreviewSellerEmail').text(row.user.email || '');
                $('#adPreviewSellerCard').show();
            } else {
                $('#adPreviewSellerCard').hide();
            }

            $('#editModal').modal('show');
        });

        // Initialize map after modal is fully shown
        let adPreviewMapInitialized = false;
        $('#editModal').on('shown.bs.modal', function () {
            if (window._adPreviewMapCoords) {
                let coords = window._adPreviewMapCoords;
                let wrap = document.getElementById('adPreviewMapWrap');

                // Destroy previous map instance if exists
                if (adPreviewMapInitialized) {
                    window.mapUtils.removeMap('adPreviewLeafletMap');
                    adPreviewMapInitialized = false;
                }

                window.mapUtils.initializeMap('adPreviewLeafletMap', coords.lat, coords.lng, 13, {
                    provider: wrap.dataset.mapProvider || 'free_api',
                    googleMapKey: wrap.dataset.googleMapKey || '',
                    draggable: false,
                    interactive: false
                });
                adPreviewMapInitialized = true;
            }
        });
        // Clean up map + video when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            if (adPreviewMapInitialized) {
                window.mapUtils.removeMap('adPreviewLeafletMap');
                adPreviewMapInitialized = false;
            }
            const $v = $('#adPreviewMainVideo');
            $v[0].pause();
            $v.attr('src', '').hide();
            $('#adPreviewMainEmbed').attr('src', '').hide();
            $('#adPreviewMainImg').show();
        });

        // Gallery thumbnail click — image, reel, or product video
        $(document).on('click', '.ad-gallery-thumb', function() {
            $('.ad-gallery-thumb').removeClass('active');
            $(this).addClass('active');
            const $vid = $('#adPreviewMainVideo');
            const $img = $('#adPreviewMainImg');
            const $embed = $('#adPreviewMainEmbed');

            if ($(this).hasClass('ad-gallery-reel-thumb')) {
                const videoSrc = $(this).data('video');
                $img.hide(); $embed.hide();
                $vid.attr('src', videoSrc).show()[0].play();
            } else if ($(this).hasClass('ad-gallery-product-video-thumb')) {
                const videoSrc = $(this).data('video-file');
                $img.hide(); $embed.hide();
                $vid.attr('src', videoSrc).show()[0].play();
            } else if ($(this).hasClass('ad-gallery-product-link-thumb')) {
                const link = $(this).data('video-link');
                const type = $(this).data('video-type');
                $vid[0].pause(); $vid.attr('src', '').hide(); $img.hide();
                let embedSrc = link;
                if (type === 'youtube_link') {
                    const ytId = link.match(/(?:v=|youtu\.be\/|shorts\/)([^&?\/]+)/)?.[1];
                    if (ytId) embedSrc = 'https://www.youtube.com/embed/' + ytId + '?autoplay=1';
                } else if (type === 'vimeo_link') {
                    const vmId = link.match(/vimeo\.com\/(?:video\/)?(\d+)/)?.[1];
                    if (vmId) embedSrc = 'https://player.vimeo.com/video/' + vmId + '?autoplay=1';
                }
                if (embedSrc !== link) {
                    $embed.attr('src', embedSrc).show();
                } else {
                    // other_link: open in new tab, show link card
                    window.open(link, '_blank');
                    $(this).removeClass('active');
                }
            } else {
                $vid[0].pause(); $vid.attr('src', '').hide();
                $embed.attr('src', '').hide();
                $img.attr('src', $(this).attr('src')).show();
            }
        });

        // Reel circle popup in table cell → video modal
        $(document).on('click', '.reel-circle-popup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const videoSrc = $(this).data('video');
            $('#reelVideoModalPlayer').attr('src', videoSrc)[0].load();
            $('#reelVideoModalPlayer')[0].play();
            $('#reelVideoModal').modal('show');
        });
        $('#reelVideoModal').on('hide.bs.modal', function() {
            const $v = $('#reelVideoModalPlayer');
            $v[0].pause();
            $v.attr('src', '');
        });

        // Gallery navigation - prev/next image
        $(document).on('click', '#adGalleryPrev', function() {
            let $thumbs = $('.ad-gallery-thumb');
            let $active = $thumbs.filter('.active');
            let idx = $thumbs.index($active);
            let prevIdx = idx > 0 ? idx - 1 : $thumbs.length - 1;
            $thumbs.eq(prevIdx).trigger('click');
            scrollThumbIntoView($thumbs.eq(prevIdx));
        });
        $(document).on('click', '#adGalleryNext', function() {
            let $thumbs = $('.ad-gallery-thumb');
            let $active = $thumbs.filter('.active');
            let idx = $thumbs.index($active);
            let nextIdx = idx < $thumbs.length - 1 ? idx + 1 : 0;
            $thumbs.eq(nextIdx).trigger('click');
            scrollThumbIntoView($thumbs.eq(nextIdx));
        });
        // Scroll thumbnail into visible area of the track
        function scrollThumbIntoView($thumb) {
            if (!$thumb.length) return;
            let $track = $('#adGalleryTrack');
            let trackLeft = $track.scrollLeft();
            let trackWidth = $track.outerWidth();
            let thumbLeft = $thumb[0].offsetLeft;
            let thumbWidth = $thumb.outerWidth();
            if (thumbLeft < trackLeft) {
                $track.animate({ scrollLeft: thumbLeft }, 200);
            } else if (thumbLeft + thumbWidth > trackLeft + trackWidth) {
                $track.animate({ scrollLeft: thumbLeft + thumbWidth - trackWidth }, 200);
            }
        }

        // Helper to parse Quill Delta operations to sanitized HTML client-side
        function parseQuillDeltaToHtml(ops) {
            if (!Array.isArray(ops)) return '';
            let html = '';
            let currentLine = '';

            for (let i = 0; i < ops.length; i++) {
                let op = ops[i];
                if (typeof op.insert === 'string') {
                    let text = op.insert;
                    let attrs = op.attributes || {};

                    let parts = text.split('\n');
                    for (let p = 0; p < parts.length; p++) {
                        let seg = $('<span>').text(parts[p]).html();
                        if (seg.length > 0) {
                            if (attrs.bold) seg = `<strong>${seg}</strong>`;
                            if (attrs.italic) seg = `<em>${seg}</em>`;
                            if (attrs.underline) seg = `<u>${seg}</u>`;
                            if (attrs.strike) seg = `<s>${seg}</s>`;
                            let styles = [];
                            if (attrs.color) styles.push(`color: ${attrs.color}`);
                            if (attrs.background) styles.push(`background-color: ${attrs.background}`);
                            if (styles.length) seg = `<span style="${styles.join('; ')}">${seg}</span>`;
                            if (attrs.link) seg = `<a href="${attrs.link}" target="_blank" rel="noopener noreferrer">${seg}</a>`;
                            currentLine += seg;
                        }

                        if (p < parts.length - 1) {
                            if (attrs.header === 1) {
                                html += `<h3 class="fw-bold mt-2 mb-1">${currentLine}</h3>`;
                            } else if (attrs.header === 2) {
                                html += `<h4 class="fw-bold mt-2 mb-1">${currentLine}</h4>`;
                            } else if (attrs.header === 3) {
                                html += `<h5 class="fw-bold mt-2 mb-1">${currentLine}</h5>`;
                            } else if (attrs.blockquote) {
                                html += `<blockquote class="blockquote">${currentLine}</blockquote>`;
                            } else if (attrs.list === 'bullet') {
                                html += `<ul><li>${currentLine}</li></ul>`;
                            } else if (attrs.list === 'ordered') {
                                html += `<ol><li>${currentLine}</li></ol>`;
                            } else {
                                html += `<p class="mb-2">${currentLine || '&nbsp;'}</p>`;
                            }
                            currentLine = '';
                        }
                    }
                } else if (typeof op.insert === 'object' && op.insert && op.insert.image) {
                    currentLine += `<img src="${op.insert.image}" class="img-fluid rounded my-2" alt="embedded photo" style="max-height: 350px;">`;
                }
            }
            if (currentLine) {
                html += `<p class="mb-2">${currentLine}</p>`;
            }
            return html;
        }

        // Description toggle
        $(document).on('click', '#adDescToggle', function() {
            let $container = $('#adDescriptionContainer');
            let isCollapsed = $container.hasClass('is-collapsed');
            if (isCollapsed) {
                $container.removeClass('is-collapsed');
                $(this).find('.ad-desc-toggle-text').text("{{ __('Show less') }}");
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $container.addClass('is-collapsed');
                $(this).find('.ad-desc-toggle-text').text("{{ __('Show more') }}");
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });

        // Status reject reason toggle in preview
        function adPreviewToggleRejectReason() {
            let s = $('#adPreviewStatusSelect').val();
            $('#adPreviewRejectReasonWrap').toggle(s === 'soft rejected' || s === 'permanent rejected');
        }
        $(document).on('change', '#adPreviewStatusSelect', adPreviewToggleRejectReason);

        // Status form submit in preview
        $(document).on('submit', '#adPreviewStatusForm', function(e) {
            e.preventDefault();
            let $form = $(this);
            if ($form.data('submitting')) return false;
            $form.data('submitting', true);
            let $btn = $form.find('button[type="submit"]');
            let originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving...') }}');
            let formData = $form.serialize();
            $.ajax({
                url: "{{ route('advertisement.approval') }}",
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#editModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message || "{{ __('Status updated successfully') }}");
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || "{{ __('Something went wrong') }}";
                    showErrorToast(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                    $form.data('submitting', false);
                }
            });
        });

        // Delete from preview modal
        $(document).on('click', '.ad-preview-delete-btn', function() {
            let id = $(this).data('id');
            $('#editModal').modal('hide');
            let deleteUrl = "{{ url('advertisement') }}/" + id;
            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('You will not be able to revert this!') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "{{ __('Yes, delete it!') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(response) {
                            $('#table_list').bootstrapTable('refresh');
                            showSuccessToast(response.message || "{{ __('Deleted successfully') }}");
                        },
                        error: function(xhr) {
                            showErrorToast(xhr.responseJSON?.message || "{{ __('Something went wrong') }}");
                        }
                    });
                } else {
                    // Delete cancelled — reopen the preview modal
                    $('#editModal').modal('show');
                }
            });
        });

        // "Update Status" button
        $(document).on('click', '.edit-status', function (e) {
            e.preventDefault();
            let $table = $('#table_list');
            let $row = $(this).closest('tr');
            let rowId = $(this).attr('id');

            let allData = $table.bootstrapTable('getData', { useCurrentPage: true });
            let row = allData.find(function(r) { return String(r.id) === String(rowId); });

            if (!row) {
                let rowIndex = $table.find('tbody tr').index($row);
                row = allData[rowIndex];
            }

            if (!row) return;

            $("#id").val(row.id);
            $('#status').val(row.status).trigger('change');
            $('#rejected_reason').val(row.rejected_reason);
            $('#editStatusModal').modal('show');
        });
        // ── End direct delegated handlers ──────────────────────────────────────

        // ============================================================
        // BULK SELECT / BULK APPROVAL LOGIC
        // ============================================================
        window.bulkSelectedRows = [];

        // Show/hide the Update Status button and update its count label
        function syncBulkSelectionUI() {
            const count = window.bulkSelectedRows.length;
            $('#selected-count').text(count);

            if (count > 0) {
                $('#bulk-action-btns').css('display', 'inline-block');
            } else {
                $('#bulk-action-btns').css('display', 'none');
            }
        }

        // Build confirmation table body from selected rows
        function buildBulkConfirmTable(rows) {
            let html = '';
            rows.forEach(function(row, index) {
                const statusBadgeClass = {
                    'approved': 'bg-success',
                    'review': 'bg-warning text-dark',
                    'resubmitted': 'bg-info text-dark',
                    'soft rejected': 'bg-danger',
                    'permanent rejected': 'bg-dark',
                    'sold out': 'bg-secondary',
                    'expired': 'bg-secondary',
                    'inactive': 'bg-secondary',
                }[row.status] || 'bg-secondary';

                const userName = (row.user && row.user.name) ? $('<div>').text(row.user.name).html() : '-';
                const categoryName = (row.category && row.category.name) ? $('<div>').text(row.category.name).html() : '-';
                const itemName = row.name ? $('<div>').text(row.name).html() : '-';

                html += `<tr>
                    <td>${index + 1}</td>
                    <td><strong>${row.id}</strong></td>
                    <td>${itemName}</td>
                    <td>${categoryName}</td>
                    <td><span class="badge ${statusBadgeClass}">${row.status || '-'}</span></td>
                    <td>${userName}</td>
                </tr>`;
            });
            $('#bulk-confirm-tbody').html(html);
        }

        // Open the bulk modal (single entry point)
        function openBulkModal() {
            const rows = window.bulkSelectedRows;
            if (rows.length === 0) return;

            // Update header count label
            $('#bulk-modal-count-label').text(rows.length + ' {{ __('advertisement(s) selected') }}');

            // Reset form
            $('#bulk_status_select').val('approved');
            $('#bulk_rejected_reason').val('').removeClass('is-invalid');
            $('#bulk-rejected-reason-container').css('display', 'none');

            // Build preview table
            buildBulkConfirmTable(rows);
            $('#bulkStatusModal').modal('show');
        }

        function updateBulkReasonVisibility() {
            const s = $('#bulk_status_select').val();
            if (s === 'soft rejected' || s === 'permanent rejected') {
                $('#bulk-rejected-reason-container').css('display', 'block');
            } else {
                $('#bulk-rejected-reason-container').css('display', 'none');
            }
        }

        // Perform the actual bulk update via AJAX
        function performBulkUpdate() {
            const rows = window.bulkSelectedRows;
            const status = $('#bulk_status_select').val();

            // Validate rejection reason if required
            const rejectedReason = $('#bulk_rejected_reason').val().trim();
            if ((status === 'soft rejected' || status === 'permanent rejected') && !rejectedReason) {
                $('#bulk_rejected_reason').addClass('is-invalid');
                return;
            }
            $('#bulk_rejected_reason').removeClass('is-invalid');

            // Show spinner
            $('#bulk-update-spinner').removeClass('d-none');
            $('#bulk-update-icon').addClass('d-none');
            $('#btn-confirm-bulk-update').prop('disabled', true);

            const ids = rows.map(r => r.id);

            $.ajax({
                url: '{{ route("advertisement.bulk-approval") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                    status: status,
                    rejected_reason: rejectedReason
                },
                success: function(response) {
                    $('#bulkStatusModal').modal('hide');
                    window.bulkSelectedRows = [];
                    syncBulkSelectionUI();
                    $('#table_list').bootstrapTable('uncheckAll');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message || '{{ __('Status updated successfully') }}');
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : '{{ __('Something went wrong') }}';
                    showErrorToast(msg);
                },
                complete: function() {
                    $('#bulk-update-spinner').addClass('d-none');
                    $('#bulk-update-icon').removeClass('d-none');
                    $('#btn-confirm-bulk-update').prop('disabled', false);
                }
            });
        }

        // Custom queryParams function for items table to preserve filters during pagination
        function itemListQueryParams(params) {
            // Get current filter values from filter controls
            const currentFilters = {};

            // Get status filter based on button mode and dropdown selection
            const statusFilterValue = $('#filter').val();

            if (statusFilterValue === 'deleted_user') {
                // Deleted User filter — handled separately from status
                currentFilters.deleted_user = 1;
            } else if (window.itemStatusFilterMode === 'active') {
                // Active mode: always show approved (ignore dropdown)
                currentFilters.status = 'approved';
            } else if (window.itemStatusFilterMode === 'requested') {
                // Requested mode: if dropdown has a value, use it (it's already not approved)
                // Otherwise, use status_not: 'approved'
                if (statusFilterValue) {
                    currentFilters.status = statusFilterValue;
                } else {
                    currentFilters.status_not = 'approved';
                }
            } else if (window.itemStatusFilterMode === 'admin') {
                currentFilters.posted_by = 'admin';
            } else if (window.itemStatusFilterMode === 'favorite') {
                currentFilters.favorite = 1;
                if (statusFilterValue) {
                    currentFilters.status = statusFilterValue;
                }
            } else {
                // All mode: use dropdown value if selected
                if (statusFilterValue) {
                    currentFilters.status = statusFilterValue;
                }
            }

            // FIRST: Get all non-status filters (country, state, city, featured_status)
            // These should ALWAYS be preserved regardless of button mode

            // Get featured/premium filter - try multiple selectors
            let featuredStatus = $('#filter_featured_premium').val() ||
                $('.bootstrap-table-filter-control-featured_status').val() ||
                $('select[data-field="featured_status"]').val() ||
                $('select.bootstrap-table-filter-control-featured_status').val() || '';

            // Get category filter
            let category = $('#p_category').val() ||
                $('.bootstrap-table-filter-control-category').val() ||
                $('select[data-field="category"]').val() || '';

            if (category && category.trim() !== '') {
                currentFilters.category_id = category.trim();
            }


            // Get country filter - try multiple selectors
            let country = $('#filter_country_item_test').val() ||
                $('.bootstrap-table-filter-control-country').val() ||
                $('select[data-field="country"]').val() ||
                $('select.bootstrap-table-filter-control-country').val() || '';

            // Get state filter - try multiple selectors
            let state = $('#filter_state_item').val() ||
                $('.bootstrap-table-filter-control-state').val() ||
                $('select[data-field="state"]').val() ||
                $('select.bootstrap-table-filter-control-state').val() || '';

            // Get city filter - try multiple selectors
            let city = $('#filter_city_item').val() ||
                $('.bootstrap-table-filter-control-city').val() ||
                $('select[data-field="city"]').val() ||
                $('select.bootstrap-table-filter-control-city').val() || '';

            // Add non-status filters if they have values (always preserve these)
            if (featuredStatus && featuredStatus.trim() !== '') {
                currentFilters.featured_status = featuredStatus.trim();
            }
            if (country && country.trim() !== '') {
                currentFilters.country = country.trim();
            }
            if (state && state.trim() !== '') {
                currentFilters.state = state.trim();
            }
            if (city && city.trim() !== '') {
                currentFilters.city = city.trim();
            }

            // Get ad type filter (reel / normal)
            let adType = $('#filter_ad_type').val() || '';
            if (adType && adType.trim() !== '') {
                currentFilters.item_type = adType.trim();
            }

            // Build query params
            const queryParams = {
                limit: params.limit,
                offset: params.offset,
                order: params.order,
                search: params.search,
                sort: params.sort
            };

            // Add filter if we have any filters
            if (Object.keys(currentFilters).length > 0) {
                queryParams.filter = JSON.stringify(currentFilters);
            }

            return queryParams;
        }

        $(document).ready(function() {

            // ---- Bulk action button handler ----
            $('#btn-bulk-update-status').on('click', function() {
                openBulkModal();
            });

            $('#btn-confirm-bulk-update').on('click', function() {
                performBulkUpdate();
            });

            // Show/hide rejection reason dynamically
            $('#bulk_status_select').on('change', function() {
                updateBulkReasonVisibility();
            });

            // Guard flag: when we programmatically call uncheckAll() it fires 'uncheck-all.bs.table'.
            // We ignore that event so it does not race with genuine user selections.
            window.resettingBulkSelection = false;

            // Bootstrap Table check/uncheck events (user-driven)
            $('#table_list').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function() {
                if (window.resettingBulkSelection) return; // ignore programmatic reset events
                // Short timeout so bootstrap-table can finish updating its internal _data state
                setTimeout(function() {
                    window.bulkSelectedRows = $('#table_list').bootstrapTable('getSelections');
                    syncBulkSelectionUI();
                }, 50);
            });

            // ---- End Bulk action button handlers ----

            // Global variable to track status filter mode
            window.itemStatusFilterMode = 'all'; // 'all', 'active', 'requested' - default to 'all'

            // Global object to track intended filter values (to prevent Bootstrap Table from resetting them)
            window.intendedFilters = {
                country: '',
                state: '',
                city: '',
                featuredStatus: '',
                status: '',
                category: '',
                adType: ''
            };

            // Function to get current filters from filter controls and merge with status filter
            function getMergedFilters() {
                // Get current filter values from filter controls
                const currentFilters = {};

                // FIRST: Get all non-status filters (country, state, city, featured_status, adType)
                // These should ALWAYS be preserved regardless of button mode

                // Get featured/premium filter - try multiple selectors, fallback to intended filter
                let featuredStatus = $('#filter_featured_premium').val() ||
                    $('.bootstrap-table-filter-control-featured_status').val() ||
                    $('select[data-field="featured_status"]').val() ||
                    $('select.bootstrap-table-filter-control-featured_status').val() ||
                    (window.intendedFilters ? window.intendedFilters.featuredStatus : '') || '';

                // Get ad type filter (reel / normal)
                let adType = $('#filter_ad_type').val() ||
                    (window.intendedFilters ? window.intendedFilters.adType : '') || '';

                // Get category filter
                let category = $('#p_category').val() ||
                    $('.bootstrap-table-filter-control-category').val() ||
                    (window.intendedFilters ? window.intendedFilters.category : '') || '';

                if (category && category.trim() !== '') {
                    currentFilters.category_id = category.trim();
                }

                // Get country filter - try multiple selectors, fallback to intended filter
                let country = $('#filter_country_item_test').val() ||
                    $('.bootstrap-table-filter-control-country').val() ||
                    $('select[data-field="country"]').val() ||
                    $('select.bootstrap-table-filter-control-country').val() ||
                    (window.intendedFilters ? window.intendedFilters.country : '') || '';

                // Get state filter - try multiple selectors, fallback to intended filter
                let state = $('#filter_state_item').val() ||
                    $('.bootstrap-table-filter-control-state').val() ||
                    $('select[data-field="state"]').val() ||
                    $('select.bootstrap-table-filter-control-state').val() ||
                    (window.intendedFilters ? window.intendedFilters.state : '') || '';

                // Get city filter - try multiple selectors, fallback to intended filter
                let city = $('#filter_city_item').val() ||
                    $('.bootstrap-table-filter-control-city').val() ||
                    $('select[data-field="city"]').val() ||
                    $('select.bootstrap-table-filter-control-city').val() ||
                    (window.intendedFilters ? window.intendedFilters.city : '') || '';

                // Add non-status filters if they have values (always preserve these)
                if (featuredStatus && featuredStatus.trim() !== '') {
                    currentFilters.featured_status = featuredStatus.trim();
                }
                if (adType && adType.trim() !== '') {
                    currentFilters.item_type = adType.trim();
                }
                if (country && country.trim() !== '') {
                    currentFilters.country = country.trim();
                }
                if (state && state.trim() !== '') {
                    currentFilters.state = state.trim();
                }
                if (city && city.trim() !== '') {
                    currentFilters.city = city.trim();
                }

                // SECOND: Build status filter based on button mode and dropdown
                const statusFilterValue = $('#filter').val() || '';

                if (window.itemStatusFilterMode === 'active') {
                    // Active mode: always show approved (ignore dropdown)
                    currentFilters.status = 'approved';
                } else if (window.itemStatusFilterMode === 'requested') {
                    // Requested mode: if dropdown has a value, use it (it's already not approved)
                    // Otherwise, use status_not: 'approved'
                    if (statusFilterValue && statusFilterValue.trim() !== '') {
                        currentFilters.status = statusFilterValue.trim();
                    } else {
                        currentFilters.status_not = 'approved';
                    }
                } else if (window.itemStatusFilterMode === 'admin') {
                    currentFilters.posted_by = 'admin';
                } else if (window.itemStatusFilterMode === 'favorite') {
                    currentFilters.favorite = 1;
                    if (statusFilterValue && statusFilterValue.trim() !== '') {
                        currentFilters.status = statusFilterValue.trim();
                    }
                } else {
                    // All mode: use dropdown value if selected
                    if (statusFilterValue && statusFilterValue.trim() !== '') {
                        currentFilters.status = statusFilterValue.trim();
                    }
                }


                return Object.keys(currentFilters).length > 0 ? currentFilters : null;
            }

            // Function to update button active states
            function updateButtonStates(activeButton) {
                $('#btn-active-ads, #btn-requested-ads, #btn-all-ads, #btn-admin-ads, #btn-favorite-ads').removeClass('active');
                $(activeButton).addClass('active');
            }

            // Function to store current filter values
            function storeFilterValues() {
                // Try to get values from multiple sources (direct IDs and Bootstrap Table controls)
                // Also check global intendedFilters as fallback
                const stored = {
                    featuredStatus: $('#filter_featured_premium').val() ||
                        $('.bootstrap-table-filter-control-featured_status').val() ||
                        window.intendedFilters.featuredStatus || '',
                    adType: $('#filter_ad_type').val() ||
                        window.intendedFilters.adType || '',
                    country: $('#filter_country_item_test').val() ||
                        $('.bootstrap-table-filter-control-country').val() ||
                        window.intendedFilters.country || '',
                    state: $('#filter_state_item').val() ||
                        $('.bootstrap-table-filter-control-state').val() ||
                        window.intendedFilters.state || '',
                    city: $('#filter_city_item').val() ||
                        $('.bootstrap-table-filter-control-city').val() ||
                        window.intendedFilters.city || '',
                    status: $('#filter').val() || window.intendedFilters.status || ''
                };

                // Update global intended filters
                window.intendedFilters = {
                    country: stored.country,
                    state: stored.state,
                    city: stored.city,
                    featuredStatus: stored.featuredStatus,
                    adType: stored.adType,
                    status: stored.status
                };

                return stored;
            }

            // Function to restore filter values (without triggering change events to prevent loops)
            function restoreFilterValues(storedValues, skipChangeEvent) {
                if (storedValues) {
                    // Only restore if value exists and is not empty
                    if (storedValues.featuredStatus && storedValues.featuredStatus.trim() !== '') {
                        const currentVal = $('#filter_featured_premium').val();
                        if (currentVal !== storedValues.featuredStatus) {
                            $('#filter_featured_premium').val(storedValues.featuredStatus);
                        }
                        $('.bootstrap-table-filter-control-featured_status').val(storedValues.featuredStatus);
                        $('select[data-field="featured_status"]').val(storedValues.featuredStatus);
                    }
                    if (storedValues.adType && storedValues.adType.trim() !== '') {
                        const currentAdType = $('#filter_ad_type').val();
                        if (currentAdType !== storedValues.adType) {
                            $('#filter_ad_type').val(storedValues.adType);
                        }
                    }
                    if (storedValues.country && storedValues.country.trim() !== '') {
                        // Restore to all possible selectors (only if value is different to prevent loops)
                        const currentCountry = $('#filter_country_item_test').val();
                        if (currentCountry !== storedValues.country) {
                            $('#filter_country_item_test').val(storedValues.country).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-country').val(storedValues.country).prop('selected',
                            true);
                        $('select[data-field="country"]').val(storedValues.country).prop('selected', true);
                        // Also try to find Bootstrap Table's generated filter control
                        $('th[data-field="country"]').find('select').val(storedValues.country).prop('selected',
                            true);
                    }
                    if (storedValues.state && storedValues.state.trim() !== '') {
                        const currentState = $('#filter_state_item').val();
                        if (currentState !== storedValues.state) {
                            $('#filter_state_item').val(storedValues.state).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-state').val(storedValues.state).prop('selected', true);
                        $('select[data-field="state"]').val(storedValues.state).prop('selected', true);
                        $('th[data-field="state"]').find('select').val(storedValues.state).prop('selected', true);
                    }
                    if (storedValues.city && storedValues.city.trim() !== '') {
                        const currentCity = $('#filter_city_item').val();
                        if (currentCity !== storedValues.city) {
                            $('#filter_city_item').val(storedValues.city).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-city').val(storedValues.city).prop('selected', true);
                        $('select[data-field="city"]').val(storedValues.city).prop('selected', true);
                        $('th[data-field="city"]').find('select').val(storedValues.city).prop('selected', true);
                    }
                    // Status is handled separately by updateStatusDropdown
                }
            }

            // Function to update status dropdown based on button mode
            function updateStatusDropdown() {
                if (typeof window.itemStatusFilterMode === 'undefined') {
                    window.itemStatusFilterMode = 'all';
                }
                const $statusDropdown = $('#filter');
                const currentValue = $statusDropdown.val();

                // Store all options
                const allOptions = [{
                        value: '',
                        text: '{{ __('All') }}'
                    },
                    {
                        value: 'approved',
                        text: '{{ __('Approved') }}'
                    },
                    {
                        value: 'review',
                        text: '{{ __('Under Review') }}'
                    },
                    {
                        value: 'sold out',
                        text: '{{ __('Sold Out') }}'
                    },
                    {
                        value: 'expired',
                        text: '{{ __('Expired') }}'
                    },
                    {
                        value: 'inactive',
                        text: '{{ __('Inactive') }}'
                    },
                    {
                        value: 'soft rejected',
                        text: '{{ __('Soft Rejected') }}'
                    },
                    {
                        value: 'permanent rejected',
                        text: '{{ __('Permanent Rejected') }}'
                    },
                    {
                        value: 'resubmitted',
                        text: '{{ __('Resubmitted') }}'
                    }
                ];

                if (window.itemStatusFilterMode === 'active') {
                    // Active mode: Disable dropdown and show only approved (but it's handled by button)
                    $statusDropdown.prop('disabled', true);
                    $statusDropdown.html('<option value="">{{ __('All') }}</option>');
                } else if (window.itemStatusFilterMode === 'admin') {
                    // Admin mode: status filter not applicable, disable dropdown
                    $statusDropdown.prop('disabled', true);
                    $statusDropdown.val('');
                    $statusDropdown.html('<option value="">{{ __('All') }}</option>');
                } else if (window.itemStatusFilterMode === 'requested') {
                    // Requested mode: Remove approved option, enable dropdown
                    $statusDropdown.prop('disabled', false);
                    let html = '<option value="">{{ __('All') }}</option>';
                    allOptions.forEach(option => {
                        if (option.value !== 'approved' && option.value !== '') {
                            html += `<option value="${option.value}">${option.text}</option>`;
                        }
                    });
                    $statusDropdown.html(html);
                    // Restore previous value if it wasn't 'approved'
                    if (currentValue && currentValue !== 'approved') {
                        $statusDropdown.val(currentValue);
                    }
                } else {
                    // All mode: Show all options, enable dropdown
                    $statusDropdown.prop('disabled', false);
                    let html = '';
                    allOptions.forEach(option => {
                        html += `<option value="${option.value}">${option.text}</option>`;
                    });
                    $statusDropdown.html(html);
                    // Restore previous value
                    if (currentValue) {
                        $statusDropdown.val(currentValue);
                    }
                }
            }

            // Active Ads - only show approved, remove status filter from dropdown
            $('#btn-active-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'active';
                updateButtonStates(this);
                updateStatusDropdown();
                // Clear status dropdown filter since we're using button filter
                $('#filter').val('');

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    status: 'approved'
                });

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Requested Ads - exclude approved, allow status filter from dropdown
            $('#btn-requested-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'requested';
                updateButtonStates(this);
                updateStatusDropdown();
                // Don't clear status dropdown - user can still filter by specific status

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    status_not: 'approved'
                });

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Admin Ads - only ads posted by admin users
            $('#btn-admin-ads').on('click', function() {
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'admin';
                updateButtonStates(this);
                updateStatusDropdown();
                $('#filter').val('');

                restoreFilterValues(storedFilters);

                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    posted_by: 'admin'
                });
                

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                setTimeout(function() { restoreFilterValues(storedFilters); }, 100);
                setTimeout(function() { restoreFilterValues(storedFilters); }, 500);
                setTimeout(function() { restoreFilterValues(storedFilters); }, 1000);
            });

            // Favorite Ads - only ads favourited by at least one user
            $('#btn-favorite-ads').on('click', function() {
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'favorite';
                updateButtonStates(this);
                updateStatusDropdown();

                restoreFilterValues(storedFilters);

                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    favorite: 1
                });

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                setTimeout(function() { restoreFilterValues(storedFilters); }, 100);
                setTimeout(function() { restoreFilterValues(storedFilters); }, 500);
                setTimeout(function() { restoreFilterValues(storedFilters); }, 1000);
            });

            // Auto-select Favorite Ads filter when arriving with ?filter=favorite
            (function() {
                const params = new URLSearchParams(window.location.search);
                if (params.get('filter') === 'favorite') {
                    $('#btn-favorite-ads').trigger('click');
                }
            })();

            // Show All - show all statuses, allow status filter from dropdown
            $('#btn-all-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'all';
                updateButtonStates(this);
                updateStatusDropdown();
                // Don't clear status dropdown - user can filter by status

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : '';

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Helper function to refresh table with current filters
            let filterChangeTimeout = null;
            let isRefreshing = false;

            function refreshTableWithFilters() {
                // Prevent multiple simultaneous refreshes
                if (isRefreshing) {
                    return;
                }

                // Clear any pending timeout
                if (filterChangeTimeout) {
                    clearTimeout(filterChangeTimeout);
                }

                // Store current filter values before refresh
                const currentFilters = storeFilterValues();

                // Debounce the refresh to prevent duplicate calls
                filterChangeTimeout = setTimeout(function() {
                    isRefreshing = true;

                    const mergedFilters = getMergedFilters();
                    const filterString = mergedFilters ? JSON.stringify(mergedFilters) : '';

                    $('#table_list').bootstrapTable('refresh', {
                        query: {
                            filter: filterString
                        }
                    });

                    // Mark as not refreshing after a delay
                    setTimeout(function() {
                        isRefreshing = false;
                    }, 1000);

                    // Restore filter values after refresh (without triggering change events)
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 100);
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 300);
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 600);
                }, 150);
            }

            // When status filter dropdown changes, update based on current mode
            $('#filter').on('change', function() {
                refreshTableWithFilters();
            });

            // When country filter changes, refresh table and preserve status filter mode
            // Note: custom.js will handle loading states, we just need to refresh the table
            $(document).on('change',
                '#filter_country_item_test, .bootstrap-table-filter-control-country, select[data-field="country"], th[data-field="country"] select',
                function(e) {
                    // Get the selected country value from the element that triggered the event
                    const selectedCountry = $(this).val() || '';

                    // Skip if empty (user selected "All")
                    if (!selectedCountry) {
                        window.intendedFilters.country = '';
                        refreshTableWithFilters();
                        return;
                    }

                    // Update global intended filter IMMEDIATELY
                    window.intendedFilters.country = selectedCountry;

                    // Clear city when country changes (states will be reloaded by custom.js)
                    $('#filter_city_item').val('');
                    $('.bootstrap-table-filter-control-city').val('');
                    $('select[data-field="city"]').val('');
                    window.intendedFilters.city = '';

                    // Sync country value to ALL possible selectors IMMEDIATELY
                    const syncCountryValue = function() {
                        if (selectedCountry) {
                            $('#filter_country_item_test').val(selectedCountry).prop('selected', true);
                            $('.bootstrap-table-filter-control-country').val(selectedCountry).prop(
                                'selected', true);
                            $('select[data-field="country"]').val(selectedCountry).prop('selected', true);
                            $('th[data-field="country"]').find('select').val(selectedCountry).prop(
                                'selected', true);
                        }
                    };

                    // Sync immediately
                    syncCountryValue();
                    updateMoreFiltersBadge();

                    // Refresh table after a short delay to allow states to load
                    setTimeout(function() {
                        // Re-sync before refresh
                        syncCountryValue();
                        updateMoreFiltersBadge();
                        refreshTableWithFilters();

                        // Aggressively restore country value after refresh
                        setTimeout(syncCountryValue, 50);
                        setTimeout(syncCountryValue, 150);
                        setTimeout(syncCountryValue, 300);
                        setTimeout(syncCountryValue, 500);
                        setTimeout(syncCountryValue, 1000);
                        setTimeout(syncCountryValue, 2000);
                    }, 300);
                });

            // When state filter changes, refresh table and preserve status filter mode
            // Note: custom.js will handle loading cities, we just need to refresh the table
            $('#filter_state_item, .bootstrap-table-filter-control-state').on('change', function() {
                // Store the selected state value immediately to prevent loss
                const selectedState = $(this).val() || '';

                // Update global intended filter
                window.intendedFilters.state = selectedState;

                // Ensure state value is set in both selectors
                if (selectedState) {
                    $('#filter_state_item').val(selectedState);
                    $('.bootstrap-table-filter-control-state').val(selectedState);
                }

                updateMoreFiltersBadge();

                // Refresh table after a short delay to allow cities to load
                setTimeout(function() {
                    // Re-ensure state value is still set before refresh
                    if (selectedState) {
                        $('#filter_state_item').val(selectedState);
                        $('.bootstrap-table-filter-control-state').val(selectedState);
                    }
                    updateMoreFiltersBadge();
                    refreshTableWithFilters();

                    // Restore state value after refresh in case Bootstrap Table reset it
                    setTimeout(function() {
                        if (selectedState) {
                            $('#filter_state_item').val(selectedState);
                            $('.bootstrap-table-filter-control-state').val(selectedState);
                        }
                    }, 100);
                }, 300);
            });

            // When city filter changes, refresh table and preserve status filter mode
            $('#filter_city_item, .bootstrap-table-filter-control-city').on('change', function() {
                // Store the selected city value immediately to prevent loss
                const selectedCity = $(this).val() || '';

                // Update global intended filter
                window.intendedFilters.city = selectedCity;

                // Ensure city value is set in both selectors
                if (selectedCity) {
                    $('#filter_city_item').val(selectedCity);
                    $('.bootstrap-table-filter-control-city').val(selectedCity);
                }

                updateMoreFiltersBadge();
                refreshTableWithFilters();

                // Restore city value after refresh in case Bootstrap Table reset it
                setTimeout(function() {
                    if (selectedCity) {
                        $('#filter_city_item').val(selectedCity);
                        $('.bootstrap-table-filter-control-city').val(selectedCity);
                    }
                }, 100);
            });

            // Function to update More Filters active badge & button style
            function updateMoreFiltersBadge() {
                let count = 0;
                const adType = $('#filter_ad_type').val() || '';
                const featured = $('#filter_featured_premium').val() || '';
                const country = $('#filter_country_item_test').val() || '';
                const state = $('#filter_state_item').val() || '';
                const city = $('#filter_city_item').val() || '';

                if (adType.trim() !== '') count++;
                if (featured.trim() !== '') count++;
                if (country.trim() !== '') count++;
                if (state.trim() !== '') count++;
                if (city.trim() !== '') count++;

                const $badge = $('#more-filters-badge');
                const $btn = $('#btn-toggle-more-filters');
                if (count > 0) {
                    $badge.text(count).removeClass('d-none');
                    $btn.addClass('btn-primary').removeClass('btn-outline-primary');
                } else {
                    $badge.addClass('d-none');
                    $btn.removeClass('btn-primary').addClass('btn-outline-primary');
                }
            }

            // Handle More Filters collapse chevron rotation
            $('#moreFiltersCollapse').on('show.bs.collapse', function () {
                $('#more-filters-chevron').css('transform', 'rotate(180deg)');
            }).on('hide.bs.collapse', function () {
                $('#more-filters-chevron').css('transform', 'rotate(0deg)');
            });

            // Handle Reset Filters button
            $('#btn-reset-filters').on('click', function () {
                // Reset Category (handles select2)
                const $cat = $('#p_category');
                if ($cat.length) {
                    $cat.val('').trigger('change');
                }
                // Reset Status
                $('#filter').val('');
                // Reset Ad Type
                $('#filter_ad_type').val('');
                // Reset Featured
                $('#filter_featured_premium').val('');
                // Reset Country, State, City
                $('#filter_country_item_test').val('');
                $('#filter_state_item').html('<option value="">{{ __("All") }}</option>').val('');
                $('#filter_city_item').html('<option value="">{{ __("All") }}</option>').val('');

                window.intendedFilters = {
                    country: '',
                    state: '',
                    city: '',
                    featuredStatus: '',
                    status: '',
                    category: '',
                    adType: ''
                };

                updateMoreFiltersBadge();
                refreshTableWithFilters();
            });

            $('#filter_category, .bootstrap-table-filter-control-category').on('change', function() {
                const selectedCategory = $(this).val() || '';

                window.intendedFilters.category = selectedCategory;

                refreshTableWithFilters();
            });

            // When ad type filter changes (Reel / Normal / All), refresh table and update badge
            $('#filter_ad_type').on('change', function() {
                const selectedAdType = $(this).val() || '';
                window.intendedFilters.adType = selectedAdType;
                updateMoreFiltersBadge();
                refreshTableWithFilters();
            });


            // When featured/premium filter changes, refresh table and preserve status filter mode
            let featuredFilterChanging = false;
            $('#filter_featured_premium, .bootstrap-table-filter-control-featured_status, select[data-field="featured_status"]')
                .on('change', function(e) {
                    // Prevent infinite loops - if we're already processing a change, skip
                    if (featuredFilterChanging) {
                        return;
                    }

                    // Get the selected featured status value
                    const selectedFeatured = $(this).val() || '';

                    // Check if value actually changed
                    const currentIntended = window.intendedFilters.featuredStatus || '';
                    if (selectedFeatured === currentIntended && selectedFeatured !== '') {
                        // Value hasn't changed, don't refresh
                        return;
                    }

                    // Set flag to prevent loops
                    featuredFilterChanging = true;

                    // Update global intended filter
                    window.intendedFilters.featuredStatus = selectedFeatured;

                    // Ensure featured status value is set in all selectors (without triggering change)
                    if (selectedFeatured) {
                        $('#filter_featured_premium').val(selectedFeatured);
                        $('.bootstrap-table-filter-control-featured_status').val(selectedFeatured);
                        $('select[data-field="featured_status"]').val(selectedFeatured);
                    }

                    updateMoreFiltersBadge();

                    // Refresh table
                    refreshTableWithFilters();

                    // Clear flag after a delay
                    setTimeout(function() {
                        featuredFilterChanging = false;
                    }, 500);
                });

            // Initialize status dropdown on page load
            updateStatusDropdown();

            // Auto-apply Status / Featured / Item Type filters when arriving from dashboard or links
            // (?status=sold out|expired|approved, ?featured=1, ?item_type=reel|normal). Runs AFTER all change
            // handlers are bound + dropdown initialized, so trigger('change') actually refreshes.
            (function() {
                const params = new URLSearchParams(window.location.search);
                const status = params.get('status');
                if (status === 'approved') {
                    // Approved → select the Active Advertisements button (active mode)
                    $('#btn-active-ads').trigger('click');
                } else if (status) {
                    $('#filter').val(status).trigger('change');
                }
                if (params.get('featured') === '1') {
                    $('#filter_featured_premium').val('featured').trigger('change');
                }
                const itemType = params.get('item_type');
                if (itemType) {
                    $('#filter_ad_type').val(itemType).trigger('change');
                }

                updateMoreFiltersBadge();

                // Auto-expand More Filters if any additional filter is pre-applied
                if ($('#filter_ad_type').val() || $('#filter_featured_premium').val() || $('#filter_country_item_test').val()) {
                    const collapseEl = document.getElementById('moreFiltersCollapse');
                    if (collapseEl && window.bootstrap && bootstrap.Collapse) {
                        bootstrap.Collapse.getOrCreateInstance(collapseEl).show();
                    } else {
                        $('#moreFiltersCollapse').addClass('show');
                        $('#more-filters-chevron').css('transform', 'rotate(180deg)');
                    }
                }
            })();

            // Listen to Bootstrap Table refresh events to restore filter values
            $('#table_list').on('refresh.bs.table', function() {
                // Restore all intended filter values after Bootstrap Table refreshes (without triggering change events)
                if (window.intendedFilters) {
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 50);
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 200);
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 500);
                }
            });

            // Clear all selections whenever the table loads new data.
            // Uses the proper bootstrap-table API (uncheckAll) so its internal _data state
            // is correctly reset — DOM-only manipulation causes getSelections() to drift.
            // The guard flag suppresses the 'uncheck-all.bs.table' event that uncheckAll() fires,
            // so it cannot interfere with a user's genuine selection.
            function clearBulkSelection() {
                window.resettingBulkSelection = true;

                // uncheckAll() resets bootstrap-table's internal _data + DOM checkboxes
                $('#table_list').bootstrapTable('uncheckAll');

                // Reset our JS tracking array and hide the bulk action buttons
                window.bulkSelectedRows = [];
                syncBulkSelectionUI();

                // Release the flag after a safe delay (longer than the 50 ms listener timeout)
                setTimeout(function() { window.resettingBulkSelection = false; }, 150);
            }

            // post-body fires after table rows are rendered (initial load, pagination, filter, refresh)
            $('#table_list').on('post-body.bs.table', function() {
                if (window.intendedFilters) {
                    restoreFilterValues(window.intendedFilters, true);
                }
                // Small delay so bootstrap-table finishes its own post-render work first
                setTimeout(function() {
                    clearBulkSelection();
                    disableNonSelectableCheckboxes();
                }, 50);
            });

            // Active/deactive toggle: persist status then refresh the table so the
            // row re-renders with fresh data, while preserving the active filters.
            // Bound on #table_list (inner of document) and stopPropagation prevents
            // the global .update-status handler in common.js from firing the request
            // a second time. post-body restores filters from window.intendedFilters.
            $('#table_list').on('change', '.update-status', function(e) {
                e.stopPropagation();
                const $sw = $(this);
                const tableElement = $sw.closest('table');
                const url = tableElement.data('custom-status-change-url') || window.baseurl + 'common/change-status';
                storeFilterValues(); // snapshot current filters into window.intendedFilters
                ajaxRequest('PUT', url, {
                    id: $sw.attr('id'),
                    table: tableElement.data('table'),
                    column: tableElement.data('status-column') || '',
                    status: $sw.is(':checked') ? 1 : 0
                }, null, function(response) {
                    showSuccessToast(response.message);
                    $('#table_list').bootstrapTable('refresh');
                }, function(error) {
                    showErrorToast(error.message);
                    $sw.prop('checked', !$sw.is(':checked')); // revert toggle on failure
                });
            });

            // Disable checkboxes for rows where selectable === false (sold out / expired)
            function disableNonSelectableCheckboxes() {
                const rows = $('#table_list').bootstrapTable('getData');
                $('#table_list tbody tr').each(function(index) {
                    const row = rows[index];
                    if (row && row.selectable === false) {
                        // Only the bootstrap-table row-select checkbox — NOT the
                        // active/deactive toggle switch (also type=checkbox) which
                        // admin/sold-out/expired rows must keep usable.
                        const $cb = $(this).find('input[name="btSelectItem"]');
                        $cb.prop('disabled', true).removeAttr('title');
                        // Admin listings: disable checkbox silently (no tooltip);
                        // only non-admin sold-out/expired rows explain why.
                        if (!row.is_admin_listing) {
                            $cb.closest('td').addClass('not-selectable-cell').css('cursor', 'not-allowed')
                                .attr('data-tip', '{{ __('Cannot update sold out or expired advertisements') }}');
                        } else {
                            $cb.closest('td').css('cursor', 'not-allowed');
                        }
                    }
                });

                // Also strip any accidentally selected non-selectable rows
                window.bulkSelectedRows = window.bulkSelectedRows.filter(function(r) {
                    return r.selectable !== false;
                });
                syncBulkSelectionUI();
            }

            // Delegated tooltip on disabled cells — survives table re-render, stays inside viewport
            const notSelectableTip = '{{ __('Cannot update sold out or expired advertisements') }}';
            $(document).on('mouseenter', '#table_list tbody td.not-selectable-cell', function() {
                const el = this;
                let tip = bootstrap.Tooltip.getInstance(el);
                if (!tip) {
                    tip = new bootstrap.Tooltip(el, {
                        title: el.getAttribute('data-tip') || notSelectableTip,
                        placement: 'left',
                        container: 'body',
                        boundary: 'viewport',
                        trigger: 'manual',
                        fallbackPlacements: ['left', 'top', 'bottom']
                    });
                }
                tip.show();
            }).on('mouseleave', '#table_list tbody td.not-selectable-cell', function() {
                const tip = bootstrap.Tooltip.getInstance(this);
                if (tip) tip.hide();
            });
        });

        // Init category select2 with the bootstrap-5 theme. Dropdown attaches
        // to body (select2 default) so it isn't clipped by the narrow filter
        // column; custom.css's `.select2-container:has(> .select2-selection)`
        // rule already scopes the width fix to the inline control only.
        $(function () {
            const $cat = $('#p_category');
            if ($cat.length) {
                if ($cat.hasClass('select2-hidden-accessible')) {
                    $cat.select2('destroy');
                }
                $cat.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    allowClear: true,
                    placeholder: $cat.data('placeholder') || "{{ __('All') }}"
                });
            }
        });

        // Ad Type Modal
        $(document).on('click', '.ad-type-option', function () {
            $('.ad-type-option').removeClass('selected border-primary');
            $(this).addClass('selected border-primary');
        });

        $('#adTypeNextBtn').on('click', function () {
            const type = $('.ad-type-option.selected').data('type') || 'normal';
            window.location.href = '{{ route('advertisement.create') }}?item_type=' + type;
        });
    </script>
@endsection
