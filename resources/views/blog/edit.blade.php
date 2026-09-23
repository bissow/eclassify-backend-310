@extends('layouts.main')
@section('title')
    {{__("Edit Blogs")}}
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
            <a class="btn btn-primary" href="{{ route('blog.index') }}">< {{__("Back to All Blogs")}} </a>
        </div>
        <div class="row">
            <form action="{{ route('blog.update', $blog->id) }}" id="blog-ajax-form" method="POST" data-parsley-validate enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <input type="hidden" name="edit_data" value="{{ $blog->id }}">
                <div class="card">
                    <div class="card-header">{{__("Edit Blogs")}}</div>
                    <div class="card-body mt-3">
                        <ul class="nav nav-tabs" id="languageTabs" role="tablist">
                            @foreach($languages as $lang)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ $lang->id == $defaultLanguage->id ? 'active' : '' }}" data-bs-toggle="tab" href="#lang-{{ $lang->id }}">
                                        {{ $lang->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mt-3">
                            @foreach($languages as $lang)
                                @php
                                    $isDefault = $lang->id == $defaultLanguage->id;
                                    $trans = isset($translations) ? ($translations instanceof \Illuminate\Support\Collection ? $translations->get($lang->id) : ($translations[$lang->id] ?? null)) : null;
                                    if ($isDefault) {
                                        $tagsRaw = $blog->tags ?? '';
                                    } else {
                                        $tagsRaw = $trans->tags ?? '';
                                    }
                                    if (is_string($tagsRaw)) {
                                        $decoded = json_decode($tagsRaw, true);
                                        $tagsArr = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $tagsRaw)));
                                    } else {
                                        $tagsArr = is_array($tagsRaw) ? $tagsRaw : [];
                                    }
                                @endphp
                                <div class="tab-pane fade {{ $isDefault ? 'show active' : '' }}" id="lang-{{ $lang->id }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ __("Title") }} ({{ $lang->name }}) @if($isDefault)<span class="text-danger">*</span>@endif</label>
                                                @if($isDefault)
                                                    <input type="text" name="title" class="form-control" data-parsley-required="true" value="{{ $blog->title ?? '' }}">
                                                @else
                                                    <input type="text" name="translations[{{ $lang->id }}][title]" class="form-control" value="{{ $trans->title ?? '' }}">
                                                @endif
                                            </div>
                                        </div>
                                        @if($isDefault)
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __("Slug") }} </label>
                                                    <input type="text" name="slug" class="form-control" data-parsley-pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" value="{{ $blog->slug ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __("Image") }}</label>
                                                    @if(!empty($blog->image))
                                                        <img src="{{ $blog->image }}" alt="Image" style="max-height: 100px;" class="mb-2 d-block">
                                                    @endif
                                                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __("Category") }} <span class="text-danger">*</span></label>
                                                    <select name="category_id" class="form-control select-search" data-placeholder="{{ __('Select Category') }}" data-parsley-required="true">
                                                        <option value="">{{ __('Select Category') }}</option>
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}" {{ $blog->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>{{ __("Tags") }} ({{ $lang->name }}) @if($isDefault)<span class="text-danger">*</span>@endif</label>
                                                @if($isDefault)
                                                    <select name="tags[]" data-tags="true" data-placeholder="{{__("Tags")}}" data-allow-clear="true" class="select-search col-12 w-100" multiple="multiple" data-parsley-required="true">
                                                        @foreach($tagsArr as $tag)
                                                            <option value="{{ $tag }}" selected>{{ $tag }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <select name="translations[{{ $lang->id }}][tags][]" data-tags="true" data-placeholder="{{__("Tags")}}" data-allow-clear="true" class="select-search col-12 w-100" multiple="multiple">
                                                        @foreach($tagsArr as $tag)
                                                            <option value="{{ $tag }}" selected>{{ $tag }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>{{ __("Description") }} ({{ $lang->name }}) @if($isDefault)<span class="text-danger">*</span>@endif</label>
                                                @if($isDefault)
                                                    <textarea name="blog_description" id="tinymce_editor_{{ $lang->id }}" class="tinymce_editor form-control" rows="5" data-parsley-required="true">{{ $blog->description ?? '' }}</textarea>
                                                @else
                                                    <textarea name="translations[{{ $lang->id }}][description]" id="tinymce_editor_{{ $lang->id }}" class="tinymce_editor form-control" rows="5">{{ $trans->description ?? '' }}</textarea>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            @include('components.seo-fields', ['lang' => $lang, 'seoTranslations' => $seoTranslations ?? []])
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <input type="submit" class="btn btn-primary" value="{{__("Save and Back")}}">
                </div>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        tinymce.init({
            selector: '.tinymce_editor',
            height: 400,
            menubar: false,
            plugins: [
                'advlist autolink lists link charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat | code',
            setup: function (editor) {
                editor.on("change keyup", function () {
                    editor.save();
                });
            }
        });

        const tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                tinymce.execCommand('mceRemoveEditor', false, null);
                tinymce.init({
                    selector: '.tinymce_editor',
                    height: 400,
                    menubar: false,
                    plugins: 'lists link image table code',
                    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | code'
                });
            });
        });
    });

    $(function() {
        const $form = $('#blog-ajax-form');
        if ($form.length && typeof $form.parsley === 'function') {
            $form.parsley().on('form:error', function() {
                const $firstErr = $form.find('.parsley-error, [data-parsley-error]').first();
                if (!$firstErr.length) return;
                const $pane = $firstErr.closest('.tab-pane');
                if (!$pane.length) return;
                const tabTrigger = document.querySelector(`a[data-bs-toggle="tab"][href="#${$pane.attr('id')}"]`);
                if (tabTrigger && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
                } else if (tabTrigger) {
                    $(tabTrigger).tab('show');
                }
                setTimeout(() => {
                    $firstErr[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 200);
            });
        }
    });

    $(document).on('submit', '#blog-ajax-form', function (e) {
        e.preventDefault();
        let formElement = $(this);
        let submitButtonElement = $(this).find(':submit');
        if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }
        let data = new FormData(this);
        function successCallback(response) {
            setTimeout(function () {
                let url = (response && response.data && response.data.redirect_url) ? response.data.redirect_url : "{{ route('blog.index') }}";
                window.location.href = url;
            }, 800);
        }
        formAjaxRequest('POST', $(this).attr('action'), data, formElement, submitButtonElement, successCallback);
    });
</script>
@endsection
