@extends('layouts.main')
@section('title')
    {{ __('Create Chat Template') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form id="chat_template_form" action="{{ route('chat-templates.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="cs-card">
                        <div class="cs-header">
                            <p class="cs-title">{{ __('Basic Information') }}</p>
                        </div>
                        <div class="ql-body">
                            <div>
                                <label class="cs-field-label">{{ __('Title') }} <span class="text-danger">*</span></label>
                                <ul class="nav nav-tabs" id="nameLangTabs" role="tablist">
                                    @foreach ($languages as $key => $lang)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if ($key == 0) active @endif" id="name-tab-{{ $lang->id }}"
                                                data-bs-toggle="tab" data-bs-target="#name-lang-{{ $lang->id }}" type="button" role="tab">
                                                {{ $lang->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content mt-2">
                                    @foreach ($languages as $key => $lang)
                                        <div class="tab-pane fade @if ($key == 0) show active @endif" id="name-lang-{{ $lang->id }}" role="tabpanel">
                                            <input type="text" name="name[{{ $lang->id }}]" class="cs-form-input"
                                                placeholder="{{ __('Enter template name') }}"
                                                @if ($lang->id == 1) required @endif maxlength="255">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="status" checked>
                                <input type="hidden" name="status" class="status-hidden" value="1">
                                <label class="form-check-label" for="status">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>

                    @include('chat-templates.question-section', [
                        'title' => __('Quick chats for buyer'),
                        'subtitle' => null,
                        'field_base' => 'customer_questions',
                        'questions' => [],
                        'languages' => $languages,
                        'default_lang_id' => 1,
                        'empty_text' => __('No message added yet. Click "Add Message" to configure conversation starters.'),
                    ])

                    @include('chat-templates.question-section', [
                        'title' => __('Quick chats for seller'),
                        'subtitle' => null,
                        'field_base' => 'seller_questions',
                        'questions' => [],
                        'languages' => $languages,
                        'default_lang_id' => 1,
                        'empty_text' => __('No message added yet. Click "Add Message" to configure conversation starters.'),
                    ])
                </div>

                <div class="col-md-6 col-sm-12">
                    @include('category.category-scope', [
                        'categories' => $categories,
                        'selected_categories' => $selected_categories,
                        'selected_all_categories' => $selected_all_categories,
                        'wrapper_id' => 'category_selection_scope',
                        'subtitle' => __('Select the categories where this Chat Template Should be Applied'),
                        'show_global' => true,
                        'global_id' => 'is_global',
                        'global_label' => __('Global (Apply to All Categories)'),
                    ])
                </div>
            </div>

            <div class="col-md-12 text-end mb-3">
                <a href="{{ route('chat-templates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        $('#status').on('change', function () {
            $(this).siblings('.status-hidden').val($(this).is(':checked') ? 1 : 0);
        });

        $('#is_global').on('change', function () {
            $('#chat_template_form input[name="selected_categories[]"]').prop('disabled', $(this).is(':checked'));
        });

        $('#chat_template_form').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');

            if ($submitBtn.prop('disabled')) {
                return;
            }

            if (typeof hasUnsavedQuestionDraft === 'function' && hasUnsavedQuestionDraft()) {
                showErrorToast('{{ __('You have an unsaved question. Please confirm or cancel it before saving.') }}');
                return;
            }

            const originalBtnHtml = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + '{{ __('Saving...') }}');

            const formData = new FormData(this);

            ajaxRequest('POST', $form.attr('action'), formData, null, function (response) {
                showSuccessToast(response.message);
                setTimeout(function () {
                    window.location.href = "{{ route('chat-templates.index') }}";
                }, 1000);
            }, function (error) {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                showErrorToast(error.message);
            }, function (response) {
                if (!response || response.error) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });
    </script>
@endsection
