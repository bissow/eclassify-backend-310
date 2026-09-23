@extends('layouts.main')

@section('title')
    {{ __('Promotions & Offer Zones') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Manage Flash Sales, Stock Clearance Sales, Deals of the Day, and promotional events.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                @can('promotion-create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPromotionModal">
                        <i class="ph ph-plus-circle me-1"></i> {{ __('Add Promotion') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <!-- Quick Stats Cards -->
        <div class="row mb-3 g-3">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-percent fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalPromotions ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-lightning fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Flash Sales') }}</p>
                            <h4 class="mb-0 fw-bold text-danger">{{ $flashSales ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-warning text-dark rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-tag fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Clearance') }}</p>
                            <h4 class="mb-0 fw-bold text-warning">{{ $clearanceSales ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-info text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-clock-countdown fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Daily Deals') }}</p>
                            <h4 class="mb-0 fw-bold text-info">{{ $dealsOfTheDay ?? 0 }}</h4>
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
                        <label class="form-label fw-bold">{{ __('Promotion Type') }}</label>
                        <select id="filter_type" class="form-select">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="flash_sale">{{ __('Flash Sale') }}</option>
                            <option value="clearance_sale">{{ __('Stock Clearance Sale') }}</option>
                            <option value="deal_of_the_day">{{ __('Deal of the Day') }}</option>
                            <option value="custom">{{ __('Custom Promotion') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('Campaign') }}</label>
                        <select id="filter_campaign" class="form-select">
                            <option value="">{{ __('All Campaigns') }}</option>
                            @foreach ($campaigns as $camp)
                                <option value="{{ $camp->id }}">{{ $camp->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('Status') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="scheduled">{{ __('Scheduled') }}</option>
                            <option value="expired">{{ __('Expired') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex justify-content-end gap-2">
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
                <table
                    id="promotions_table"
                    data-toggle="table"
                    data-url="{{ route('promotions.show') }}"
                    data-side-pagination="server"
                    data-pagination="true"
                    data-page-list="[10, 25, 50, 100]"
                    data-search="true"
                    data-show-refresh="true"
                    data-query-params="promotionQueryParams"
                    class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="banner">{{ __('Banner') }}</th>
                            <th data-field="title" data-sortable="true">{{ __('Title & Campaign') }}</th>
                            <th data-field="type">{{ __('Type') }}</th>
                            <th data-field="schedule">{{ __('Schedule / Timing') }}</th>
                            <th data-field="discount">{{ __('Default Discount') }}</th>
                            <th data-field="items_count">{{ __('Items On Sale') }}</th>
                            <th data-field="status">{{ __('Status') }}</th>
                            <th data-field="actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>

    <!-- Modal: CREATE PROMOTION -->
    <div class="modal fade" id="createPromotionModal" tabindex="-1" aria-labelledby="createPromotionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('promotions.store') }}" method="POST" enctype="multipart/form-data" id="createPromotionForm" class="d-flex flex-column h-100">
                    @csrf
                    <div class="modal-header bg-primary text-white flex-shrink-0">
                        <h5 class="modal-title text-white fw-bold" id="createPromotionModalLabel">
                            <i class="ph ph-percent me-2"></i> {{ __('Add New Promotion') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                        <!-- Language Tabs -->
                        <ul class="nav nav-tabs mb-3" id="createPromotionTabs" role="tablist">
                            @foreach ($languages as $key => $lang)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $key == 0 ? 'active' : '' }}"
                                        id="pcreate-tab-{{ $lang->id }}" data-bs-toggle="tab"
                                        data-bs-target="#pcreate-lang-{{ $lang->id }}" type="button" role="tab">
                                        {{ $lang->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mb-3">
                            @foreach ($languages as $key => $lang)
                                <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                                    id="pcreate-lang-{{ $lang->id }}" role="tabpanel">
                                    @if ($lang->id == 1)
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Promotion Title') }} ({{ $lang->name }}) <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" placeholder="{{ __('e.g., Midnight Flash Sale') }}" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Description') }} ({{ $lang->name }})</label>
                                            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Short description of promotion...') }}"></textarea>
                                        </div>
                                    @else
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Promotion Title') }} ({{ $lang->name }})</label>
                                            <input type="text" name="translations[{{ $lang->id }}][title]" class="form-control">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Description') }} ({{ $lang->name }})</label>
                                            <textarea name="translations[{{ $lang->id }}][description]" class="form-control" rows="2"></textarea>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Promotion Type') }} <span class="text-danger">*</span></label>
                                <select name="promotion_type" id="create_promotion_type" class="form-select" required>
                                    <option value="flash_sale">{{ __('Flash Sale (Short-term, Countdown Timer, Strict Stock)') }}</option>
                                    <option value="clearance_sale">{{ __('Stock Clearance Sale (Clear out old stock)') }}</option>
                                    <option value="deal_of_the_day">{{ __('Deal of the Day (24-Hour Rotating Deals)') }}</option>
                                    <option value="custom">{{ __('Custom Promotion') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Parent Campaign') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                                <select name="campaign_id" class="form-select">
                                    <option value="">{{ __('None (Standalone Promotion)') }}</option>
                                    @foreach ($campaigns as $camp)
                                        <option value="{{ $camp->id }}">{{ $camp->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('Start Time') }}</label>
                                <input type="time" name="start_time" class="form-control" value="00:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('End Time') }}</label>
                                <input type="time" name="end_time" class="form-control" value="23:59">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Frequency') }} <span class="text-danger">*</span></label>
                                <select name="frequency" class="form-select" required>
                                    <option value="custom">{{ __('Custom (Between Start & End)') }}</option>
                                    <option value="daily">{{ __('Daily (Refreshes Daily)') }}</option>
                                    <option value="weekly">{{ __('Weekly') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('Discount Type') }}</label>
                                <select name="discount_type" class="form-select">
                                    <option value="percentage">{{ __('Percentage (%)') }}</option>
                                    <option value="flat">{{ __('Flat Amount') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('Default / Guide Discount') }}</label>
                                <input type="number" step="0.01" name="discount" class="form-control" placeholder="{{ __('e.g., 25') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('Priority') }}</label>
                                <input type="number" name="priority" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Initial Status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="scheduled">{{ __('Scheduled') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Banner Image') }} <small class="text-muted">({{ __('Max 5MB') }})</small></label>
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_countdown_enabled" id="create_countdown" value="1" checked>
                                    <label class="form-check-label fw-bold" for="create_countdown">{{ __('Enable Live Countdown Timer in App & Web Frontend') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light flex-shrink-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="savePromotionBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Save Promotion') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: EDIT PROMOTION -->
    <div class="modal fade" id="editPromotionModal" tabindex="-1" aria-labelledby="editPromotionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('promotions.update') }}" method="POST" enctype="multipart/form-data" id="editPromotionForm" class="d-flex flex-column h-100">
                    @csrf
                    <input type="hidden" name="id" id="edit_promotion_id">
                    <div class="modal-header bg-primary text-white flex-shrink-0">
                        <h5 class="modal-title text-white fw-bold" id="editPromotionModalLabel">
                            <i class="ph ph-pencil-simple me-2"></i> {{ __('Edit Promotion') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Promotion Title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="edit_promotion_title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Promotion Type') }} <span class="text-danger">*</span></label>
                                <select name="promotion_type" id="edit_promotion_type" class="form-select" required>
                                    <option value="flash_sale">{{ __('Flash Sale') }}</option>
                                    <option value="clearance_sale">{{ __('Stock Clearance Sale') }}</option>
                                    <option value="deal_of_the_day">{{ __('Deal of the Day') }}</option>
                                    <option value="custom">{{ __('Custom Promotion') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Parent Campaign') }}</label>
                                <select name="campaign_id" id="edit_promotion_campaign_id" class="form-select">
                                    <option value="">{{ __('None (Standalone)') }}</option>
                                    @foreach ($campaigns as $camp)
                                        <option value="{{ $camp->id }}">{{ $camp->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Status') }}</label>
                                <select name="status" id="edit_promotion_status" class="form-select">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="scheduled">{{ __('Scheduled') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                    <option value="expired">{{ __('Expired') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Description') }}</label>
                                <textarea name="description" id="edit_promotion_description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Start Date') }}</label>
                                <input type="date" name="start_date" id="edit_promotion_start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('End Date') }}</label>
                                <input type="date" name="end_date" id="edit_promotion_end_date" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('Start Time') }}</label>
                                <input type="time" name="start_time" id="edit_promotion_start_time" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('End Time') }}</label>
                                <input type="time" name="end_time" id="edit_promotion_end_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Frequency') }}</label>
                                <select name="frequency" id="edit_promotion_frequency" class="form-select">
                                    <option value="custom">{{ __('Custom') }}</option>
                                    <option value="daily">{{ __('Daily') }}</option>
                                    <option value="weekly">{{ __('Weekly') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Discount Type') }}</label>
                                <select name="discount_type" id="edit_promotion_discount_type" class="form-select">
                                    <option value="percentage">{{ __('Percentage (%)') }}</option>
                                    <option value="flat">{{ __('Flat Amount') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Discount') }}</label>
                                <input type="number" step="0.01" name="discount" id="edit_promotion_discount" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Change Banner Image') }}</label>
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light flex-shrink-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="updatePromotionBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Update Promotion') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function promotionQueryParams(params) {
        return {
            limit: params.limit,
            offset: params.offset,
            sort: params.sort,
            order: params.order,
            search: params.search,
            promotion_type: $('#filter_type').val(),
            campaign_id: $('#filter_campaign').val(),
            status: $('#filter_status').val()
        };
    }

    $(document).ready(function() {
        $('#apply_filters').on('click', function() {
            $('#promotions_table').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function() {
            $('#filter_type').val('');
            $('#filter_campaign').val('');
            $('#filter_status').val('');
            $('#promotions_table').bootstrapTable('refresh');
        });

        // AJAX submit for Create Promotion
        $('#createPromotionForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#savePromotionBtn');
            var formData = new FormData(this);

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Saving...") }}');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.error === false) {
                        showSuccessToast(res.message);
                        $('#createPromotionModal').modal('hide');
                        form[0].reset();
                        $('#promotions_table').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message || '{{ __("Failed to save promotion") }}');
                    }
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || '{{ __("An error occurred") }}');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ph ph-check me-1"></i> {{ __("Save Promotion") }}');
                }
            });
        });

        // Edit Promotion Click
        $(document).on('click', '.edit-promotion', function() {
            var promo = $(this).data('promotion');
            $('#edit_promotion_id').val(promo.id);
            $('#edit_promotion_title').val(promo.title);
            $('#edit_promotion_type').val(promo.promotion_type);
            $('#edit_promotion_campaign_id').val(promo.campaign_id || '');
            $('#edit_promotion_status').val(promo.status);
            $('#edit_promotion_description').val(promo.description);
            $('#edit_promotion_start_date').val(promo.start_date ? promo.start_date.substring(0, 10) : '');
            $('#edit_promotion_end_date').val(promo.end_date ? promo.end_date.substring(0, 10) : '');
            $('#edit_promotion_start_time').val(promo.start_time ? promo.start_time.substring(0, 5) : '');
            $('#edit_promotion_end_time').val(promo.end_time ? promo.end_time.substring(0, 5) : '');
            $('#edit_promotion_frequency').val(promo.frequency || 'custom');
            $('#edit_promotion_discount_type').val(promo.discount_type || 'percentage');
            $('#edit_promotion_discount').val(promo.discount || '');

            $('#editPromotionModal').modal('show');
        });

        // AJAX submit for Update Promotion
        $('#editPromotionForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#updatePromotionBtn');
            var formData = new FormData(this);

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Updating...") }}');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.error === false) {
                        showSuccessToast(res.message);
                        $('#editPromotionModal').modal('hide');
                        $('#promotions_table').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message || '{{ __("Failed to update promotion") }}');
                    }
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || '{{ __("An error occurred") }}');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ph ph-check me-1"></i> {{ __("Update Promotion") }}');
                }
            });
        });

        // Toggle Promotion Status Switch
        $(document).on('change', '.update-promotion-status', function() {
            var id = $(this).data('id');
            var status = $(this).is(':checked') ? 'active' : 'inactive';

            $.post('{{ route("promotions.status.update") }}', {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            }, function(res) {
                if (res.error === false) {
                    showSuccessToast(res.message);
                } else {
                    showErrorToast(res.message);
                    $('#promotions_table').bootstrapTable('refresh');
                }
            }).fail(function() {
                showErrorToast('{{ __("Failed to update status") }}');
                $('#promotions_table').bootstrapTable('refresh');
            });
        });

        // Delete Promotion
        $(document).on('click', '.delete-promotion', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: '{{ __("This promotion will be deleted and its items will be unlinked.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, delete it!") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("promotions") }}/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (res.error === false) {
                                showSuccessToast(res.message);
                                $('#promotions_table').bootstrapTable('refresh');
                            } else {
                                showErrorToast(res.message);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
