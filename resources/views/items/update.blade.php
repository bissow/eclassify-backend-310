@extends('layouts.main')

@section('title')
    {{ __('Update Advertisements') }}
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
                <form method="POST" action="{{ route('advertisement.update', $item->id) }}" class="edit-form" data-parsley-validate data-pre-submit-function="validateAdvertisementUpdateForm" data-success-function="handleAdvertisementSuccess" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $item->id }}">
                    <input type="hidden" name="item_type" id="item_type_input" value="{{ $item->item_type ?? 'normal' }}">

                    <ul class="nav nav-tabs" id="editItemTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="listing-tab" data-bs-toggle="tab" href="#listing" role="tab" aria-controls="listing" aria-selected="true">{{ __('Listing Details') }}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="custom-tab" data-bs-toggle="tab" href="#custom" role="tab" aria-controls="custom" aria-selected="false">{{ __('Extra Details') }}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="images-tab" data-bs-toggle="tab" href="#images" role="tab" aria-controls="images" aria-selected="false">{{ __('Media') }}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="address-tab" data-bs-toggle="tab" href="#address" role="tab" aria-controls="address" aria-selected="false">{{ __('Address') }}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="seo-tab" data-bs-toggle="tab" href="#seo" role="tab" aria-controls="seo" aria-selected="false">{{ __('SEO') }}</a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="editItemTabContent">
                        {{-- Listing Details --}}
                        <div class="tab-pane fade show active" id="listing" role="tabpanel" aria-labelledby="listing-tab">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label class="form-label mb-1">{{ __('Selected category') }}</label>
                                            <div class="text-primary">
                                                @if ($item->category)
                                                    {{ $item->category->name }}
                                                @else
                                                    {{ __('No category selected') }}
                                                @endif
                                            </div>
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
                                            <input type="text" name="name" id="name-input" value="{{ $item->name }}"
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
                                            <textarea name="description" id="description-input" class="form-control" rows="5" required placeholder="{{ __('Enter description') }}">{{ $item->description }}</textarea>
                                </div>
                                    </div>
                                </div>

                                {{-- Other Language Fields - Only Name and Description --}}
                                @foreach ($languages as $lang)
                                    @if ($lang->id != $defaultLanguage->id)
                                        @php
                                            $translation = isset($translations) ? ($translations instanceof \Illuminate\Support\Collection ? $translations->get($lang->id) : ($translations[$lang->id] ?? null)) : null;
                                        @endphp
                                        <div class="col-12 language-fields other-language-fields" data-language-id="{{ $lang->id }}" style="display: none;">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label>{{ __('Title') }}</label>
                                                    <input type="text" name="translations[{{ $lang->id }}][name]" 
                                                        class="form-control translation-name" 
                                                        value="{{ $translation->name ?? '' }}"
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
                                                        placeholder="{{ __('Enter description') }}">{{ $translation->description ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Non-Translatable Fields - Always Visible --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label>{{ __('Category') }}</label>
                                    <select name="category_id" class="form-control" id="category-select">
                                        @if ($item->category)
                                            <option value="{{ $item->category->id }}" selected>{{ $item->category->name }}
                                            </option>
                                        @else
                                            <option value="">{{ __('Select Category') }}</option>
                                        @endif
                                    </select>

                                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal"
                                        data-bs-target="#subcategory-modal">
                                        {{ __('Change') }}
                                    </button>
                                </div>
                                <div class="col-12 mb-3">
                                    <label>{{ __('Currency') }}</label>
                                    <select class="form-control select2" id="currency" name="currency_id">
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}"
                                                data-iso-code="{{ $currency->iso_code ?? '' }}"
                                                data-symbol="{{ $currency->symbol ?? '' }}"
                                                {{ $item->currency_id == $currency->id ? 'selected' : '' }}>
                                                {{ $currency->name }}{{ $currency->symbol ? ' (' . $currency->symbol . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @php
                                    $isJobCategory = $item->category && $item->category->is_job_category;
                                    $isPriceOptional = $item->category && $item->category->price_optional;
                                @endphp

                                <div class="col-12 mb-3" id="price-field"
                                    style="{{ $isJobCategory ? 'display: none;' : '' }}">
                                    <label id="price-label">{{ __('Price') }}{!! !$isPriceOptional ? ' <span class="text-danger">*</span>' : '' !!}</label>
                                    <input type="number" name="price" id="price-input" value="{{ $item->price }}" class="form-control"
                                        {{ !$isPriceOptional ? 'required' : '' }} placeholder="{{ __('Enter price') }}">
                                </div>

                                <div class="col-12 mb-3" id="salary-fields"
                                    style="{{ $isJobCategory ? '' : 'display: none;' }}">
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                    <label id="min-salary-label">{{ __('Min Salary') }}{!! !$isPriceOptional ? ' <span class="text-danger">*</span>' : '' !!}</label>
                                    <input type="number" name="min_salary" id="min-salary-input"
                                                value="{{ old('min_salary', $item->min_salary ?? '') }}" class="form-control"
                                                {{ !$isPriceOptional ? 'required' : '' }} placeholder="{{ __('Enter minimum salary') }}">
                                        </div>
                                        <div class="col-12">
                                    <label id="max-salary-label">{{ __('Max Salary') }}{!! !$isPriceOptional ? ' <span class="text-danger">*</span>' : '' !!}</label>
                                    <input type="number" name="max_salary" id="max-salary-input"
                                        value="{{ old('max_salary', $item->max_salary ?? '') }}" class="form-control"
                                                {{ !$isPriceOptional ? 'required' : '' }} placeholder="{{ __('Enter maximum salary') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Phone Number') }}</label>
                                    <input type="tel" name="contact" id="contact-input"
                                        value="{{ $item->contact }}" class="form-control"
                                        placeholder="{{ __('Enter phone number') }}">
                                    <input type="hidden" name="country_code" id="country-code-input" value="{{ $item->country_code ?? '' }}">
                                    <input type="hidden" name="region_code" id="region-code-input" value="{{ $item->region_code ?? '' }}">
                                </div>

                                <div class="col-12 mb-3">
                                    <label>{{ __('Slug') }}</label>
                                    <input type="text" name="slug" value="{{ $item->slug }}" class="form-control"
                                        placeholder="{{ __('Enter slug (optional)') }}">
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <div></div>
                            <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="custom-or-images">{{ __('Next') }}</button>
                        </div>
                        </div>

                        {{-- Extra Details - Custom Fields --}}
                        <div class="tab-pane fade" id="custom" role="tabpanel" aria-labelledby="custom-tab">
                            <div class="row mb-3">
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
                            </div>

                            <div class="row">
                                {{-- Default Language Custom Fields --}}
                                <div class="col-12 custom-fields-language-section" data-language-id="{{ $defaultLanguage->id }}">
                            <div class="row">
                                @forelse($custom_fields as $field)
                                    <div class="col-md-6 mb-3">
                                        <label>{{ $field->name }} @if ($field->required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @php
                                            $isRequired = $field->required ? 'required' : '';
                                        @endphp

                                        @if ($field->type === 'textbox')
                                             @if(!is_null($field->max_length) && $field->max_length > 200)
                                                 <textarea name="custom_fields[{{ $field->id }}]"
                                                     class="form-control custom-field-input" rows="3"
                                                     @if(!is_null($field->max_length)) maxlength="{{ $field->max_length }}" data-max-length="{{ $field->max_length }}" @endif
                                                     @if(!is_null($field->min_length)) minlength="{{ $field->min_length }}" data-min-length="{{ $field->min_length }}" @endif
                                                     {{ $isRequired }} data-parsley-trigger="input">{{ $field->value ?? '' }}</textarea>
                                             @else
                                                 <input type="text" name="custom_fields[{{ $field->id }}]"
                                                     class="form-control custom-field-input" value="{{ $field->value ?? '' }}"
                                                     @if(!is_null($field->max_length)) maxlength="{{ $field->max_length }}" data-max-length="{{ $field->max_length }}" @endif
                                                     @if(!is_null($field->min_length)) minlength="{{ $field->min_length }}" data-min-length="{{ $field->min_length }}" @endif
                                                     {{ $isRequired }} data-parsley-trigger="input">
                                             @endif
                                            @if(!is_null($field->max_length) || !is_null($field->min_length))
                                                <small class="form-text text-muted cf-char-counter d-block text-end"></small>
                                            @endif
                                        @elseif($field->type === 'number')
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="custom_fields[{{ $field->id }}]"
                                                class="form-control custom-field-input" value="{{ $field->value ?? '' }}"
                                                @if(!is_null($field->max_length)) maxlength="{{ $field->max_length }}" data-max-length="{{ $field->max_length }}" @endif
                                                @if(!is_null($field->min_length)) minlength="{{ $field->min_length }}" data-min-length="{{ $field->min_length }}" @endif
                                                @if(!is_null($field->max_length) && !is_null($field->min_length)) data-parsley-length-message="This value should be between {{ $field->min_length }} and {{ $field->max_length }} digits long." @endif
                                                {{ $isRequired }} data-parsley-trigger="input">
                                        @elseif($field->type === 'fileinput')
                                            @php
                                                $fileUrl = is_array($field->value) ? ($field->value[0] ?? '') : ($field->value ?? '');
                                                $fileExtension = $fileUrl ? strtolower(pathinfo(parse_url($fileUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) : '';
                                                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                            @endphp
                                            @if (!empty($fileUrl))
                                                <div class="mb-2 p-2 border rounded d-flex align-items-center gap-2">
                                                    @if ($isImage)
                                                        <img src="{{ $fileUrl }}" alt="{{ $field->name }}" width="80" height="80" style="object-fit: cover; border-radius: 4px;">
                                                    @else
                                                        <i class="fas fa-file-alt fa-2x text-secondary"></i>
                                                    @endif
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> {{ __('View File') }}
                                                    </a>
                                                </div>
                                            @endif
                                            <input type="file" name="custom_field_files[{{ $field->id }}]"
                                                class="form-control custom-field-input" {{ !empty($fileUrl) ? '' : $isRequired }} data-parsley-trigger="change">
                                        @elseif($field->type === 'dropdown' || $field->type === 'radio')
                                            @php $options = is_array($field->values) ? $field->values : json_decode($field->values, true); @endphp
                                            <select name="custom_fields[{{ $field->id }}]" class="form-select custom-field-input"
                                                {{ $isRequired }} data-parsley-trigger="change">
                                                <option value="">{{ __('Select') }}</option>
                                                @foreach ($options as $option)
                                                    <option value="{{ $option }}"
                                                        {{ $field->value == $option ? 'selected' : '' }}>
                                                        {{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field->type === 'checkbox')
                                            @php $options = is_array($field->values) ? $field->values : json_decode($field->values, true); @endphp
                                            @foreach ($options as $option)
                                                <div class="form-check">
                                                    <input class="form-check-input custom-field-checkbox" type="checkbox"
                                                        name="custom_fields[{{ $field->id }}][]"
                                                        value="{{ $option }}"
                                                        {{ is_array($field->value) && in_array($option, $field->value) ? 'checked' : '' }}
                                                        {{ $isRequired }} data-parsley-trigger="change">
                                                    <label class="form-check-label">{{ $option }}</label>
                                                </div>
                                            @endforeach
                                        @endif

                                    </div>
                                @empty
                                    <div class="col-12"><p class="text-muted">{{ __('No custom fields for this category.') }}</p></div>
                                @endforelse
                            </div>
                        </div>

                                {{-- Other Language Custom Fields - Only show translatable textbox fields --}}
                                @foreach ($languages as $lang)
                                    @if ($lang->id != $defaultLanguage->id)
                                        <div class="col-12 custom-fields-language-section" data-language-id="{{ $lang->id }}" style="display: none;">
                            <div class="row">
                                            @php
                                                // Get all textbox type custom fields for other languages (regardless of whether they have translations)
                                                $translatableFields = $custom_fields->filter(function($field) {
                                                    return $field->type === 'textbox';
                                                });
                                            @endphp
                                            @forelse($translatableFields as $field)
                                                @php
                                                    $fieldNameTrans = $field->translations->where('language_id', $lang->id)->where('key', 'name')->first();
                                                    $fieldName = $fieldNameTrans ? $fieldNameTrans->value : $field->name;
                                                    $isRequired = $field->required ? 'required' : '';
                                                    // Get translated value from ItemCustomFieldValue
                                                    $translatedValue = $field->translated_values[$lang->id] ?? '';
                                                @endphp
                                                <div class="col-md-6 mb-3">
                                                    <label>{{ $fieldName }} @if ($field->required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="text" name="custom_field_translations[{{ $lang->id }}][{{ $field->id }}]"
                                                        class="form-control" value="{{ is_array($translatedValue) ? ($translatedValue[0] ?? '') : $translatedValue }}">
                                </div>
                                            @empty
                                                <p class="text-muted">{{ __('No translatable custom fields for this language.') }}</p>
                                            @endforelse
                                        </div>
                                        </div>
                                    @endif
                                    @endforeach
                                </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="listing">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="images">{{ __('Next') }}</button>
                                </div>
                            </div>

                        {{-- Media --}}
                        <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">

                            @php
                                $itemVideo        = $item->itemVideo;
                                $currentVideoType = $itemVideo?->video_type ?? '';
                                $currentVideoLink = $itemVideo?->video_link ?? '';
                                $currentVideoFile = $itemVideo?->video_file ?? '';
                                $currentVideoTypeLabel = match($currentVideoType) {
                                    'youtube_link' => __('Youtube Link'),
                                    'vimeo_link'   => __('Vimeo Link'),
                                    'other_link'   => __('Other Link'),
                                    'file'         => __('Custom'),
                                    default        => __('None'),
                                };
                            @endphp

                            {{-- Row 1: Video Ads (reel) + Product Images --}}
                            <div class="row g-3 mb-3">

                                {{-- VIDEO ADS (reel only) --}}
                                <div class="col-md-6" id="reel-video-section" style="{{ ($item->item_type ?? 'normal') === 'reel' ? '' : 'display:none;' }}">
                                    <p class="fw-semibold mb-1">{{ __('Video Ads') }} <span class="text-danger">*</span></p>

                                    <input type="hidden" name="delete_reel" id="reel-delete-flag" value="0">

                                    @if($item->reel && $item->reel->video)
                                    <div id="reel-existing-wrap" class="mb-2">
                                        <div class="media-file-card" id="reel-existing-card">
                                            @if($item->reel->thumbnail)
                                                <img src="{{ $item->reel->thumbnail }}" style="width:48px;height:64px;object-fit:cover;border-radius:4px;flex-shrink:0;">
                                            @else
                                                <div style="width:48px;height:64px;background:#111;border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                                                    <i class="ph-bold ph-video" style="color:#fff;font-size:20px;"></i>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1 ms-2 overflow-hidden">
                                                <div class="fw-semibold small">{{ __('Current Video Ad') }}</div>
                                                <small class="text-muted">{{ __('Upload below to replace') }}</small>
                                            </div>
                                            <a href="{{ $item->reel->video }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">{{ __('Preview') }}</a>
                                        </div>
                                    </div>
                                    @endif

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

                                    <div id="reel-video-selected" style="display:none;" class="mt-2">
                                        <div class="media-file-card">
                                            <canvas id="reel-thumb-preview-small" width="48" height="64" style="border-radius:4px;flex-shrink:0;background:#111;"></canvas>
                                            <div class="flex-grow-1 ms-2 overflow-hidden">
                                                <div class="fw-semibold text-truncate small" id="reel-video-filename"></div>
                                                <small class="text-muted" id="reel-video-duration"></small>
                                                <small id="reel-trim-badge" style="display:none;" class="text-muted"></small>
                                            </div>
                                            <div class="d-flex gap-2 ms-2 flex-shrink-0">
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
                                <div class="{{ ($item->item_type ?? 'normal') === 'reel' ? 'col-md-6' : 'col-md-12' }}" id="product-images-col">
                                    <p class="fw-semibold mb-1">
                                        {{ __('Product Images') }}
                                        <i class="fas fa-info-circle ms-1 text-muted" data-bs-toggle="tooltip" title="{{ __('Upload images for your advertisement. The first image will be highlighted and considered the main image.') }}"></i>
                                    </p>

                                    <div class="media-drop-zone" id="gallery-images-upload" onclick="document.getElementById('gallery-images-input').click()">
                                        <p class="mb-1 text-secondary">{{ __('Drag & Drop your files') }}</p>
                                        <p class="mb-2 text-muted small">{{ __('or') }}</p>
                                        <a href="#" class="text-primary fw-semibold" onclick="event.preventDefault();event.stopPropagation();document.getElementById('gallery-images-input').click()">
                                            <i class="ph-bold ph-upload-simple me-1"></i>{{ __('Upload') }}
                                        </a>
                                        <input type="file" name="gallery_images[]" id="gallery-images-input" class="d-none" multiple accept="image/png,image/jpeg,image/jpg">
                                    </div>

                                    {{-- Unified gallery images container --}}
                                    <div id="gallery-images-preview" class="mt-2 d-flex flex-nowrap gap-2" style="overflow: hidden; width: 100%;"></div>

                                    {{-- Hidden checkboxes for existing images delete tracking --}}
                                    <div id="existing-images-checkboxes" class="d-none">
                                        @foreach ($item->gallery_images as $img)
                                            <input type="checkbox" name="delete_item_image_id[]" id="delete-img-{{ $img->id }}" value="{{ $img->id }}" class="delete-existing-img-cb">
                                        @endforeach
                                    </div>
                                </div>

                            </div>

                            {{-- Row 2: Product Video --}}
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="media-product-video-wrap">
                                        <div class="media-product-video-header">
                                            <span class="fw-semibold text-secondary">{{ __('Product Video') }}</span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" id="videoTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span id="videoTypeLabel">{{ $currentVideoTypeLabel }}</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="videoTypeDropdownBtn">
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('','{{ __('None') }}');return false;">{{ __('None') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('youtube_link','{{ __('Youtube Link') }}');return false;">{{ __('Youtube Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('vimeo_link','{{ __('Vimeo Link') }}');return false;">{{ __('Vimeo Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('other_link','{{ __('Other Link') }}');return false;">{{ __('Other Link') }}</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="setVideoType('file','{{ __('Custom') }}');return false;">{{ __('Custom') }}</a></li>
                                                </ul>
                                            </div>
                                            <input type="hidden" name="video_type" id="video_type_hidden" value="{{ $currentVideoType }}">
                                            <input type="hidden" name="delete_item_video" id="delete-item-video-flag" value="0">
                                        </div>

                                        <div id="item-video-link-wrap" style="{{ in_array($currentVideoType, ['youtube_link','vimeo_link','other_link']) ? '' : 'display:none;' }}" class="p-3 border-top">
                                            <input type="url" name="video_link" id="item-video-link-input" class="form-control border-0 p-0 ignore"
                                                style="box-shadow:none;font-size:0.95rem;"
                                                value="{{ in_array($currentVideoType, ['youtube_link','vimeo_link','other_link']) ? $currentVideoLink : '' }}"
                                                placeholder="{{ __('Enter URL...') }}">
                                        </div>

                                        <div id="item-video-file-wrap" style="{{ $currentVideoType === 'file' ? '' : 'display:none;' }}" class="p-3 border-top">
                                            @if($currentVideoType === 'file' && $currentVideoFile)
                                            <div class="media-file-card mb-2" id="item-video-existing-file-card">
                                                <i class="ph-bold ph-file-video" style="font-size:28px;color:var(--bs-primary);flex-shrink:0;"></i>
                                                <div class="flex-grow-1 ms-2"><small class="text-muted">{{ __('Uploaded file') }}</small></div>
                                                <a href="{{ $currentVideoFile }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">{{ __('Preview') }}</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExistingItemVideo()">{{ __('Remove') }}</button>
                                            </div>
                                            @endif
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
                                                    <i class="ph-bold ph-file-video" style="font-size:28px;color:var(--bs-primary);flex-shrink:0;"></i>
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
                                <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="custom-or-images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="address">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- Address --}}
                        <div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab">
                            <div class="card">
                                <div class="card-body">
                                    <label class="form-label mb-3">{{ __('Map Address') }}</label>
                                    
                                    <!-- Search and Locate Bar -->
                                    <div class="d-flex gap-2 mb-3">
                                        <div class="flex-grow-1 position-relative">
                                            <i class="fas fa-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #999; z-index: 10;"></i>
                                            <input type="text" id="location-search" class="form-control ps-5" placeholder="{{ __('Select Location') }}" style="border-radius: 5px;">
                                            <div id="search-results" class="position-absolute w-100 bg-white border rounded mt-1" style="display: none; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                        </div>
                                        <button type="button" class="btn btn-primary" id="locate-me-btn" style="background: var(--bs-primary); border: none; white-space: nowrap;">
                                            <i class="fas fa-crosshairs me-2"></i>
                                            {{ __('Locate me') }}
                                        </button>
                                    </div>

                                    <div id="map"
                                        data-map-provider="{{ $mapProvider ?? 'free_api' }}"
                                        data-google-map-key="{{ $googleMapKey ?? '' }}"
                                        data-settings-lat="{{ $defaultLatitude ?? '' }}"
                                        data-settings-lng="{{ $defaultLongitude ?? '' }}"
                                        data-item-lat="{{ $item->latitude ?? '' }}"
                                        data-item-lng="{{ $item->longitude ?? '' }}"
                                        data-get-location-url="{{ url('api/get-location') }}"
                                        data-locale="{{ app()->getLocale() }}"
                                        style="height: 500px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                                    </div>

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
                                    <input type="hidden" id="latitude-input" name="latitude" value="{{ $item->latitude ?? '' }}" />
                                    <input type="hidden" id="longitude-input" name="longitude" value="{{ $item->longitude ?? '' }}" />
                                    <input type="hidden" name="country_input" id="country-input" value="{{ $item->country ?? '' }}">
                                    <input type="hidden" name="state_input" id="state-input" value="{{ $item->state ?? '' }}">
                                    <input type="hidden" name="city_input" id="city-input" value="{{ $item->city ?? '' }}">
                                    <input type="hidden" name="address" id="address-hidden" value="{{ $item->address ?? '' }}">
                                </div>
                            </div>

                            @if($item->user_id != auth()->user()->id)
                                <hr class="my-4">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="admin_edit_reason">{{ __('Reason for Admin Edit') }} <span class="text-danger">*</span></label>
                                            <textarea name="admin_edit_reason" id="admin_edit_reason" class="form-control" rows="3" required>{{ $item->admin_edit_reason }}</textarea>

                                            @error('admin_edit_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="images">{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-primary btn-next-tab" data-next-tab="seo">{{ __('Next') }}</button>
                            </div>
                        </div>

                        {{-- SEO Details --}}
                        <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
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

                            @php
                                $seoData = $seoTranslations ?? [];
                            @endphp

                            {{-- Default Language SEO Fields --}}
                            <div class="seo-language-fields" data-seo-language-id="{{ $defaultLanguage->id }}">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Title') }}</label>
                                        <input type="text" name="meta_title[{{ $defaultLanguage->id }}]" class="form-control" value="{{ $seoData[$defaultLanguage->id]['meta_title'] ?? '' }}" placeholder="{{ __('Enter meta title') }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Description') }}</label>
                                        <textarea name="meta_description[{{ $defaultLanguage->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}">{{ $seoData[$defaultLanguage->id]['meta_description'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Meta Keywords') }}</label>
                                        <textarea name="meta_keywords[{{ $defaultLanguage->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}">{{ $seoData[$defaultLanguage->id]['meta_keywords'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label>{{ __('Schema') }}</label>
                                        <textarea name="schema[{{ $defaultLanguage->id }}]" class="form-control" rows="4" placeholder='{"@@context": "https://schema.org", ...}'>{{ $seoData[$defaultLanguage->id]['schema'] ?? '' }}</textarea>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle"></i>
                                            {{ __('Schema is not auto-generated by AI. Add JSON-LD schema manually using the saved item data (image URLs, price, etc.) which are now available.') }}
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
                                                <input type="text" name="meta_title[{{ $lang->id }}]" class="form-control" value="{{ $seoData[$lang->id]['meta_title'] ?? '' }}" placeholder="{{ __('Enter meta title') }}">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Description') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_description[{{ $lang->id }}]" class="form-control" rows="3" placeholder="{{ __('Enter meta description') }}">{{ $seoData[$lang->id]['meta_description'] ?? '' }}</textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Meta Keywords') }} ({{ $lang->name }})</label>
                                                <textarea name="meta_keywords[{{ $lang->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter meta keywords') }}">{{ $seoData[$lang->id]['meta_keywords'] ?? '' }}</textarea>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label>{{ __('Schema') }} ({{ $lang->name }})</label>
                                                <textarea name="schema[{{ $lang->id }}]" class="form-control" rows="4" placeholder='{"@@context": "https://schema.org", ...}'>{{ $seoData[$lang->id]['schema'] ?? '' }}</textarea>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ __('Schema is not auto-generated by AI. Add JSON-LD schema manually using the saved item data (image URLs, price, etc.) which are now available.') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-prev-tab" data-prev-tab="address">{{ __('Previous') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Update Item') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <script>
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
                    
                    // Validate video link format on input
                    $(document).ready(function() {
                        // Validate item video link on input and blur
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
                    });
                </script>
            </div>
        </div>
        </div>
        <div class="modal fade" id="subcategory-modal" tabindex="-1" role="dialog"
            aria-labelledby="subcategory-modal-label" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="subcategory-modal-label">{{ __('Select Category or Subcategory') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="current-category mb-3">
                            <label>{{ __('Current Category:') }}</label>
                            @if ($item->category)
                                <input type="text" class="form-control" value="{{ $item->category->name }}" readonly>
                            @else
                                <input type="text" class="form-control" value="Select Category" readonly>
                            @endif
                        </div>
                        <div class="categories-list">
                            @include('items.treeview', [
                                'categories' => $categories,
                                'selected_category' => $item->category?->id ?? '',
                            ])
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" class="btn btn-primary"
                            id="save-subcategory">{{ __('Save changes') }}</button>
                    </div>
                </div>
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
        #gallery-images-preview .gallery-thumb-wrap,
        #existing-images-container .gallery-thumb-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }
        #gallery-images-preview .gallery-thumb-wrap img,
        #existing-images-container .gallery-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        #gallery-images-preview .gallery-thumb-wrap .remove-thumb,
        #existing-images-container .gallery-thumb-wrap .remove-thumb {
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
        .existing-image-item.marked-delete { opacity: 0.4; }

        /* Currency select2 full width */
        #currency + .select2-container {
            width: 100% !important;
        }
        /* Ensure phone input field displays correctly */
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
        /* Ensure inactive tabs don't take up space */
        .tab-pane:not(.show):not(.active) {
            display: none !important;
        }
        /* Ensure active tabs are visible */
        .tab-pane.active.show {
            display: block !important;
        }
        .tab-pane.fade {
            transition: opacity 0.15s linear;
        }
        .tab-pane.fade:not(.show) {
            opacity: 0;
        }
        .tab-pane.fade.show {
            opacity: 1;
        }
        /* Ensure tab content is properly contained */
        #editItemTabContent {
            min-height: 200px;
        }
        /* Ensure navigation buttons are always visible in active tabs */
        .tab-pane.active .btn-next-tab,
        .tab-pane.active .btn-prev-tab {
            display: inline-block !important;
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
        const initialHasVideo = {{ !empty($currentVideoType) ? 'true' : 'false' }};

        function setVideoType(type, label, force = false) {
            if (type === '' && initialHasVideo && !force) {
                const deleteFlag = document.getElementById('delete-item-video-flag').value;
                if (deleteFlag !== '1') {
                    Swal.fire({
                        title: "{{ __('Are you sure?') }}",
                        text: "{{ __('Selecting None will remove the existing product video.') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: "{{ __('Yes, remove it!') }}",
                        cancelButtonText: "{{ __('Cancel') }}"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('delete-item-video-flag').value = '1';
                            const card = document.getElementById('item-video-existing-file-card');
                            if (card) card.style.display = 'none';
                            setVideoType('', '{{ __('None') }}', true);
                        }
                    });
                    return;
                }
            }

            document.getElementById('video_type_hidden').value = type;
            document.getElementById('videoTypeLabel').textContent = label;
            document.getElementById('item-video-link-wrap').style.display = ['youtube_link','vimeo_link','other_link'].includes(type) ? '' : 'none';
            document.getElementById('item-video-file-wrap').style.display = type === 'file' ? '' : 'none';
            if (type !== 'file') { 
                const f = document.getElementById('item-video-file-input'); 
                if(f) f.value = ''; 
                document.getElementById('item-video-file-selected').style.display = 'none'; 
            }
            if (!['youtube_link','vimeo_link','other_link'].includes(type)) { 
                const l = document.getElementById('item-video-link-input'); 
                if(l) l.value = ''; 
            }

            if (type !== '') {
                document.getElementById('delete-item-video-flag').value = '0';
            } else {
                document.getElementById('delete-item-video-flag').value = '1';
            }
        }

        function removeExistingItemVideo() {
            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: "{{ __('The existing product video will be removed.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "{{ __('Yes, remove it!') }}",
                cancelButtonText: "{{ __('Cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-item-video-flag').value = '1';
                    const card = document.getElementById('item-video-existing-file-card');
                    if (card) card.style.display = 'none';
                    setVideoType('', '{{ __('None') }}', true);
                }
            });
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
                    $itemRegionCode = !empty($item->region_code) ? strtolower($item->region_code) : 'in';
                @endphp

                try {
                    const iti = window.intlTelInput(phoneInput, {
                        initialCountry: "{{ $itemRegionCode }}",
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
            
            // Initialize phone input when Details tab is shown
            $('#editItemTabs a[href="#listing"]').on('shown.bs.tab', function() {
                setTimeout(initPhoneInput, 300);
            });
            
            // Also initialize on page load if Details tab is already visible
            if ($('#listing').hasClass('active') || $('#listing').hasClass('show')) {
                setTimeout(initPhoneInput, 500);
            } else {
                // Initialize after a short delay to ensure library is loaded
                setTimeout(initPhoneInput, 200);
            }
            
            // Language switching for custom fields - use event delegation
            $(document).on('change', '#custom-fields-language-selector', function() {
                const selectedLangId = $(this).val();
                
                // Hide all language sections and remove required from hidden fields
                $('#custom .custom-fields-language-section').each(function() {
                    $(this).hide();
                    // Remove required attribute from all inputs in hidden sections
                    $(this).find('input[required], select[required], textarea[required]').each(function() {
                        $(this).attr('data-was-required', 'true');
                        $(this).removeAttr('required');
                    });
                });
                
                // Show selected language section and restore required attributes
                const $selectedSection = $(`#custom .custom-fields-language-section[data-language-id="${selectedLangId}"]`);
                $selectedSection.show();
                $selectedSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                    $(this).attr('required', 'required');
                });
            });
            
            // Initialize: Show default language custom fields and mark required fields
            setTimeout(function() {
                const defaultCustomLangId = $('#custom-fields-language-selector').val();
                if (defaultCustomLangId) {
                    // Hide all sections first
                    $('#custom .custom-fields-language-section').each(function() {
                        $(this).hide();
                        // Mark required fields in hidden sections
                        $(this).find('input[required], select[required], textarea[required]').each(function() {
                            $(this).attr('data-was-required', 'true');
                            $(this).removeAttr('required');
                        });
                    });
                    
                    // Show default language section and restore required
                    const $defaultSection = $(`#custom .custom-fields-language-section[data-language-id="${defaultCustomLangId}"]`);
                    $defaultSection.show();
                    // Restore required for fields that were marked
                    $defaultSection.find('input[data-was-required="true"], select[data-was-required="true"], textarea[data-was-required="true"]').each(function() {
                        $(this).attr('required', 'required');
                    });
                    // Add required for fields that should be required (newly created fields)
                    $defaultSection.find('input[data-should-be-required="true"], select[data-should-be-required="true"], textarea[data-should-be-required="true"]').each(function() {
                        $(this).attr('required', 'required');
                    });
                }
            }, 100);

            $('#category-select').on('change', function() {
                let categoryId = $(this).val();
                $.ajax({
                    url: `/get-custom-fields/${categoryId}`,
                    type: 'GET',
                    data: {
                        item_id: {{ $item->id }}
                    },
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
                            html += `<div class="col-12"><p class="text-muted">{{ __('No custom fields for this category.') }}</p></div>`;
                        } else {
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
                                     html += `<textarea name="custom_fields[${field.id}]" class="form-control custom-field-input" rows="3" ${isRequired} ${maxAttr} ${minAttr} data-parsley-trigger="input">${field.value ?? ''}</textarea>`;
                                 } else {
                                     html += `<input type="text" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired} ${maxAttr} ${minAttr} value="${field.value ?? ''}" data-parsley-trigger="input">`;
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
                                    html += `<input type="text" inputmode="numeric" pattern="[0-9]*" name="custom_fields[${field.id}]" class="form-control custom-field-input" ${isRequired} ${maxAttr} ${minAttr} ${lengthMsgAttr} value="${field.value ?? ''}" data-parsley-trigger="input">`;
                            } else if (field.type === 'fileinput') {
                                const fileUrl = Array.isArray(field.value) ? (field.value[0] || '') : (field.value || '');
                                if (fileUrl) {
                                    const ext = fileUrl.split('.').pop().split('?')[0].toLowerCase();
                                    const isImage = ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
                                    html += `<div class="mb-2 p-2 border rounded d-flex align-items-center gap-2">`;
                                    if (isImage) {
                                        html += `<img src="${fileUrl}" alt="${field.name}" width="80" height="80" style="object-fit: cover; border-radius: 4px;">`;
                                    } else {
                                        html += `<i class="fas fa-file-alt fa-2x text-secondary"></i>`;
                                    }
                                    html += `<a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> {{ __('View File') }}</a>`;
                                    html += `</div>`;
                                }
                                const fileRequired = fileUrl ? '' : isRequired;
                                    html += `<input type="file" name="custom_field_files[${field.id}]" class="form-control custom-field-input" ${fileRequired} data-parsley-trigger="change">`;
                                } else if (field.type === 'dropdown' || field.type === 'radio') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    html += `<select name="custom_fields[${field.id}]" class="form-select custom-field-input" ${isRequired} data-parsley-trigger="change">`;
                                    html += `<option value="">{{ __('Select') }}</option>`;
                                options.forEach(option => {
                                        const selected = (field.value === option) ? 'selected' : '';
                                        html += `<option value="${option}" ${selected}>${option}</option>`;
                                });
                                html += `</select>`;
                            } else if (field.type === 'checkbox') {
                                    const options = Array.isArray(field.values) ? field.values : JSON.parse(field.values ?? '[]');
                                    // Handle checkbox value - it can be string, array, or already processed
                                    let fieldValueArray = [];
                                    if (field.value) {
                                        if (Array.isArray(field.value)) {
                                            fieldValueArray = field.value;
                                        } else if (typeof field.value === 'string') {
                                            // Try to parse if it's a JSON string
                                            try {
                                                const parsed = JSON.parse(field.value);
                                                fieldValueArray = Array.isArray(parsed) ? parsed : [parsed];
                                            } catch (e) {
                                                // If not JSON, treat as comma-separated or single value
                                                fieldValueArray = field.value.includes(',') ? field.value.split(',').map(v => v.trim()) : [field.value];
                                            }
                                        } else {
                                            fieldValueArray = [field.value];
                                        }
                                    }
                                options.forEach(option => {
                                        const checked = fieldValueArray.includes(option) || fieldValueArray.includes(String(option)) ? 'checked' : '';
                                    html += `
                            <div class="form-check">
                                        <input class="form-check-input custom-field-checkbox" type="checkbox" name="custom_fields[${field.id}][]" value="${option}" ${checked} ${isRequired} data-parsley-trigger="change">
                                <label class="form-check-label">${option}</label>
                            </div>
                        `;
                                });
                            }
                                html += `</div>`;
                            });
                            
                            html += `</div></div>`;
                            
                            // Other language fields - only show translatable textbox fields
                            languages.forEach(function(lang) {
                                if (lang.id != defaultLanguageId) {
                                    html += `<div class="col-12 custom-fields-language-section" data-language-id="${lang.id}" style="display: none;">`;
                                    html += `<div class="row">`;
                                    
                                    let hasTranslatableFields = false;
                                    
                                    // Show all textbox type fields for other languages (regardless of whether they have translations)
                                    response.fields.forEach(function(field) {
                                        // Only show textbox type fields for other languages
                                        if (field.type !== 'textbox') return;
                                        
                                        hasTranslatableFields = true;
                                        // Get translated value if exists
                                        const translatedValue = (field.translated_values && field.translated_values[lang.id]) ? field.translated_values[lang.id] : '';
                                        const displayValue = Array.isArray(translatedValue) ? (translatedValue[0] || '') : translatedValue;

                                        // Use translated field name for this language if available, else default name
                                        const nameTranslation = Array.isArray(field.translations)
                                            ? field.translations.find(t => t.language_id == lang.id && t.key === 'name' && t.value)
                                            : null;
                                        const fieldLabel = nameTranslation ? nameTranslation.value : field.name;

                                        // Translation fields are NOT required - only default language fields are required
                                        html += `<div class="col-md-6 mb-3">`;
                                        html += `<label>${fieldLabel}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>`;
                                        html += `<input type="text" name="custom_field_translations[${lang.id}][${field.id}]" class="form-control custom-field-input-translation" value="${displayValue}">`;
                            html += `</div>`;
                        });

                                    if (!hasTranslatableFields) {
                                        html += `<div class="col-12"><p class="text-muted">{{ __('No translatable custom fields for this language.') }}</p></div>`;
                                    }

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
                        
                        // Update hasCustomFields status
                        updateCustomFieldsStatus();

                        // Bind Parsley explicitly to dynamic custom fields
                        $('#custom .custom-field-input, #custom .custom-field-checkbox').each(function() {
                            $(this).parsley();
                        });

                        // Init char counters for textbox custom fields
                        $('#custom .custom-field-input[data-max-length], #custom .custom-field-input[data-min-length]').trigger('input.cfcounter');

                        // Language switching for custom fields - use event delegation to ensure it works after AJAX
                        $(document).off('change', '#custom-fields-language-selector').on('change', '#custom-fields-language-selector', function() {
                            const selectedLangId = $(this).val();
                            
                            // Hide all language sections and remove required from hidden fields
                            $('#custom .custom-fields-language-section').each(function() {
                                $(this).hide();
                                // Remove required attribute from all inputs in hidden sections
                                $(this).find('input[required], select[required], textarea[required]').each(function() {
                                    $(this).attr('data-was-required', 'true');
                                    $(this).removeAttr('required');
                                });
                            });
                            
                            // Show selected language section and restore required attributes
                            const $selectedSection = $(`#custom .custom-fields-language-section[data-language-id="${selectedLangId}"]`);
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
                        
                        // Initialize: Show default language fields
                        setTimeout(function() {
                            const defaultCustomLangId = $('#custom-fields-language-selector').val();
                            if (defaultCustomLangId) {
                                $('#custom .custom-fields-language-section').hide();
                                $(`#custom .custom-fields-language-section[data-language-id="${defaultCustomLangId}"]`).show();
                            }
                        }, 100);

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
                                $('#min-salary-label').html('{{ __('Min Salary') }}');
                                $('#max-salary-label').html('{{ __('Max Salary') }}');
                                $('#min-salary-input').removeAttr('required');
                                $('#max-salary-input').removeAttr('required');
                            } else {
                                // Job category but price not optional: salary is required
                                $('#min-salary-label').html('{{ __('Min Salary') }} <span class="text-danger">*</span>');
                                $('#max-salary-label').html('{{ __('Max Salary') }} <span class="text-danger">*</span>');
                                $('#min-salary-input').attr('required', 'required');
                                $('#max-salary-input').attr('required', 'required');
                            }
                        } else {
                            // Not a job category: show price field, hide salary
                            $('#price-field').show();
                            $('#salary-fields').hide();
                            
                            if (isPriceOptional) {
                                // Price optional: remove required
                                $('#price-label').html('{{ __('Price') }}');
                                $('#price-input').removeAttr('required');
                            } else {
                                // Price not optional: make required
                                $('#price-label').html('{{ __('Price') }} <span class="text-danger">*</span>');
                                $('#price-input').attr('required', 'required');
                            }
                        }
                    }
                });
            });


            // Toggle subcategories on click
            $('.toggle-button').on('click', function() {
                $(this).siblings('.subcategories').toggle();
                $(this).toggleClass('open');
            });

            // Tab navigation with validation
            let hasCustomFields = false;

            // Update hasCustomFields when custom fields are loaded
            function updateCustomFieldsStatus() {
                hasCustomFields = $('#custom .custom-field-input').length > 0;
            }

            // Initialize hasCustomFields
            updateCustomFieldsStatus();

            // All tabs freely accessible in update form
            $('#editItemTabs a[data-bs-toggle="tab"]').css('pointer-events', 'auto').css('cursor', 'pointer');

            // Prevent tab switching if video is processing
            $(document).on('click', '#editItemTabs a[data-bs-toggle="tab"]', function(e) {
                if (window.reelTrimming || window.reelUploading) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    showErrorToast('{{ __('Please wait for the video to finish processing.') }}');
                    return false;
                }
            });

            $('#editItemTabs a[data-bs-toggle="tab"]').on('show.bs.tab', function(e) {
                if (window.reelTrimming || window.reelUploading) {
                    e.preventDefault();
                    return false;
                }
            });

            // Init map on address tab show
            $('#editItemTabs a[href="#address"]').on('shown.bs.tab', function() {
                setTimeout(() => { initMap(); }, 300);
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

            // Bind Parsley explicitly to server-rendered custom fields on page load
            $('#custom .custom-field-input, #custom .custom-field-checkbox').each(function() {
                $(this).parsley();
            });

            // Init char counters for server-rendered textbox custom fields on page load
            $('#custom .custom-field-input[data-max-length], #custom .custom-field-input[data-min-length]').trigger('input.cfcounter');

            // Clear invalid highlight once user edits the admin edit reason
            $(document).on('input', '#admin_edit_reason', function() {
                $(this).removeClass('is-invalid');
            });

            // Next button handler
            $(document).on('click', '.btn-next-tab', function(e) {
                if (window.reelTrimming || window.reelUploading) {
                    e.preventDefault();
                    e.stopPropagation();
                    showErrorToast('{{ __('Please wait for the video to finish processing.') }}');
                    return false;
                }
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

                setTimeout(() => {
                    const activeTab = $('.tab-pane.active');
                    if (activeTab.length) {
                        activeTab.addClass('show').css('display', 'block');
                    }
                }, 100);
            });

            // Previous button handler
            $(document).on('click', '.btn-prev-tab', function(e) {
                if (window.reelTrimming || window.reelUploading) {
                    e.preventDefault();
                    e.stopPropagation();
                    showErrorToast('{{ __('Please wait for the video to finish processing.') }}');
                    return false;
                }
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

                setTimeout(() => {
                    const activeTab = $('.tab-pane.active');
                    if (activeTab.length) {
                        activeTab.addClass('show').css('display', 'block');
                    }
                }, 100);
            });

            // Validation function for current tab
            function validateCurrentTab(tabId) {
                let isValid = true;
                let firstInvalidField = null;

                if (tabId === 'listing') {
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
                    // Validate required custom fields (only default language fields)

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
                    // For reel items, a reel video is required (unless existing one kept)
                    if ($('#item_type_input').val() === 'reel') {
                        const hasExistingReel = $('#reel-existing-wrap').length > 0 && $('#reel-delete-flag').val() !== '1';
                        const hasNewVideo = document.getElementById('reel-video-input') && document.getElementById('reel-video-input').files.length > 0;
                        if (!hasExistingReel && !hasNewVideo) {
                            showErrorToast(window.trans('Please upload a Video Ad video.'));
                            isValid = false;
                        }
                        if (window.reelTrimming) {
                            showErrorToast(window.trans('Please wait for video processing to complete.'));
                            isValid = false;
                        }
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
                        const hasExistingVideo = $('#item-video-existing-file-card').length > 0 && $('#delete-item-video-flag').val() !== '1';
                        const videoFileInput = document.getElementById('item-video-file-input');
                        const hasNewVideo = videoFileInput && videoFileInput.files && videoFileInput.files.length > 0;
                        if (!hasExistingVideo && !hasNewVideo) {
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

                    // Required: reason for admin edit
                    const $reason = $('#admin_edit_reason');
                    $reason.removeClass('is-invalid');
                    if ($reason.length && !$reason.val().trim()) {
                        $reason.addClass('is-invalid');
                        if (isValid) {
                            showErrorToast(window.trans('Reason for Admin Edit') + ' ' + window.trans('is required.'));
                            $reason.focus();
                        }
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

            // Pre-submit validation function (called by global form handler)
            window.validateAdvertisementUpdateForm = function() {
                const tabs = ['listing', 'images', 'address'];
                if (hasCustomFields) {
                    tabs.splice(1, 0, 'custom');
                }

                for (let i = 0; i < tabs.length; i++) {
                    if (!validateCurrentTab(tabs[i])) {
                        $('[href="#' + tabs[i] + '"]').tab('show');
                        if (tabs[i] === 'address') {
                            setTimeout(() => { initMap(); }, 300);
                        }
                        return false;
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

                return true; // Allow form submission
            };
        });
    </script>
    <script>
        document.querySelectorAll('input[name="selected_category"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedName = this.closest('label').innerText.trim();
                document.querySelector('.current-category input').value = selectedName;
            });
        });
    </script>

    <script>
        document.getElementById('save-subcategory').addEventListener('click', function() {
            const selectedRadio = document.querySelector('input[name="selected_category"]:checked');
            if (selectedRadio) {
                const selectedId = selectedRadio.value;
                const selectedName = selectedRadio.closest('label').innerText.trim();

                const categorySelect = document.getElementById('category-select');

                // Clear current options
                categorySelect.innerHTML = '';

                // Add the newly selected category
                const option = document.createElement('option');
                option.value = selectedId;
                option.text = selectedName;
                option.selected = true;

                categorySelect.appendChild(option);

                $('#category-select').trigger('change');

                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('subcategory-modal'));
                modal.hide();
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            // Initialize address display if address already exists
            @if ($item->address)
                const existingAddress = '{{ $item->address }}';
                if (existingAddress) {
                    $('#selected-address-display').show();
                    $('#selected-address-text').text(existingAddress);
                    $('#location-search').val(existingAddress);
                }
            @endif

            // Image upload handlers — drop zone click handled via onclick in HTML

            // Store gallery files for removal and appending
            let galleryFiles = [];
            const maxGalleryImages = parseInt("{{ \App\Services\CachingService::getSystemSettings('max_gallery_images') ?: 5 }}");
            const existingImages = @json($item->gallery_images ?? []);

            function remainingGallerySlots() {
                const existingCount = existingImages.filter(img => {
                    const cb = document.getElementById('delete-img-' + img.id);
                    return cb ? !cb.checked : true;
                }).length;
                return maxGalleryImages - existingCount - galleryFiles.length;
            }

            function appendGalleryFiles(newFiles) {
                const remaining = remainingGallerySlots();
                if (newFiles.length > remaining) {
                    showErrorToast(window.trans('You can upload a maximum of') + ' ' + maxGalleryImages + ' ' + window.trans('images'));
                    newFiles = newFiles.slice(0, Math.max(0, remaining));
                }
                if (newFiles.length > 0) { galleryFiles = galleryFiles.concat(newFiles); }
            }

            // Helper to get active cover image info
            function getCoverInfo() {
                // Find the first existing image that has is_default = 1 AND is not deleted
                let defaultImg = existingImages.find(img => img.is_default && !document.getElementById('delete-img-' + img.id)?.checked);
                if (defaultImg) {
                    return { type: 'existing', id: defaultImg.id };
                }
                // If not found (either because default is deleted or there is no default), find the first non-deleted existing image
                let firstActiveExisting = existingImages.find(img => !document.getElementById('delete-img-' + img.id)?.checked);
                if (firstActiveExisting) {
                    return { type: 'existing', id: firstActiveExisting.id };
                }
                // If no active existing images, the first new image (index 0) is the cover
                if (galleryFiles.length > 0) {
                    return { type: 'new', index: 0 };
                }
                return null;
            }

            window.toggleExistingImage = function(id, event) {
                if (event) {
                    event.stopPropagation();
                    event.preventDefault();
                }
                const cb = document.getElementById('delete-img-' + id);
                if (!cb) return;
                cb.checked = !cb.checked;
                renderGalleryPreview();
                if ($('#galleryAllImagesModal').is(':visible')) {
                    renderModalImages();
                }
            };

            function renderGalleryPreview() {
                const preview = document.getElementById('gallery-images-preview');
                if (!preview) return;
                preview.innerHTML = '';

                // Calculate available width
                preview.style.display = 'flex';
                preview.style.flexWrap = 'nowrap';
                preview.style.overflow = 'hidden';
                
                const W = preview.clientWidth || preview.parentElement.clientWidth;
                
                const totalItems = existingImages.length + galleryFiles.length;
                let visibleCount = totalItems;
                let extraCount = 0;

                // Dynamic width calculation
                if (totalItems * 88 - 8 > W && W > 0) {
                    visibleCount = Math.floor((W - 80) / 88);
                    if (visibleCount < 1) visibleCount = 1;
                    extraCount = totalItems - visibleCount;
                }

                let renderedCount = 0;
                const coverInfo = getCoverInfo();

                // 1. Render existing images
                existingImages.forEach((img) => {
                    if (renderedCount >= visibleCount) return;

                    const isDeleted = document.getElementById('delete-img-' + img.id)?.checked || false;
                    const isCover = coverInfo && coverInfo.type === 'existing' && coverInfo.id === img.id;

                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap existing-image-item' + (isCover ? ' cover-thumb' : '') + (isDeleted ? ' marked-delete' : '');
                    wrap.dataset.id = img.id;
                    wrap.dataset.isDefault = img.is_default;
                    wrap.style.cursor = 'pointer';

                    wrap.innerHTML = `
                        <img src="${img.image}" alt="">
                        ${isCover ? '<span class="cover-badge">Cover</span>' : ''}
                        <label class="remove-thumb" title="{{ __('Remove') }}" onclick="toggleExistingImage(${img.id}, event)">
                            ✕
                        </label>
                    `;
                    wrap.addEventListener('click', function(e) {
                        if (e.target.classList.contains('remove-thumb') || e.target.closest('.remove-thumb')) {
                            return;
                        }
                        openAllImagesModal();
                    });
                    preview.appendChild(wrap);
                    renderedCount++;
                });

                // 2. Render new images
                galleryFiles.forEach((file, i) => {
                    if (renderedCount >= visibleCount) return;

                    const imgSrc = URL.createObjectURL(file);
                    const isCover = coverInfo && coverInfo.type === 'new' && coverInfo.index === i;

                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap' + (isCover ? ' cover-thumb' : '');
                    wrap.dataset.fileIndex = i;
                    wrap.style.cursor = 'pointer';

                    wrap.innerHTML = `
                        <img src="${imgSrc}" alt="">
                        ${isCover ? '<span class="cover-badge">Cover</span>' : ''}
                        <span class="remove-thumb remove-gallery-image" data-index="${i}">✕</span>
                    `;
                    wrap.addEventListener('click', function(e) {
                        if (e.target.classList.contains('remove-thumb') || e.target.closest('.remove-thumb') || e.target.classList.contains('remove-gallery-image')) {
                            return;
                        }
                        openAllImagesModal();
                    });
                    preview.appendChild(wrap);
                    renderedCount++;
                });

                // 3. Render +N badge if extraCount > 0
                if (extraCount > 0) {
                    const more = document.createElement('div');
                    more.className = 'gallery-more-badge';
                    more.style.cursor = 'pointer';
                    more.innerHTML = `<span>+${extraCount}</span><small style="font-size:10px;font-weight:400;">More</small>`;
                    more.addEventListener('click', openAllImagesModal);
                    preview.appendChild(more);
                }

                // Sync new files to input
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

                const coverInfo = getCoverInfo();

                // Existing images inside modal
                existingImages.forEach((img) => {
                    const isDeleted = document.getElementById('delete-img-' + img.id)?.checked || false;
                    const isCover = coverInfo && coverInfo.type === 'existing' && coverInfo.id === img.id;

                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap existing-image-item' + (isCover ? ' cover-thumb' : '') + (isDeleted ? ' marked-delete' : '');
                    wrap.style.position = 'relative';
                    wrap.style.width = '100px';
                    wrap.style.height = '100px';

                    wrap.innerHTML = `
                        <img src="${img.image}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" alt="">
                        ${isCover ? '<span class="cover-badge" style="position: absolute; bottom: 0; left: 0; right: 0; background: var(--bs-primary); color: #fff; text-align: center; font-size: 11px;">Cover</span>' : ''}
                        <label class="remove-thumb" title="{{ __('Remove') }}" onclick="toggleExistingImage(${img.id}, event)" style="position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; background: #fff; border: 1px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px; line-height: 1;">
                            ✕
                        </label>
                    `;
                    grid.appendChild(wrap);
                });

                // New images inside modal
                galleryFiles.forEach((file, i) => {
                    const imgSrc = URL.createObjectURL(file);
                    const isCover = coverInfo && coverInfo.type === 'new' && coverInfo.index === i;

                    const wrap = document.createElement('div');
                    wrap.className = 'gallery-thumb-wrap' + (isCover ? ' cover-thumb' : '');
                    wrap.style.position = 'relative';
                    wrap.style.width = '100px';
                    wrap.style.height = '100px';

                    wrap.innerHTML = `
                        <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" alt="">
                        ${isCover ? '<span class="cover-badge" style="position: absolute; bottom: 0; left: 0; right: 0; background: var(--bs-primary); color: #fff; text-align: center; font-size: 11px;">Cover</span>' : ''}
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
                
                const totalItems = existingImages.length + galleryFiles.length;
                if (totalItems === 0) {
                    const modalEl = document.getElementById('galleryAllImagesModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if (modalInst) modalInst.hide();
                }
            });

            $('#gallery-images-input').on('change', function(e) {
                const newFiles = Array.from(e.target.files);
                if (newFiles.length > 0) { appendGalleryFiles(newFiles); }
                renderGalleryPreview();
            });

            // Handle new gallery image removal
            $(document).on('click', '.remove-gallery-image', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const index = parseInt($(this).data('index'));
                galleryFiles.splice(index, 1);
                renderGalleryPreview();
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

            // Render gallery preview on tab activation & screen resize
            $('#editItemTabs a[href="#images"]').on('shown.bs.tab', function() {
                renderGalleryPreview();
            });

            $(window).on('resize', function() {
                if ($('#images').hasClass('active') || $('#images').hasClass('show')) {
                    renderGalleryPreview();
                }
            });

            renderGalleryPreview();
        });
        
        // Success callback function for advertisement form
        window.handleAdvertisementSuccess = function(response) {
            // Redirect to advertisement index page
            window.location.href = '{{ route('advertisement.index') }}';
        };

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

            let prefilled = false;
            $modal.on('shown.bs.modal', function() {
                plainSelect2($country, '{{ __('Country') }}');
                ajaxSelect2($state, '{{ url('api/states') }}', '{{ __('State') }}', function() {
                    return { country_id: $country.val() || '' };
                });
                ajaxSelect2($city, '{{ url('api/cities') }}', '{{ __('City') }}', function() {
                    return { state_id: $state.val() || '' };
                });
                if (!prefilled) {
                    prefilled = true;
                    prefillFromItem();
                }
            });

            // Append a pre-selected option carrying lat/lng so dependent loads use the numeric id.
            function setSelected($el, item) {
                const opt = new Option(item.name, item.id, true, true);
                $el.append(opt).data('selLat', item.latitude || '').data('selLng', item.longitude || '');
                $el.val(String(item.id)).trigger('change');
            }

            function prefillFromItem() {
                const itemCountry = @json($item->country ?? '');
                const itemState = @json($item->state ?? '');
                const itemCity = @json($item->city ?? '');
                const itemAddress = @json($item->address ?? '');

                if (itemAddress) {
                    let raw = itemAddress;
                    [itemCity, itemState, itemCountry].forEach(function(part) {
                        if (part) {
                            const suffix = ', ' + part;
                            if (raw.endsWith(suffix)) raw = raw.slice(0, -suffix.length);
                            else if (raw.endsWith(part)) raw = raw.slice(0, -part.length).replace(/,\s*$/, '');
                        }
                    });
                    $address.val(raw.trim());
                }

                if (!itemCountry) return;
                const $countryOpt = $country.find('option').filter(function() {
                    return ($(this).data('name') || '').toString().toLowerCase() === itemCountry.toLowerCase();
                }).first();
                if (!$countryOpt.length) return;
                $country.val($countryOpt.val()).trigger('change');
                $state.prop('disabled', false);

                if (!itemState) return;
                $.ajax({
                    url: '{{ url('api/states') }}',
                    data: { country_id: $countryOpt.val(), search: itemState, per_page: PER_PAGE },
                    success: function(res) {
                        const list = (res && res.data && res.data.data) ? res.data.data : [];
                        const s = list.find(function(x) { return (x.name || '').toLowerCase() === itemState.toLowerCase(); }) || list[0];
                        if (!s) return;
                        setSelected($state, s);
                        $city.prop('disabled', false);

                        if (!itemCity) return;
                        $.ajax({
                            url: '{{ url('api/cities') }}',
                            data: { state_id: s.id, search: itemCity, per_page: PER_PAGE },
                            success: function(res2) {
                                const list2 = (res2 && res2.data && res2.data.data) ? res2.data.data : [];
                                const c = list2.find(function(x) { return (x.name || '').toLowerCase() === itemCity.toLowerCase(); }) || list2[0];
                                if (c) setSelected($city, c);
                            }
                        });
                    }
                });
            }

            function clearSelect($el) {
                $el.val(null).trigger('change.select2');
                $el.removeData('selLat').removeData('selLng');
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

                if (!countryName) { showErrorToast('{{ __('Please select a country') }}'); return; }
                if (!stateName) { showErrorToast('{{ __('Please select a state') }}'); return; }
                if (!cityName) { showErrorToast('{{ __('Please select a city') }}'); return; }

                const lat = cityData.lat || $city.data('selLat') || stateData.lat || $state.data('selLat') || countrySel.data('lat') || '';
                const lng = cityData.lng || $city.data('selLng') || stateData.lng || $state.data('selLng') || countrySel.data('lng') || '';

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
        });
        @endif

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
                    category_name: '{{ $item->category->name ?? '' }}',
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

            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('name', $('#name-input').val().trim());
            formData.append('description', $('#description-input').val().trim());
            formData.append('source_language_id', {{ $defaultLanguage->id }});
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
                const submitBtn = $('form.edit-form [type=submit]');
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
                    
                    $('#reel-video-duration').text(fmtDur(window.reelActualDuration));
                    $('#reel-trim-badge').text(fmtDur(window.reelTrimDuration) + ' {{ __('Trimming…') }}').removeClass('text-success').addClass('text-warning').show();
                } else {
                    loadingState.hide();
                    instructions.show();
                    uploadArea.hide();
                    
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

            // Override formAjaxRequest to inject trimmed blob at submit time and show upload progress
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
                    $('#reel-existing-card').css({
                        'opacity': '0.65',
                        'border': '1px dashed #ff9800',
                        'background-color': '#fffaf0'
                    });
                    $('#reel-existing-title').html('{{ __("Current Video Ad") }} <span class="badge bg-warning text-dark ms-2">{{ __("To be replaced") }}</span>');
                    $('#reel-existing-subtitle').text('{{ __("This video ad will be replaced by the new video below on save.") }}');
                    $('#reel-video-filename').text(file.name);
                    const clipDuration = Math.min(MAX_DURATION, video.duration);
                    window.reelActualDuration = video.duration;
                    $('#reel-video-duration').text(fmtDur(video.duration));
                    $('#reel-trim-badge').hide();
                    $('#reel-video-selected').show();

                    // When a new video is selected for replace, clear the delete flag
                    $('#reel-delete-flag').val('0');
                    // item_type should be reel since section is showing
                    $('#item_type_input').val('reel');

                    startReelTrim(blobUrl, 0, clipDuration);
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
                    _clipRenderFn(_savedClipStartTime, _savedClipDuration || MAX_DURATION);
                }
            });

            $('#reel-goto-thumb-btn').on('click', function () {
                buildThumbStrip();
                showStep(2);
            });

            $('#reel-back-to-step1-btn').on('click', function () { showStep(1); });

            $('#reel-edit-video-btn').on('click', function () {
                if (!editModal) editModal = new bootstrap.Modal(reelModalEl);
                editModal.show();
            });

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

            $('#reel-video-change-btn').on('click', function () {
                $('#reel-video-selected').hide();
                const uploadArea = $('#reel-video-upload-area');
                uploadArea.find('.upload-instructions').show();
                uploadArea.find('.upload-loading-state').hide();
                uploadArea.show();
                
                $('#reel-existing-card').css({
                    'opacity': '1',
                    'border': '1px solid #dee2e6',
                    'background-color': '#f9f9f9'
                });
                $('#reel-existing-title').text('{{ __("Current Video Ad") }}');
                $('#reel-existing-subtitle').text('{{ __("Upload a new video below to replace it.") }}');
                
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

            // ── Thumbnail scrubber ─────────────────────────────────
            let thumbScrubVideo = null;

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

            let _clipRenderFn = null;
            let _savedClipDuration = null;

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

                video.ontimeupdate = function() {
                    const clipDurSec = (clipPx / tlW) * duration;
                    const elapsed    = video.currentTime - clipStartTime;
                    playhead.style.left = (Math.min(elapsed / clipDurSec, 1) * (clipPx - 3)) + 'px';
                    if (video.currentTime >= clipStartTime + clipDurSec) {
                        video.pause();
                        video.currentTime = clipStartTime;
                    }
                };

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
