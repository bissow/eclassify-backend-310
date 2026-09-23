@extends('layouts.main')

@section('title')
    {{ __('Gemini AI Settings') }}
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
        <form method="POST" action="{{ route('settings.gemini-settings.store') }}" data-parsley-validate class="create-form" data-success-function="formSuccessFunction">
        @csrf

        <div class="row">
            {{-- API Configuration --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('API Configuration') }}</h6>
                        </div>

                        {{-- Enable/Disable --}}
                        <div class="form-group mt-3">
                            <label class="form-label" for="gemini_ai_enabled">{{ __('Enable Gemini AI') }}</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="gemini_ai_enabled"
                                       id="gemini_ai_enabled" value="1"
                                       {{ ($settings['gemini_ai_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                <input type="hidden" name="gemini_ai_enabled" value="0" id="gemini_ai_enabled_hidden">
                                <label class="form-check-label" for="gemini_ai_enabled">
                                    {{ __('Enable AI features for content generation') }}
                                </label>
                            </div>
                        </div>

                        {{-- Auto Translate --}}
                        <div class="form-group mt-3">
                            <label class="form-label" for="gemini_auto_translate_enabled">{{ __('Auto Translate Ad Details') }}</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="gemini_auto_translate_enabled"
                                       id="gemini_auto_translate_enabled" value="1"
                                       {{ ($settings['gemini_auto_translate_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                <input type="hidden" name="gemini_auto_translate_enabled" value="0" id="gemini_auto_translate_enabled_hidden">
                                <label class="form-check-label" for="gemini_auto_translate_enabled">
                                    {{ __('Allow auto-translating item name and description into other languages') }}
                                </label>
                            </div>
                        </div>

                        {{-- API Key --}}
                        <div class="form-group mt-3 position-relative has-icon-right">
                            <label class="form-label" for="gemini_api_key">{{ __('Gemini API Key') }}</label>
                            <input name="gemini_api_key" type="password" class="form-control" id="gemini_api_key" placeholder="{{ __('Enter Gemini API Key') }}" value="{{ config('app.demo_mode') ? '****************************' : ($settings['gemini_api_key'] ?? '') }}">
                            <div class="form-control-icon lh-1 top-0 mt-2">
                                <i class="bi bi-eye toggle-password"></i>
                            </div>
                        </div>

                        {{-- Model Selection --}}
                        <div class="form-group mt-3">
                            <label class="form-label" for="gemini_model">{{ __('Gemini Model') }}</label>
                            <div class="input-group">
                                <select name="gemini_model" id="gemini_model" class="form-control">
                                    @php $currentModel = $settings['gemini_model'] ?? 'gemini-2.5-flash-lite'; @endphp
                                    <option value="{{ $currentModel }}" selected>{{ $currentModel }}</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" id="fetch-models-btn">
                                    <i class="fas fa-sync-alt"></i> {{ __('Fetch Models') }}
                                </button>
                            </div>
                            <div id="fetch-models-loading" class="d-none text-primary mt-1">
                                <small><i class="bi bi-hourglass-split"></i> {{ __('Fetching models from Google...') }}</small>
                            </div>
                            <div id="model-info" class="mt-2 p-2 bg-light rounded border" style="display: none;"></div>
                        </div>
                    </div>

                    {{-- Rate Limits --}}

                    <!-- Global Rate Limits -->
                    <div class="card-body">
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('Global Rate Limits (per day)') }}</h6>
                        </div>

                        <div class="row">
                            <!-- Description Limit -->
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label class="form-label">{{ __('Description Limit') }}</label>
                                    <input name="gemini_description_limit_global" type="number" class="form-control"
                                           min="0" max="1000" required
                                           value="{{ $settings['gemini_description_limit_global'] ?? 100 }}">
                                    <small class="text-muted">{{ __('0 = unlimited') }}</small>
                                </div>
                            </div>

                            <!-- Meta Details Limit -->
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label class="form-label">{{ __('Meta Details Limit') }}</label>
                                    <input name="gemini_meta_limit_global" type="number" class="form-control"
                                           min="0" max="1000" required
                                           value="{{ $settings['gemini_meta_limit_global'] ?? 100 }}">
                                    <small class="text-muted">{{ __('0 = unlimited') }}</small>
                                </div>
                            </div>
                        </div>
                        

                        <!-- Per User Rate Limits -->
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('Per User Rate Limits (per day)') }}</h6>
                        </div>

                        <div class="row">
                            <!-- Description Limit -->
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label class="form-label">{{ __('Description Limit') }}</label>
                                    <input name="gemini_description_limit" type="number" class="form-control"
                                           min="0" max="1000" required
                                           value="{{ $settings['gemini_description_limit'] ?? 10 }}">
                                    <small class="text-muted">{{ __('0 = unlimited') }}</small>
                                </div>
                            </div>

                            <!-- Meta Details Limit -->
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label class="form-label">{{ __('Meta Details Limit') }}</label>
                                    <input name="gemini_meta_limit" type="number" class="form-control"
                                           min="0" max="1000" required
                                           value="{{ $settings['gemini_meta_limit'] ?? 10 }}">
                                    <small class="text-muted">{{ __('0 = unlimited') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
                    </div>
                </div>
            </div>

            {{-- Cache Management --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('Cache Management') }}</h6>
                        </div>
                        <p class="text-muted">{{ __('Clear all cached AI-generated content. This will force regeneration on next request.') }}</p>
                        <button type="button" class="btn btn-warning" id="clear-cache-btn">
                            <i class="bi bi-trash"></i> {{ __('Clear AI Cache') }}
                        </button>
                        <div id="cache-clear-loading" class="d-none text-primary mt-2">
                            <small><i class="bi bi-hourglass-split"></i> {{ __('Clearing cache...') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </form>
    </section>
@endsection

@section('script')
    <script>
        let fetchedModels = [];

        function updateModelInfo() {
            const model = $('#gemini_model').val();
            const modelData = fetchedModels.find(m => m.name === model);

            if (modelData) {
                let html = `<small class="text-muted">`;
                html += `<strong>${modelData.displayName}</strong>`;
                html += ` &mdash; Input: ${(modelData.inputTokenLimit / 1024).toFixed(0)}K tokens, Output: ${(modelData.outputTokenLimit / 1024).toFixed(0)}K tokens`;
                if (modelData.name.includes('preview')) {
                    html += ` <span class="badge bg-secondary ms-1">Preview</span>`;
                }
                html += `</small>`;
                $('#model-info').html(html).show();
            } else {
                $('#model-info').hide();
            }
        }

        function formSuccessFunction(response) {
            if (!response.error) {
                setTimeout(() => { window.location.reload(); }, 500);
            }
        }

        $(document).ready(function () {
            // Handle hidden field for checkbox toggle
            $('#gemini_ai_enabled').on('change', function () {
                $('#gemini_ai_enabled_hidden').prop('disabled', this.checked);
            }).trigger('change');

            $('#gemini_auto_translate_enabled').on('change', function () {
                $('#gemini_auto_translate_enabled_hidden').prop('disabled', this.checked);
            }).trigger('change');

            // Model change - update info
            $('#gemini_model').on('change', updateModelInfo);
            updateModelInfo();

            // Fetch Models from Google API
            $('#fetch-models-btn').on('click', function () {
                const btn = $(this);
                const loadingDiv = $('#fetch-models-loading');
                const apiKey = $('#gemini_api_key').val();

                btn.prop('disabled', true);
                loadingDiv.removeClass('d-none');

                $.ajax({
                    url: '{{ route("settings.gemini-settings.fetch-models") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { api_key: apiKey },
                    success: function (response) {
                        if (!response.error && response.data) {
                            fetchedModels = response.data;
                            const currentModel = $('#gemini_model').val();
                            const select = $('#gemini_model');
                            select.empty();

                            response.data.forEach(function (model) {
                                const isPreview = model.name.includes('preview');
                                const label = model.displayName + (isPreview ? ' (Preview)' : '');
                                const selected = model.name === currentModel ? 'selected' : '';
                                select.append(`<option value="${model.name}" ${selected}>${label}</option>`);
                            });

                            // If current model not in list, add it
                            if (!response.data.find(m => m.name === currentModel)) {
                                select.prepend(`<option value="${currentModel}" selected>${currentModel} ({{ __('current') }})</option>`);
                            }

                            updateModelInfo();

                            Toastify({ text: response.data.length + ' {{ __("models loaded") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            Toastify({ text: response.message || '{{ __("Failed to fetch models") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    },
                    error: function (xhr) {
                        Toastify({ text: xhr.responseJSON?.message || '{{ __("Failed to fetch models. Check API key.") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                        loadingDiv.addClass('d-none');
                    }
                });
            });

            // Clear AI Cache
            $('#clear-cache-btn').on('click', function () {
                if (!confirm('{{ __("Are you sure you want to clear all AI cache?") }}')) return;

                const btn = $(this);
                const loadingDiv = $('#cache-clear-loading');
                btn.prop('disabled', true);
                loadingDiv.removeClass('d-none');

                $.ajax({
                    url: '{{ route("settings.gemini-settings.clear-cache") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        Toastify({ text: response.message || (response.error ? '{{ __("Failed") }}' : '{{ __("Cache cleared") }}'), duration: 3000, close: true, backgroundColor: response.error ? '#dc3545' : 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                    },
                    error: function (xhr) {
                        Toastify({ text: xhr.responseJSON?.message || '{{ __("An error occurred") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                        loadingDiv.addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection
