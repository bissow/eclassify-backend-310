@extends('layouts.main')

@section('title')
    {{ __('Install & Activate') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12">
                <h4 class="mb-1">@yield('title')</h4>
                <p class="text-muted small mb-0">{{ __('Install the plugin and activate it to start using all available features.') }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section plugin-install-page">
        <div class="card border-0 shadow-sm install-page-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="plugin-avatar-wrapper bg-soft-primary text-primary shadow-sm me-3">
                        <i class="ph-bold ph-puzzle-piece fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">{{ $displayName ?? $slug ?? __('Plugin') }}</h5>
                        @if ($catalogItem?->slug ?? $slug)
                            <span class="text-muted small">{{ $catalogItem->slug ?? $slug }}</span>
                        @endif
                    </div>
                </div>
                <hr class="hr-subtle mb-4">

                <form class="create-form" action="{{ route('plugin-manager.install') }}" method="POST" enctype="multipart/form-data" data-success-function="installSuccess">
                    {{ csrf_field() }}

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-semibold small mb-1">{{ __('Plugin Slug') }}</label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text"><i class="ph-bold ph-puzzle-piece"></i></span>
                                    <input required name="slug" id="install_slug" type="text" class="form-control shadow-none" placeholder="PaymentGateway" value="{{ old('slug', $slug) }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-semibold small mb-1">{{ __('Purchase Code') }}</label>
                                <div class="input-group input-group-modern">
                                    <span class="input-group-text"><i class="ph-bold ph-key"></i></span>
                                    <input required name="purchase_code" type="text" class="form-control shadow-none" placeholder="xxxx-xxxx-xxxx-xxxx" value="{{ old('purchase_code') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label fw-semibold small mb-1">{{ __('Zip Package') }}</label>
                        <div class="upload-drop-zone rounded-3 text-center p-4 border border-2 border-dashed" id="drop-zone" style="min-height: 150px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                            <i class="ph-bold ph-cloud-arrow-up fs-1 text-muted mb-2 upload-icon"></i>
                            <p class="small text-muted mb-1 upload-text px-2">{{ __('Drag & drop your zip file here or click to browse') }}</p>
                            <input type="file" required name="file" id="file-input" class="d-none" accept=".zip">
                            <span class="file-name-display text-primary mt-2 d-none fw-semibold small text-truncate d-block" style="max-width: 90%;"></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('plugin-manager.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 d-flex align-items-center fw-bold">
                            <i class="ph-bold ph-download-simple me-2 fs-5"></i>{{ __('Install Plugin') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('css')
<style>
    .install-page-card {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.03) !important;
    }
    .plugin-avatar-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .bg-soft-primary {
        background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
    }
    .hr-subtle {
        opacity: 0.08;
    }
    .input-group-modern {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #ced4da;
        background-color: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .input-group-modern:focus-within {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.15);
    }
    .input-group-modern .input-group-text {
        background: transparent;
        border: none;
        padding-left: 15px;
        color: #6c757d;
    }
    .input-group-modern .form-control {
        border: none;
        padding-top: 10px;
        padding-bottom: 10px;
        box-shadow: none !important;
        background: transparent;
    }
    .upload-drop-zone {
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        border-color: #dee2e6 !important;
    }
    .upload-drop-zone:hover, .upload-drop-zone.dragover {
        border-color: var(--bs-primary) !important;
        background-color: rgba(var(--bs-primary-rgb), 0.02);
    }
    .upload-drop-zone:hover .upload-icon, .upload-drop-zone.dragover .upload-icon {
        color: var(--bs-primary) !important;
        transform: translateY(-2px);
    }
</style>
@endsection

@section('script')
    <script>
        function installSuccess() {
            setTimeout(() => {
                window.location.href = "{{ route('plugin-manager.index') }}";
            }, 1200);
        }

        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        if (dropZone && fileInput) {
            const fileDisplay = dropZone.querySelector('.file-name-display');
            const uploadText = dropZone.querySelector('.upload-text');

            dropZone.addEventListener('click', () => fileInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            ['dragleave', 'dragend'].forEach(type => {
                dropZone.addEventListener(type, () => {
                    dropZone.classList.remove('dragover');
                });
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileDisplay(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    updateFileDisplay(fileInput.files[0]);
                }
            });

            function updateFileDisplay(file) {
                if (fileDisplay) {
                    fileDisplay.textContent = file.name;
                    fileDisplay.classList.remove('d-none');
                }
                if (uploadText) {
                    uploadText.textContent = "{{ __('Change zip file') }}";
                }
            }
        }
    </script>
@endsection
