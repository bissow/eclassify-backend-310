@extends('layouts.main')

@section('title')
    {{ __('Promotion Items & Submissions') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4 class="mb-0">@yield('title')</h4>
                <p class="text-subtitle text-muted mb-0">{{ __('Review and manage seller products, goods, and services listed in promotions and offer zones.') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end gap-2">
                <a href="{{ route('promotions.index') }}" class="btn btn-secondary">
                    <i class="ph ph-arrow-left me-1"></i> {{ __('Back to Promotions') }}
                </a>
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
                            <i class="ph ph-package fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Total Submitted Items') }}</p>
                            <h4 class="mb-0 fw-bold">{{ $totalItems ?? 0 }}</h4>
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
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Active & In-Stock') }}</p>
                            <h4 class="mb-0 fw-bold text-success">{{ $activeItems ?? 0 }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="avatar avatar-lg bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ph ph-x-circle fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small text-uppercase fw-bold">{{ __('Sold Out') }}</p>
                            <h4 class="mb-0 fw-bold text-danger">{{ $soldOutItems ?? 0 }}</h4>
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
                        <label class="form-label fw-bold">{{ __('Filter By Promotion') }}</label>
                        <select id="filter_promotion_id" class="form-select">
                            <option value="">{{ __('All Promotions') }}</option>
                            @foreach ($promotions as $promo)
                                <option value="{{ $promo->id }}" {{ (string)$promo->id === (string)$selectedPromotionId ? 'selected' : '' }}>
                                    {{ $promo->title }} ({{ ucfirst(str_replace('_', ' ', $promo->promotion_type)) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('Status Filter') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="sold_out">{{ __('Sold Out') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="expired">{{ __('Expired') }}</option>
                            <option value="pending">{{ __('Pending Approval') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
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
                    id="promotion_items_table"
                    data-toggle="table"
                    data-url="{{ route('promotions.items.show') }}"
                    data-side-pagination="server"
                    data-pagination="true"
                    data-page-list="[10, 25, 50, 100]"
                    data-search="true"
                    data-show-refresh="true"
                    data-query-params="promotionItemQueryParams"
                    class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="image">{{ __('Image') }}</th>
                            <th data-field="item">{{ __('Advertisement') }}</th>
                            <th data-field="seller">{{ __('Seller') }}</th>
                            <th data-field="promotion">{{ __('Promotion') }}</th>
                            <th data-field="original_price">{{ __('Original') }}</th>
                            <th data-field="promo_price">{{ __('Sale Price') }}</th>
                            <th data-field="stock">{{ __('Stock Units') }}</th>
                            <th data-field="valid_until">{{ __('Valid Until') }}</th>
                            <th data-field="status">{{ __('Status') }}</th>
                            <th data-field="actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>

    <!-- Modal: EDIT PROMOTION ITEM -->
    <div class="modal fade" id="editPromoItemModal" tabindex="-1" aria-labelledby="editPromoItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('promotions.items.update') }}" method="POST" id="editPromoItemForm" class="d-flex flex-column h-100">
                    @csrf
                    <input type="hidden" name="id" id="edit_pi_id">
                    <div class="modal-header bg-primary text-white flex-shrink-0">
                        <h5 class="modal-title text-white fw-bold" id="editPromoItemModalLabel">
                            <i class="ph ph-pencil-simple me-2"></i> {{ __('Edit Promotional Item') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Sale / Promotional Price') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="promotional_price" id="edit_pi_price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Total Stock Set') }} <span class="text-danger">*</span></label>
                                <input type="number" name="stock_quantity" id="edit_pi_stock" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('Remaining Stock') }} <span class="text-danger">*</span></label>
                                <input type="number" name="remaining_stock_quantity" id="edit_pi_rem_stock" class="form-control" min="0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Valid Until Date & Time') }}</label>
                                <input type="datetime-local" name="valid_until" id="edit_pi_valid_until" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('Status') }} <span class="text-danger">*</span></label>
                                <select name="status" id="edit_pi_status" class="form-select" required>
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                    <option value="sold_out">{{ __('Sold Out') }}</option>
                                    <option value="expired">{{ __('Expired') }}</option>
                                    <option value="pending">{{ __('Pending') }}</option>
                                    <option value="rejected">{{ __('Rejected') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light flex-shrink-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="updatePromoItemBtn">
                            <i class="ph ph-check me-1"></i> {{ __('Update Item') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function promotionItemQueryParams(params) {
        return {
            limit: params.limit,
            offset: params.offset,
            sort: params.sort,
            order: params.order,
            search: params.search,
            promotion_id: $('#filter_promotion_id').val(),
            status: $('#filter_status').val()
        };
    }

    $(document).ready(function() {
        $('#apply_filters').on('click', function() {
            $('#promotion_items_table').bootstrapTable('refresh');
        });

        $('#reset_filters').on('click', function() {
            $('#filter_promotion_id').val('');
            $('#filter_status').val('');
            $('#promotion_items_table').bootstrapTable('refresh');
        });

        // Edit Promotion Item Modal Trigger
        $(document).on('click', '.edit-promo-item', function() {
            var item = $(this).data('item');
            $('#edit_pi_id').val(item.id);
            $('#edit_pi_price').val(item.promotional_price);
            $('#edit_pi_stock').val(item.stock_quantity);
            $('#edit_pi_rem_stock').val(item.remaining_stock_quantity);
            $('#edit_pi_status').val(item.status);

            if (item.valid_until) {
                var d = new Date(item.valid_until);
                var formatted = d.toISOString().slice(0, 16);
                $('#edit_pi_valid_until').val(formatted);
            } else {
                $('#edit_pi_valid_until').val('');
            }

            $('#editPromoItemModal').modal('show');
        });

        // AJAX update
        $('#editPromoItemForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#updatePromoItemBtn');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Updating...") }}');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(res) {
                    if (res.error === false) {
                        showSuccessToast(res.message);
                        $('#editPromoItemModal').modal('hide');
                        $('#promotion_items_table').bootstrapTable('refresh');
                    } else {
                        showErrorToast(res.message || '{{ __("Failed to update item") }}');
                    }
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || '{{ __("An error occurred") }}');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ph ph-check me-1"></i> {{ __("Update Item") }}');
                }
            });
        });

        // Delete item from promotion
        $(document).on('click', '.delete-promo-item', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: '{{ __("Remove item from promotion?") }}',
                text: '{{ __("This ad will no longer be shown in this promotion.") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, remove it!") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("promotions/items") }}/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            if (res.error === false) {
                                showSuccessToast(res.message);
                                $('#promotion_items_table').bootstrapTable('refresh');
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
