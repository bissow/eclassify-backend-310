@extends('layouts.main')

@section('title')
    {{ __('Bulk Upload Stores & Shops') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row d-flex align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 text-end">
                <a href="{{ route('stores.index') }}" class="btn btn-secondary mb-0">
                    <i class="ph ph-arrow-left me-1"></i> {{ __('Back to Stores') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">
            <!-- Instructions & Reference Guide -->
            <div class="col-md-12 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="ph ph-info me-2"></i> {{ __('Stores Bulk Upload Guide & Field Specifications') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Section 1: Core Instructions -->
                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded bg-light">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center p-2 me-2" style="width: 36px; height: 36px;">
                                            <i class="ph ph-file-text"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">{{ __('General Rules') }}</h6>
                                    </div>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="ph ph-check-circle text-success me-2 mt-1"></i>
                                            <span>{{ __('Download the example CSV file to see the expected columns and formatting.') }}</span>
                                        </li>
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="ph ph-check-circle text-success me-2 mt-1"></i>
                                            <span>{{ __('User Identifier matches an existing registered user by Email, Mobile number, or User ID.') }}</span>
                                        </li>
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="ph ph-check-circle text-success me-2 mt-1"></i>
                                            <span>{{ __('Store Name is mandatory. Slugs will be auto-generated if left empty.') }}</span>
                                        </li>
                                        <li class="mb-0 d-flex align-items-start">
                                            <i class="ph ph-check-circle text-success me-2 mt-1"></i>
                                            <span>{{ __('If a user already owns a store, bulk upload updates their existing store details.') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Section 2: Field Values Reference -->
                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded bg-light">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center p-2 me-2" style="width: 36px; height: 36px;">
                                            <i class="ph ph-table"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">{{ __('Field Format Reference') }}</h6>
                                    </div>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-2">
                                            <i class="ph ph-dot-outline text-primary me-1"></i>
                                            <strong>{{ __('Is Verified:') }}</strong> 1 = {{ __('Verified (Locked)'), 0 = {{ __('Unverified') }}
                                        </li>
                                        <li class="mb-2">
                                            <i class="ph ph-dot-outline text-primary me-1"></i>
                                            <strong>{{ __('Status:') }}</strong> 1 = {{ __('Active'), 0 = {{ __('Inactive') }}
                                        </li>
                                        <li class="mb-2">
                                            <i class="ph ph-dot-outline text-primary me-1"></i>
                                            <strong>{{ __('Hours:') }}</strong> {{ __('e.g. 09:00 AM and 08:00 PM') }}
                                        </li>
                                        <li class="mb-0">
                                            <i class="ph ph-dot-outline text-primary me-1"></i>
                                            <strong>{{ __('Working Days:') }}</strong> {{ __('Comma-separated (Monday,Tuesday,Wednesday,Thursday,Friday,Saturday)') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Section 3: Image Gallery Guide -->
                            <div class="col-md-4">
                                <div class="h-100 p-3 border rounded bg-light">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center p-2 me-2" style="width: 36px; height: 36px;">
                                            <i class="ph ph-image"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">{{ __('Image Gallery Guide') }}</h6>
                                    </div>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="ph ph-arrow-right text-info me-2 mt-1"></i>
                                            <span>{{ __('Click Open Image Gallery below to upload store logos and banners in batch.') }}</span>
                                        </li>
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="ph ph-arrow-right text-info me-2 mt-1"></i>
                                            <span>{{ __('Click Copy Path on any uploaded image to copy its server relative path.') }}</span>
                                        </li>
                                        <li class="mb-0 d-flex align-items-start">
                                            <i class="ph ph-arrow-right text-info me-2 mt-1"></i>
                                            <span>{{ __('Paste the copied path into Logo Path or Banner Path columns.') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Template & Gallery Action Cards -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="ph ph-download-simple me-2"></i> {{ __('Download Template & Gallery') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="p-3 border rounded mb-3">
                                    <h6 class="fw-bold mb-1"><i class="ph ph-file-csv text-success me-1"></i> {{ __('Download Sample CSV Template') }}</h6>
                                    <p class="text-muted small mb-3">{{ __('Get the pre-formatted CSV template with sample data rows.') }}</p>
                                    <a href="{{ route('stores.bulk-upload.example') }}" class="btn btn-success">
                                        <i class="ph ph-download-simple me-1"></i> {{ __('Download Example CSV') }}
                                    </a>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <h6 class="fw-bold mb-1"><i class="ph ph-images text-info me-1"></i> {{ __('Store Image Gallery') }}</h6>
                                    <p class="text-muted small mb-3">{{ __('Upload and manage store logos and cover banners to copy their paths.') }}</p>
                                    <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#galleryModal">
                                        <i class="ph ph-images me-1"></i> {{ __('Open Image Gallery') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="ph ph-upload-simple me-2"></i> {{ __('Upload & Process Stores File') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('stores.bulk-upload.process') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                            @csrf
                            <div class="mb-3">
                                <label for="excel_file" class="form-label fw-bold">
                                    {{ __('Select Spreadsheet File') }} <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="excel_file" id="excel_file" class="form-control form-control-lg" accept=".csv,.xlsx,.xls" required>
                                <div class="form-text">
                                    <i class="ph ph-info me-1"></i> {{ __('Supported formats: CSV, XLSX, XLS (Max: 10MB)') }}
                                </div>
                            </div>

                            <div class="alert alert-light border mb-4 small">
                                <div class="d-flex align-items-center gap-2 mb-1 fw-bold text-dark">
                                    <i class="ph ph-lightbulb text-warning fs-5"></i> {{ __('Important Note:') }}
                                </div>
                                <p class="mb-0 text-muted">{{ __('Ensure the first row remains the header row. Blank rows will be skipped automatically.') }}</p>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" id="bulkUploadSubmitBtn">
                                <i class="ph ph-upload-simple me-1"></i> {{ __('Upload and Process Stores') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Gallery Modal -->
        <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title text-white fw-bold" id="galleryModalLabel">
                            <i class="ph ph-images me-2"></i> {{ __('Store Image Gallery') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Upload New Images -->
                        <div class="card border shadow-none mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="ph ph-upload text-primary me-1"></i> {{ __('Upload New Store Images') }}</h6>
                                <form id="galleryUploadForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <input type="file" name="images[]" id="galleryImageInput" class="form-control form-control-lg" accept=".jpg,.jpeg,.png,.webp" multiple required>
                                        <button type="submit" class="btn btn-primary text-nowrap" id="galleryUploadBtn">
                                            <i class="ph ph-upload me-1"></i> {{ __('Upload Images') }}
                                        </button>
                                    </div>
                                    <small class="text-muted mt-2 d-block">{{ __('You can select multiple images at once (PNG, JPG, JPEG, WEBP - Max: 5MB per file).') }}</small>
                                </form>
                            </div>
                        </div>

                        <!-- Gallery Images List -->
                        <h6 class="fw-bold mb-3"><i class="ph ph-image text-success me-1"></i> {{ __('Uploaded Images') }}</h6>
                        <div id="galleryImagesList" class="row g-3" style="max-height: 420px; overflow-y: auto;">
                            <div class="col-12 text-center py-4 text-muted">
                                <i class="ph ph-spinner fa-spin fs-2"></i>
                                <p class="mt-2 mb-0">{{ __('Loading images...') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            // Gallery Image Loader
            function loadGalleryImages() {
                $.ajax({
                    url: '{{ route("stores.bulk-upload.gallery.list") }}',
                    type: 'GET',
                    success: function (res) {
                        if (res.status === 'success' && res.images) {
                            if (res.images.length === 0) {
                                $('#galleryImagesList').html('<div class="col-12 text-center py-4 text-muted"><i class="ph ph-images fs-1 text-secondary"></i><p class="mt-2 mb-0">{{ __("No images found. Upload some images above.") }}</p></div>');
                                return;
                            }

                            var html = '';
                            res.images.forEach(function (img) {
                                html += '<div class="col-6 col-md-4 col-lg-3">' +
                                    '<div class="card h-100 border shadow-sm">' +
                                    '<img src="' + img.url + '" class="card-img-top" style="height: 120px; object-fit: cover; cursor: pointer;" onclick="copyImagePath(\'' + img.path + '\')">' +
                                    '<div class="card-body p-2">' +
                                    '<input type="text" class="form-control form-control-sm mb-2 text-truncate" value="' + img.path + '" readonly style="font-size: 11px;">' +
                                    '<button type="button" class="btn btn-sm btn-primary w-100 copy-path-btn" data-path="' + img.path + '">' +
                                    '<i class="ph ph-copy me-1"></i> {{ __("Copy Path") }}' +
                                    '</button>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>';
                            });
                            $('#galleryImagesList').html(html);
                        } else {
                            $('#galleryImagesList').html('<div class="col-12 text-center py-4 text-danger">{{ __("Failed to load images.") }}</div>');
                        }
                    },
                    error: function () {
                        $('#galleryImagesList').html('<div class="col-12 text-center py-4 text-danger">{{ __("Error loading gallery.") }}</div>');
                    }
                });
            }

            $('#galleryModal').on('shown.bs.modal', function () {
                loadGalleryImages();
            });

            // Upload Gallery Images Form
            $('#galleryUploadForm').on('submit', function (e) {
                e.preventDefault();
                var btn = $('#galleryUploadBtn');
                var origHtml = btn.html();
                btn.prop('disabled', true).html('<i class="ph ph-spinner fa-spin me-1"></i> {{ __("Uploading...") }}');

                var formData = new FormData(this);
                $.ajax({
                    url: '{{ route("stores.bulk-upload.gallery.upload") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message || '{{ __("Images uploaded successfully") }}');
                            $('#galleryImageInput').val('');
                            loadGalleryImages();
                        } else {
                            toastr.error(res.message || '{{ __("Failed to upload images") }}');
                        }
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || '{{ __("Error uploading images") }}';
                        toastr.error(msg);
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(origHtml);
                    }
                });
            });

            // Copy Image Path Helper
            window.copyImagePath = function (path) {
                navigator.clipboard.writeText(path).then(function () {
                    toastr.success('{{ __("Path copied to clipboard:") }} ' + path);
                }).catch(function () {
                    toastr.info(path);
                });
            };

            $(document).on('click', '.copy-path-btn', function () {
                var p = $(this).data('path');
                copyImagePath(p);
            });

            // Process Bulk Upload Form
            $('#bulkUploadForm').on('submit', function (e) {
                e.preventDefault();
                var btn = $('#bulkUploadSubmitBtn');
                var origHtml = btn.html();
                btn.prop('disabled', true).html('<i class="ph ph-spinner fa-spin me-1"></i> {{ __("Processing Stores...") }}');

                var formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.error === false) {
                            toastr.success(res.message || '{{ __("Stores processed successfully!") }}');
                            $('#bulkUploadForm')[0].reset();
                        } else {
                            toastr.error(res.message || '{{ __("Bulk upload encountered errors.") }}');
                        }
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON?.message || '{{ __("An unexpected error occurred during bulk upload.") }}';
                        toastr.error(msg);
                    },
                    complete: function () {
                        btn.prop('disabled', false).html(origHtml);
                    }
                });
            });
        });
    </script>
@endsection
