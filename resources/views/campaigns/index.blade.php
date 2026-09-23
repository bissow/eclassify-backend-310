@extends('layouts.main')

@section('title')
    {{ __('Campaigns Management') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Create and manage seasonal marketing campaigns and festival events.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                @can('campaign-create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                        <i class="ph ph-plus-circle me-1"></i> {{ __('Add Campaign') }}
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
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-flag-banner fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total Campaigns') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalCampaigns ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-check-circle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Currently Running') }}</p>
                            <h4 class="mb-0 fw-bold text-success">{{ $activeCampaigns ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-secondary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-clock fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Expired / Past') }}</p>
                            <h4 class="mb-0 fw-bold text-secondary">{{ $expiredCampaigns ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('Status Filter') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="scheduled">{{ __('Scheduled') }}</option>
                            <option value="expired">{{ __('Expired') }}</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex justify-content-end gap-2">
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
                    id="campaigns_table"
                    data-toggle="table"
                    data-url="{{ route('campaigns.show') }}"
                    data-side-pagination="server"
                    data-pagination="true"
                    data-page-list="[10, 25, 50, 100]"
                    data-search="true"
                    data-show-refresh="true"
                    data-query-params="campaignQueryParams"
                    class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="banner">{{ __('Banner') }}</th>
                            <th data-field="title" data-sortable="true">{{ __('Title & Slug') }}</th>
                            <th data-field="start_date" data-sortable="true">{{ __('Start Date') }}</th>
                            <th data-field="end_date" data-sortable="true">{{ __('End Date') }}</th>
                            <th data-field="promotions_count">{{ __('Promotions') }}</th>
                            <th data-field="priority" data-sortable="true">{{ __('Priority') }}</th>
                            <th data-field="status">{{ __('Status') }}</th>
                            <th data-field="actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>

    <!-- Modal: CREATE CAMPAIGN -->
    <div class="modal fade" id="createCampaignModal" tabindex="-1" aria-labelledby="createCampaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('campaigns.store') }}" method="POST" enctype="multipart/form-data" id="createCampaignForm" class="d-flex flex-column h-100">
                    @csrf
                    <div class="modal-header bg-primary text-white flex-shrink-0">
                        <h5 class="modal-title text-white fw-bold" id="createCampaignModalLabel">
                            <i class="ph ph-flag-banner me-2"></i> {{ __('Add New Campaign') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                        <!-- Language Tabs -->
                        <ul class="nav nav-tabs mb-3" id="createCampaignTabs" role="tablist">
                            @foreach ($languages as $key => $lang)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $key == 0 ? 'active' : '' }}"
                                        id="create-tab-{{ $lang->id }}" data-bs-toggle="tab"
                                        data-bs-target="#create-lang-{{ $lang->id }}" type="button" role="tab">
                                        {{ $lang->name }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mb-3">
                            @foreach ($languages as $key => $lang)
                                <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}"
                                    id="create-lang-{{ $lang->id }}" role="tabpanel">
                                    @if ($lang->id == 1)
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Campaign Title') }} ({{ $lang->name }}) <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" placeholder="{{ __('e.g., Black Friday 2026') }}" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Description') }} ({{ $lang->name }})</label>
                                            <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Brief description of the campaign...') }}"></textarea>
                                        </div>
                                    @else
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Campaign Title') }} ({{ $lang->name }})</label>
                                            <input type="text" name="translations[{{ $lang->id }}][title]" class="form-control">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">{{ __('Description') }} ({{ $lang->name }})</label>
                                            <textarea name="translations[{{ $lang->id }}][description]" class="form-control" rows="3"></textarea>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Custom URL Slug') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                                <input type="text" name="slug" class="form-control" placeholder="{{ __('auto-generated-if-blank') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Priority / Display Order') }}</label>
                                <input type="number" name="priority" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Initial Status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="scheduled">{{ __('Scheduled') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Banner Image') }} <small class="text-muted">({{ __('Max 5MB') }})</small></label>
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light flex-shrink-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="saveCampaignBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Save Campaign') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: EDIT CAMPAIGN -->
    <div class="modal fade" id="editCampaignModal" tabindex="-1" aria-labelledby="editCampaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('campaigns.update') }}" method="POST" enctype="multipart/form-data" id="editCampaignForm" class="d-flex flex-column h-100">
                    @csrf
                    <input type="hidden" name="id" id="edit_campaign_id">
                    <div class="modal-header bg-primary text-white flex-shrink-0">
                        <h5 class="modal-title text-white fw-bold" id="editCampaignModalLabel">
                            <i class="ph ph-pencil-simple me-2"></i> {{ __('Edit Campaign') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Campaign Title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="edit_campaign_title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Slug') }}</label>
                                <input type="text" name="slug" id="edit_campaign_slug" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Description') }}</label>
                                <textarea name="description" id="edit_campaign_description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Start Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="edit_campaign_start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('End Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="edit_campaign_end_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Status') }} <span class="text-danger">*</span></label>
                                <select name="status" id="edit_campaign_status" class="form-select" required>
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="scheduled">{{ __('Scheduled') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                    <option value="expired">{{ __('Expired') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Priority') }}</label>
                                <input type="number" name="priority" id="edit_campaign_priority" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Change Banner Image') }}</label>
                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light flex-shrink-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="updateCampaignBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Update Campaign') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function campaignQueryParams(params) {
        return {
            limit: params.limit,
            offset: params.offset,
            sort: params.sort,
            order: params.order,
            search: params.search,
            status: $('#filter_status').val()
        };
    }

    $(document).ready(function() {
        $('#apply_filters').on('click', function() {
            $('#campaigns_table').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function() {
            $('#filter_status').val('');
            $('#campaigns_table').bootstrapTable('refresh');
        });

        // AJAX submit for Create Campaign
        $('#createCampaignForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#saveCampaignBtn');
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
                        $('#createCampaignModal').modal('hide');
                        form[0].reset();
                        $('#campaigns_table').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message || '{{ __("Failed to save campaign") }}');
                    }
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || '{{ __("An error occurred") }}');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ph ph-check me-1"></i> {{ __("Save Campaign") }}');
                }
            });
        });

        // Edit Campaign Click
        $(document).on('click', '.edit-campaign', function() {
            var campaign = $(this).data('campaign');
            $('#edit_campaign_id').val(campaign.id);
            $('#edit_campaign_title').val(campaign.title);
            $('#edit_campaign_slug').val(campaign.slug);
            $('#edit_campaign_description').val(campaign.description);
            $('#edit_campaign_start_date').val(campaign.start_date ? campaign.start_date.substring(0, 10) : '');
            $('#edit_campaign_end_date').val(campaign.end_date ? campaign.end_date.substring(0, 10) : '');
            $('#edit_campaign_status').val(campaign.status);
            $('#edit_campaign_priority').val(campaign.priority);

            $('#editCampaignModal').modal('show');
        });

        // AJAX submit for Update Campaign
        $('#editCampaignForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#updateCampaignBtn');
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
                        $('#editCampaignModal').modal('hide');
                        $('#campaigns_table').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message || '{{ __("Failed to update campaign") }}');
                    }
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || '{{ __("An error occurred") }}');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ph ph-check me-1"></i> {{ __("Update Campaign") }}');
                }
            });
        });

        // Toggle Campaign Status Switch
        $(document).on('change', '.update-campaign-status', function() {
            var id = $(this).data('id');
            var status = $(this).is(':checked') ? 'active' : 'inactive';

            $.post('{{ route("campaigns.status.update") }}', {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            }, function(res) {
                if (res.error === false) {
                    showSuccessToast(res.message);
                } else {
                    showErrorToast(res.message);
                    $('#campaigns_table').bootstrapTable('refresh');
                }
            }).fail(function() {
                showErrorToast('{{ __("Failed to update status") }}');
                $('#campaigns_table').bootstrapTable('refresh');
            });
        });

        // Delete Campaign
        $(document).on('click', '.delete-campaign', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: '{{ __("This campaign and its settings will be deleted.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, delete it!") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("campaigns") }}/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (res.error === false) {
                                showSuccessToast(res.message);
                                $('#campaigns_table').bootstrapTable('refresh');
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
