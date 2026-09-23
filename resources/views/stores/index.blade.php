@extends('layouts.main')

@section('title')
    {{ __('Stores & Shops') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Manage user registered stores, shops, and locations.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                @can('store-create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoreModal">
                        <i class="ph ph-plus-circle me-1"></i> {{ __('Add Store') }}
                    </button>
                    <a href="{{ route('stores.bulk-upload.index') }}" class="btn btn-success">
                        <i class="ph ph-upload-simple me-1"></i> {{ __('Bulk Upload') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <!-- Quick Stats Cards -->
        <div class="row mb-3 g-3">
            <div class="col-6 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-storefront fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total Stores') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalStores ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-check-circle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Active Stores') }}</p>
                            <h4 class="mb-0 fw-bold text-success">{{ $activeStores ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-info text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-seal-check fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Verified Stores') }}</p>
                            <h4 class="mb-0 fw-bold text-info">{{ $verifiedStores ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('Status Filter') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('Verification Filter') }}</label>
                        <select id="filter_verified" class="form-select">
                            <option value="">{{ __('All Verification') }}</option>
                            <option value="1">{{ __('Verified Only') }}</option>
                            <option value="0">{{ __('Unverified Only') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="reset_filters">
                            <i class="ph ph-arrow-counter-clockwise me-1"></i> {{ __('Reset') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="apply_filters">
                            <i class="ph ph-funnel me-1"></i> {{ __('Apply Filter') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card">
            <div class="card-body">
                @php
                    $cols = [
                        ['field' => 'no', 'title' => __('No.'), 'sortable' => false, 'align' => 'center'],
                        ['field' => 'name', 'title' => __('Store Name'), 'sortable' => true, 'formatter' => 'storeProfileFormatter'],
                        ['field' => 'owner_name', 'title' => __('Owner'), 'sortable' => false, 'formatter' => 'storeOwnerFormatter'],
                        ['field' => 'contact', 'title' => __('Contact'), 'sortable' => false, 'formatter' => 'storeContactFormatter'],
                        ['field' => 'formatted_location', 'title' => __('Location'), 'sortable' => false],
                        ['field' => 'items_count', 'title' => __('Active Items'), 'sortable' => true, 'align' => 'center'],
                        ['field' => 'status_switch', 'title' => __('Active Status'), 'sortable' => false, 'align' => 'center', 'escape' => false],
                        ['field' => 'verify_switch', 'title' => __('Verified Badge'), 'sortable' => false, 'align' => 'center', 'escape' => false],
                        ['field' => 'operate', 'title' => __('Action'), 'escape' => false, 'align' => 'center', 'sortable' => false],
                    ];
                @endphp

                <x-data-table
                    id="table_list"
                    :url="route('stores.show', 1)"
                    click-to-select
                    fixed-columns
                    show-export
                    export-file-name="stores-list"
                    table-name="stores"
                    status-column="deleted_at"
                    :extra="['data-query-params' => 'storeQueryParams']"
                    :columns="$cols"
                />
            </div>
        </div>
    </section>

    <!-- Modal: CREATE STORE -->
    <div class="modal fade" id="createStoreModal" tabindex="-1" aria-labelledby="createStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold" id="createStoreModalLabel">
                        <i class="ph ph-storefront me-2"></i> {{ __('Add New Store / Shop') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('stores.store') }}" method="POST" enctype="multipart/form-data" id="createStoreForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Store Owner User Select -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Store Owner (Seller / Customer)') }} <span class="text-danger">*</span></label>
                                <select name="user_id" id="create_user_id" class="form-control select2-user-ajax" style="width: 100%" required>
                                    <option value="">{{ __('Type user name, email or phone to search...') }}</option>
                                </select>
                                <small class="text-muted">{{ __('Select registered user who will own this store.') }}</small>
                            </div>

                            <!-- Store Name & Slug -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Store / Shop Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Apex Electronics" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Slug (Optional)') }}</label>
                                <input type="text" name="slug" class="form-control" placeholder="e.g. apex-electronics">
                                <small class="text-muted">{{ __('Leave empty to auto-generate from store name.') }}</small>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Describe products, store specialty, and customer services...') }}"></textarea>
                            </div>

                            <!-- Logo & Banner Upload -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Store Logo / Avatar') }}</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <small class="text-muted">{{ __('Recommended: Square 1:1 ratio (PNG, JPG, WEBP)') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Cover Banner Image') }}</label>
                                <input type="file" name="banner" class="form-control" accept="image/*">
                                <small class="text-muted">{{ __('Recommended: 16:9 ratio banner (PNG, JPG, WEBP)') }}</small>
                            </div>

                            <!-- Contact Phone & Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Contact Phone') }}</label>
                                <input type="text" name="contact" class="form-control" placeholder="e.g. 9876543210">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Contact Email') }}</label>
                                <input type="email" name="email" class="form-control" placeholder="e.g. store@example.com">
                            </div>

                            <!-- Location Fields -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('Country') }}</label>
                                <input type="text" name="country" class="form-control" placeholder="e.g. India">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('State') }}</label>
                                <input type="text" name="state" class="form-control" placeholder="e.g. West Bengal">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('City') }}</label>
                                <input type="text" name="city" class="form-control" placeholder="e.g. Malda">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Detailed Street Address') }}</label>
                                <input type="text" name="address" class="form-control" placeholder="e.g. Shop 12, High Street Market, Near Central Tower">
                            </div>

                            <!-- Coordinates -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Latitude') }}</label>
                                <input type="number" step="any" name="latitude" class="form-control" placeholder="e.g. 25.0108">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Longitude') }}</label>
                                <input type="number" step="any" name="longitude" class="form-control" placeholder="e.g. 88.1411">
                            </div>

                            <!-- Operating Hours -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Opening Time') }}</label>
                                <input type="text" name="opening_time" class="form-control" value="09:00 AM" placeholder="09:00 AM">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Closing Time') }}</label>
                                <input type="text" name="closing_time" class="form-control" value="08:00 PM" placeholder="08:00 PM">
                            </div>

                            <!-- Website & Tax Number -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Website (Optional)') }}</label>
                                <input type="text" name="website" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Tax / VAT Number') }}</label>
                                <input type="text" name="tax_number" class="form-control" placeholder="e.g. GSTIN12345">
                            </div>

                            <!-- Status & Verified Switches -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-center pt-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="create_is_verified">
                                    <label class="form-check-label fw-bold ms-2" for="create_is_verified">
                                        <i class="ph ph-seal-check text-primary me-1"></i> {{ __('Mark as Verified Store') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="createStoreSubmitBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Create Store') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: EDIT STORE -->
    <div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold" id="editStoreModalLabel">
                        <i class="ph ph-note-pencil me-2"></i> {{ __('Edit Store / Shop') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" id="editStoreForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_store_id">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Store Owner User Select -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Store Owner') }}</label>
                                <select name="user_id" id="edit_user_id" class="form-control select2-user-ajax" style="width: 100%">
                                    <option value="">{{ __('Search to change owner...') }}</option>
                                </select>
                            </div>

                            <!-- Store Name & Slug -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Store / Shop Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Slug') }}</label>
                                <input type="text" name="slug" id="edit_slug" class="form-control">
                            </div>

                            <!-- Description -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Description') }}</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                            </div>

                            <!-- Existing Previews & Logo / Banner Upload -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Logo / Avatar') }}</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img id="edit_logo_preview" src="" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover;">
                                    <input type="file" name="logo" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Cover Banner') }}</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img id="edit_banner_preview" src="" class="rounded border" style="width: 80px; height: 48px; object-fit: cover;">
                                    <input type="file" name="banner" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <!-- Contact Phone & Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Contact Phone') }}</label>
                                <input type="text" name="contact" id="edit_contact" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Contact Email') }}</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>

                            <!-- Location Fields -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('Country') }}</label>
                                <input type="text" name="country" id="edit_country" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('State') }}</label>
                                <input type="text" name="state" id="edit_state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('City') }}</label>
                                <input type="text" name="city" id="edit_city" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('Detailed Address') }}</label>
                                <input type="text" name="address" id="edit_address" class="form-control">
                            </div>

                            <!-- Coordinates -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Latitude') }}</label>
                                <input type="number" step="any" name="latitude" id="edit_latitude" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Longitude') }}</label>
                                <input type="number" step="any" name="longitude" id="edit_longitude" class="form-control">
                            </div>

                            <!-- Operating Hours -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Opening Time') }}</label>
                                <input type="text" name="opening_time" id="edit_opening_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Closing Time') }}</label>
                                <input type="text" name="closing_time" id="edit_closing_time" class="form-control">
                            </div>

                            <!-- Website & Tax Number -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Website') }}</label>
                                <input type="text" name="website" id="edit_website" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Tax / VAT Number') }}</label>
                                <input type="text" name="tax_number" id="edit_tax_number" class="form-control">
                            </div>

                            <!-- Status & Verified Switches -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Status') }}</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-center pt-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="edit_is_verified">
                                    <label class="form-check-label fw-bold ms-2" for="edit_is_verified">
                                        <i class="ph ph-seal-check text-primary me-1"></i> {{ __('Verified Store') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="editStoreSubmitBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: VIEW STORE DETAILS -->
    <div class="modal fade" id="viewStoreModal" tabindex="-1" aria-labelledby="viewStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white fw-bold" id="viewStoreModalLabel">
                        <i class="ph ph-storefront me-2"></i> {{ __('Store Details') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Banner -->
                    <div class="position-relative bg-light" style="height: 180px; overflow: hidden;">
                        <img id="view_banner" src="" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='{{ asset('assets/images/placeholder.jpg') }}'">
                        <div class="position-absolute bottom-0 start-0 m-3 d-flex align-items-center gap-3">
                            <img id="view_logo" src="" class="rounded-circle border border-3 border-white shadow-sm bg-white" style="width: 72px; height: 72px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/default-profile-icon.svg') }}'">
                            <div>
                                <h4 class="text-white fw-bold text-shadow mb-0" id="view_name" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">-</h4>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-primary" id="view_slug">-</span>
                                    <span class="badge bg-success" id="view_verified_badge"><i class="ph ph-seal-check"></i> {{ __('Verified') }}</span>
                                    <span class="badge bg-secondary" id="view_status_badge">{{ __('Active') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row g-4">
                            <!-- About -->
                            <div class="col-md-12">
                                <h6 class="fw-bold text-primary mb-2"><i class="ph ph-info me-1"></i> {{ __('About Store') }}</h6>
                                <p class="text-muted mb-0" id="view_description">-</p>
                            </div>

                            <!-- Owner Info Card -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light h-100">
                                    <h6 class="fw-bold mb-2"><i class="ph ph-user me-1 text-primary"></i> {{ __('Store Owner') }}</h6>
                                    <div class="small">
                                        <div class="mb-1"><strong>{{ __('Name:') }}</strong> <span id="view_owner_name">-</span></div>
                                        <div class="mb-1"><strong>{{ __('Email:') }}</strong> <span id="view_owner_email">-</span></div>
                                        <div><strong>{{ __('Phone:') }}</strong> <span id="view_owner_phone">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Operating Hours Card -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light h-100">
                                    <h6 class="fw-bold mb-2"><i class="ph ph-clock me-1 text-primary"></i> {{ __('Operating Hours & Days') }}</h6>
                                    <div class="small">
                                        <div class="mb-1"><strong>{{ __('Hours:') }}</strong> <span id="view_hours">-</span></div>
                                        <div><strong>{{ __('Working Days:') }}</strong> <span id="view_days">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact & Location Card -->
                            <div class="col-md-12">
                                <div class="p-3 border rounded">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="ph ph-map-pin me-1"></i> {{ __('Contact & Location Details') }}</h6>
                                    <div class="row g-2 small">
                                        <div class="col-md-6"><strong>{{ __('Contact Phone:') }}</strong> <span id="view_contact">-</span></div>
                                        <div class="col-md-6"><strong>{{ __('Contact Email:') }}</strong> <span id="view_email">-</span></div>
                                        <div class="col-md-6"><strong>{{ __('Website:') }}</strong> <span id="view_website">-</span></div>
                                        <div class="col-md-6"><strong>{{ __('Tax / VAT #:') }}</strong> <span id="view_tax">-</span></div>
                                        <div class="col-md-12"><strong>{{ __('Address:') }}</strong> <span id="view_address">-</span></div>
                                        <div class="col-md-12"><strong>{{ __('Coordinates:') }}</strong> <span id="view_coords">-</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function storeQueryParams(p) {
            return {
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                limit: p.limit,
                search: p.search,
                status: $('#filter_status').val(),
                is_verified: $('#filter_verified').val(),
            };
        }

        $('#apply_filters').on('click', function () {
            $('#table_list').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function () {
            $('#filter_status').val('');
            $('#filter_verified').val('');
            $('#table_list').bootstrapTable('refresh');
        });

        function storeProfileFormatter(value, row) {
            var logo = row.logo ? row.logo : '{{ asset("assets/images/default-profile-icon.svg") }}';
            return '<div class="d-flex align-items-center gap-2">' +
                '<img src="' + logo + '" alt="' + (row.name || '') + '" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src=\'{{ asset('assets/images/default-profile-icon.svg') }}\'">' +
                '<div>' +
                '<div class="fw-bold text-dark">' + (row.name || '-') + '</div>' +
                '<small class="text-muted"><i class="ph ph-link"></i> ' + (row.slug || '') + '</small>' +
                '</div>' +
                '</div>';
        }

        function storeOwnerFormatter(value, row) {
            return '<div>' +
                '<div class="fw-bold">' + (row.owner_name || '-') + '</div>' +
                '<small class="text-muted">' + (row.owner_email || '') + '</small>' +
                '</div>';
        }

        function storeContactFormatter(value, row) {
            var phone = row.contact ? (row.country_code ? row.country_code + ' ' : '') + row.contact : '-';
            var email = row.email ? row.email : '-';
            return '<div>' +
                '<div><i class="ph ph-phone text-primary me-1"></i>' + phone + '</div>' +
                '<small class="text-muted"><i class="ph ph-envelope text-secondary me-1"></i>' + email + '</small>' +
                '</div>';
        }

        // Initialize Select2 with AJAX search for Users
        function initUserSelect2(selector, modalParent) {
            $(selector).select2({
                dropdownParent: $(modalParent),
                ajax: {
                    url: '{{ route("stores.search-users") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                placeholder: '{{ __("Type name, email or phone...") }}',
                minimumInputLength: 1
            });
        }

        $('#createStoreModal').on('shown.bs.modal', function () {
            initUserSelect2('#create_user_id', '#createStoreModal');
        });

        $('#editStoreModal').on('shown.bs.modal', function () {
            initUserSelect2('#edit_user_id', '#editStoreModal');
        });

        // AJAX Form Submit for CREATE STORE
        $('#createStoreForm').on('submit', function (e) {
            e.preventDefault();
            var btn = $('#createStoreSubmitBtn');
            var origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="ph ph-spinner fa-spin me-1"></i> {{ __("Saving...") }}');

            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.error === false) {
                        toastr.success(res.message || '{{ __("Store created successfully") }}');
                        $('#createStoreModal').modal('hide');
                        $('#createStoreForm')[0].reset();
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        toastr.error(res.message || '{{ __("Failed to create store") }}');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || '{{ __("An error occurred") }}';
                    toastr.error(msg);
                },
                complete: function () {
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        });

        // View Store Modal Trigger
        $(document).on('click', '.view-store-btn', function () {
            var storeId = $(this).data('id');
            $.get('{{ url("stores/details") }}/' + storeId, function (res) {
                if (res.error === false && res.data) {
                    var s = res.data;
                    $('#view_name').text(s.name || '-');
                    $('#view_slug').text(s.slug || '-');
                    $('#view_description').text(s.description || '{{ __("No description provided.") }}');
                    $('#view_logo').attr('src', s.logo || '{{ asset("assets/images/default-profile-icon.svg") }}');
                    $('#view_banner').attr('src', s.banner || '{{ asset("assets/images/placeholder.jpg") }}');

                    if (s.is_verified) {
                        $('#view_verified_badge').show();
                    } else {
                        $('#view_verified_badge').hide();
                    }

                    $('#view_status_badge').text(s.status ? s.status.toUpperCase() : 'ACTIVE')
                        .removeClass('bg-success bg-danger')
                        .addClass(s.status === 'active' ? 'bg-success' : 'bg-danger');

                    if (s.owner) {
                        $('#view_owner_name').text(s.owner.name || '-');
                        $('#view_owner_email').text(s.owner.email || '-');
                        $('#view_owner_phone').text(s.owner.mobile || '-');
                    } else {
                        $('#view_owner_name').text('-');
                        $('#view_owner_email').text('-');
                        $('#view_owner_phone').text('-');
                    }

                    $('#view_hours').text((s.opening_time || '09:00 AM') + ' - ' + (s.closing_time || '08:00 PM'));
                    $('#view_days').text(Array.isArray(s.working_days) ? s.working_days.join(', ') : (s.working_days || 'Mon - Sat'));

                    $('#view_contact').text(s.contact || '-');
                    $('#view_email').text(s.email || '-');
                    $('#view_website').text(s.website || '-');
                    $('#view_tax').text(s.tax_number || '-');
                    $('#view_address').text([s.address, s.city, s.state, s.country].filter(Boolean).join(', ') || '-');
                    $('#view_coords').text((s.latitude && s.longitude) ? (s.latitude + ', ' + s.longitude) : '-');

                    $('#viewStoreModal').modal('show');
                } else {
                    toastr.error(res.message || '{{ __("Could not load store details") }}');
                }
            }).fail(function () {
                toastr.error('{{ __("Error fetching store data") }}');
            });
        });

        // Edit Store Modal Trigger
        $(document).on('click', '.edit-store-btn', function () {
            var storeId = $(this).data('id');
            $.get('{{ url("stores/details") }}/' + storeId, function (res) {
                if (res.error === false && res.data) {
                    var s = res.data;
                    $('#edit_store_id').val(s.id);
                    $('#editStoreForm').attr('action', '{{ url("stores") }}/' + s.id);

                    $('#edit_name').val(s.name || '');
                    $('#edit_slug').val(s.slug || '');
                    $('#edit_description').val(s.description || '');
                    $('#edit_contact').val(s.contact || '');
                    $('#edit_email').val(s.email || '');
                    $('#edit_country').val(s.country || '');
                    $('#edit_state').val(s.state || '');
                    $('#edit_city').val(s.city || '');
                    $('#edit_address').val(s.address || '');
                    $('#edit_latitude').val(s.latitude || '');
                    $('#edit_longitude').val(s.longitude || '');
                    $('#edit_opening_time').val(s.opening_time || '09:00 AM');
                    $('#edit_closing_time').val(s.closing_time || '08:00 PM');
                    $('#edit_website').val(s.website || '');
                    $('#edit_tax_number').val(s.tax_number || '');
                    $('#edit_status').val(s.status || 'active');
                    $('#edit_is_verified').prop('checked', !!s.is_verified);

                    if (s.logo) {
                        $('#edit_logo_preview').attr('src', s.logo).show();
                    } else {
                        $('#edit_logo_preview').attr('src', '{{ asset("assets/images/default-profile-icon.svg") }}');
                    }

                    if (s.banner) {
                        $('#edit_banner_preview').attr('src', s.banner).show();
                    } else {
                        $('#edit_banner_preview').attr('src', '{{ asset("assets/images/placeholder.jpg") }}');
                    }

                    if (s.owner) {
                        var option = new Option(s.owner.name + ' (' + (s.owner.email || s.owner.mobile || 'ID: ' + s.owner.id) + ')', s.owner.id, true, true);
                        $('#edit_user_id').empty().append(option).trigger('change');
                    }

                    $('#editStoreModal').modal('show');
                } else {
                    toastr.error(res.message || '{{ __("Could not load store details") }}');
                }
            }).fail(function () {
                toastr.error('{{ __("Error fetching store data") }}');
            });
        });

        // AJAX Form Submit for EDIT STORE
        $('#editStoreForm').on('submit', function (e) {
            e.preventDefault();
            var btn = $('#editStoreSubmitBtn');
            var origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="ph ph-spinner fa-spin me-1"></i> {{ __("Updating...") }}');

            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.error === false) {
                        toastr.success(res.message || '{{ __("Store updated successfully") }}');
                        $('#editStoreModal').modal('hide');
                        $('#table_list').bootstrapTable('refresh');
                    } else {
                        toastr.error(res.message || '{{ __("Failed to update store") }}');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || '{{ __("An error occurred") }}';
                    toastr.error(msg);
                },
                complete: function () {
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        });

        // Toggle Status via AJAX
        $(document).on('change', '.toggle-store-status', function () {
            var storeId = $(this).data('id');
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var url = "{{ url('stores') }}/" + storeId;

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    status: isChecked
                },
                success: function (response) {
                    if (response.error === false) {
                        toastr.success(response.message || "{{ __('Status updated successfully') }}");
                    } else {
                        toastr.error(response.message || "{{ __('Failed to update status') }}");
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function (xhr) {
                    toastr.error("{{ __('Error updating status') }}");
                    $('#table_list').bootstrapTable('refresh');
                }
            });
        });

        // Toggle Verification via AJAX
        $(document).on('change', '.toggle-store-verify', function () {
            var storeId = $(this).data('id');
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var url = "{{ url('stores') }}/" + storeId;

            $.ajax({
                url: url,
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    is_verified: isChecked
                },
                success: function (response) {
                    if (response.error === false) {
                        toastr.success(response.message || "{{ __('Verification status updated') }}");
                    } else {
                        toastr.error(response.message || "{{ __('Failed to update verification') }}");
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function (xhr) {
                    toastr.error("{{ __('Error updating verification') }}");
                    $('#table_list').bootstrapTable('refresh');
                }
            });
        });
    </script>
@endsection

