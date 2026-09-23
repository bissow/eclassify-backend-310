@extends('layouts.main')

@section('title')
    {{ __('Edit Email Template') }} - {{ $displayName }}
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
            <form action="{{ route('settings.email-templates.store', $template) }}" method="post" class="create-form-without-reset">
                @csrf
                <div class="card-body">
                    @if($template === 'email_template_new_login')
                        <div class="form-group row mb-3">
                            <div class="col-sm-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           name="email_new_login_enabled" 
                                           id="email_new_login_enabled" 
                                           value="1"
                                           {{ ($settings['email_new_login_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_new_login_enabled">
                                        {{ __('Enable New Login Email Notification') }}
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    {{ __('When enabled, users will receive an email notification when a new device logs in to their account.') }}
                                </small>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-info-circle"></i>
                        <strong>{{ __('Note:') }}</strong>
                        {{ __('Emails will be sent using the template in the default language set in system settings.') }}
                        @php
                            $defaultLangCode = $settings['default_language'] ?? 'en';
                            $defaultLang = $languages->where('code', $defaultLangCode)->first();
                        @endphp
                        @if($defaultLang)
                            <span class="badge bg-primary ms-2">{{ __('Current Default:') }} {{ $defaultLang->name }}</span>
                        @endif
                    </div>

                    <ul class="nav nav-tabs" id="languageTabs" role="tablist">
                        @foreach($languages as $lang)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                   id="tab-{{ $lang->id }}"
                                   data-bs-toggle="tab" 
                                   href="#lang-{{ $lang->id }}" 
                                   role="tab">
                                    {{ $lang->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content mt-3">
                        @foreach($languages as $lang)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                 id="lang-{{ $lang->id }}" 
                                 role="tabpanel">
                                <input type="hidden" name="languages[]" value="{{ $lang->id }}">
                                <div class="form-group">
                                    <label>{{ __('Email Template Content') }} ({{ $lang->name }})</label>
                                    <textarea name="template_content[{{ $lang->id }}]" 
                                              id="tinymce_editor_{{ $lang->id }}" 
                                              class="tinymce_editor form-control" 
                                              rows="10">{{ old("template_content.$lang->id", $translations[$template][$lang->id] ?? ($templateValue ?? '')) }}</textarea>
                                </div>
                                
                                <div class="alert alert-info mt-2">
                                    <strong>{{ __('Available Variables:') }}</strong>
                                    <small class="d-block text-muted mb-2">{{ __('Click on any variable to insert it into the template') }}</small>
                                    @if($template === 'email_template_item_expiry')
                                        <ul class="mb-0 mt-2 list-unstyled">
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="user_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{user_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('User name') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="item_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{item_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Advertisement name') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="expiry_date" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{expiry_date}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Expiry date') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="company_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{company_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Company name') }}</span>
                                            </li>
                                        </ul>
                                    @elseif($template === 'email_template_package_expiry')
                                        <ul class="mb-0 mt-2 list-unstyled">
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="user_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{user_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('User name') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="package_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{package_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Package name') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="expiry_date" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{expiry_date}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Expiry date') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="company_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{company_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Company name') }}</span>
                                            </li>
                                        </ul>
                                    @elseif($template === 'email_template_new_login')
                                        <ul class="mb-0 mt-2 list-unstyled">
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="user_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{user_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('User name') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="device_type" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{device_type}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Device type (Android/iOS)') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="ip_address" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{ip_address}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('IP address') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="login_time" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{login_time}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Login time') }}</span>
                                            </li>
                                            <li class="mb-1">
                                                <span class="template-variable" data-variable="company_name" data-editor="tinymce_editor_{{ $lang->id }}" style="cursor: pointer;">
                                                    <code class="bg-primary text-white px-2 py-1 rounded">@{{company_name}}</code>
                                                </span>
                                                <span class="ms-2">- {{ __('Company name') }}</span>
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="col-12 mt-3 d-flex justify-content-end">
                        <a href="{{ route('settings.email-templates.index') }}" class="btn btn-secondary me-2">
                            {{ __('Cancel') }}
                        </a>
                        <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@section('script')
    <style>
        .template-variable code {
            transition: all 0.2s ease;
            display: inline-block;
        }
        .template-variable:hover code {
            background-color: #0056b3 !important;
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .template-variable:active code {
            transform: scale(0.98);
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Initialize TinyMCE editors
            const editors = {};
            tinymce.init({
                selector: '.tinymce_editor',
                height: 400,
                menubar: false,
                plugins: [
                    'advlist autolink lists link charmap preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat | code',
                setup: function(editor) {
                    // Store editor instance
                    editors[editor.id] = editor;
                    
                    editor.on("change keyup", function() {
                        editor.save();
                    });
                }
            });

            // Handle variable clicks
            document.querySelectorAll('.template-variable').forEach(function(element) {
                element.addEventListener('click', function() {
                    let variable = this.getAttribute('data-variable');
                    
                    const openBrace = '{';
                    const closeBrace = '}';
                    if (!variable.startsWith(openBrace + openBrace)) {
                        variable = openBrace + openBrace + variable + closeBrace + closeBrace;
                    }
                    
                    // Get the active tab's editor
                    const activeTab = document.querySelector('.tab-pane.active');
                    if (activeTab) {
                        const activeEditorId = activeTab.querySelector('.tinymce_editor')?.id;
                        if (activeEditorId && editors[activeEditorId]) {
                            const editor = editors[activeEditorId];
                            // Insert variable at cursor position
                            editor.insertContent(variable);
                            // Focus the editor
                            editor.focus();
                        }
                    }
                });
            });
        });
    </script>
@endsection
