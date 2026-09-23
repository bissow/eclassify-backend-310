@extends('layouts.main')
@section('title')
    {{ __('Create Categories') }}
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
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('category.index') }}">
                < {{ __('Back to All Categories') }} </a>
        </div>
        <div class="row">
            <form action="{{ route('category.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                @csrf
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{ __('Add Category') }}</div>

                        <div class="card-body mt-2">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                @foreach ($languages as $key => $lang)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if ($key == 0) active @endif"
                                            id="tab-{{ $lang->id }}" data-bs-toggle="tab"
                                            data-bs-target="#lang-{{ $lang->id }}" type="button" role="tab">
                                            {{ $lang->name }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content mt-3">
                                @foreach ($languages as $key => $lang)
                                    <div class="tab-pane fade @if ($key == 0) show active @endif"
                                        id="lang-{{ $lang->id }}" role="tabpanel">
                                        <input type="hidden" name="languages[]" value="{{ $lang->id }}">

                                        <div class="form-group">
                                            <label>{{ __('Name') }} ({{ $lang->name }})</label> <span class="text-danger">*</span>
                                            <input type="text" name="name[{{ $lang->id }}]" class="form-control"
                                                placeholder="{{ __('Enter Title') }}"
                                                value=""
                                                data-parsley-maxlength="191"
                                                maxlength="191"
                                                data-parsley-maxlength-message="{{ __('Name cannot exceed 191 characters.') }}"
                                                @if ($lang->id == 1) data-parsley-required="true" @endif>
                                        </div>

                                        @if ($lang->id == 1)
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group">
                                                        <label for="category_slug" class="form-label">{{ __('Slug') }}
                                                            <small>{{ __('(English Only)') }}</small></label>
                                                        <input type="text" name="slug" id="category_slug"
                                                            class="form-control" data-parsley-pattern="^[a-zA-Z0-9\-_]+$"
                                                            data-parsley-pattern-message="{{ __('Slug must be only English letters, numbers, hyphens (-) or underscores (_).') }}"
                                                            placeholder="auto-generated if blank">
                                                        <label>
                                                            <small
                                                                class="text-danger">{{ __('Note: Slug must be in English letters, numbers, hyphens (-) or underscores (_). No spaces or special characters.') }}</small>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group">
                                                        <label for="p_category"
                                                            class="form-label">{{ __('Parent Category') }}</label>
                                                        <select name="parent_category_id" id="p_category"
                                                            class="form-select form-control select2"
                                                            data-placeholder="{{ __('Select Category') }}">
                                                            <option value="">{{ __('Select a Category') }}</option>
                                                            @include('category.dropdowntree', [
                                                                'categories' => $categories,
                                                            ])
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="Field Name"
                                                            class="mandatory form-label">{{ __('Image') }}</label>
                                                        <input type="file" name="image" id="image"
                                                            class="form-control" data-parsley-required="true"
                                                            accept=".jpg,.jpeg,.png">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row mt-3">
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="status" id="status"
                                                                    value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch" id="statusSwitch">
                                                                <label class="form-check-label"
                                                                    for="statusSwitch">{{ __('Active') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_job_category"
                                                                    id="is_job_category" value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch" id="jobCategorySwitch">
                                                                <label class="form-check-label"
                                                                    for="jobCategorySwitch">{{ __('Job Category') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="price_optional"
                                                                    id="price_optional" value="0">
                                                                <input class="form-check-input status-switch"
                                                                    type="checkbox" role="switch"
                                                                    id="priceOptionalSwitch">
                                                                <label class="form-check-label"
                                                                    for="priceOptionalSwitch">{{ __('Price Optional') }}</label>
                                                            </div>
                                                        </div>
                                                        {{-- <div class="col-md-3">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_featured" id="is_featured" value="0">
                                                                <input class="form-check-input status-switch" type="checkbox" role="switch" id="isfeaturedSwitch">
                                                                <label class="form-check-label" for="isfeaturedSwitch">{{ __('Featured Category') }}</label>
                                                            </div>
                                                        </div> --}}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @include('components.seo-fields', ['lang' => $lang, 'seoTranslations' => []])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="{{ __('Save and Back') }}">
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[data-parsley-validate]');
        if (!form) return;

        const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
        form.addEventListener('submit', function(e) {
            // Use Parsley to check validity if initialized
            if (typeof $(form).parsley === 'function') {
                if (!$(form).parsley().isValid()) {
                    // If invalid, do NOT disable the button, allow user to correct form
                    return;
                }
            }
            // Disable submit button on valid submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = '{{ __('Saving...') }}';
            }
        });
    });

    // Auto-switch to the tab containing the first Parsley validation error
    $(document).ready(function () {
        $('form[data-parsley-validate]').parsley().on('form:error', function () {
            // Find all fields that failed validation
            this.fields.forEach(function (field) {
                if (!field.isValid()) {
                    var $field = $(field.element);
                    // Walk up to find the parent tab-pane
                    var $tabPane = $field.closest('.tab-pane');
                    if ($tabPane.length && !$tabPane.hasClass('active')) {
                        var paneId = $tabPane.attr('id');
                        // Find the tab button that targets this pane
                        var $tabBtn = $('[data-bs-target="#' + paneId + '"]');
                        if ($tabBtn.length) {
                            // Use Bootstrap's Tab API to switch
                            var tabInstance = new bootstrap.Tab($tabBtn[0]);
                            tabInstance.show();
                        }
                        // Stop after switching to the first offending tab
                        return false;
                    }
                }
            });
        });
    });
</script>
