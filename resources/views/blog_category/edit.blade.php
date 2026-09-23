@extends('layouts.main')
@section('title')
    {{ __('Edit Blog Category') }}
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
            <a class="btn btn-primary" href="{{ route('blog-category.index') }}">
                < {{ __('Back to Blog Categories') }} </a>
        </div>
        <div class="row">
            <form action="{{ route('blog-category.update', $blogCategory->id) }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{ __('Edit Blog Category') }}</div>

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
                                                placeholder="{{ __('Enter Name') }}"
                                                value="{{ $translations[$lang->id]['name'] ?? '' }}"
                                                data-parsley-maxlength="191"
                                                maxlength="191"
                                                data-parsley-maxlength-message="{{ __('Name cannot exceed 191 characters.') }}"
                                                @if ($lang->id == 1) data-parsley-required="true" @endif>
                                        </div>

                                        @if ($lang->id == 1)
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input type="hidden" name="is_active" id="is_active" value="{{ $blogCategory->is_active }}">
                                                        <input class="form-check-input status-switch" type="checkbox" role="switch" id="isActiveSwitch" {{ $blogCategory->is_active == 1 ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="isActiveSwitch">{{ __('Active') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @include('components.seo-fields', ['lang' => $lang, 'seoTranslations' => $seoTranslations ?? []])
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
            if (typeof $(form).parsley === 'function') {
                if (!$(form).parsley().isValid()) {
                    return;
                }
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = '{{ __('Saving...') }}';
            }
        });
    });

    $(document).ready(function () {
        $('form[data-parsley-validate]').parsley().on('form:error', function () {
            this.fields.forEach(function (field) {
                if (!field.isValid()) {
                    var $field = $(field.element);
                    var $tabPane = $field.closest('.tab-pane');
                    if ($tabPane.length && !$tabPane.hasClass('active')) {
                        var paneId = $tabPane.attr('id');
                        var $tabBtn = $('[data-bs-target="#' + paneId + '"]');
                        if ($tabBtn.length) {
                            var tabInstance = new bootstrap.Tab($tabBtn[0]);
                            tabInstance.show();
                        }
                        return false;
                    }
                }
            });
        });
    });
</script>
