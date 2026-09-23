@extends('layouts.main')

@section('title')
    {{ __('Create Advertisement') }}
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
                <form method="POST" action="{{ route('advertisement.store') }}" class="create-form" data-parsley-validate
                    data-pre-submit-function="validateAdvertisementForm" data-success-function="handleAdvertisementSuccess" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id" value="">
                    <input type="hidden" name="item_type" id="item_type_input" value="normal">

                    <ul class="nav nav-tabs" id="addItemTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#categories"
                                data-tab-index="0">{{ __('Categories') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#listing"
                                data-tab-index="2">{{ __('Listing Details') }}</a></li>
                        <li class="nav-item" id="custom-tab-item" style="display: none;"><a class="nav-link"
                                data-bs-toggle="tab" href="#custom" data-tab-index="3">{{ __('Other Details') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#images"
                                data-tab-index="4">{{ __('Media') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#address"
                                data-tab-index="5">{{ __('Address') }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#seo"
                                data-tab-index="6">{{ __('SEO') }}</a></li>
                    </ul>

                    <div class="tab-content pt-3">

                        {{-- Listing Details --}}
                        <div class="tab-pane fade" id="listing">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label class="form-label mb-1">{{ __('Selected category') }}</label>
                                            <div id="selected-category-breadcrumb" class="text-primary"></div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if(($geminiAutoTranslateEnabled ?? false) && $languages->count() > 1)
                                                <button type="button" id="auto-translate-btn" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-language"></i> {{ __('Auto Translate') }}
                                                </button>
                                            @endif
                                            <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                            <select class="form-control form-control-sm" id="details-language-selector" style="width: 200px;">
                                                @foreach ($languages as $lang)
                                                    <option value="{{ $lang->id }}" data-code="{{ $lang->code }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                        {{ $lang->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Translatable Fields - Title and Description Only --}}
                            <div class="row">
                                {{-- Default Language Fields - Always Visible --}}
                                <div class="col-12 language-fields default-language-fields" data-language-id="{{ $defaultLanguage->id }}">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label>{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name-input" value="{{ old('name') }}"
                                                class="form-control" required placeholder="{{ __('Enter title') }}">
                                </div>

                                        <div class="col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label>{{ __('Description') }} <span class="text-danger">*</span></label>
                                                @if($geminiEnabled ?? false)
                                                    <button type="button" class="btn btn-sm btn-outline-primary generate-description-btn">
                                                        <i class="fas fa-magic"></i> {{ __('Generate with AI') }}
                                                        <span class="spinner-border spinner-border-sm d-none description-loading"></span>
                                                    </button>
                                                @endif
                                            </div>
                                            <textarea name="description" id="description-input" class="form-control" rows="5" required placeholder="{{ __('Enter description') }}">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Other Language Fields - Only Name and Description --}}
                                @foreach ($languages as $lang)
                                    @if ($lang->id != $defaultLanguage->id)
                                        <div class="col-12 language-fields other-language-fields" data-language-id="{{ $lang->id }}" style="display: none;">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label>{{ __('Title') }}</label>
                                                    <input type="text" name="translations[{{ $lang->id }}][name]" 
                                                        class="form-control translation-name" 
                                                        data-lang-id="{{ $lang->id }}"
                                                        placeholder="{{ __('Enter title') }}">
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <label>{{ __('Description') }}</label>
                                                        @if($geminiEnabled ?? false)
                                                            <button type="button" class="btn btn-sm btn-outline-primary generate-description-btn">
                                                                <i class="fas fa-magic"></i> {{ __('Generate with AI') }}
                                                                <span class="spinner-border spinner-border-sm d-none description-loading"></span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <textarea name="translations[{{ $lang->id }}][description]" 
                                                        class="form-control translation-description" 
                                                        data-lang-id="{{ $lang->id }}" rows="5"
                                                        placeholder="{{ __('Enter description') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Non-Translatable Fields - Always Visible --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label>{{ __('Currency') }}</label>
                                    <select class="form-control select2" id="currency" name="currency_id" required>
                                        <option value="">{{ __('--Select Currency--') }}</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}" data-iso-code="{{ $currency->iso_code ?? '' }}" data-symbol="{{ $currency->symbol ?? '' }}">{{ $currency->name }}{{ $currency->symbol ? ' (' . $currency->symbol . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3" id="price-field">
                                    <label>{{ __('Price') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="price" id="price-input" value="{{ old('price') }}"
                                        class="form-control" required placeholder="{{ __('Enter price') }}">
                                </div>

                                <div class="col-12 mb-3" id="salary-fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                    <label>{{ __('Min Salary') }}</label>
                                            <input type="number" name="min_salary" id="min-salary-input" class="form-control"
                                                value="{{ old('min_salary') }}" placeholder="{{ __('Enter minimum salary') }}">
                                        </div>
                                        <div class="col-12">
                                    <label>{{ __('Max Salary') }}</label>
                                    <input type="number" name="max_salary" id="max-salary-input" class="form-control"
                                                value="{{ old('max_salary') }}" placeholder="{{ __('Enter maximum salary') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Phone Number') }}</label>
                                    <input type="tel" name="contact" id="contact-input"
                                        value="{{ old('contact', auth()->user()->phone ?? '') }}" class="form-control"
                                        placeholder="{{ __('Enter phone number') }}">
                                    <input type="hidden" name="country_code" id="country-code-input" value="{{ old('country_code', auth()->user()->country_code ?? '') }}">
                                    <input type="hidden" name="region_code" id="region-code-input" value="{{ old('region_code', auth()->user()->region_code ?? '') }}">
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Slug') }}</label>
                                    <input type="text" name="slug" value="{{ old('slug') }}" class="form-control"
                                        placeholder="{{ __('Enter slug (optional)') }}">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="categories">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="custom-or-images">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- Categories Tab --}}
                        <div class="tab-pane fade show active" id="categories">
                            <div class="row">
                                <div class="col-12">
                                    <div id="categories-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">{{ __('All Category') }}</h5>
                                            <button type="button" class="btn btn-secondary btn-sm" id="go-back-category-btn" style="display: none;">
                                                <i class="fas fa-arrow-left me-1"></i>{{ __('Go Back') }}
                                            </button>
                                        </div>
                                        <div id="breadcrumb-container" style="display: none; margin-bottom: 15px;">
                                            <nav aria-label="breadcrumb">
                                                <ol class="breadcrumb mb-0" id="category-breadcrumb"></ol>
                                            </nav>
                                        </div>
                                        <div id="categories-list" class="row"></div>
                                        <div id="load-more-container" class="text-center mt-3" style="display: none;">
                                            <button type="button" class="btn btn-primary" id="load-more-categories">
                                                {{ __('Load More') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        {{-- Other Details - Custom Fields --}}
                        <div class="tab-pane fade" id="custom">
                            <div class="text-muted">{{ __('Select a category to load custom fields.') }}</div>
                        </div>

                        {{-- Media Tab --}}
                        <div class="tab-pane fade" id="images">

                            {{-- Row 1: Video Ads (reel) + Product Images --}}
                            <div class="row g-3 mb-3">

                                {{-- VIDEO ADS (reel only) --}}
                                <div class="col-md-6" id="reel-video-section" style="display:none;">
                                    <p class="fw-semibold mb-1">{{ __('Video Ads') }} <span class="text-danger">*</span></p>
                                    <div id="reel-video-upload-area" class="media-drop-zone">
                                        <div class="upload-instructions w-100">
                                            <p class="mb-1 text-secondary">{{ __('Drag & Drop your files') }}</p>
                                            <p class="mb-2 text-muted small">{{ __('or') }}</p>
                                            <a href="#" class="text-primary fw-semibold" onclick="event.preventDefault();">
                                                <i class="ph-bold ph-upload-simple me-1"></i>{{ __('Upload') }}
                                            </a>
                                        </div>
                                        <div class="upload-loading-state w-100" style="display:none;">
                                            <div class="spinner-border text-primary mb-2" role="status"></div>
                                            <p class="mb-1 text-muted fw-semibold reel-upload-status-text">{{ __('Loading...') }}</p>
                                            <div class="progress progress-sm" style="width: 80%; margin: 0 auto; display: none;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                            </div>
                                        </div>
                                        <input type="file" id="reel-video-input" name="reel_video" class="d-none" accept="video/mp4">
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">{{ __('Maximum Size') }} : {{ \App\Services\CachingService::getSystemSettings('reel_max_file_size_mb') ?: 50 }} MB</small>
                                        <small class="text-muted">{{ __('Recommended Video Ratio') }} : 9:16</small>
                                    </div>

                                    {{-- Selected reel file card --}}
                                    <div id="reel-video-selected" style="display:none;" class="mt-2">
                                        <div class="media-file-card">
                                            <canvas id="reel-thumb-preview-small" width="48" height="64" style="border-radius:4px;flex-shrink:0;background:#111;"></canvas>
                                            <div class="flex-grow-1 overflow-hidden ms-2">
                                                <div class="fw-semibold text-truncate small" id="reel-video-filename"></div>
                                                <small class="text-muted" id="reel-video-duration"></small>
                                                <small id="reel-trim-badge" style="display:none;" class="text-muted"></small>
                                            </div>
                                            <div class="d-flex gap-2 flex-shrink-0 ms-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary px-3" id="reel-edit-video-btn">{{ __('Edit') }}</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger px-3" id="reel-video-change-btn">{{ __('Delete') }}</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="reel_thumbnail_time" id="reel-thumbnail-time" value="0">
                                        <input type="hidden" name="reel_thumbnail_custom" id="reel-thumbnail-custom" value="">
                                        <input type="hidden" name="reel_thumbnail_data" id="reel-thumbnail-data" value="">
                                    </div>
                                </div>

                                {{-- PRODUCT IMAGES --}}
                                <div class="col-md-6" id="product-images-col">
                                    <p class="fw-semibold mb-1">
                                        <span id="media-images-label">{{ __('Product Images') }}</span>
                                        <span class="text-danger">*</span>
                                        <i class="fas fa-info-circle ms-1 text-muted" data-bs-toggle="tooltip" title="{{ __('Upload images for your advertisement. The first image will be highlighted and considered the main image.') }}"></i>
                                    </p>
                                    <div class="media-drop-zone" id="gallery-images-upload" onclick="document.getElementById('gallery-images-input').click()">
                                        <p class="mb-1 text-secondary">{{ __('Drag & Drop your files') }}</p>
                                        <p class="mb-2 text-muted small">{{ __('or') }}</p>
                                        <a href="#" class="text-primary fw-semibold" onclick="event.preventDefault();event.stopPropagation();document.getElementById('gallery-images-input').click()">
                                            <i class="ph-bold ph-upload-simple me-1"></i>{{ __('Upload') }}
                                        </a>
                                        <input type="file" name="gallery_images[]" id="gallery-images-input" required class="d-none" multiple accept="image/png,image/jpeg,image/jpg">
                                    </div>
                                    <div id="gallery-images-preview" class="mt-2 d-flex flex-nowrap gap-2" style="overflow: hidden; width: 100%;"></div>
                                </div>

                            </div>

                            {{-- Row 2: Product Video --}}
                            <div class="row">
                                <div class="col-12">
                                    <div class="media-product-video-wrap">
                                        <div class="media-product-video-header">
                                            <span class="fw-semibold text-secondary">{{ __('Product Video') }}</span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" id="videoTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span id="videoTypeLabel">{{ __('None') }}</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="videoTypeDropdownBtn">
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('','{{ __('None') }}');return false;">{{ __('None') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('youtube_link','{{ __('Youtube Link') }}');return false;">{{ __('Youtube Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('vimeo_link','{{ __('Vimeo Link') }}');return false;">{{ __('Vimeo Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('other_link','{{ __('Other Link') }}');return false;">{{ __('Other Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('file','{{ __('Custom') }}');return false;">{{ __('Custom') }}</a></li>
                                                </ul>
                                            </div>
                                            <input type="hidden" name="video_type" id="video_type_hidden" value="">
                                        </div>

                                        <div id="item-video-link-wrap" style="display:none;" class="p-3 border-top">
                                            <input type="url" name="video_link" id="item-video-link-input" class="form-control border-0 p-0 ignore" style="box-shadow:none;font-size:0.95rem;" placeholder="{{ __('Enter URL...') }}">
                                        </div>

                                        <div id="item-video-file-wrap" style="display:none;" class="p-3 border-top">
                                            <div class="media-drop-zone" onclick="document.getElementById('item-video-file-input').click()">
                                                <p class="mb-1 text-secondary">{{ __('Drag & Drop your files') }}</p>
                                                <p class="mb-2 text-muted small">{{ __('or') }}</p>
                                                <a href="#" class="text-primary fw-semibold" onclick="event.preventDefault();document.getElementById('item-video-file-input').click()">
                                                    <i class="ph-bold ph-upload-simple me-1"></i>{{ __('Upload') }}
                                                </a>
                                                <input type="file" name="item_video" id="item-video-file-input" class="d-none"
                                                    accept="video/mp4,video/webm,video/avi,video/quicktime"
                                                    onchange="showItemVideoFilePreview(this)">
                                            </div>
                                            <small class="text-muted d-block mt-1">{{ __('Max size') }}: {{ \App\Services\CachingService::getSystemSettings('item_video_max_file_size_mb') ?: 50 }} MB</small>
                                            <div id="item-video-file-selected" style="display:none;" class="mt-2">
                                                <div class="media-file-card">
                                                    <i class="ph-bold ph-file-video" style="font-size:32px;color:var(--bs-primary);flex-shrink:0;"></i>
                                                    <div class="flex-grow-1 ms-2 overflow-hidden">
                                                        <div class="fw-semibold text-truncate small" id="item-video-file-name"></div>
                                                        <small class="text-muted" id="item-video-file-size"></small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearItemVideoFile()">{{ __('Delete') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Reel Edit Modal --}}
                            <div class="modal fade" id="reelEditVideoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">{{ __('Edit Video') }}</h5>
                                            <button type="button" class="btn-close" id="reel-modal-x-btn"></button>
                                        </div>
                                        <div class="modal-body" style="overflow-x:hidden;">
                                            <div id="reel-step-1">
                                                <div class="row g-3 mx-0">
                                                    <div class="col-12 col-md-5 px-2">
                                                        <p class="text-muted small mb-1"><i class="ph-bold ph-info me-1"></i>{{ __('Video Ads display in 9:16') }}</p>
                                                        <div style="aspect-ratio:9/16;background:#000;border-radius:8px;overflow:hidden;max-height:240px;">
                                                            <video id="reel-video-preview" controls style="width:100%;height:100%;object-fit:contain;display:block;"></video>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-7 d-flex flex-column gap-3 px-2">
                                                        <div class="p-3 border rounded-3">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <span class="fw-semibold">{{ __('Video Timeline') }}</span>
                                                                <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none" id="reel-goto-thumb-btn">
                                                                    {{ __('Set Thumbnail') }} <i class="ph-bold ph-arrow-right ms-1"></i>
                                                                </button>
                                                            </div>
                                                            <div id="reel-trimmer-wrap" style="position:relative;border-radius:8px;overflow:visible;user-select:none;">
                                                                <div id="reel-timeline" class="d-flex" style="border-radius:8px;overflow:hidden;"></div>
                                                                <div id="reel-dim-left" style="display:none;position:absolute;top:0;left:0;height:100%;background:rgba(0,0,0,.55);pointer-events:none;border-radius:8px 0 0 8px;"></div>
                                                                <div id="reel-dim-right" style="display:none;position:absolute;top:0;right:0;height:100%;background:rgba(0,0,0,.55);pointer-events:none;border-radius:0 8px 8px 0;"></div>
                                                                <div id="reel-clip-window" style="display:none;position:absolute;top:-3px;height:calc(100% + 6px);border:3px solid #fff;border-radius:8px;cursor:grab;pointer-events:all;box-sizing:border-box;box-shadow:0 0 0 1px rgba(0,0,0,.3);">
                                                                    <div id="reel-handle-left" style="position:absolute;left:-2px;top:0;width:14px;height:100%;background:#fff;border-radius:6px 0 0 6px;cursor:ew-resize;display:flex;align-items:center;justify-content:center;z-index:2;"><div style="width:3px;height:20px;background:#aaa;border-radius:2px;"></div></div>
                                                                    <div id="reel-handle-right" style="position:absolute;right:-2px;top:0;width:14px;height:100%;background:#fff;border-radius:0 6px 6px 0;cursor:ew-resize;display:flex;align-items:center;justify-content:center;z-index:2;"><div style="width:3px;height:20px;background:#aaa;border-radius:2px;"></div></div>
                                                                    <div id="reel-playhead" style="position:absolute;top:0;left:4px;width:3px;height:100%;background:rgba(255,255,255,.9);pointer-events:none;border-radius:2px;"></div>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted mt-1 d-block" id="reel-clip-info">{{ __('Drag window to select clip') }}</small>
                                                            <input type="hidden" name="reel_start_time" id="reel-start-time" value="0">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="reel-step-2" style="display:none;">
                                                <div class="d-flex align-items-center mb-3">
                                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary text-decoration-none me-2" id="reel-back-to-step1-btn">
                                                        <i class="ph-bold ph-arrow-left me-1"></i>{{ __('Back') }}
                                                    </button>
                                                    <span class="fw-semibold">{{ __('Video Ads Thumbnails') }}</span>
                                                </div>
                                                <div class="row g-3 mx-0">
                                                    <div class="col-12 col-md-5">
                                                        <div style="aspect-ratio:9/16;background:#111;border-radius:8px;overflow:hidden;border:2px solid var(--bs-primary);position:relative;max-height:240px;">
                                                            <img id="reel-thumb-img" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
                                                            <div id="reel-thumb-placeholder" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#555;">
                                                                <i class="ph-bold ph-image" style="font-size:36px;"></i>
                                                                <small class="mt-1">{{ __('Select a frame') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-7 d-flex flex-column gap-3">
                                                        <div class="p-3 border rounded-3">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <span class="fw-semibold">{{ __('Select Thumbnail') }}</span>
                                                                <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none" id="reel-thumb-upload-btn">
                                                                    {{ __('Upload Image') }} <i class="ph-bold ph-upload-simple ms-1"></i>
                                                                </button>
                                                                <input type="file" id="reel-thumb-custom-input" class="d-none" accept="image/*">
                                                            </div>
                                                            <div id="reel-thumb-strip" class="d-flex gap-1" style="border-radius:8px;overflow:hidden;min-height:70px;"></div>
                                                            <small class="text-muted mt-1 d-block">{{ __('Tap a frame to set as thumbnail') }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <canvas id="reel-thumb-canvas" style="display:none;"></canvas>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" id="reel-modal-cancel-btn">{{ __('Cancel') }}</button>
                                            <button type="button" class="btn btn-primary" id="reel-edit-done-btn">{{ __('Done') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="custom-or-images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="address">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- Address --}}
                        <div class="tab-pane fade" id="address">
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label mb-2">{{ __('Map Address') }}</label>
                                    
                                    <!-- Search and Locate Bar -->
                                    <div class="d-flex gap-2 mb-3" style="position: relative; z-index: 1000;">
                                        <div class="flex-grow-1 position-relative" style="z-index: 1001;">
                                            <i class="fas fa-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #999; z-index: 10;"></i>
                                            <input type="text" id="location-search" class="form-control ps-5" 
                                                   placeholder="{{ __('Select Location') }}" 
                                                   style="border-radius: 5px; z-index: 1;">
                                            <div id="search-results" class="position-absolute w-100 bg-white border rounded mt-1"
                                                 style="display: none; max-height: 200px; overflow-y: auto; z-index: 1050; box-shadow: 0 4px 12px rgba(0,0,0,0.15); top: 100%;"></div>
                                        </div>
                                        <button type="button" class="btn btn-primary" id="locate-me-btn" 
                                                style="background: var(--bs-primary); border: none; white-space: nowrap; z-index: 1;">
                                            <i class="fas fa-crosshairs me-2"></i>{{ __('Locate me') }}
                                        </button>
                                    </div>

                                    <div id="map"
                                        data-map-provider="{{ $mapProvider ?? 'free_api' }}"
                                        data-google-map-key="{{ $googleMapKey ?? '' }}"
                                        data-settings-lat="{{ $defaultLatitude ?? '' }}"
                                        data-settings-lng="{{ $defaultLongitude ?? '' }}"
                                        data-get-location-url="{{ url('api/get-location') }}"
                                        data-locale="{{ app()->getLocale() }}"
                                        style="height: 500px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; z-index: 1; position: relative;"></div>

                                    <!-- Selected Address Display -->
                                    <div id="selected-address-display" class="card mb-3" style="display: none;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1" style="font-size: 24px;"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ __('Address') }}</h6>
                                                    <p class="mb-0 text-muted" id="selected-address-text"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(($mapProvider ?? 'free_api') === 'free_api')
                                    <div class="text-center mb-3">
                                        <p class="text-muted mb-2">{{ __('Or') }}</p>
                                        <p class="mb-2">{{ __('What Is the Location Of the Advertisement You Are Selling') }}</p>
                                        <button type="button" class="btn btn-primary" id="add-location-btn" data-bs-toggle="modal" data-bs-target="#manualLocationModal">{{ __('Add Location') }}</button>
                                    </div>
                                    @endif

                                    <!-- Hidden inputs for form submission -->
                                    <input type="hidden" id="latitude-input" name="latitude" required />
                                    <input type="hidden" id="longitude-input" name="longitude" required />
                                    <input type="hidden" name="country_input" id="country-input">
                                    <input type="hidden" name="state_input" id="state-input">
                                    <input type="hidden" name="city_input" id="city-input">
                                    <input type="hidden" name="address" id="address-hidden">
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab"
                                    data-next-tab="seo">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- SEO Details --}}
                        <div class="tab-pane fade" id="seo">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>{{ __('SEO Details') }}</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($geminiEnabled ?? false)
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="generate-meta-btn">
                                                    <i class="fas fa-magic"></i> {{ __('Generate SEO with AI') }}
                                                    <span class="spinner-border spinner-border-sm d-none" id="meta-loading"></span>
                                                </button>
                                            @endif
                                            <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                            <select class="form-control form-control-sm" id="seo-language-selector" style="width: 200px;">
                                                @foreach ($languages as $lang)
                                                    <option value="{{ $lang->id }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                        {{ $lang->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Default Language SEO Fields --}}
                            <div class="seo-language-fields" data-seo-language-id="{{ $defaultLanguage->id }}">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Title') }}</label>
                                        <input type="text" name="meta_title[{{ $defaultLanguage->id }}]" class="form-control" placeholder="{{ __('Enter meta title') }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Description') }}</label>
                                        <textarea name="meta_description[{{ $defaultLanguage->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Keywords') }}</label>
                                        <textarea name="meta_keywords[{{ $defaultLanguage->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Schema') }}</label>
                                        <textarea name="schema[{{ $defaultLanguage->id }}]" class="form-control" rows="4" placeholder='{"@@context": "https://schema.org", ...}'></textarea>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle"></i>
                                            {{ __('Schema is not auto-generated by AI. Please add it manually after saving the item, when image URLs and other required data are available.') }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- Other Language SEO Fields --}}
                            @foreach ($languages as $lang)
                                @if ($lang->id != $defaultLanguage->id)
                                    <div class="seo-language-fields" data-seo-language-id="{{ $lang->id }}" style="display: none;">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Title') }} ({{ $lang->name }})</label>
                                                <input type="text" name="meta_title[{{ $lang->id }}]" class="form-control" placeholder="{{ __('Enter meta title') }}">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Description') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_description[{{ $lang->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}"></textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Keywords') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_keywords[{{ $lang->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}"></textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Schema') }} ({{ $lang->name }})</label>
                                                <textarea name="schema[{{ $lang->id }}]" class="form-control" rows="4" placeholder='{"@@context": "https://schema.org", ...}'></textarea>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ __('Schema is not auto-generated by AI. Please add it manually after saving the item, when image URLs and other required data are available.') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab"
                                    data-prev-tab="address">{{ __('Previous') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Post') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @if(($geminiEnabled ?? false) && $languages->count() > 1)
    {{-- Auto Translate Language Picker Modal --}}
    <div class="modal fade" id="autoTranslateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-language me-2"></i>{{ __('Select Languages to Translate') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="at-error-alert" class="alert alert-danger d-none mb-2 py-2 small"></div>
                    <div class="mb-2">
                        <a href="#" id="at-select-all" class="small me-2">{{ __('Select All') }}</a>
                        <a href="#" id="at-deselect-all" class="small">{{ __('Deselect All') }}</a>
                    </div>
                    @foreach ($languages as $lang)
                        @if ($lang->id != $defaultLanguage->id)
                            <div class="form-check mb-1">
                                <input class="form-check-input at-lang-check" type="checkbox"
                                    id="at_lang_{{ $lang->id }}" value="{{ $lang->id }}" checked>
                                <label class="form-check-label" for="at_lang_{{ $lang->id }}">{{ $lang->name }}</label>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-success btn-sm" id="at-confirm-btn">
                        <i class="fas fa-language me-1"></i>{{ __('Translate') }}
                        <span class="spinner-border spinner-border-sm d-none" id="auto-translate-loading"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(($mapProvider ?? 'free_api') === 'free_api')
    <div class="modal fade" id="manualLocationModal" tabindex="-1" aria-labelledby="manualLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualLocationModalLabel">{{ __('Manually Add Location') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <select class="form-select" id="manual-country-select">
                            <option value="">{{ __('Country') }}</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" data-name="{{ $country->name }}" data-lat="{{ $country->latitude }}" data-lng="{{ $country->longitude }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="manual-state-select" disabled>
                            <option value="">{{ __('State') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select" id="manual-city-select" disabled>
                            <option value="">{{ __('City') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" id="manual-address-input" rows="3" placeholder="{{ __('Enter address') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="manual-location-save">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Trim blocking overlay — covers entire page while MediaRecorder is processing --}}
    <div id="reel-trim-overlay" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.45);cursor:not-allowed;flex-direction:column;align-items:center;justify-content:center;gap:12px;">
        <div class="spinner-border text-light" style="width:3rem;height:3rem;" role="status"></div>
        <span class="text-white fw-semibold fs-6">{{ __('Video uploading...') }}</span>
    </div>

    {{-- ALL IMAGES MODAL --}}
    <div class="modal fade" id="galleryAllImagesModal" tabindex="-1" aria-labelledby="galleryAllImagesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="galleryAllImagesModalLabel">{{ __('All Images') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                    <div id="modal-images-grid" class="d-flex flex-wrap gap-3 justify-content-start">
                        <!-- Dynamic grid of all images inside the modal -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- ffmpeg.wasm — client-side reel trim, always outputs mp4 (no server ffmpeg needed, works on shared hosting) -->
    <script src="{{ asset('assets/js/ffmpeg/ffmpeg.js') }}"></script>
    <script src="{{ asset('assets/js/ffmpeg/ffmpeg-util.js') }}"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/leaflet.css') }}">
    <!-- Leaflet JS -->
    <script src="{{ asset('assets/js/leaflet.js') }}"></script>
    
    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="{{ asset('assets/extensions/intl-tel-input/css/intlTelInput.css') }}">
    <!-- intl-tel-input JS -->
    <script src="{{ asset('assets/extensions/intl-tel-input/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/js/custom/item-map.js') }}"></script>
    
    <style>
        /* ── Media Upload UI ── */
        .media-drop-zone {
            border: 1.5px dashed #bbb;
            border-radius: 8px;
            padding: 32px 20px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: border-color .2s;
        }
        .media-drop-zone:hover { border-color: var(--bs-primary); }
        .media-file-card {
            display: flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            background: #f9fafb;
        }
        .media-product-video-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .media-product-video-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background: #f3f4f6;
        }
        #gallery-images-preview .gallery-thumb-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }
        #gallery-images-preview .gallery-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        #gallery-images-preview .gallery-thumb-wrap .remove-thumb {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            line-height: 1;
        }
        #gallery-images-preview .gallery-more-badge {
            width: 80px;
            height: 80px;
            background: #222;
            color: #fff;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .gallery-thumb-wrap.cover-thumb img { border: 2px solid var(--bs-primary); }
        .cover-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bs-primary);
            color: #fff;
            font-size: 10px;
            text-align: center;
            border-radius: 0 0 6px 6px;
            padding: 1px 0;
        }

        /* Currency select2 full width */
        #currency + .select2-container {
            width: 100% !important;
        }
        #addItemTabs .nav-link.tab-disabled {
            opacity: 0.45;
            color: #888 !important;
            background-color: #f5f5f5;
            pointer-events: none;
            cursor: not-allowed;
        }
        #contact-input {
            width: 100% !important;
        }
        .iti {
            width: 100%;
        }
        .iti__flag-container {
            z-index: 2;
        }
        .iti__selected-flag {
            z-index: 3;
        }

        #modal-images-grid .gallery-thumb-wrap {
            position: relative;
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }
        #modal-images-grid .gallery-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        #modal-images-grid .gallery-thumb-wrap .remove-thumb {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            line-height: 1;
            z-index: 10;
        }
        #modal-images-grid .gallery-thumb-wrap.cover-thumb img {
            border: 2px solid var(--bs-primary);
        }
        #modal-images-grid .gallery-thumb-wrap .cover-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bs-primary);
            color: #fff;
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            padding: 1px 0;
            pointer-events: none;
        }
        #modal-images-grid .existing-image-item.marked-delete {
            opacity: 0.4;
        }
    </style>
    
    <script>
        function setVideoType(type, label) {
            document.getElementById('video_type_hidden').value = type;
            document.getElementById('videoTypeLabel').textContent = label;
            document.getElementById('item-video-link-wrap').style.display = ['youtube_link','vimeo_link','other_link'].includes(type) ? '' : 'none';
            document.getElementById('item-video-file-wrap').style.display = type === 'file' ? '' : 'none';
            if (type !== 'file') { const f = document.getElementById('item-video-file-input'); if(f) f.value = ''; document.getElementById('item-video-file-selected').style.display = 'none'; }
            if (!['youtube_link','vimeo_link','other_link'].includes(type)) { const l = document.getElementById('item-video-link-input'); if(l) l.value = ''; }
        }
        function showItemVideoFilePreview(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            document.getElementById('item-video-file-name').textContent = file.name;
            document.getElementById('item-video-file-size').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('item-video-file-selected').style.display = '';
        }
        function clearItemVideoFile() {
            document.getElementById('item-video-file-input').value = '';
            document.getElementById('item-video-file-selected').style.display = 'none';
        }
    </script>
    <script>
        $(document).ready(function() {
            // Languages array for JavaScript
            const languages = @json($languages);
            const defaultLanguageId = {{ $defaultLanguage->id }};
            
            let selectedCategoryId = null;
            let selectedCategoryName = '';
            let selectedCategoryPath = []; // Track full category path
            let currentParentId = null;
            let currentPage = 1;
            let hasMoreCategories = true;
            let categoryHistory = []; // Track navigation history

            // Language switching for Details tab - only show/hide translatable fields (name and description)
            $('#details-language-selector').on('change', function() {
                const selectedLangId = $(this).val();
                const defaultLangId = {{ $defaultLanguage->id }};
                
                // Hide all other language fields (non-default)
                $('#listing .other-language-fields').hide();
                
                // If default language is selected, show default fields
                if (selectedLangId == defaultLangId) {
                    $('#listing .default-language-fields').show();
                } else {
                    // Hide default language fields (only the translatable ones)
                    $('#listing .default-language-fields').hide();
                    // Show selected language fields (only name and description)
                    $(`#listing .other-language-fields[data-language-id="${selectedLangId}"]`).show();
                }
                
                // Ensure non-translatable fields are always visible
                // They are in a separate div outside language-fields containers, so they should remain visible
            });

            // Initialize: Show default language fields (name and description only)
            const defaultLangId = {{ $defaultLanguage->id }};
            $('#listing .default-language-fields').show();
            $('#listing .other-language-fields').hide();

            // Language switching for SEO tab
            $('#seo-language-selector').on('change', function() {
                const selectedLangId = $(this).val();
                $('.seo-language-fields').hide();
                $(`.seo-language-fields[data-seo-language-id="${selectedLangId}"]`).show();
            });

            // Initialize country code selector for phone number
            let phoneInputInitialized = false;
            let phoneIti = null;

            function initPhoneInput() {
                const phoneInput = document.getElementById('contact-input');
                if (!phoneInput || phoneInputInitialized) {
                    return;
                }

                // Check if intlTelInput is already initialized on this input
                if (phoneInput.classList.contains('iti-mobile') || phoneInput.closest('.iti')) {
                    phoneInputInitialized = true;
                    return;
                }

                // Wait for intlTelInput library to load
                if (typeof window.intlTelInput === 'undefined') {
                    setTimeout(initPhoneInput, 100);
                    return;
                }

                @php
                    $userInitialCountry = !empty(auth()->user()->region_code)
                        ? strtolower(auth()->user()->region_code)
                        : 'in';
                @endphp

                try {
                    const iti = window.intlTelInput(phoneInput, {
                        initialCountry: "{{ $userInitialCountry }}",
                        preferredCountries: ['us', 'gb', 'in', 'ca', 'au'],
                        separateDialCode: true,
                        utilsScript: "{{ asset('assets/extensions/intl-tel-input/js/utils.js') }}"
                    });

                    phoneIti = iti;
                    phoneInputInitialized = true;

                    // Strip dial code from a raw value string based on selected country
                    function stripDialCode(value) {
                        const countryData = iti.getSelectedCountryData();
                        if (!countryData || !countryData.dialCode) return value;
                        value = value.trim();
                        if (value.startsWith('+' + countryData.dialCode)) {
                            value = value.slice(1 + countryData.dialCode.length).trim();
                        } else if (value.startsWith('+')) {
                            value = value.slice(1).trim();
                        }
                        return value;
                    }

                    // Format the current input value as a national phone number, preserving cursor
                    function formatPhoneNumber() {
                        if (typeof intlTelInputUtils === 'undefined') return;
                        const countryData = iti.getSelectedCountryData();
                        if (!countryData || !countryData.iso2) return;
                        const raw = phoneInput.value;
                        const digits = raw.replace(/\D/g, '');
                        if (!digits) { phoneInput.value = ''; return; }
                        try {
                            const cursorPos = phoneInput.selectionStart;
                            const digitsBeforeCursor = raw.slice(0, cursorPos).replace(/\D/g, '').length;
                            const formatted = intlTelInputUtils.formatNumber(
                                digits, countryData.iso2, intlTelInputUtils.numberFormat
                            );
                            if (formatted && formatted !== raw) {
                                phoneInput.value = formatted;
                                // Restore cursor by matching digit count
                                let newPos = formatted.length;
                                let dc = 0;
                                for (let i = 0; i < formatted.length; i++) {
                                    if (/\d/.test(formatted[i])) {
                                        dc++;
                                        if (dc === digitsBeforeCursor) { newPos = i + 1; break; }
                                    }
                                }
                                phoneInput.setSelectionRange(newPos, newPos);
                            }
                        } catch (e) { /* skip formatting on error */ }
                    }

                    // Auto-detect country from a +dialcode number, switch flag, then format
                    let countryDetectTimer = null;
                    function detectAndSetCountry(value) {
                        try {
                            iti.setNumber(value); // sets flag + strips dial code into input
                            const countryData = iti.getSelectedCountryData();
                            if (countryData && countryData.dialCode) {
                                $('#country-code-input').val('+' + countryData.dialCode);
                                $('#region-code-input').val(countryData.iso2 ?? '');
                            }
                            formatPhoneNumber();
                        } catch (e) {
                            phoneInput.value = stripDialCode(value);
                            formatPhoneNumber();
                        }
                    }

                    // Format normally; if starts with +, debounce 400ms to detect country
                    phoneInput.addEventListener('input', function() {
                        clearTimeout(countryDetectTimer);
                        if (this.value.startsWith('+')) {
                            countryDetectTimer = setTimeout(function() {
                                detectAndSetCountry(phoneInput.value.trim());
                            }, 400);
                        } else {
                            formatPhoneNumber();
                        }
                    });

                    // Paste: detect country immediately (no debounce needed)
                    phoneInput.addEventListener('paste', function() {
                        clearTimeout(countryDetectTimer);
                        setTimeout(function() {
                            const val = phoneInput.value.trim();
                            if (val.startsWith('+')) {
                                detectAndSetCountry(val);
                            } else {
                                formatPhoneNumber();
                            }
                        }, 0);
                    });

                    // Update hidden fields, strip dial code, and reformat for new country
                    phoneInput.addEventListener('countrychange', function() {
                        const countryData = iti.getSelectedCountryData();
                        if (countryData && countryData.dialCode) {
                            $('#country-code-input').val('+' + countryData.dialCode);
                            $('#region-code-input').val(countryData.iso2 ?? '');
                        }
                        phoneInput.value = stripDialCode(phoneInput.value);
                        formatPhoneNumber();
                    });

                    // Set initial hidden field values and format pre-filled value after utils load
                    setTimeout(function() {
                        const initialCountryData = iti.getSelectedCountryData();
                        if (initialCountryData && initialCountryData.dialCode) {
                            $('#country-code-input').val('+' + initialCountryData.dialCode);
                            $('#region-code-input').val(initialCountryData.iso2 ?? '');
                        }
                        if (phoneInput.value) {
                            phoneInput.value = stripDialCode(phoneInput.value);
                            formatPhoneNumber();
                        }
                    }, 1000);
                } catch (error) {
                    // Silently handle error
                }
            }
            
            // Initialize when Details tab is shown
            $('#addItemTabs a[href="#listing"]').on('shown.bs.tab', function() {
                setTimeout(initPhoneInput, 300);
            });
            
            // Also initialize on page load if Details tab is already visible
            if ($('#listing').hasClass('active') || $('#listing').hasClass('show')) {
                setTimeout(initPhoneInput, 500);
            } else {
                // Initialize after a short delay to ensure library is loaded
                setTimeout(initPhoneInput, 200);
            }

            // Load initial parent categories when categories tab is shown
            $('#addItemTabs a[href="#categories"]').on('shown.bs.tab', function() {
                if ($('#categories-list').is(':empty')) {
                    loadCategories(null, 1);
                }
            });

            // Load categories immediately if categories tab is already active on page load
            // Check if categories tab is visible/active
            if (($('#categories').hasClass('active') || $('#categories').hasClass('show')) && $('#categories-list')
                .is(':empty')) {
                loadCategories(null, 1);
            }

            // Fallback: Load categories after a short delay if still empty (for edge cases)
            setTimeout(function() {
                if ($('#categories-list').is(':empty') && ($('#categories').is(':visible') || $(
                        '#categories').hasClass('active'))) {
                    loadCategories(null, 1);
                }
            }, 100);

            // Load categories function
            function loadCategories(parentId, page = 1) {
                const url = parentId ?
                    '{{ route('advertisement.get-subcategories') }}' :
                    '{{ route('advertisement.get-parent-categories') }}';

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        category_id: parentId,
                        page: page,
                        per_page: 10
                    },
                    success: function(response) {
                        if (page === 1) {
                            $('#categories-list').html('');
                        }

                        if (response.data && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(function(category) {
                                const hasSubcategories = category.subcategories_count > 0;
                                const categoryImage = category.image ||
                                    '/assets/images/default-category.png';

                                // Only show arrow if there are subcategories
                                const arrowHtml = hasSubcategories ? 
                                    '<div class="category-arrow ms-2" style="color: #999;"><i class="fas fa-chevron-right"></i></div>' : 
                                    '';

                                html += `
                            <div class="col-md-3 col-lg-3 col-xl-3 mb-4">
                                <div class="category-card d-flex align-items-center p-3" 
                                     data-category-id="${category.id}" 
                                     data-category-name="${category.name}"
                                     data-has-subcategories="${hasSubcategories}"
                                     style="cursor: pointer; border: 1px solid #e0e0e0; border-radius: 8px; transition: all 0.3s; background: white; height: 80px;">
                                    <div class="category-icon me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <img src="${categoryImage}" alt="${category.name}" class="img-fluid" style="max-height: 50px; max-width: 50px; object-fit: contain;">
                                    </div>
                                    <div class="category-name flex-grow-1" style="font-size: 14px; font-weight: 500; color: #333;">
                                        ${category.name}
                                    </div>
                                    ${arrowHtml}
                                </div>
                            </div>
                        `;
                            });
                            $('#categories-list').append(html);

                            // Add click handlers
                            $('.category-card').off('click').on('click', function() {
                                const categoryId = $(this).data('category-id');
                                const categoryName = $(this).data('category-name');
                                const hasSubcategories = $(this).data('has-subcategories') == 1 || $(this).data('has-subcategories') === true;

                                // Visual feedback - remove previous selections
                                $('.category-card').css({
                                    'border-color': '#e0e0e0',
                                    'background-color': 'white',
                                    'box-shadow': 'none'
                                });

                                // Highlight selected
                                $(this).css({
                                    'border-color': 'var(--bs-primary)',
                                    'background-color': 'rgba(var(--bs-primary-rgb), 0.08)',
                                    'box-shadow': '0 2px 4px rgba(var(--bs-primary-rgb), 0.2)'
                                });

                                if (hasSubcategories) {
                                    // Navigate to subcategories
                                    categoryHistory.push({
                                        parentId: currentParentId,
                                        page: currentPage,
                                        html: $('#categories-list').html(),
                                        loadMoreVisible: $('#load-more-container').is(
                                            ':visible'),
                                        categoryPath: [...selectedCategoryPath]
                                    });
                                    selectedCategoryPath.push(categoryName);
                                    currentParentId = categoryId;
                                    currentPage = 1;
                                    hasMoreCategories = true;
                                    updateBreadcrumb(categoryName, categoryId);
                                    loadCategories(categoryId, 1);
                                } else {
                                    // Select this category (no subcategories)
                                    // Build the full category path
                                    const fullPath = [...selectedCategoryPath, categoryName];
                                    selectCategory(categoryId, fullPath.join(', '));
                                }
                            });

                            // Show/hide load more button
                            hasMoreCategories = response.has_more || false;
                            if (hasMoreCategories && page === 1) {
                                $('#load-more-container').show();
                            } else if (!hasMoreCategories) {
                                $('#load-more-container').hide();
                            }

                            currentPage = page;
                        } else {
                            if (page === 1) {
                                $('#categories-list').html(
                                    '<div class="col-12"><p class="text-muted text-center">No categories found.</p></div>'
                                );
                            }
                            $('#load-more-container').hide();
                            hasMoreCategories = false;
                        }
                    },
                    error: function() {
                        $('#categories-list').html(
                            '<div class="col-12"><p class="text-danger text-center">Error loading categories.</p></div>'
                        );
                    }
                });
            }

            // Load more categories
            $('#load-more-categories').on('click', function() {
                if (hasMoreCategories) {
                    loadCategories(currentParentId, currentPage + 1);
                }
            });
            
            // Go back button handler
            $('#go-back-category-btn').on('click', function() {
                if (categoryHistory.length > 0) {
                    const previous = categoryHistory[categoryHistory.length - 1];
                    goBackToCategory(previous.parentId);
                } else {
                    goBackToCategory(null);
                }
            });

            // Select category function
            function selectCategory(categoryId, categoryName) {
                if (!categoryId) {
                    return;
                }
                
                selectedCategoryId = categoryId;
                selectedCategoryName = categoryName;
                
                // Set the hidden input field
                const categoryInput = $('#category_id');
                if (categoryInput.length) {
                    categoryInput.val(categoryId);
                }
                
                // Update selected category display in Details tab
                updateSelectedCategoryDisplay(categoryName);

                // Load custom fields in the "Other Details" tab
                loadCustomFields(categoryId);

                // Unlock all tabs once category selected
                $('#addItemTabs a[data-bs-toggle="tab"]').removeClass('tab-disabled');

                // Auto-jump to Listing Details
                setTimeout(() => {
                    $('[href="#listing"]').tab('show');
                }, 100);
            }

            // Update selected category display
            function updateSelectedCategoryDisplay(categoryPath) {
                $('#selected-category-breadcrumb').text(categoryPath);
            }

            // Update breadcrumb
            function updateBreadcrumb(categoryName, categoryId) {
                $('#breadcrumb-container').show();
                $('#go-back-category-btn').show();
                let breadcrumbHtml =
                    '<li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link" data-parent-id="null">Home</a></li>';

                // Add current category to breadcrumb
                breadcrumbHtml += `<li class="breadcrumb-item active">${categoryName}</li>`;

                $('#category-breadcrumb').html(breadcrumbHtml);

                // Add click handler for breadcrumb
                $('.breadcrumb-link').off('click').on('click', function() {
                    const parentId = $(this).data('parent-id') === 'null' ? null : $(this).data(
                        'parent-id');
                    goBackToCategory(parentId);
                });
            }

            // Go back to previous category level
            function goBackToCategory(parentId) {
                if (categoryHistory.length > 0) {
                    const previous = categoryHistory.pop();
                    currentParentId = previous.parentId;
                    currentPage = previous.page;
                    selectedCategoryPath = previous.categoryPath || [];
                    $('#categories-list').html(previous.html);

                    if (previous.loadMoreVisible) {
                        $('#load-more-container').show();
                    } else {
                        $('#load-more-container').hide();
                    }

                    if (currentParentId === null) {
                        $('#breadcrumb-container').hide();
                        $('#go-back-category-btn').hide();
                        selectedCategoryPath = [];
                    } else {
                        // Update breadcrumb
                        updateBreadcrumbForBack();
                        $('#go-back-category-btn').show();
                    }

                    // Re-attach click handlers
                    $('.category-card').off('click').on('click', function() {
                        const categoryId = $(this).data('category-id');
                        const categoryName = $(this).data('category-name');
                        const hasSubcategories = $(this).data('has-subcategories') == 1 || $(this).data('has-subcategories') === true;

                        // Visual feedback - remove previous selections
                        $('.category-card').css({
                            'border-color': '#e0e0e0',
                            'background-color': 'white',
                            'box-shadow': 'none'
                        });

                        // Highlight selected
                        $(this).css({
                            'border-color': 'var(--bs-primary)',
                            'background-color': 'rgba(var(--bs-primary-rgb), 0.08)',
                            'box-shadow': '0 2px 4px rgba(var(--bs-primary-rgb), 0.2)'
                        });

                        if (hasSubcategories) {
                            categoryHistory.push({
                                parentId: currentParentId,
                                page: currentPage,
                                html: $('#categories-list').html(),
                                loadMoreVisible: $('#load-more-container').is(':visible'),
                                categoryPath: [...selectedCategoryPath]
                            });
                            selectedCategoryPath.push(categoryName);
                            currentParentId = categoryId;
                            currentPage = 1;
                            hasMoreCategories = true;
                            updateBreadcrumb(categoryName, categoryId);
                            loadCategories(categoryId, 1);
                        } else {
                            // Build the full category path
                            const fullPath = [...selectedCategoryPath, categoryName];
                            selectCategory(categoryId, fullPath.join(', '));
                        }
                    });
                } else {
                    // Go to root
                    currentParentId = null;
                    currentPage = 1;
                    categoryHistory = [];
                    selectedCategoryPath = [];
                    $('#breadcrumb-container').hide();
                    $('#go-back-category-btn').hide();
                    loadCategories(null, 1);
                }
            }

            function updateBreadcrumbForBack() {
                // Simplified breadcrumb for back navigation
                $('#breadcrumb-container').show();
                let breadcrumbHtml =
                    '<li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link" data-parent-id="null">Home</a></li>';
                $('#category-breadcrumb').html(breadcrumbHtml);

                $('.breadcrumb-link').off('click').on('click', function() {
                    goBackToCategory(null);
                });
            }


            // Function to load custom fields in the "Other Details" tab
            function loadCustomFields(categoryId) {
                if (!categoryId) {
                    $('#custom').html(
                        '<div class="text-muted">{{ __('Select a category to load custom fields.') }}</div>');
                    $('#custom-tab-item').hide();
                    return;
                }

                $.ajax({
                    url: `/get-custom-fields/${categoryId}`,
                    type: 'GET',
                    success: function(response) {
                        let html = '';
                        
                        // Add language selector for custom fields
                        html += `<div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ __('Extra Details') }}</h5>
                                    <div class="d-flex align-items-center">
                                        <label class="me-2 mb-0">{{ __('Select Language') }}:</label>
                                        <select class="form-control form-control-sm" id="custom-fields-language-selector" style="width: 200px;">
                                            @foreach ($languages as $lang)
                                                <option value="{{ $lang->id }}" data-code="{{ $lang->code }}" {{ $lang->id == $defaultLanguage->id ? 'selected' : '' }}>
                                                    {{ $lang->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                        html += `<div class="row">`;

                        if (response.fields.length === 0) {
                            html += `<p class="text-muted">No custom fields for this category.</p>`;
                            html += `<div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="listing">{{ __('Previous') }}</button>
                        <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="images">{{ __('Next') }}</button>
                    </div>`;
                            $('#custom-tab-item').hide();
                            hasCustomFields = false;
                            $('[href="#custom"]').css('pointer-events', 'none').css('cursor', 'not-allowed');
                        } else {
                            $('#custom-tab-item').show();
                            hasCustomFields = true;
                            $('[href="#custom"]').css('pointer-events', 'auto').css('cursor', 'pointer');
                            
                            // Filter translatable fields (fields that have translations)
                            const translatableFields = response.fields.filter(field => field.has_translations || field.translations_count > 0);
                            
                            // Default language - show all fields
                            html += `<div class="col-12 custom-fields-language-section" data-language-id="${defaultLanguageId}"><div class="row">`;
                            
                            response.fields.forEach(function(field) {
                                const isRequired = field.required ? 'required' : '';
                                html += `<div class="col-md-6 mb-3">`;
                                html += `<label>${field.name}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;

                                if (field.type === 'textbox') {
                                    const maxLen = field.max_length ? parseInt(field.max_length) : null;
                                    const minLen = field.min_length ? parseInt(field.min_length) : null;
                                    const maxAttr = maxLen ? `maxlength="${maxLen}" data-max-length="${maxLen}"` : '';
                                    const minAttr = minLen ? `minlength="${minLen}" data-min-length="${minLen}"` : '';
                                    if (maxLen && maxLen > 200) {
                                        html += `<textarea name="custom_fields[${field.id}]" class="form-control custom-field-input" rows="3" ${isRequired} ${maxAttr} ${minAttr} data-parsley-trigger="input"></textarea>`;
                                    } else {
                                        html += `<input type="text" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired} ${maxAttr} ${minAttr} data-parsley-trigger="input">`;
                                    }
                                    if (maxLen || minLen) {
                                        html += `<small class="form-text text-muted cf-char-counter d-block text-end"></small>`;
                                    }
                                } else if (field.type === 'number') {
                                    const maxDigits = field.max_length ? parseInt(field.max_length) : null;
                                    const minDigits = field.min_length ? parseInt(field.min_length) : null;
                                    const maxAttr = maxDigits !== null ? `maxlength="${maxDigits}" data-max-length="${maxDigits}"` : '';
                                    const minAttr = minDigits !== null ? `minlength="${minDigits}" data-min-length="${minDigits}"` : '';
                                    const lengthMsgAttr = (maxDigits !== null && minDigits !== null)
                                        ? `data-parsley-length-message="This value should be between ${minDigits} and ${maxDigits} digits long."`
                                        : '';
                                    html += `<input type="text" inputmode="numeric" pattern="[0-9]*" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired} ${maxAttr} ${minAttr} ${lengthMsgAttr} data-parsley-trigger="input">`;
                                } else if (field.type === 'fileinput') {
                                    html += `<input type="file" name="custom_field_files[${field.id}]" class="form-control custom-field-input" ${isRequired} data-parsley-trigger="change">`;
                                } else if (field.type === 'dropdown' || field.type === 'radio') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    html += `<select name="custom_fields[${field.id}]" class="form-select custom-field-input" ${isRequired} data-parsley-trigger="change">`;
                                    html += `<option value="">Select</option>`;
                                    options.forEach(option => {
                                        html += `<option value="${option}">${option}</option>`;
                                    });
                                    html += `</select>`;
                                } else if (field.type === 'checkbox') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    options.forEach(option => {
                                        html += `
                                    <div class="form-check">
                                        <input class="form-check-input custom-field-checkbox" type="checkbox" name="custom_fields[${field.id}][]" value="${option}" ${isRequired} data-parsley-trigger="change">
                                        <label class="form-check-label">${option}</label>
                                    </div>
                                `;
                                    });
                                }
                                html += `</div>`;
                            });
                            
                            html += `</div></div>`;
                            
                            // Other language fields - only show translatable fields
                            languages.forEach(function(lang) {
                                if (lang.id != defaultLanguageId) {
                                    html += `<div class="col-12 custom-fields-language-section" data-language-id="${lang.id}" style="display: none;">`;
                                    html += `<div class="row">`;
                                    
                                    // Show all textbox type fields for other languages (regardless of whether they have translations)
                                    response.fields.forEach(function(field) {
                                        // Only show textbox type fields for other languages
                                        if (field.type !== 'textbox') return;

                                        // Use translated field name for this language if available, else default name
                                        const nameTranslation = Array.isArray(field.translations)
                                            ? field.translations.find(t => t.language_id == lang.id && t.key === 'name' && t.value)
                                            : null;
                                        const fieldLabel = nameTranslation ? nameTranslation.value : field.name;

                                        // Translation fields are NOT required - only default language fields are required
                                        html += `<div class="col-md-6 mb-3">`;
                                        html += `<label>${fieldLabel}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;
                                        html += `<input type="text" name="custom_field_translations[${lang.id}][${field.id}]" class="form-control custom-field-input-translation">`;
                                html += `</div>`;
                            });
                                    
                                    html += `</div></div>`;
                                }
                            });
                        }

                        html += `</div>`;

                            // Add navigation buttons for custom fields
                            html += `<div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="listing">{{ __('Previous') }}</button>
                        <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="images">{{ __('Next') }}</button>
                    </div>`;

                        $('#custom').html(html);
                        updateCustomFieldsStatus();

                        // Bind Parsley explicitly to dynamic custom fields
                        $('#custom .custom-field-input, #custom .custom-field-checkbox').each(function() {
                            $(this).parsley();
                        });

                        // Init char counters for textbox custom fields
                        $('#custom .custom-field-input[data-max-length], #custom .custom-field-input[data-min-length]').trigger('input.cfcounter');

                        // Language switching for custom fields
                        $('#custom-fields-language-selector').off('change').on('change', function() {
                            const selectedLangId = $(this).val();
                            
                            // Hide all language sections and remove required from hidden fields
                            $('.custom-fields-language-section').each(function() {
                                $(this).hide();
                                // Remove required attribute from all inputs in hidden sections
                                $(this).find('input[required], select[required], textarea[required]').each(function() {
                                    $(this).attr('data-was-required', 'true');
                                    $(this).removeAttr('required');
                                });
                            });
                            
                            // Show selected language section and restore required attributes
                            const $selectedSection = $(`.custom-fields-language-section[data-language-id="${selectedLangId}"]`);
                            $selectedSection.show();
                            // Restore required for fields that were marked
                            $selectedSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                                $(this).attr('required', 'required');
                            });
                            // Add required for fields that should be required (newly created fields)
                            $selectedSection.find('input[data-should-be-required="true"], select[data-should-be-required="true"], textarea[data-should-be-required="true"]').each(function() {
                                $(this).attr('required', 'required');
                            });
                        });
                        
                        // Initialize: Show default language fields and mark required fields
                        const defaultCustomLangId = $('#custom-fields-language-selector').val();
                        // Hide all sections first
                        $('.custom-fields-language-section').each(function() {
                            $(this).hide();
                            // Mark required fields in hidden sections
                            $(this).find('input[required], select[required], textarea[required]').each(function() {
                                $(this).attr('data-was-required', 'true');
                                $(this).removeAttr('required');
                            });
                        });
                        
                        // Show default language section and restore required
                        const $defaultSection = $(`.custom-fields-language-section[data-language-id="${defaultCustomLangId}"]`);
                        $defaultSection.show();
                        // Restore required for fields that were marked
                        $defaultSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                            $(this).attr('required', 'required');
                        });
                        // Add required for fields that should be required (newly created fields)
                        $defaultSection.find('input[data-should-be-required="true"], select[data-should-be-required="true"], textarea[data-should-be-required="true"]').each(function() {
                            $(this).attr('required', 'required');
                        });

                        // Handle job category and price optional logic
                        const isJobCategory = response.is_job_category;
                        const isPriceOptional = response.price_optional;
                        
                        if (isJobCategory) {
                            // Job category: show salary fields, hide price
                            $('#price-field').hide();
                            $('#price-input').removeAttr('required');
                            $('#salary-fields').show();
                            
                            // Update salary field labels and requirements
                            if (isPriceOptional) {
                                // Both job category AND price optional: salary is optional
                                $('#salary-fields label').each(function() {
                                    $(this).html($(this).html().replace(' <span class="text-danger">*</span>', ''));
                                });
                                $('#min-salary-input').removeAttr('required');
                                $('#max-salary-input').removeAttr('required');
                            } else {
                                // Job category but price not optional: salary is required
                                $('#salary-fields label').first().html('{{ __('Min Salary') }} <span class="text-danger">*</span>');
                                $('#salary-fields label').last().html('{{ __('Max Salary') }} <span class="text-danger">*</span>');
                                $('#min-salary-input').attr('required', 'required');
                                $('#max-salary-input').attr('required', 'required');
                            }
                        } else {
                            // Not a job category: show price field, hide salary
                            $('#price-field').show();
                            $('#salary-fields').hide();
                            
                            if (isPriceOptional) {
                                // Price optional: remove required
                                $('#price-field label').html('{{ __('Price') }}');
                                $('#price-input').removeAttr('required');
                            } else {
                                // Price not optional: make required
                                $('#price-field label').html('{{ __('Price') }} <span class="text-danger">*</span>');
                                $('#price-input').attr('required', 'required');
                            }
                        }
                    },
                    error: function() {
                        $('#custom').html(
                            '<div class="text-danger">Error loading custom fields.</div>');
                        $('#custom-tab-item').hide();
                    }
                });
            }

            // Tab navigation with validation
            let hasCustomFields = false;

            // Update hasCustomFields when custom fields are loaded
            function updateCustomFieldsStatus() {
                hasCustomFields = $('#custom .custom-field-input').length > 0;
            }

            // Disable all tab links except categories (first tab)
            $('#addItemTabs a[data-bs-toggle="tab"]').each(function() {
                const href = $(this).attr('href');
                if (href !== '#categories') {
                    $(this).addClass('tab-disabled');
                }
            });

            // Prevent tab switching only if category not selected yet
            $(document).on('click', '#addItemTabs a[data-bs-toggle="tab"]', function(e) {
                const targetTab = $(this).attr('href');
                if (targetTab === '#categories') return true;

                const categoryIdValue = $('#category_id').val();
                if (!selectedCategoryId || !categoryIdValue) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    showErrorToast(window.trans('Please select a category first.'));
                    return false;
                }
            });

            $('#addItemTabs a[data-bs-toggle="tab"]').on('show.bs.tab', function(e) {
                const targetTab = $(e.target).attr('href');
                if (targetTab === '#categories') return true;

                const categoryIdValue = $('#category_id').val();
                if (!selectedCategoryId || !categoryIdValue) {
                    e.preventDefault();
                    return false;
                }
            });

            // Clear invalid highlight once user edits a custom field
            $(document).on('input change', '#custom .custom-field-input, #custom .custom-field-checkbox', function() {
                $(this).removeClass('is-invalid');
            });

            // Char counter for textbox custom fields with min/max length
            $(document).on('input input.cfcounter', '#custom .custom-field-input[data-max-length], #custom .custom-field-input[data-min-length]', function() {
                const $input = $(this);
                const len = ($input.val() || '').length;
                const max = $input.data('max-length');
                const min = $input.data('min-length');
                let text = max ? `${len}/${max}` : `${len}`;
                if (min && len < min) text += ` ({{ __('min') }} ${min})`;
                $input.next('.cf-char-counter').text(text).toggleClass('text-danger', !!(min && len > 0 && len < min));
            });

            // Next button handler
            $(document).on('click', '.btn-next-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const nextTab = $(this).data('next-tab');
                const currentTab = $('.tab-pane.active').attr('id');

                if (!validateCurrentTab(currentTab)) {
                    return false;
                }

                if (nextTab === 'custom-or-images') {
                    if (hasCustomFields) {
                        $('[href="#custom"]').tab('show');
                    } else {
                        $('[href="#images"]').tab('show');
                    }
                } else {
                    $('[href="#' + nextTab + '"]').tab('show');
                    if (nextTab === 'address') {
                        setTimeout(() => { initMap(); }, 300);
                    }
                }
            });

            // Previous button handler
            $(document).on('click', '.btn-prev-tab', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const prevTab = $(this).data('prev-tab');

                if (prevTab === 'custom-or-images') {
                    if (hasCustomFields) {
                        $('[href="#custom"]').tab('show');
                    } else {
                        $('[href="#listing"]').tab('show');
                    }
                } else {
                    $('[href="#' + prevTab + '"]').tab('show');
                }
            });

            // Init map when address tab shown via direct click
            $('#addItemTabs a[href="#address"]').on('shown.bs.tab', function() {
                setTimeout(() => { initMap(); }, 300);
            });

            // Validation function for current tab
            function validateCurrentTab(tabId) {
                let isValid = true;
                let firstInvalidField = null;

                if (tabId === 'categories') {
                    const categoryIdValue = $('#category_id').val();
                    if (!selectedCategoryId || !categoryIdValue || categoryIdValue === '') {
                        showErrorToast(window.trans('Please select a category first.'));
                        isValid = false;
                    }
                } else if (tabId === 'listing') {
                    const name = $('#name-input').val().trim();
                    const description = $('#description-input').val().trim();
                    const price = $('#price-input').val();
                    const minSalary = $('#min-salary-input').val();
                    const maxSalary = $('#max-salary-input').val();
                    const contact = $('#contact-input').val().trim();

                    if (!name) {
                        showErrorToast(window.trans('Please enter a english title.'));
                        $('#name-input').focus();
                        isValid = false;
                    } else if (!description) {
                        showErrorToast(window.trans('Please enter a english description.'));
                        $('#description-input').focus();
                        isValid = false;
                    } else if ($('#price-field').css('display') !== 'none' && $('#price-input').attr('required') && !price) {
                        showErrorToast(window.trans('Please enter a price.'));
                        $('#price-input').focus();
                        isValid = false;
                    } else if ($('#salary-fields').css('display') !== 'none') {
                        const minSalaryRequired = $('#min-salary-input').attr('required');
                        const maxSalaryRequired = $('#max-salary-input').attr('required');
                        
                        if (minSalaryRequired && !minSalary) {
                            showErrorToast(window.trans('Please enter a minimum salary.'));
                            $('#min-salary-input').focus();
                            isValid = false;
                        } else if (maxSalaryRequired && !maxSalary) {
                            showErrorToast(window.trans('Please enter a maximum salary.'));
                            $('#max-salary-input').focus();
                            isValid = false;
                        } else if (minSalary && maxSalary && parseFloat(minSalary) > parseFloat(maxSalary)) {
                            showErrorToast(window.trans('Min salary cannot be greater than max salary.'));
                            $('#min-salary-input').focus();
                            isValid = false;
                        }
                    }

                    // Phone number validation
                    if (isValid && contact && phoneIti) {
                        try {
                            if (typeof intlTelInputUtils !== 'undefined' && !phoneIti.isValidNumber()) {
                                showErrorToast(window.trans('Please enter a valid phone number for the selected country.'));
                                $('#contact-input').focus();
                                isValid = false;
                            }
                        } catch (e) {
                            // Skip validation if utils not loaded
                        }
                    }

                } else if (tabId === 'custom') {
                    // Validate required custom fields
                    let firstInvalidField = null;

                    // Clear previous invalid state
                    $('#custom .custom-field-input, #custom .custom-field-checkbox').removeClass('is-invalid');

                    // Resolve the visible label text for a given field
                    const fieldLabel = function($field) {
                        const text = $field.closest('.col-md-6, .col-md-12, .col-12').find('label').first().clone()
                            .children().remove().end().text().trim();
                        return text || window.trans('This field');
                    };
                    
                    const formElement = $('#custom').closest('form');
                    const hasParsley = formElement.length && typeof formElement.parsley === 'function';

                    $('#custom .custom-field-input').each(function() {
                        const $field = $(this);
                        let isFieldValid = true;

                        if (hasParsley) {
                            const parsleyField = $field.parsley();
                            if (parsleyField) {
                                isFieldValid = parsleyField.validate() === true;
                            }
                        } else {
                            if ($field.attr('required')) {
                                let invalid = false;
                                if ($field.is(':file')) {
                                    invalid = !$field[0].files || $field[0].files.length === 0;
                                } else if ($field.is('select')) {
                                    invalid = !$field.val();
                                } else {
                                    invalid = !$field.val().trim();
                                }
                                isFieldValid = !invalid;
                            }
                        }

                        if (!isFieldValid) {
                            $field.addClass('is-invalid');
                            if (!firstInvalidField) {
                                firstInvalidField = $field;
                            }
                            isValid = false;
                        } else {
                            $field.removeClass('is-invalid');
                        }
                    });

                    if (!isValid && firstInvalidField) {
                        if (hasParsley) {
                            showErrorToast(fieldLabel(firstInvalidField) + ' ' + window.trans('is invalid.'));
                        } else {
                            showErrorToast(fieldLabel(firstInvalidField) + ' ' + window.trans('is required.'));
                        }
                        firstInvalidField.focus();
                    }

                    // Validate checkbox groups
                    const checkedGroups = {};
                    $('#custom .custom-field-checkbox[required]').each(function() {
                        const $field = $(this);
                        const name = $field.attr('name');
                        if (checkedGroups[name]) return;
                        if (!$('input[name="' + name + '"]:checked').length) {
                            checkedGroups[name] = true;
                            $('input[name="' + name + '"]').addClass('is-invalid');
                            if (isValid) {
                                showErrorToast(fieldLabel($field) + ' ' + window.trans('is required.'));
                            }
                            isValid = false;
                        }
                    });
                } else if (tabId === 'images') {
                    const isReel = $('#item_type_input').val() === 'reel';
                    if (isReel) {
                        const reelVideoInput = document.getElementById('reel-video-input');
                        if (!reelVideoInput || !reelVideoInput.files || reelVideoInput.files.length === 0) {
                            showErrorToast(window.trans('Please upload a Video Ad video.'));
                            isValid = false;
                        }
                    }
                    const galleryInput = $('#gallery-images-input')[0];
                    if (!galleryInput || !galleryInput.files || galleryInput.files.length === 0) {
                        showErrorToast(window.trans('Please select at least one product image.'));
                        $('#gallery-images-input').focus();
                        isValid = false;
                    }
                    
                    // Validate item video link/file based on selected type
                    const videoType = document.getElementById('video_type_hidden')?.value || '';
                    if (['youtube_link','vimeo_link','other_link'].includes(videoType)) {
                        const videoLinkInput = document.getElementById('item-video-link-input');
                        const videoLink = videoLinkInput?.value?.trim() || '';
                        if (!videoLink) {
                            showErrorToast(window.trans('Please enter a video URL for the selected video type.'));
                            $(videoLinkInput).addClass('is-invalid').removeClass('is-valid');
                            videoLinkInput?.focus();
                            isValid = false;
                        } else {
                            const error = validateVideoLink(videoLink, videoType);
                            if (error) {
                                showErrorToast(error);
                                $(videoLinkInput).addClass('is-invalid').removeClass('is-valid');
                                videoLinkInput?.focus();
                                isValid = false;
                            } else {
                                $(videoLinkInput).removeClass('is-invalid').removeClass('is-valid');
                            }
                        }
                    } else if (videoType === 'file') {
                        const videoFileInput = document.getElementById('item-video-file-input');
                        if (!videoFileInput || !videoFileInput.files || videoFileInput.files.length === 0) {
                            showErrorToast(window.trans('Please upload a video file.'));
                            isValid = false;
                        }
                    }
                } else if (tabId === 'address') {
                    const lat = $('#latitude-input').val();
                    const lng = $('#longitude-input').val();

                    if (!lat || !lng) {
                        showErrorToast(window.trans('Please select a location on the map.'));
                        isValid = false;
                    }
                }

                return isValid;
            }

            // URL validation helper
            function isValidUrl(string) {
                if (!string || !string.trim()) return true; // Empty is valid (nullable)
                try {
                    const url = new URL(string);
                    return url.protocol === 'http:' || url.protocol === 'https:';
                } catch (_) {
                    return false;
                }
            }

            // Mirrors App\Rules\VideoLink server-side validation
            function validateVideoLink(link, videoType) {
                let url;
                try {
                    url = new URL(link);
                } catch (_) {
                    return window.trans('Please enter a valid URL.');
                }
                const host = url.hostname.toLowerCase().replace(/^(www\.|m\.)/, '');
                const hostVimeo = url.hostname.toLowerCase().replace(/^www\./, '');

                if (videoType === 'youtube_link') {
                    const youtubePattern = /^(https?:\/\/)?(www\.|m\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)\/.+$/i;
                    if (!youtubePattern.test(link)) return window.trans('The video link must be a valid YouTube URL.');
                } else if (videoType === 'vimeo_link') {
                    const vimeoPattern = /^(https?:\/\/)?(www\.|player\.)?vimeo\.com\/(video\/)?[0-9]{8,}([?#][^\s]*)?$/i;
                    if (!vimeoPattern.test(link)) return window.trans('The video link must be a valid Vimeo URL.');
                } else if (videoType === 'other_link') {
                    const isYoutube = ['youtube.com', 'youtu.be', 'youtube-nocookie.com'].includes(host);
                    const isVimeo = ['vimeo.com', 'player.vimeo.com'].includes(hostVimeo);
                    if (isYoutube || isVimeo) return window.trans('The video link must not be a YouTube or Vimeo URL.');
                    const allowedExtensions = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm3u8', 'mkv', 'avi'];
                    const extMatch = url.pathname.toLowerCase().match(/\.([a-z0-9]+)$/);
                    const extension = extMatch ? extMatch[1] : '';
                    if (!allowedExtensions.includes(extension)) {
                        return window.trans('The video link must be a direct link to a video file (e.g. mp4, webm, m3u8).');
                    }
                }
                return null;
            }

            // Validate video link format on input and blur
            $('#item-video-link-input').on('input blur', function() {
                const val = $(this).val().trim();
                const videoType = document.getElementById('video_type_hidden')?.value || '';
                const hasTypeError = val && typeof validateVideoLink === 'function' && videoType && validateVideoLink(val, videoType);
                if (val && (!isValidUrl(val) || hasTypeError)) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else {
                    $(this).removeClass('is-invalid').removeClass('is-valid');
                }
            });

            // Pre-submit validation function (called by global form handler)
            window.validateAdvertisementForm = function() {
                const tabs = ['categories', 'listing', 'images', 'address'];
                if (hasCustomFields) {
                    tabs.splice(2, 0, 'custom');
                }

                for (let i = 0; i < tabs.length; i++) {
                    if (!validateCurrentTab(tabs[i])) {
                        $('[href="#' + tabs[i] + '"]').tab('show');
                        if (tabs[i] === 'address') {
                            setTimeout(() => { initMap(); }, 300);
                        }
                        return false; // Prevent form submission
                    }
                }

                // Normalize contact: send digits-only national number to backend
                // If no phone number entered, clear country_code and region_code so they aren't passed
                if (phoneIti) {
                    try {
                        const rawContact = $('#contact-input').val().trim();
                        if (rawContact) {
                            let nationalDigits = rawContact.replace(/\D/g, '');
                            $('#contact-input').val(nationalDigits);
                        } else {
                            $('#country-code-input').val('');
                            $('#region-code-input').val('');
                        }
                    } catch (e) {
                        // Keep original value on error
                    }
                }

                // Reel validation
                if ($('#item_type_input').val() === 'reel') {
                    if (!window.reelTrimmedBlob && !$('#reel-video-input')[0].files.length) {
                        toastr.error('{{ __('Please upload a video for Video Ad listing.') }}');
                        $('[href="#images"]').tab('show');
                        return false;
                    }
                    if (window.reelTrimming) {
                        toastr.warning('{{ __('Video is still being prepared, please wait a moment.') }}');
                        return false;
                    }
                }

                return true; // Allow form submission
            };

            // Email validation helper
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Toggle password visibility for user details
            // $('#toggle-user-password-visibility').on('click', function() {
            //     const passwordInput = $('#user-password-input');
            //     const eyeIcon = $('#user-password-eye-icon');

            //     if (passwordInput.attr('type') === 'password') {
            //         passwordInput.attr('type', 'text');
            //         eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            //     } else {
            //         passwordInput.attr('type', 'password');
            //         eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            //     }
            // });
        });

        // Country/State/City dropdown handlers - kept for map address population
        $(document).ready(function() {
            // These handlers are now only used for populating dropdowns from map selection
            // No manual dropdowns are shown in the UI anymore
        });

        $(document).ready(function() {
            // Image upload handlers
            // Image upload handlers
            
            // Gallery images — drop zone click handled via onclick in HTML
            
            // Store gallery files for removal and appending
            let galleryFiles = [];
            const maxGalleryImages = parseInt("{{ \App\Services\CachingService::getSystemSettings('max_gallery_images') ?: 5 }}");

            function appendGalleryFiles(newFiles) {
                if (galleryFiles.length + newFiles.length > maxGalleryImages) {
                    showErrorToast(window.trans('You can upload a maximum of') + ' ' + maxGalleryImages + ' ' + window.trans('images'));
                    newFiles = newFiles.slice(0, Math.max(0, maxGalleryImages - galleryFiles.length));
                }
                if (newFiles.length > 0) {
                    galleryFiles = galleryFiles.concat(newFiles);
                }
            }

            function renderGalleryPreview() {
                const preview = document.getElementById('gallery-images-preview');
                if (!preview) return;
                preview.innerHTML = '';

                // Calculate available width dynamically
                preview.style.display = 'flex';
                preview.style.flexWrap = 'nowrap';
                preview.style.overflow = 'hidden';
                
                const W = preview.clientWidth || preview.parentElement.clientWidth;
                
                const totalItems = galleryFiles.length;
                let visibleCount = totalItems;
                let extraCount = 0;

                // Dynamic width calculation
                if (totalItems * 88 - 8 > W && W > 0) {
                    visibleCount = Math.floor((W - 80) / 88);
                    if (visibleCount < 1) visibleCount = 1;
                    extraCount = totalItems - visibleCount;
                }

                const visible = galleryFiles.slice(0, visibleCount);

                visible.forEach((file, i) => {
                    const imgSrc = URL.createObjectURL(file);
                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap' + (i === 0 ? ' cover-thumb' : '');
                    wrap.dataset.fileIndex = i;
                    wrap.style.cursor = 'pointer';
                    wrap.innerHTML = `
                        <img src="${imgSrc}" alt="">
                        ${i === 0 ? '<span class="cover-badge">Cover</span>' : ''}
                        <span class="remove-thumb remove-gallery-image" data-index="${i}">✕</span>
                    `;
                    wrap.addEventListener('click', function(e) {
                        if (e.target.classList.contains('remove-thumb') || e.target.closest('.remove-thumb')) {
                            return;
                        }
                        openAllImagesModal();
                    });
                    preview.appendChild(wrap);
                });

                if (extraCount > 0) {
                    const more = document.createElement('div');
                    more.className = 'gallery-more-badge';
                    more.style.cursor = 'pointer';
                    more.innerHTML = `<span>+${extraCount}</span><small style="font-size:10px;font-weight:400;">More</small>`;
                    more.addEventListener('click', openAllImagesModal);
                    preview.appendChild(more);
                }

                // Sync file input
                const input = document.getElementById('gallery-images-input');
                if (input) {
                    const dt = new DataTransfer();
                    galleryFiles.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                }
            }

            window.openAllImagesModal = function() {
                renderModalImages();
                const modal = new bootstrap.Modal(document.getElementById('galleryAllImagesModal'));
                modal.show();
            };

            window.renderModalImages = function() {
                const grid = document.getElementById('modal-images-grid');
                if (!grid) return;
                grid.innerHTML = '';
                
                galleryFiles.forEach((file, i) => {
                    const imgSrc = URL.createObjectURL(file);
                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap' + (i === 0 ? ' cover-thumb' : '');
                    wrap.style.position = 'relative';
                    wrap.style.width = '100px';
                    wrap.style.height = '100px';
                    
                    wrap.innerHTML = `
                        <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" alt="">
                        ${i === 0 ? '<span class="cover-badge" style="position: absolute; bottom: 0; left: 0; right: 0; background: var(--bs-primary); color: #fff; text-align: center; font-size: 11px;">Cover</span>' : ''}
                        <span class="remove-thumb remove-gallery-image-modal" data-index="${i}" style="position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; background: #fff; border: 1px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px; line-height: 1;">✕</span>
                    `;
                    grid.appendChild(wrap);
                });
            };

            $(document).on('click', '.remove-gallery-image-modal', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const index = parseInt($(this).data('index'));
                galleryFiles.splice(index, 1);
                renderGalleryPreview();
                renderModalImages();
                if (galleryFiles.length === 0) {
                    const modalEl = document.getElementById('galleryAllImagesModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if (modalInst) modalInst.hide();
                }
            });
            
            $('#gallery-images-input').on('change', function(e) {
                const newFiles = Array.from(e.target.files);

                if (newFiles.length > 0) {
                    appendGalleryFiles(newFiles);
                }

                renderGalleryPreview();
            });
            
            // Handle gallery image removal
            $(document).on('click', '.remove-gallery-image', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const index = parseInt($(this).data('index'));
                
                galleryFiles.splice(index, 1);
                renderGalleryPreview();
            });

            // Render gallery preview on tab activation & screen resize
            $('#addItemTabs a[href="#images"]').on('shown.bs.tab', function() {
                renderGalleryPreview();
            });

            $(window).on('resize', function() {
                if ($('#images').hasClass('active') || $('#images').hasClass('show')) {
                    renderGalleryPreview();
                }
            });
            

            
            // Drag and drop for gallery images
            const galleryUploadArea = document.getElementById('gallery-images-upload');
            if (galleryUploadArea) {
                galleryUploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', 'var(--bs-primary)');
                    $(this).css('background', 'rgba(var(--bs-primary-rgb), 0.08)');
                });
                
                galleryUploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#ddd');
                    $(this).css('background', '#f9f9f9');
                });
                
                galleryUploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#ddd');
                    $(this).css('background', '#f9f9f9');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const newFiles = [];
                        for (let i = 0; i < files.length; i++) {
                            if (files[i].type.startsWith('image/')) {
                                newFiles.push(files[i]);
                            }
                        }
                        if (newFiles.length > 0) {
                            appendGalleryFiles(newFiles);
                            renderGalleryPreview();
                        }
                    }
                });
            }

            @if($geminiEnabled ?? false)
            // Gemini AI - Generate Description (per-language aware)
            $('.generate-description-btn').on('click', function() {
                const btn = $(this);
                const spinner = btn.find('.description-loading');
                const $wrap = btn.closest('.language-fields');
                const langId = $wrap.data('language-id');
                const isDefault = $wrap.hasClass('default-language-fields');

                const $title = isDefault
                    ? $('#name-input')
                    : $(`input.translation-name[data-lang-id="${langId}"]`);
                const $desc = isDefault
                    ? $('#description-input')
                    : $(`textarea.translation-description[data-lang-id="${langId}"]`);

                const title = $title.val();
                if (!title) {
                    Toastify({ text: '{{ __("Please enter a title first") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("gemini.generate-description") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        title: title,
                        location: $('[name="address"]').val() || '',
                        city: $('[name="city"]').val() || $('[name="city"] option:selected').text() || '',
                        state: $('[name="state"]').val() || $('[name="state"] option:selected').text() || '',
                        country: $('[name="country"] option:selected').text() || '',
                        price: $('[name="price"]').val() || '',
                        category_name: $('#selected-category-breadcrumb').text().trim() || '',
                        currency_iso_code: $('#currency option:selected').data('iso-code') || '',
                        language_id: langId
                    },
                    success: function(response) {
                        if (!response.error && response.data) {
                            $desc.val(response.data.description);
                            Toastify({ text: '{{ __("Description generated successfully") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            Toastify({ text: response.message || '{{ __("Failed to generate description") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    },
                    error: function(xhr) {
                        const errMsg = xhr.responseJSON?.message || '{{ __("Gemini AI service is currently unavailable. Please try again later.") }}';
                        Toastify({ text: errMsg, duration: 6000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Gemini AI - Generate Meta Details
            $('#generate-meta-btn').on('click', function() {
                const btn = $(this);
                const spinner = $('#meta-loading');
                const selectedSeoLangId = $('#seo-language-selector').val();
                const defaultLangId = {{ $defaultLanguage->id }};
                const isDefaultLang = parseInt(selectedSeoLangId) === defaultLangId;

                const title = isDefaultLang
                    ? $('#name-input').val()
                    : $(`input.translation-name[data-lang-id="${selectedSeoLangId}"]`).val();
                const description = isDefaultLang
                    ? $('#description-input').val()
                    : $(`textarea.translation-description[data-lang-id="${selectedSeoLangId}"]`).val();

                if (!title || !title.trim() || !description || !description.trim()) {
                    Toastify({ text: '{{ __("Please enter title and description for the selected language first") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("gemini.generate-meta") }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        title: title,
                        location: $('[name="address"]').val() || '',
                        city: $('[name="city"]').val() || $('[name="city"] option:selected').text() || '',
                        state: $('[name="state"]').val() || $('[name="state"] option:selected').text() || '',
                        country: $('[name="country"] option:selected').text() || '',
                        price: $('[name="price"]').val() || '',
                        currency_iso_code: $('#currency option:selected').data('iso-code') || '',
                        language_id: selectedSeoLangId
                    },
                    success: function(response) {
                        if (!response.error && response.data) {
                            const langId = selectedSeoLangId;
                            const seoFields = $(`.seo-language-fields[data-seo-language-id="${langId}"]`);
                            seoFields.find(`[name="meta_title[${langId}]"]`).val(response.data.meta_title || '');
                            seoFields.find(`[name="meta_description[${langId}]"]`).val(response.data.meta_description || '');
                            seoFields.find(`[name="meta_keywords[${langId}]"]`).val(response.data.meta_keywords || '');
                            Toastify({ text: '{{ __("SEO details generated successfully") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            Toastify({ text: response.message || '{{ __("Failed to generate SEO details") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    },
                    error: function(xhr) {
                        const errMsg = xhr.responseJSON?.message || '{{ __("Gemini AI service is currently unavailable. Please try again later.") }}';
                        Toastify({ text: errMsg, duration: 6000, close: true, backgroundColor: '#dc3545' }).showToast();
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            @if($languages->count() > 1)
            // Auto Translate — open modal
            $('#auto-translate-btn').on('click', function() {
                const name = $('#name-input').val().trim();
                const description = $('#description-input').val().trim();
                if (!name || !description) {
                    Toastify({ text: '{{ __("Please enter title and description in the default language first") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }
                new bootstrap.Modal(document.getElementById('autoTranslateModal')).show();
            });

            $('#at-select-all').on('click', function(e) { e.preventDefault(); $('.at-lang-check').prop('checked', true); });
            $('#at-deselect-all').on('click', function(e) { e.preventDefault(); $('.at-lang-check').prop('checked', false); });

            $('#at-confirm-btn').on('click', function() {
                const selectedIds = $('.at-lang-check:checked').map(function() { return $(this).val(); }).get();
                if (!selectedIds.length) {
                    Toastify({ text: '{{ __("Please select at least one language") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                    return;
                }

                const btn = $(this);
                const spinner = $('#auto-translate-loading');
                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                const customFields = {};
                $('#custom .custom-fields-language-section[data-language-id="{{ $defaultLanguage->id }}"] .custom-field-input').each(function() {
                    const $el = $(this);
                    const fieldName = $el.attr('name') || '';
                    const match = fieldName.match(/^custom_fields\[(\d+)\]$/);
                    if (match && $el.val().trim() !== '') {
                        const elType = $el.attr('type');
                        if (elType === 'text' || elType === 'number') {
                            customFields[match[1]] = $el.val().trim();
                        }
                    }
                });

                const postData = {
                    name: $('#name-input').val().trim(),
                    description: $('#description-input').val().trim(),
                    source_language_id: {{ $defaultLanguage->id }},
                    custom_fields: customFields,
                };
                selectedIds.forEach(function(id) { postData['target_language_ids[]'] = postData['target_language_ids[]'] ? postData['target_language_ids[]'] : []; });
                // Build proper array param
                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('name', postData.name);
                formData.append('description', postData.description);
                formData.append('source_language_id', postData.source_language_id);
                selectedIds.forEach(function(id) { formData.append('target_language_ids[]', id); });
                Object.entries(customFields).forEach(function([k, v]) { formData.append(`custom_fields[${k}]`, v); });

                $('#at-error-alert').addClass('d-none').text('');

                $.ajax({
                    url: '{{ route("gemini.auto-translate") }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('[AutoTranslate] response:', JSON.stringify(response));
                        if (!response.error && response.data && response.data.translations) {
                            const translations = response.data.translations;
                            let firstLangId = null;
                            Object.entries(translations).forEach(function([langId, fields]) {
                                $(`input.translation-name[data-lang-id="${langId}"]`).val(fields.name || '');
                                $(`textarea.translation-description[data-lang-id="${langId}"]`).val(fields.description || '');
                                if (fields.custom_fields && typeof fields.custom_fields === 'object') {
                                    Object.entries(fields.custom_fields).forEach(function([fieldId, value]) {
                                        $(`input[name="custom_field_translations[${langId}][${fieldId}]"]`).val(value || '');
                                    });
                                }
                                if (!firstLangId) firstLangId = langId;
                            });
                            bootstrap.Modal.getInstance(document.getElementById('autoTranslateModal')).hide();
                            if (firstLangId) $('#details-language-selector').val(firstLangId).trigger('change');
                            Toastify({ text: '{{ __("Content translated successfully") }}', duration: 3000, close: true, backgroundColor: 'linear-gradient(to right, #00b09b, #96c93d)' }).showToast();
                        } else {
                            $('#at-error-alert').removeClass('d-none').text(response.message || '{{ __("Failed to translate content") }}');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || '{{ __("Gemini AI service is currently unavailable. Please try again later.") }}';
                        $('#at-error-alert').removeClass('d-none').text(msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });
            @endif
            @endif
        });

        @if(($mapProvider ?? 'free_api') === 'free_api')
        // Manual location modal (free_api only)
        $(document).ready(function() {
            const $country = $('#manual-country-select');
            const $state = $('#manual-state-select');
            const $city = $('#manual-city-select');
            const $address = $('#manual-address-input');
            const $modal = $('#manualLocationModal');

            const PER_PAGE = 50;

            function ajaxSelect2($el, url, placeholder, buildParams) {
                if ($el.data('select2')) $el.select2('destroy');
                $el.select2({
                    dropdownParent: $modal,
                    placeholder: placeholder,
                    width: '100%',
                    allowClear: true,
                    ajax: {
                        url: url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return $.extend({
                                search: params.term || '',
                                page: params.page || 1,
                                per_page: PER_PAGE
                            }, buildParams());
                        },
                        processResults: function(res, params) {
                            params.page = params.page || 1;
                            const d = (res && res.data) ? res.data : {};
                            const list = d.data || [];
                            return {
                                results: list.map(function(r) {
                                    return { id: r.id, text: r.name, lat: r.latitude || '', lng: r.longitude || '' };
                                }),
                                pagination: { more: (d.current_page || 1) < (d.last_page || 1) }
                            };
                        },
                        cache: true
                    }
                });
            }

            function plainSelect2($el, placeholder) {
                if ($el.data('select2')) $el.select2('destroy');
                $el.select2({ dropdownParent: $modal, placeholder: placeholder, width: '100%', allowClear: true });
            }

            $modal.on('shown.bs.modal', function() {
                plainSelect2($country, '{{ __('Country') }}');
                ajaxSelect2($state, '{{ url('api/states') }}', '{{ __('State') }}', function() {
                    return { country_id: $country.val() || '' };
                });
                ajaxSelect2($city, '{{ url('api/cities') }}', '{{ __('City') }}', function() {
                    return { state_id: $state.val() || '' };
                });
            });

            function clearSelect($el) {
                $el.val(null).trigger('change.select2');
            }

            $country.on('change', function() {
                clearSelect($state);
                clearSelect($city);
                $state.prop('disabled', !$(this).val());
                $city.prop('disabled', true);
            });

            $state.on('change', function() {
                clearSelect($city);
                $city.prop('disabled', !$(this).val());
            });

            $('#manual-location-save').on('click', function() {
                const countrySel = $country.find('option:selected');
                const countryName = countrySel.data('name') || '';
                const stateData = ($state.select2('data') || [])[0] || {};
                const cityData = ($city.select2('data') || [])[0] || {};
                const stateName = stateData.text || '';
                const cityName = cityData.text || '';
                const addressText = ($address.val() || '').trim();

                if (!countryName) {
                    showErrorToast('{{ __('Please select a country') }}');
                    return;
                }
                if (!stateName) {
                    showErrorToast('{{ __('Please select a state') }}');
                    return;
                }
                if (!cityName) {
                    showErrorToast('{{ __('Please select a city') }}');
                    return;
                }

                const lat = cityData.lat || stateData.lat || countrySel.data('lat') || '';
                const lng = cityData.lng || stateData.lng || countrySel.data('lng') || '';

                const fullAddress = [addressText, cityName, stateName, countryName].filter(Boolean).join(', ');

                $('#country-input').val(countryName);
                $('#state-input').val(stateName);
                $('#city-input').val(cityName);
                $('#address-hidden').val(fullAddress);
                if (lat && lng) {
                    $('#latitude-input').val(lat);
                    $('#longitude-input').val(lng);
                    if (typeof selectLocationFromCoords === 'function') {
                        selectLocationFromCoords(parseFloat(lat), parseFloat(lng));
                    }
                }

                $('#selected-address-display').show();
                $('#selected-address-text').text(fullAddress);
                $('#location-search').val(fullAddress);

                bootstrap.Modal.getInstance(document.getElementById('manualLocationModal')).hide();
            });

            $('#manualLocationModal').on('hidden.bs.modal', function() {
                // keep values; just allow reopen
            });
        });
        @endif

        // Success callback function for advertisement form
        window.handleAdvertisementSuccess = function(response) {
            // Redirect to advertisement index page
            window.location.href = '{{ route('advertisement.index') }}';
        };

        // Set item_type from query param
        $(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const itemType = urlParams.get('item_type') || 'normal';
            $('#item_type_input').val(itemType);

            if (itemType === 'reel') {
                $('#reel-video-section').show();
                $('#product-images-col').removeClass('col-md-12').addClass('col-md-6');
                $('#media-images-label').text('{{ __('Product Images') }}');
            } else {
                $('#product-images-col').removeClass('col-md-6').addClass('col-md-12');
            }
        });

        // ── Reel Video / Thumbnail ─────────────────────────────────
        (function () {
            const FRAME_COUNT = 10;
            const MAX_DURATION = parseInt("{{ \App\Services\CachingService::getSystemSettings('reel_max_duration_sec') ?: 60 }}");
            let editModal = null;
            let clipStartTime = 0;
            window.reelTrimmedBlob = null;
            window.reelTrimming = false;
            window.reelActualDuration = 0;
            window.reelTrimDuration = 0;

            function fmtDur(sec) {
                const s = Math.floor(sec);
                return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
            }

            let originalSubmitButtonHtml = null;

            function updateSubmitButtonState() {
                const submitBtn = $('form.create-form [type=submit]');
                if (!submitBtn.length) return;
                
                if (originalSubmitButtonHtml === null) {
                    originalSubmitButtonHtml = submitBtn.html();
                }

                if (window.reelTrimming || window.reelUploading) {
                    submitBtn.prop('disabled', true);
                    let label = '{{ __('Please Wait...') }}';
                    submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ' + label);
                } else {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalSubmitButtonHtml);
                }
            }

            function setTrimBlocking(blocking) {
                window.reelTrimming = blocking;
                updateSubmitButtonState();
                $('#reel-edit-video-btn,#reel-video-change-btn').prop('disabled', blocking);
                $('#reel-trim-overlay').hide();
                
                const uploadArea = $('#reel-video-upload-area');
                const instructions = uploadArea.find('.upload-instructions');
                const loadingState = uploadArea.find('.upload-loading-state');
                const statusText = uploadArea.find('.reel-upload-status-text');
                const progressBarWrap = uploadArea.find('.progress');
                
                if (blocking) {
                    uploadArea.show();
                    instructions.hide();
                    loadingState.show();
                    progressBarWrap.hide();
                    statusText.text('{{ __('Trimming video...') }}');
                    
                    // Show: actual duration + trimming badge
                    $('#reel-video-duration').text(fmtDur(window.reelActualDuration));
                    $('#reel-trim-badge').text(fmtDur(window.reelTrimDuration) + ' {{ __('Trimming…') }}').removeClass('text-success').addClass('text-warning').show();
                } else {
                    loadingState.hide();
                    instructions.show();
                    uploadArea.hide();
                    
                    // Show: trimmed duration / actual (if different) + Ready badge
                    const trimmed = fmtDur(window.reelTrimDuration);
                    const actual  = fmtDur(window.reelActualDuration);
                    $('#reel-video-duration').text(
                        window.reelTrimDuration < window.reelActualDuration
                            ? trimmed + ' / ' + actual
                            : actual
                    );
                    $('#reel-trim-badge').text('{{ __('Ready') }}').removeClass('text-warning').addClass('text-success');
                }
            }

            let _ffmpegInstance = null;
            async function getReelFFmpeg() {
                if (_ffmpegInstance) return _ffmpegInstance;
                const { FFmpeg } = FFmpegWASM;
                const { toBlobURL } = FFmpegUtil;
                const ffmpeg = new FFmpeg();
                ffmpeg.on('log', function(l) { console.log('[ffmpeg]', l.message); });
                console.log('[ffmpeg] loading core...');
                const baseURL = '{{ asset('assets/js/ffmpeg') }}';
                await ffmpeg.load({
                    coreURL: await toBlobURL(baseURL + '/ffmpeg-core.js', 'text/javascript'),
                    wasmURL: await toBlobURL(baseURL + '/ffmpeg-core.wasm', 'application/wasm'),
                });
                console.log('[ffmpeg] core loaded');
                _ffmpegInstance = ffmpeg;
                return ffmpeg;
            }

            async function startReelTrim(src, startSec, durationSec) {
                window.reelTrimmedBlob = null;
                window.reelTrimDuration = durationSec;

                setTrimBlocking(true);

                const originalFile = document.getElementById('reel-video-input').files[0];

                try {
                    const ffmpeg = await getReelFFmpeg();
                    const { fetchFile } = FFmpegUtil;
                    console.log('[ffmpeg] reading input file...');
                    const inputData = await fetchFile(originalFile || src);
                    console.log('[ffmpeg] input file read, bytes:', inputData.length);

                    const inName = 'in.mp4';
                    const outName = 'out.mp4';

                    await ffmpeg.writeFile(inName, inputData);
                    console.log('[ffmpeg] wrote to virtual FS, starting copy exec...');

                    // Fast path: stream copy (remux only, no decode/encode — near-instant).
                    // Cut snaps to the nearest keyframe, so boundaries may be off by a bit.
                    try {
                        await ffmpeg.exec([
                            '-ss', String(startSec),
                            '-i', inName,
                            '-t', String(durationSec),
                            '-c', 'copy',
                            '-avoid_negative_ts', 'make_zero',
                            outName
                        ]);
                        console.log('[ffmpeg] copy exec done');
                    } catch (copyErr) {
                        console.log('[ffmpeg] copy failed, falling back to re-encode', copyErr);
                        // Fallback: re-encode (slow, but frame-accurate / handles unsupported codecs).
                        await ffmpeg.deleteFile(outName).catch(function() {});
                        await ffmpeg.exec([
                            '-ss', String(startSec),
                            '-i', inName,
                            '-t', String(durationSec),
                            '-c:v', 'libx264',
                            '-preset', 'ultrafast',
                            '-c:a', 'aac',
                            '-movflags', '+faststart',
                            outName
                        ]);
                        console.log('[ffmpeg] re-encode exec done');
                    }

                    const data = await ffmpeg.readFile(outName);
                    console.log('[ffmpeg] output read, bytes:', data.length);
                    // Pass Uint8Array directly — data.buffer may include extra bytes outside the view
                    window.reelTrimmedBlob = new Blob([data], { type: 'video/mp4' });

                    await ffmpeg.deleteFile(inName);
                    await ffmpeg.deleteFile(outName);
                } catch (e) {
                    console.error('Reel trim (ffmpeg.wasm) failed, using original file', e);
                    if (originalFile) {
                        window.reelTrimmedBlob = originalFile;
                    }
                } finally {
                    setTrimBlocking(false);
                }
            }

            window.reelUploading = false;

            // Override formAjaxRequest to inject trimmed blob into FormData at submit time and show upload progress
            (function() {
                const _orig = window.formAjaxRequest;
                window.formAjaxRequest = function(type, url, data, formElement, submitBtn, successCb, errorCb) {
                    const isReel = $('#item_type_input').val() === 'reel';
                    const hasVideo = window.reelTrimmedBlob || (document.getElementById('reel-video-input') && document.getElementById('reel-video-input').files.length > 0);

                    if (isReel && hasVideo) {
                        // Check parsley validation
                        let parsley = formElement.parsley({
                            excluded: 'input[type=button], input[type=submit], input[type=reset], :hidden'
                        });
                        parsley.validate();
                        if (!parsley.isValid()) {
                            return false;
                        }

                        // Inject trimmed blob if exists
                        if (window.reelTrimmedBlob && data instanceof FormData) {
                            const ext = window.reelTrimmedBlob.type.includes('mp4') ? 'mp4' : 'webm';
                            data.delete('reel_video');
                            data.append('reel_video', window.reelTrimmedBlob, 'reel.' + ext);
                        }

                        window.reelUploading = true;
                        updateSubmitButtonState();

                        // Show loading/progress in the upload input area
                        const uploadArea = $('#reel-video-upload-area');
                        const instructions = uploadArea.find('.upload-instructions');
                        const loadingState = uploadArea.find('.upload-loading-state');
                        const statusText = uploadArea.find('.reel-upload-status-text');
                        const progressBarWrap = uploadArea.find('.progress');
                        const progressBar = uploadArea.find('.progress-bar');

                        uploadArea.show();
                        instructions.hide();
                        loadingState.show();
                        progressBarWrap.show();
                        statusText.text('{{ __('Uploading video...') }}');
                        progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');

                        // Disable Edit/Delete buttons during upload
                        $('#reel-edit-video-btn,#reel-video-change-btn').prop('disabled', true);

                        // Adjust request type for _method PUT/DELETE if needed
                        let ajaxType = type;
                        if (!["get", "post"].includes(type.toLowerCase())) {
                            if (data instanceof FormData) {
                                data.append("_method", type);
                            }
                            ajaxType = "POST";
                        }

                        $.ajax({
                            type: ajaxType,
                            url: url,
                            data: data,
                            cache: false,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            xhr: function() {
                                var xhr = new window.XMLHttpRequest();
                                xhr.upload.addEventListener("progress", function(evt) {
                                    if (evt.lengthComputable) {
                                        var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                                        progressBar.css('width', percentComplete + '%').attr('aria-valuenow', percentComplete).text(percentComplete + '%');
                                        statusText.text('{{ __('Uploading video...') }} ' + percentComplete + '%');
                                    }
                                }, false);
                                return xhr;
                            },
                            success: function(response) {
                                try {
                                    if (!response.error) {
                                        if (successCb != null) {
                                            successCb(response);
                                        }
                                    } else {
                                        showErrorToast(response.message);
                                        if (errorCb != null) {
                                            errorCb(response);
                                        }
                                    }
                                } finally {
                                    window.reelUploading = false;
                                    updateSubmitButtonState();
                                    loadingState.hide();
                                    instructions.show();
                                    uploadArea.hide();
                                    $('#reel-edit-video-btn,#reel-video-change-btn').prop('disabled', false);
                                }
                            },
                            error: function(jqXHR) {
                                if (jqXHR.responseJSON) {
                                    showErrorToast(jqXHR.responseJSON.message);
                                } else {
                                    showErrorToast('{{ __('Something went wrong during upload.') }}');
                                }
                                window.reelUploading = false;
                                updateSubmitButtonState();
                                loadingState.hide();
                                instructions.show();
                                $('#reel-edit-video-btn,#reel-video-change-btn').prop('disabled', false);
                                if (errorCb != null) {
                                    errorCb();
                                }
                            }
                        });
                    } else {
                        // Fallback to original formAjaxRequest
                        if (window.reelTrimmedBlob && data instanceof FormData) {
                            const ext = window.reelTrimmedBlob.type.includes('mp4') ? 'mp4' : 'webm';
                            data.delete('reel_video');
                            data.append('reel_video', window.reelTrimmedBlob, 'reel.' + ext);
                        }
                        return _orig(type, url, data, formElement, submitBtn, successCb, errorCb);
                    }
                };
            })();

            $('#reel-video-upload-area').on('click', function (e) {
                if (window.reelTrimming || window.reelUploading) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (e.target !== document.getElementById('reel-video-input')) {
                    document.getElementById('reel-video-input').click();
                }
            });

            $('#reel-video-input').on('click', function (e) { e.stopPropagation(); });
            $('#reel-video-btn').on('click', function (e) { e.stopPropagation(); $('#reel-video-input').click(); });

            $('#reel-video-input').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const blobUrl = URL.createObjectURL(file);
                const video = document.getElementById('reel-video-preview');
                video.src = blobUrl;
                video.load();
                video.onloadedmetadata = function () {
                    clipStartTime = 0;
                    $('#reel-start-time').val(0);
                    $('#reel-video-upload-area').hide();
                    $('#reel-video-filename').text(file.name);
                    const clipDuration = Math.min(MAX_DURATION, video.duration);
                    window.reelActualDuration = video.duration;
                    $('#reel-video-duration').text(fmtDur(video.duration));
                    $('#reel-trim-badge').hide();
                    $('#reel-video-selected').show();

                    // Auto-trim first MAX_DURATION seconds immediately
                    startReelTrim(blobUrl, 0, clipDuration);

                    // Auto-capture first frame and copy to small preview
                    captureAt(0, function() { copyThumbToSmall(); });
                };
            });

            // Drag and drop for reel video
            const reelUploadArea = document.getElementById('reel-video-upload-area');
            if (reelUploadArea) {
                reelUploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (window.reelTrimming || window.reelUploading) return;
                    $(this).css('border-color', 'var(--bs-primary)');
                    $(this).css('background', 'rgba(var(--bs-primary-rgb), 0.08)');
                });
                
                reelUploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#bbb');
                    $(this).css('background', '#fff');
                });
                
                reelUploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    $(this).css('border-color', '#bbb');
                    $(this).css('background', '#fff');
                    
                    if (window.reelTrimming || window.reelUploading) return;
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const file = files[0];
                        if (file.type.startsWith('video/')) {
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            const fileInput = document.getElementById('reel-video-input');
                            fileInput.files = dt.files;
                            $(fileInput).trigger('change');
                        } else {
                            Toastify({ text: '{{ __("Please select a valid video file.") }}', duration: 3000, close: true, backgroundColor: '#dc3545' }).showToast();
                        }
                    }
                });
            }

            // ── Two-step modal ─────────────────────────────────────
            let clipSetupDone = false;
            const reelModalEl = document.getElementById('reelEditVideoModal');

            function showStep(n) {
                $('#reel-step-1').toggle(n === 1);
                $('#reel-step-2').toggle(n === 2);
                // footer button label
                $('#reel-edit-done-btn').text(n === 1 ? '{{ __('Save Video') }}' : '{{ __('Save Thumbnail') }}');
            }

            reelModalEl.addEventListener('shown.bs.modal', function () {
                showStep(1);
                const video = document.getElementById('reel-video-preview');
                if (!video.src && document.getElementById('reel-video-input').files[0]) {
                    video.src = URL.createObjectURL(document.getElementById('reel-video-input').files[0]);
                }
                if (!clipSetupDone) {
                    const ready = function() {
                        if (clipSetupDone || !video.duration || isNaN(video.duration)) return;
                        clipSetupDone = true;
                        extractFrames(video);
                        setupClipWindow(video, video.duration);
                    };
                    if (video.duration && !isNaN(video.duration)) { ready(); }
                    else { video.onloadedmetadata = ready; }
                } else if (_clipRenderFn) {
                    // Restore to last saved position + duration on re-open
                    _clipRenderFn(_savedClipStartTime, _savedClipDuration || MAX_DURATION);
                }
            });

            // Step 1 → Step 2: build clip-only thumbnail strip
            $('#reel-goto-thumb-btn').on('click', function () {
                buildThumbStrip();
                showStep(2);
            });

            $('#reel-back-to-step1-btn').on('click', function () { showStep(1); });

            $('#reel-edit-video-btn').on('click', function () {
                if (!editModal) editModal = new bootstrap.Modal(reelModalEl);
                editModal.show();
            });

            // Done button — step-aware
            // Track the last *saved* clip start so Cancel can restore it
            let _savedClipStartTime = 0;

            function closeReelModal(save) {
                const video = document.getElementById('reel-video-preview');
                if (save && video && video.src) {
                    _savedClipStartTime = clipStartTime;
                    _savedClipDuration  = window.reelTrimDuration || Math.min(MAX_DURATION, video.duration - clipStartTime);
                    $('#reel-start-time').val(clipStartTime.toFixed(3));
                    window.reelActualDuration = video.duration;
                    startReelTrim(video.src, clipStartTime, _savedClipDuration);
                } else {
                    // Discard — restore visually on next open via _clipRenderFn
                    clipStartTime = _savedClipStartTime;
                    if (_clipRenderFn) _clipRenderFn(_savedClipStartTime, _savedClipDuration || MAX_DURATION);
                }
                if (editModal) editModal.hide();
            }

            $('#reel-modal-cancel-btn, #reel-modal-x-btn').on('click', function () {
                closeReelModal(false);
            });

            $('#reel-edit-done-btn').on('click', function () {
                const onStep2 = $('#reel-step-2').is(':visible');
                if (onStep2) {
                    copyThumbToSmall();
                    closeReelModal(true);
                } else {
                    buildThumbStrip();
                    showStep(2);
                }
            });

            // Change video — reset everything
            $('#reel-video-change-btn').on('click', function () {
                $('#reel-video-selected').hide();
                const uploadArea = $('#reel-video-upload-area');
                uploadArea.find('.upload-instructions').show();
                uploadArea.find('.upload-loading-state').hide();
                uploadArea.show();
                $('#reel-video-input').val('');
                document.getElementById('reel-video-preview').src = '';
                document.getElementById('reel-timeline').innerHTML = '';
                $('#reel-thumbnail-time').val('0');
                $('#reel-thumbnail-custom').val('');
                $('#reel-start-time').val('0');
                clipStartTime = 0;
                clipSetupDone = false;
                $('#reel-clip-window,#reel-dim-left,#reel-dim-right').hide();
                $('#reel-thumb-img').attr('src','').hide();
                $('#reel-thumb-placeholder').show();
            });

            // ── Thumbnail scrubber (independent) ───────────────────
            let thumbScrubVideo = null; // dedicated video element for frame capture

            function initThumbScrubber(src, duration) {
                // hidden video for frame capture — separate from preview
                thumbScrubVideo = document.createElement('video');
                thumbScrubVideo.src = src;
                thumbScrubVideo.muted = true;
                thumbScrubVideo.preload = 'auto';

                const scrubber = document.getElementById('reel-thumb-scrubber');
                const timeLabel = document.getElementById('reel-thumb-time-label');
                const durLabel  = document.getElementById('reel-thumb-duration-label');

                scrubber.max   = duration;
                scrubber.step  = duration / 1000;
                scrubber.value = 0;
                durLabel.textContent = fmtTime(duration);
                timeLabel.textContent = '0:00';

                // Seek and capture on scrub
                let scrubTimer = null;
                scrubber.addEventListener('input', function () {
                    const t = parseFloat(this.value);
                    timeLabel.textContent = fmtTime(t);
                    clearTimeout(scrubTimer);
                    scrubTimer = setTimeout(function() { captureAt(t); }, 80);
                });

                // capture first frame on load
                thumbScrubVideo.onloadeddata = function() { captureAt(0); };
            }

            function captureAt(time, callback) {
                const video = document.getElementById('reel-video-preview');
                if (!video || !video.src) return;

                const isNew = !thumbScrubVideo || thumbScrubVideo.src !== video.src;
                if (isNew) {
                    thumbScrubVideo = document.createElement('video');
                    thumbScrubVideo.src = video.src;
                    thumbScrubVideo.muted = true;
                    thumbScrubVideo.preload = 'auto';
                    thumbScrubVideo.crossOrigin = 'anonymous';
                }

                function doSeekAndCapture() {
                    thumbScrubVideo.onseeked = function() {
                        const vw = thumbScrubVideo.videoWidth  || 720;
                        const vh = thumbScrubVideo.videoHeight || 1280;
                        const off = document.createElement('canvas');
                        off.width = vw; off.height = vh;
                        const ctx = off.getContext('2d');
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(thumbScrubVideo, 0, 0, vw, vh);
                        const dataUrl = off.toDataURL('image/jpeg', 0.92);
                        showThumb(dataUrl);
                        $('#reel-thumbnail-time').val(time.toFixed(3));
                        $('#reel-thumbnail-custom').val('');
                        thumbScrubVideo.onseeked = null;
                        if (typeof callback === 'function') callback(dataUrl);
                    };
                    thumbScrubVideo.currentTime = time;
                }

                if (isNew) {
                    thumbScrubVideo.onloadedmetadata = function() {
                        thumbScrubVideo.onloadedmetadata = null;
                        doSeekAndCapture();
                    };
                } else {
                    doSeekAndCapture();
                }
            }

            function showThumb(dataUrl) {
                const img = document.getElementById('reel-thumb-img');
                img.src = dataUrl;
                img.style.display = 'block';
                document.getElementById('reel-thumb-placeholder').style.display = 'none';
                $('#reel-thumbnail-data').val(dataUrl);
            }

            function copyThumbToSmall(dataUrl) {
                const src = dataUrl || document.getElementById('reel-thumb-img').src;
                if (!src) return;
                const small = document.getElementById('reel-thumb-preview-small');
                const i = new Image();
                i.onload = function() {
                    small.width = 40; small.height = 60;
                    small.getContext('2d').drawImage(i, 0, 0, 40, 60);
                };
                i.src = src;
            }

            function fmtTime(t) {
                return Math.floor(t / 60) + ':' + String(Math.floor(t % 60)).padStart(2, '0');
            }

            // ── Clip-only thumbnail strip ───────────────────────────
            function buildThumbStrip() {
                const strip = document.getElementById('reel-thumb-strip');
                strip.innerHTML = '';
                const video = document.getElementById('reel-video-preview');
                if (!video.src || !video.duration) return;

                const THUMB_N   = 5;
                const start     = clipStartTime;
                const clipDur   = window.reelTrimDuration || Math.min(MAX_DURATION, video.duration - start);
                const end       = Math.min(start + clipDur, video.duration);
                const stripW  = strip.parentElement.offsetWidth || 400;
                const frameW  = Math.floor((stripW - (THUMB_N - 1) * 4) / THUMB_N);
                const frameH  = 70;

                const times = [], wrappers = [], canvases = [];
                for (let i = 0; i < THUMB_N; i++) {
                    const t = start + ((end - start) / (THUMB_N - 1)) * i;
                    times.push(t);

                    const w = document.createElement('div');
                    w.style.cssText = 'flex-shrink:0;border-radius:6px;overflow:hidden;cursor:pointer;border:2px solid transparent;width:' + frameW + 'px;height:' + frameH + 'px;background:#111;';
                    w.dataset.time = t;

                    const c = document.createElement('canvas');
                    c.width = frameW; c.height = frameH;
                    c.style.cssText = 'width:100%;height:100%;display:block;';
                    w.appendChild(c);
                    strip.appendChild(w);
                    wrappers.push(w); canvases.push(c);

                    (function(el, time) {
                        el.addEventListener('click', function() {
                            captureAt(time);
                            strip.querySelectorAll('div[data-time]').forEach(function(x) { x.style.borderColor = 'transparent'; });
                            el.style.borderColor = 'var(--bs-primary)';
                        });
                    })(w, t);
                }

                extractFramesSeq(video.src, times, function(idx, vid) {
                    canvases[idx].getContext('2d').drawImage(vid, 0, 0, frameW, frameH);
                }, function() {
                    if (wrappers[0]) wrappers[0].click();
                });
            }

            // ── Sequential frame extractor (single video element) ──
            function extractFramesSeq(src, times, onFrame, onDone) {
                const tmp = document.createElement('video');
                tmp.src     = src;
                tmp.muted   = true;
                tmp.preload = 'auto';
                let idx = 0;
                tmp.onloadedmetadata = function() { seekNext(); };
                tmp.onseeked = function() {
                    onFrame(idx, tmp);
                    idx++;
                    if (idx < times.length) seekNext();
                    else { if (onDone) onDone(); tmp.src = ''; }
                };
                function seekNext() { tmp.currentTime = times[idx]; }
            }

            // ── Filmstrip (clip timeline) ───────────────────────────
            function extractFrames(video) {
                const duration = video.duration;
                const timeline = document.getElementById('reel-timeline');
                timeline.innerHTML = '';

                const tlWidth = timeline.parentElement.offsetWidth || 500;
                const frameW  = Math.floor(tlWidth / FRAME_COUNT);
                const frameH  = 60;

                const times = [], canvases = [];
                for (let i = 0; i < FRAME_COUNT; i++) {
                    const t = i === 0 ? 0 : (duration / (FRAME_COUNT - 1)) * i;
                    times.push(t);
                    const c = document.createElement('canvas');
                    c.width  = frameW;
                    c.height = frameH;
                    c.style.cssText = 'flex-shrink:0;width:' + frameW + 'px;height:' + frameH + 'px;display:block;';
                    timeline.appendChild(c);
                    canvases.push(c);
                }

                extractFramesSeq(video.src, times, function(idx, vid) {
                    canvases[idx].getContext('2d').drawImage(vid, 0, 0, frameW, frameH);
                });
            }

            // ── Filmstrip trimmer ───────────────────────────────────
            let _clipRenderFn = null; // exposed so modal-open can restore saved position

            let _savedClipDuration = null; // persists across modal open/close

            function setupClipWindow(video, duration) {
                const clipWin  = document.getElementById('reel-clip-window');
                const dimLeft  = document.getElementById('reel-dim-left');
                const dimRight = document.getElementById('reel-dim-right');
                const playhead = document.getElementById('reel-playhead');
                const tl       = document.getElementById('reel-timeline');

                const MIN_SEC = 1;
                let tlW   = tl.offsetWidth;
                let maxPx = Math.min(Math.round((MAX_DURATION / duration) * tlW), tlW);
                let minPx = Math.max(1, Math.ceil((MIN_SEC / duration) * tlW));

                let clipLeft = 0;
                let clipPx   = maxPx;

                clipWin.style.display = 'block';
                dimLeft.style.display = dimRight.style.display = 'block';

                const fmt = t => Math.floor(t/60) + ':' + String(Math.floor(t%60)).padStart(2,'0');

                function render(left, width) {
                    width    = Math.max(minPx, Math.min(width, maxPx));
                    clipLeft = Math.max(0, Math.min(left, tlW - width));
                    clipPx   = width;

                    clipWin.style.left  = clipLeft + 'px';
                    clipWin.style.width = clipPx   + 'px';

                    dimLeft.style.width  = clipLeft + 'px';
                    dimRight.style.left  = (clipLeft + clipPx) + 'px';
                    dimRight.style.width = (tlW - clipLeft - clipPx) + 'px';
                    dimLeft.style.display  = clipLeft > 0 ? 'block' : 'none';
                    dimRight.style.display = (clipLeft + clipPx < tlW) ? 'block' : 'none';

                    clipStartTime = tlW > 0 ? (clipLeft / tlW) * duration : 0;
                    if (!isFinite(clipStartTime)) clipStartTime = 0;

                    const clipDurSec = (clipPx / tlW) * duration;
                    const endSec     = clipStartTime + clipDurSec;

                    window.reelTrimDuration = clipDurSec;
                    $('#reel-start-time').val(clipStartTime.toFixed(2));
                    $('#reel-clip-info').text(fmt(clipStartTime) + ' – ' + fmt(endSec) + '  (' + fmtDur(clipDurSec) + ')');

                    if (!video.paused) video.pause();
                    if (isFinite(clipStartTime)) video.currentTime = clipStartTime;
                }

                // Expose so modal-open can restore saved position + duration
                _clipRenderFn = function(startSec, durSec) {
                    const left  = tlW > 0 ? (startSec / duration) * tlW : 0;
                    const width = tlW > 0 ? (Math.min(durSec || MAX_DURATION, MAX_DURATION) / duration) * tlW : maxPx;
                    render(left, width);
                };

                // Recompute px bounds when the container resizes (e.g. DevTools open/close,
                // viewport resize) — stale absolute px otherwise pokes outside the modal.
                window.addEventListener('resize', function() {
                    const newTlW = tl.offsetWidth;
                    if (!newTlW || newTlW === tlW) return;
                    tlW   = newTlW;
                    maxPx = Math.min(Math.round((MAX_DURATION / duration) * tlW), tlW);
                    minPx = Math.max(1, Math.ceil((MIN_SEC / duration) * tlW));
                    _clipRenderFn(clipStartTime, window.reelTrimDuration || _savedClipDuration || MAX_DURATION);
                });

                _clipRenderFn(_savedClipStartTime, _savedClipDuration || MAX_DURATION);

                // Playhead — use actual clip duration
                video.ontimeupdate = function() {
                    const clipDurSec = (clipPx / tlW) * duration;
                    const elapsed    = video.currentTime - clipStartTime;
                    playhead.style.left = (Math.min(elapsed / clipDurSec, 1) * (clipPx - 3)) + 'px';
                    if (video.currentTime >= clipStartTime + clipDurSec) {
                        video.pause();
                        video.currentTime = clipStartTime;
                    }
                };

                // Unified drag handler — type: 'window' | 'left' | 'right'
                let drag = null;

                function dragStart(clientX, type) {
                    drag = { type, startX: clientX, startLeft: clipLeft, startWidth: clipPx };
                    if (type === 'window') clipWin.style.cursor = 'grabbing';
                }
                function dragMove(clientX) {
                    if (!drag) return;
                    const dx = clientX - drag.startX;
                    if (drag.type === 'window') {
                        render(drag.startLeft + dx, drag.startWidth);
                    } else if (drag.type === 'left') {
                        render(drag.startLeft + dx, drag.startWidth - dx);
                    } else {
                        render(drag.startLeft, drag.startWidth + dx);
                    }
                }
                function dragEnd() { drag = null; clipWin.style.cursor = 'grab'; }

                const hleft  = document.getElementById('reel-handle-left');
                const hright = document.getElementById('reel-handle-right');

                hleft.addEventListener('mousedown',  e => { e.stopPropagation(); e.preventDefault(); dragStart(e.clientX, 'left'); });
                hright.addEventListener('mousedown', e => { e.stopPropagation(); e.preventDefault(); dragStart(e.clientX, 'right'); });
                clipWin.addEventListener('mousedown', e => {
                    if (hleft.contains(e.target) || hright.contains(e.target)) return;
                    e.preventDefault(); dragStart(e.clientX, 'window');
                });
                hleft.addEventListener('touchstart',  e => { e.stopPropagation(); dragStart(e.touches[0].clientX, 'left'); }, {passive:true});
                hright.addEventListener('touchstart', e => { e.stopPropagation(); dragStart(e.touches[0].clientX, 'right'); }, {passive:true});
                clipWin.addEventListener('touchstart', e => {
                    if (hleft.contains(e.target) || hright.contains(e.target)) return;
                    dragStart(e.touches[0].clientX, 'window');
                }, {passive:true});

                document.addEventListener('mousemove', e => dragMove(e.clientX));
                document.addEventListener('touchmove', e => dragMove(e.touches[0].clientX), {passive:true});
                document.addEventListener('mouseup',  dragEnd);
                document.addEventListener('touchend', dragEnd, {passive:true});
            }

            // Custom thumbnail upload
            $('#reel-thumb-upload-btn').on('click', function () { $('#reel-thumb-custom-input').click(); });
            $('#reel-thumb-custom-input').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    showThumb(e.target.result);
                    $('#reel-thumbnail-custom').val(e.target.result);
                    $('#reel-thumbnail-time').val('');
                    document.querySelectorAll('#reel-timeline canvas').forEach(c => c.style.borderColor = 'transparent');
                    copyThumbToSmall(e.target.result);
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
@endsection
