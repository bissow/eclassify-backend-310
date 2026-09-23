{{-- SEO Fields Component - Include inside each language tab --}}
{{-- Required variables: $lang (language object), $seoTranslations (array keyed by language_id, optional) --}}

@php
    $seoData = $seoTranslations[$lang->id] ?? [];
@endphp

<div class="seo-data-container">
    <div class="card mt-3">
        <div class="card-header p-2">
            <h6 class="mb-0">{{ __('SEO Details') }} ({{ $lang->name }})</h6>
        </div>
        <div class="card-body p-2">
            <div class="form-group">
                <label>{{ __('Meta Title') }} ({{ $lang->name }})</label>
                <input type="text" name="meta_title[{{ $lang->id }}]" class="form-control" value="{{ $seoData['meta_title'] ?? '' }}">
            </div>

            <div class="form-group">
                <label>{{ __('Meta Description') }} ({{ $lang->name }})</label>
                <textarea name="meta_description[{{ $lang->id }}]" class="form-control" rows="3">{{ $seoData['meta_description'] ?? '' }}</textarea>
            </div>

            <div class="form-group">
                <label>{{ __('Meta Keywords') }} ({{ $lang->name }})</label>
                <input type="text" name="meta_keywords[{{ $lang->id }}]" class="tagify-input form-control" value="{{ $seoData['meta_keywords'] ?? '' }}" placeholder="{{ __('Enter keywords...') }}">
            </div>

            <div class="form-group">
                <label>{{ __('Schema') }} ({{ $lang->name }})</label>
                <textarea name="schema[{{ $lang->id }}]" class="form-control" rows="4" placeholder='{"@@context": "https://schema.org", ...}'>{{ $seoData['schema'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

</div>
