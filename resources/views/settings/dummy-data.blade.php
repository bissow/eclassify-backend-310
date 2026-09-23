@extends('layouts.main')

@section('title')
    {{ __('Dummy Data') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
<section class="section">
    <div class="card">
        <div class="card-body">

            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle"></i> {{ __('How it works') }}
                </h6>
                <ul class="mb-0">
                    <li>{{ __('Dummy data is fully isolated — populating or deleting it never touches your real categories, custom fields or advertisements.') }}</li>
                    <li>{{ __('Dummy categories and custom fields are imported once. Re-populating only adds more dummy advertisements.') }}</li>
                    <li>{{ __('Dummy advertisements are owned by the admin account, you can populate them as many times as you want.') }}</li>
                </ul>
            </div>

            <div class="row mb-4" id="dummyCounts">
                <div class="col-md-4">
                    <div class="card border text-center mb-2">
                        <div class="card-body py-3">
                            <h3 class="mb-0" id="dummyCategoriesCount">{{ $dummyCounts['categories'] }}</h3>
                            <span class="text-muted">{{ __('Dummy Categories') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border text-center mb-2">
                        <div class="card-body py-3">
                            <h3 class="mb-0" id="dummyCustomFieldsCount">{{ $dummyCounts['custom_fields'] }}</h3>
                            <span class="text-muted">{{ __('Dummy Custom Fields') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border text-center mb-2">
                        <div class="card-body py-3">
                            <h3 class="mb-0" id="dummyItemsCount">{{ $dummyCounts['items'] }}</h3>
                            <span class="text-muted">{{ __('Dummy Advertisements') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="itemsCount" class="form-label">{{ __('Advertisements to create') }}</label>
                    <input type="number" id="itemsCount" class="form-control" value="20" min="0" max="500">
                </div>
                <div class="col-md-9 mt-3 mt-md-0">
                    <button type="button" id="populateDummyBtn" class="btn btn-primary">
                        <i class="fas fa-database"></i> {{ __('Populate Dummy Data') }}
                    </button>
                    <button type="button" id="deleteDummyBtn" class="btn btn-danger">
                        <i class="fas fa-trash"></i> {{ __('Delete Dummy Data') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@section('script')
<script>
$(document).ready(function () {
    function updateCounts(counts) {
        if (!counts) {
            return;
        }
        $('#dummyCategoriesCount').text(counts.categories);
        $('#dummyCustomFieldsCount').text(counts.custom_fields);
        $('#dummyItemsCount').text(counts.items);
    }

    $('#populateDummyBtn').on('click', function () {
        let button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: "{{ route('settings.dummy-data.import') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                items_count: $('#itemsCount').val()
            },
            success: function (response) {
                if (response.error === false) {
                    showSuccessToast(response.message);
                    updateCounts(response.data ? response.data.counts : null);
                } else {
                    showErrorToast(response.message);
                }
            },
            error: function () {
                showErrorToast("{{ __('Something Went Wrong') }}");
            },
            complete: function () {
                button.prop('disabled', false);
            }
        });
    });

    $('#deleteDummyBtn').on('click', function () {
        showSweetAlertForDataConfirmPopup(
            "{{ route('settings.dummy-data.delete') }}",
            "POST",
            {
                text: "{{ __('This will remove all dummy advertisements, categories and custom fields. Your real data stays untouched.') }}",
                confirmButtonText: "{{ __('Yes, Delete Dummy Data') }}",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                successCallBack: function (response) {
                    updateCounts(response && response.data ? response.data.counts : null);
                }
            }
        );
    });
});
</script>
@endsection
