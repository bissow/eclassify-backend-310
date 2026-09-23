@extends('layouts.main')

@section('title')
    {{ __('Create Banner Ads') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 order-md-1 order-last">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>{{ __('Create Banner Ads') }}</h4>
                    <a href="{{ route('banner-ad.index') }}" class="btn btn-primary">{{ __('Back') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section banner-wizard">
        <div class="card">
            <div class="card-body">

                {{-- Stepper --}}
                <div class="stepper mb-4">
                    <div class="step active" data-step="1">
                        <span class="step-num"><span class="step-num-text">1</span><i class="ph ph-check step-check"></i></span>
                        <span class="step-label">{{ __('Platform and Select Page') }}</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" data-step="2">
                        <span class="step-num"><span class="step-num-text">2</span><i class="ph ph-check step-check"></i></span>
                        <span class="step-label">{{ __('Banner Layout') }}</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" data-step="3">
                        <span class="step-num"><span class="step-num-text">3</span><i class="ph ph-check step-check"></i></span>
                        <span class="step-label">{{ __('Upload Banner') }}</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" data-step="4">
                        <span class="step-num"><span class="step-num-text">4</span><i class="ph ph-check step-check"></i></span>
                        <span class="step-label">{{ __('Banner Placement') }}</span>
                    </div>
                </div>

                {{-- Selection recap — shows choices from previous + current step --}}
                <div class="wizard-recap d-none mb-4" id="wizard-recap"></div>

                <form id="create-form" method="POST" action="{{ route('banner-ad.store') }}" enctype="multipart/form-data" data-success-function="successFunction">
                    @csrf
                    <input type="hidden" name="platform" id="input-platform">
                    <input type="hidden" name="page" id="input-page">
                    <input type="hidden" name="layout" id="input-layout">
                    <input type="hidden" name="home_screen_section_id" id="input-hs-id">
                    <input type="hidden" name="feature_section_id" id="input-feature-id">
                    <input type="hidden" name="detail_page_section" id="input-detail-section">
                    <input type="hidden" name="listing_page_section" id="input-listing-section">
                    <input type="hidden" name="placement" id="input-placement">

                    {{-- Step 1 --}}
                    <div class="wizard-step" data-step-content="1">
                        <div class="mb-4">
                            <div class="section-title">
                                {{ __('Select Platform') }}<span class="required">*</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="option-card" data-group="platform" data-value="web">
                                        <div class="option-left">
                                            <span class="option-icon">
                                                <i class="ph-bold ph-monitor"></i>
                                            </span>
                                            <span class="option-label">{{ __('Website') }}</span>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="option-card" data-group="platform" data-value="app">
                                        <div class="option-left">
                                            <span class="option-icon">
                                                <i class="ph-bold ph-device-mobile-camera"></i>
                                            </span>
                                            <span class="option-label">{{ __('App') }}</span>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 d-none" id="page-select-section">
                            <div class="section-title">
                                {{ __('Select Page') }}<span class="required">*</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4" data-page-col="home">
                                    <div class="option-card" data-group="page" data-value="home">
                                        <div class="option-left">
                                            <span class="option-icon">
                                                <i class="ph-bold ph-house"></i>
                                            </span>
                                            <span class="option-label">{{ __('Home Page') }}</span>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                                <div class="col-md-4" data-page-col="detail">
                                    <div class="option-card" data-group="page" data-value="detail">
                                        <div class="option-left">
                                            <span class="option-icon">
                                                <i class="ph-bold ph-file-text"></i>
                                            </span>
                                            <span class="option-label">{{ __('Details Page') }}</span>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                                <div class="col-md-4" data-page-option="listing">
                                    <div class="option-card" data-group="page" data-value="listing">
                                        <div class="option-left">
                                            <span class="option-icon">
                                                <i class="ph-bold ph-list"></i>
                                            </span>
                                            <span class="option-label">{{ __('Listing Page') }}</span>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn-continue" id="btn-step-1-continue" disabled>
                                {{ __('Continue') }}
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Banner Layout --}}
                    <div class="wizard-step d-none" data-step-content="2">
                        <div class="section-title">
                            {{ __('Select Layout') }}<span class="required">*</span>
                        </div>
                        <div class="row g-3 layout-grid">
                            <div class="col-md-6" data-layout-group="primary">
                                <div class="layout-card" data-group="layout" data-value="single">
                                    <div class="layout-preview">
                                        <div class="lp-frame">
                                            <div class="lp-bar lp-bar-full"></div>
                                        </div>
                                    </div>
                                    <div class="layout-meta">
                                        <div>
                                            <div class="layout-title">{{ __('Single Banner Layout') }}</div>
                                            <div class="layout-sub">{{ __('Only One Banner Is Display in screen') }}</div>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" data-layout-group="primary">
                                <div class="layout-card" data-group="layout" data-value="dual">
                                    <div class="layout-preview">
                                        <div class="lp-frame">
                                            <div class="lp-bar lp-bar-half"></div>
                                            <div class="lp-bar lp-bar-half"></div>
                                        </div>
                                    </div>
                                    <div class="layout-meta">
                                        <div>
                                            <div class="layout-title">{{ __('Dual Banner Layout') }}</div>
                                            <div class="layout-sub">{{ __('Two Banner Is Display in screen') }}</div>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-none" data-layout-group="large">
                                <div class="layout-card" data-group="layout" data-value="large">
                                    <div class="layout-preview">
                                        <div class="lp-frame">
                                            <div class="lp-bar lp-bar-large"></div>
                                        </div>
                                    </div>
                                    <div class="layout-meta">
                                        <div>
                                            <div class="layout-title">{{ __('Large Banner Layout') }}</div>
                                            <div class="layout-sub">{{ __('One Large Banner Is Display In a Screen') }}</div>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-none" data-layout-group="side">
                                <div class="layout-card" data-group="layout" data-value="single_side">
                                    <div class="layout-preview">
                                        <div class="lp-frame lp-frame-side">
                                            <div class="lp-side-left">
                                                <div class="lp-line"></div>
                                                <div class="lp-line lp-line-long"></div>
                                                <div class="lp-line lp-line-short"></div>
                                            </div>
                                            <div class="lp-side-right">
                                                <div class="lp-side-box"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layout-meta">
                                        <div>
                                            <div class="layout-title">{{ __('Side Single Banner Layout') }}</div>
                                            <div class="layout-sub">{{ __('Banner Is Display in the Side of screen') }}</div>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-none" data-layout-group="side">
                                <div class="layout-card" data-group="layout" data-value="dual_side">
                                    <div class="layout-preview">
                                        <div class="lp-frame lp-frame-side">
                                            <div class="lp-side-left">
                                                <div class="lp-line"></div>
                                                <div class="lp-line lp-line-long"></div>
                                                <div class="lp-line lp-line-short"></div>
                                            </div>
                                            <div class="lp-side-right lp-side-right-split">
                                                <div class="lp-side-box"></div>
                                                <div class="lp-side-box"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layout-meta">
                                        <div>
                                            <div class="layout-title">{{ __('Side Dual Banner Layout') }}</div>
                                            <div class="layout-sub">{{ __('Banner Is Display in the Side of screen') }}</div>
                                        </div>
                                        <span class="radio-dot"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn-previous" data-prev-to="1">
                                {{ __('Previous') }}
                            </button>
                            <button type="button" class="btn-continue" id="btn-step-2-continue" disabled>
                                {{ __('Continue') }}
                            </button>
                        </div>
                    </div>
                    {{-- Step 3: Upload Banner --}}
                    <div class="wizard-step d-none" data-step-content="3">
                        <div id="banner-accordions"></div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn-previous" data-prev-to="2">
                                {{ __('Previous') }}
                            </button>
                            <button type="button" class="btn-continue" id="btn-step-3-continue" disabled>
                                {{ __('Continue') }}
                            </button>
                        </div>
                    </div>

                    {{-- Banner accordion template --}}
                    <template id="banner-accordion-template">
                        <div class="banner-accordion" data-banner-index="">
                            <div class="banner-accordion-header">
                                <div class="banner-accordion-title">
                                    <span class="banner-title-text">{{ __('Banner') }} <span class="banner-num"></span></span>
                                    <span class="banner-file-name d-none"></span>
                                </div>
                                <div class="banner-accordion-head-right">
                                    <span class="banner-accordion-error d-none">
                                        <i class="ph-fill ph-warning-circle"></i>
                                        <span class="banner-accordion-error-text"></span>
                                    </span>
                                    <button type="button" class="banner-accordion-toggle">
                                        <i class="ph ph-caret-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="banner-accordion-body">
                                <hr class="m-0">
                                <div class="p-3">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Banner Image') }}<span class="required">*</span></label>
                                        <div class="banner-dropzone" tabindex="0">
                                            <input type="file" class="banner-file-input" accept=".jpg,.jpeg,.png" hidden>
                                            <div class="dz-preview-wrap d-none">
                                                <img class="dz-preview-img" alt="">
                                                <button type="button" class="dz-remove" aria-label="Remove"><i class="ph ph-x"></i></button>
                                            </div>
                                            <div class="dz-placeholder">
                                                <div class="dz-image-icon">
                                                    <i class="ph ph-image"></i>
                                                </div>
                                                <div class="dz-upload-circle">
                                                    <i class="ph ph-upload-simple"></i>
                                                </div>
                                                <div class="dz-primary-text">{{ __('Select File to Drop') }}</div>
                                                <div class="dz-secondary-text">{{ __('Or Drag and Drop Here') }}</div>
                                            </div>
                                        </div>
                                        <div class="dz-meta">
                                            <div>
                                                <span>{{ __('Supported Format .JPG , .PNG') }}</span>
                                            </div>
                                            <div>
                                                <span>{{ __('Maximum Size : 8 MB') }}</span>
                                                <span> | </span>
                                                <span class="banner-recommended-size"></span>
                                            </div>
                                        </div>
                                        <div class="field-error banner-err-image d-none"></div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-lg-6">
                                            <label class="form-label">{{ __('Banner Ad Type') }}<span class="required">*</span></label>
                                            <select class="form-select banner-ad-type">
                                                <option value="only_banner">{{ __('Only Banner') }}</option>
                                                <option value="category">{{ __('Category') }}</option>
                                                <option value="advertisement">{{ __('Advertisement') }}</option>
                                                <option value="external_link">{{ __('External Link') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-6 d-none banner-field-category">
                                            <label class="form-label">{{ __('Select Category') }}<span class="required">*</span></label>
                                            <select class="form-select banner-category">
                                                <option value="">{{ __('Select Category') }}</option>
                                                @include('category.dropdowntree', ['categories' => clone $categories])
                                            </select>
                                        </div>
                                        <div class="col-lg-6 d-none banner-field-advertisement">
                                            <label class="form-label">{{ __('Select Advertisement') }}<span class="required">*</span></label>
                                            <select class="form-select banner-advertisement" disabled>
                                                <option value="">{{ __('Select Category First') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-6 d-none banner-field-external">
                                            <label class="form-label">{{ __('External Link') }}<span class="required">*</span></label>
                                            <input type="url" class="form-control banner-external-link" placeholder="https://example.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    {{-- Step 4: Banner Placement --}}
                    <div class="wizard-step d-none" data-step-content="4">
                        <div class="row g-3 placement-wrap">
                            <div class="col-lg-6" id="placement-col-drag">
                                <div class="placement-panel">
                                    <div class="placement-panel-title">{{ __('Drag Section') }}</div>
                                    <div class="placement-panel-sub">{{ __('Drag sections to reorder the homepage layout.') }}</div>
                                    <ul class="section-list" id="section-list"></ul>
                                </div>
                            </div>
                            <div class="col-lg-6" id="placement-col-preview">
                                <div class="preview-panel">
                                    <div class="preview-scroll" id="preview-scroll"></div>
                                </div>
                            </div>
                        </div>

    
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn-previous" data-prev-to="3">
                                {{ __('Previous') }}
                            </button>
                            <button type="submit" class="btn-continue" id="btn-step-4-save">
                                {{ __('Save Banner') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- Stacked banners modal — lists every banner sitting at one placement spot --}}
    <div class="stack-modal-overlay" id="stack-banner-modal" aria-hidden="true">
        <div class="stack-modal" role="dialog" aria-modal="true">
            <div class="stack-modal-head">
                <span class="stack-modal-title">{{ __('Banners at this Position') }}</span>
                <button type="button" class="stack-modal-close" aria-label="{{ __('Close') }}">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <div class="stack-modal-body"></div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            const selections = { platform: null, page: null, layout: null };

            // Human labels for the recap bar
            const RECAP_PLATFORM = { web: '{{ __('Website') }}', app: '{{ __('App') }}' };
            const RECAP_PAGE = {
                home: '{{ __('Home Page') }}',
                detail: '{{ __('Details Page') }}',
                listing: '{{ __('Listing Page') }}',
            };
            const RECAP_LAYOUT = {
                single: '{{ __('Single Banner') }}',
                dual: '{{ __('Dual Banner') }}',
                large: '{{ __('Large Banner') }}',
                single_side: '{{ __('Side Single Banner') }}',
                dual_side: '{{ __('Side Dual Banner') }}',
            };
            const RECAP_ADTYPE = {
                only_banner: '{{ __('Only Banner') }}',
                category: '{{ __('Category') }}',
                advertisement: '{{ __('Advertisement') }}',
                external_link: '{{ __('External Link') }}',
            };

            function renderRecap() {
                const recap = document.getElementById('wizard-recap');
                const items = [];
                if (selections.platform) items.push(['{{ __('Platform') }}', RECAP_PLATFORM[selections.platform]]);
                if (selections.page) items.push(['{{ __('Page') }}', RECAP_PAGE[selections.page]]);
                if (selections.layout) items.push(['{{ __('Banner Layout') }}', RECAP_LAYOUT[selections.layout]]);
                if (bannerState.length) {
                    const types = [...new Set(bannerState.map(b => b.type))];
                    const label = types.length === 1 ? RECAP_ADTYPE[types[0]] : '{{ __('Mixed') }}';
                    if (label) items.push(['{{ __('Banner Type') }}', label]);
                }
                recap.innerHTML = items.map(([l, v]) =>
                    `<div class="recap-item"><div class="recap-label">${l}</div><div class="recap-value">${v}</div></div>`
                ).join('');
            }

            document.querySelectorAll('.banner-wizard .option-card').forEach(card => {
                card.addEventListener('click', function () {
                    const group = this.dataset.group;
                    const value = this.dataset.value;
                    document.querySelectorAll(`.option-card[data-group="${group}"]`).forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selections[group] = value;
                    document.getElementById('input-' + group).value = value;
                    if (group === 'platform') {
                        applyPagesVisibility();
                        document.getElementById('page-select-section').classList.remove('d-none');
                        document.querySelector('.banner-wizard').dataset.platform = value;
                    }
                    document.getElementById('btn-step-1-continue').disabled = !(selections.platform && selections.page);
                });
            });

            function applyPagesVisibility() {
                // App platform: only Home + Detail pages, expand cols to fill row
                const isApp = selections.platform === 'app';
                const listingCol = document.querySelector('[data-page-option="listing"]');
                if (listingCol) listingCol.classList.toggle('d-none', isApp);
                document.querySelectorAll('[data-page-col]').forEach(col => {
                    col.classList.toggle('col-md-4', !isApp);
                    col.classList.toggle('col-md-6', isApp);
                });
                if (isApp && selections.page === 'listing') {
                    document.querySelectorAll('.option-card[data-group="page"]').forEach(c => c.classList.remove('selected'));
                    selections.page = null;
                    document.getElementById('input-page').value = '';
                }
            }

            document.querySelectorAll('.banner-wizard .layout-card').forEach(card => {
                card.addEventListener('click', function () {
                    document.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selections.layout = this.dataset.value;
                    document.getElementById('input-layout').value = selections.layout;
                    document.getElementById('btn-step-2-continue').disabled = false;
                    renderRecap();
                });
            });

            document.getElementById('btn-step-1-continue').addEventListener('click', function () {
                applyLayoutVisibility();
                fetchExistingBanners();
                goToStep(2);
            });

            document.getElementById('btn-step-2-continue').addEventListener('click', function () {
                buildBannerAccordions();
                goToStep(3);
            });

            const ITEMS_BY_CAT_URL = "{{ route('banner-ad.items-by-category') }}";
            const CSRF = "{{ csrf_token() }}";
            const bannerState = [];

            function bannerCountForLayout(layout) {
                return (layout === 'dual' || layout === 'dual_side') ? 2 : 1;
            }

            function bannerSizeSpec(layout) {
                const p = selections.platform;
                const l = layout || selections.layout;
                if (p === 'app') {
                    if (l === 'single') return { w: 343, h: 114 };
                    if (l === 'dual') return { w: 343, h: 80 };
                    if (l === 'large') return { w: 343, h: 286 };
                }
                if (p === 'web') {
                    if (l === 'single') return { w: 1512, h: 350 };
                    if (l === 'dual') return { w: 741, h: 220 };
                    if (l === 'single_side' || l === 'dual_side') {
                        if (selections.page === 'listing') return { w: 353, h: 450 };
                        if (selections.page === 'detail') return { w: 484, h: 617 };
                    }
                }
                return null;
            }

            function buildBannerAccordions() {
                const wrap = document.getElementById('banner-accordions');
                const count = bannerCountForLayout(selections.layout);
                const buildKey = (selections.platform || '') + '|' + (selections.layout || '');
                if (wrap.dataset.builtFor === buildKey) return;
                wrap.innerHTML = '';
                bannerState.length = 0;
                const tpl = document.getElementById('banner-accordion-template');
                const spec = bannerSizeSpec();
                const sizeLabel = spec ? `{{ __('Recommended Size') }} : ${spec.w} x ${spec.h} px` : '';
                for (let i = 1; i <= count; i++) {
                    const node = tpl.content.firstElementChild.cloneNode(true);
                    node.dataset.bannerIndex = i;
                    node.querySelector('.banner-num').textContent = i;
                    const recEl = node.querySelector('.banner-recommended-size');
                    if (recEl && sizeLabel) recEl.textContent = sizeLabel;

                    const idx0 = i - 1;
                    const fInput = node.querySelector('.banner-file-input');
                    const tSel = node.querySelector('.banner-ad-type');
                    const cSel = node.querySelector('.banner-category');
                    const aSel = node.querySelector('.banner-advertisement');
                    const lInp = node.querySelector('.banner-external-link');
                    if (fInput) fInput.name = `banners[${idx0}][image]`;
                    if (tSel) tSel.name = `banners[${idx0}][ad_type]`;
                    if (cSel) cSel.name = `banners[${idx0}][category_id]`;
                    if (aSel) aSel.name = `banners[${idx0}][advertisement_id]`;
                    if (lInp) lInp.name = `banners[${idx0}][link]`;
                    const posHidden = document.createElement('input');
                    posHidden.type = 'hidden';
                    posHidden.name = `banners[${idx0}][position]`;
                    posHidden.value = i;
                    node.appendChild(posHidden);

                    wrap.appendChild(node);
                    bannerState.push({ index: i, file: null, type: 'only_banner', category: '', ad: '', link: '' });
                    wireAccordion(node, i);
                    wireAdControls(node, i);
                    initCategorySelect2(node);
                }
                wrap.dataset.builtFor = buildKey;
                validateStep3();
            }

            // Make the category dropdown searchable (select2). Bridge select2's
            // jQuery-only change event to a native one so the existing
            // addEventListener('change') wiring (state + ad loading) still fires.
            function initCategorySelect2(node) {
                if (!(window.jQuery && jQuery.fn.select2)) return;
                const wrap = node.querySelector('.banner-field-category');
                const sel = node.querySelector('.banner-category');
                if (!sel) return;
                const $sel = jQuery(sel);
                $sel.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: "{{ __('Select Category') }}",
                    dropdownParent: jQuery(wrap)
                });
                $sel.on('change', function (e) {
                    if (e.originalEvent) return; // native change already handled
                    sel.dispatchEvent(new Event('change'));
                });
            }

            function wireAccordion(node, index) {
                const body = node.querySelector('.banner-accordion-body');
                node.querySelector('.banner-accordion-toggle').addEventListener('click', () => {
                    node.classList.toggle('collapsed');
                });
                node.querySelector('.banner-accordion-header').addEventListener('click', (e) => {
                    if (e.target.closest('.banner-accordion-toggle')) return;
                    node.classList.toggle('collapsed');
                });

                const fileInput = node.querySelector('.banner-file-input');
                const dz = node.querySelector('.banner-dropzone');
                const placeholder = node.querySelector('.dz-placeholder');
                const previewWrap = node.querySelector('.dz-preview-wrap');
                const previewImg = node.querySelector('.dz-preview-img');
                const fileNameLabel = node.querySelector('.banner-file-name');
                const titleText = node.querySelector('.banner-title-text');

                dz.addEventListener('click', (e) => {
                    if (e.target.closest('.dz-remove')) return;
                    fileInput.click();
                });
                dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('dragover'); });
                dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
                dz.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dz.classList.remove('dragover');
                    if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
                });
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length) handleFile(e.target.files[0]);
                });
                node.querySelector('.dz-remove').addEventListener('click', (e) => {
                    e.stopPropagation();
                    fileInput.value = '';
                    bannerState[index - 1].file = null;
                    previewImg.src = '';
                    previewWrap.classList.add('d-none');
                    placeholder.classList.remove('d-none');
                    fileNameLabel.classList.add('d-none');
                    fileNameLabel.textContent = '';
                    titleText.classList.remove('d-none');
                    validateStep3();
                });

                function handleFile(file) {
                    if (!/^image\/(jpeg|png)$/.test(file.type)) {
                        alert('{{ __('Only JPG / PNG allowed') }}');
                        return;
                    }
                    if (file.size > 8 * 1024 * 1024) {
                        alert('{{ __('Max size 8 MB') }}');
                        return;
                    }
                    bannerState[index - 1].file = file;
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        previewImg.src = ev.target.result;
                        previewWrap.classList.remove('d-none');
                        placeholder.classList.add('d-none');
                        fileNameLabel.textContent = file.name;
                        fileNameLabel.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                    validateStep3();
                }

            }

            function wireAdControls(node, index) {
                const state = bannerState[index - 1];
                const typeSelect = node.querySelector('.banner-ad-type');
                const catWrap = node.querySelector('.banner-field-category');
                const adWrap = node.querySelector('.banner-field-advertisement');
                const extWrap = node.querySelector('.banner-field-external');
                const catSelect = node.querySelector('.banner-category');
                const adSelect = node.querySelector('.banner-advertisement');
                const linkInput = node.querySelector('.banner-external-link');

                typeSelect.addEventListener('change', () => {
                    const t = typeSelect.value;
                    state.type = t;
                    catWrap.classList.toggle('d-none', !(t === 'category' || t === 'advertisement'));
                    adWrap.classList.toggle('d-none', t !== 'advertisement');
                    extWrap.classList.toggle('d-none', t !== 'external_link');
                    // Switched to advertisement with a category already picked → load its ads now
                    if (t === 'advertisement' && catSelect.value) {
                        catSelect.dispatchEvent(new Event('change'));
                    }
                    renderRecap();
                    validateStep3();
                });

                catSelect.addEventListener('change', () => {
                    state.category = catSelect.value;
                    state.ad = '';
                    adSelect.innerHTML = '<option value="">{{ __('Loading...') }}</option>';
                    adSelect.disabled = true;
                    if (catSelect.value && state.type === 'advertisement') {
                        fetch(ITEMS_BY_CAT_URL + '?category_id=' + encodeURIComponent(catSelect.value), {
                            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                        })
                            .then(r => r.json())
                            .then(j => {
                                const items = j.data || [];
                                adSelect.innerHTML = '<option value="">{{ __('Select Advertisement') }}</option>' +
                                    items.map(it => `<option value="${it.id}">${it.name}</option>`).join('');
                                adSelect.disabled = items.length === 0;
                            })
                            .catch(() => {
                                adSelect.innerHTML = '<option value="">{{ __('Failed to load') }}</option>';
                            });
                    }
                    validateStep3();
                });

                adSelect.addEventListener('change', () => {
                    state.ad = adSelect.value;
                    validateStep3();
                });

                linkInput.addEventListener('input', () => {
                    state.link = linkInput.value.trim();
                    validateStep3();
                });
            }

            function validateStep3() {
                const filesOk = bannerState.length > 0 && bannerState.every(b => !!b.file);
                const typeOk = bannerState.every(b => {
                    if (b.type === 'category') return !!b.category;
                    if (b.type === 'advertisement') return !!b.category && !!b.ad;
                    if (b.type === 'external_link') return /^https?:\/\/.+/i.test(b.link);
                    return true;
                });
                document.getElementById('btn-step-3-continue').disabled = !(filesOk && typeOk);
            }

            document.getElementById('btn-step-3-continue').addEventListener('click', function () {
                buildPlacement();
                goToStep(4);
            });

            // Step 4: Banner Placement — sections from home_screen_sections + feature_sections
            const HOMEPAGE_SECTIONS_WEB = @json($homepageSections ?? []);

            // Existing banners already saved for the selected platform + page
            const EXISTING_BANNERS_URL = "{{ route('banner-ad.existing-banners') }}";
            let existingBanners = [];
            let existingSpots = [];

            function fetchExistingBanners() {
                existingBanners = [];
                existingSpots = [];
                if (!selections.platform || !selections.page) return;
                fetch(EXISTING_BANNERS_URL + '?platform=' + encodeURIComponent(selections.platform) +
                        '&page=' + encodeURIComponent(selections.page), {
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(j => {
                        existingBanners = j.data || [];
                        const step4 = document.querySelector('[data-step-content="4"]');
                        if (step4 && !step4.classList.contains('d-none') && placementOrder.length) {
                            renderPreview();
                        }
                    })
                    .catch(() => { existingBanners = []; });
            }

            // Resolve which section in the current placement order an existing banner is anchored to
            function resolveAnchorId(rec) {
                if (selections.page === 'home') {
                    const fid = rec.feature_section_id;
                    const ent = placementOrder.find(s => {
                        if (fid) return String(s.feature_id ?? '') === String(fid);
                        return s.hs_id != null && String(s.hs_id) === String(rec.home_screen_section_id) && !s.feature_id;
                    });
                    return ent ? ent.id : null;
                }
                if (selections.page === 'detail') {
                    const ent = placementOrder.find(s => DETAIL_SECTION_ENUM[s.id] === rec.detail_page_section);
                    return ent ? ent.id : null;
                }
                if (selections.page === 'listing') {
                    const ent = placementOrder.find(s => LISTING_SECTION_ENUM[s.id] === rec.listing_page_section);
                    return ent ? ent.id : null;
                }
                return null;
            }

            // Group existing banners into "spots" — anchor section + above/below (or side)
            function buildExistingSpots() {
                existingSpots = [];
                const map = {};
                existingBanners.forEach(rec => {
                    const anchorId = resolveAnchorId(rec);
                    if (!anchorId) return;
                    const isSide = rec.layout === 'single_side' || rec.layout === 'dual_side';
                    const side = isSide ? 'side' : (rec.placement || 'below');
                    const key = anchorId + '|' + side;
                    if (!map[key]) {
                        map[key] = { key, anchorId, side, isSide, images: [], records: [] };
                        existingSpots.push(map[key]);
                    }
                    if (rec.image) {
                        map[key].images.push(rec.image);
                        map[key].records.push(rec);
                    }
                });
            }

            // Current spot the new banner occupies — anchor section + above/below (or side)
            function bannerNewSpot() {
                const isSide = selections.layout === 'single_side' || selections.layout === 'dual_side';
                if (isSide) {
                    if (selections.page === 'detail') return { anchorId: 'ads_detail', side: 'side' };
                    if (selections.page === 'listing') return { anchorId: 'listing_data', side: 'side' };
                    return null;
                }
                const bi = placementOrder.findIndex(s => s.id === 'banner_new');
                if (bi === -1) return null;
                let ref, side;
                if (bi === 0) { ref = placementOrder[1]; side = 'above'; }
                else { ref = placementOrder[bi - 1]; side = 'below'; }
                return ref ? { anchorId: ref.id, side } : null;
            }

            // Registry of image lists per rendered stack — index referenced by data-stack-id
            let stackRegistry = [];

            // Stacked-paper wrapper — badge counts only banners behind the front one
            function wrapStack(rowHtml, behindCount, stackData) {
                // 1 behind → one card behind; 2 or more → two cards behind
                const behind = behindCount >= 2
                    ? '<span class="prev-banner-stack-behind prev-banner-stack-behind-2"></span><span class="prev-banner-stack-behind"></span>'
                    : (behindCount === 1 ? '<span class="prev-banner-stack-behind"></span>' : '');
                let countHtml = '';
                if (behindCount > 0) {
                    const sid = stackRegistry.push(stackData || []) - 1;
                    countHtml = `<span class="prev-stack-count" role="button" tabindex="0" data-stack-id="${sid}">+${behindCount} {{ __('Banners') }}</span>`;
                }
                return `
                    <div class="prev-banner-stack">
                        ${behind}
                        ${countHtml}
                        <div class="prev-banner-stack-front">${rowHtml}</div>
                    </div>`;
            }

            // Group records into banner units — a dual banner = 2 records sharing group_id
            function groupRecordsToUnits(records) {
                const units = [];
                const byGroup = {};
                (records || []).forEach(r => {
                    if (r.group_id) {
                        if (!byGroup[r.group_id]) {
                            byGroup[r.group_id] = { items: [] };
                            units.push(byGroup[r.group_id]);
                        }
                        byGroup[r.group_id].items.push(r);
                    } else {
                        units.push({ items: [r] });
                    }
                });
                units.forEach(u => u.items.sort((a, b) => (a.position || 0) - (b.position || 0)));
                return units;
            }

            // Render one old banner unit in its actual layout (dual → two tiles side by side)
            function renderExistingUnit(unit) {
                const layout = unit.items[0] ? unit.items[0].layout : 'single';
                const spec = bannerSizeSpec(layout);
                const aspectStyle = spec ? `style="--bnr-ratio:${(spec.h / spec.w * 100).toFixed(3)}%;"` : '';
                const tile = (img) => `
                    <div class="prev-banner-tile has-image" ${aspectStyle}>
                        <span class="prev-banner-badge prev-banner-badge-old">{{ __('Old Banner') }}</span>
                        <img src="${img}" alt="">
                    </div>`;
                const imgs = unit.items.filter(r => r.image).map(r => r.image);
                if (layout === 'dual') {
                    return `<div class="prev-banner-row prev-banner-dual">${imgs.map(tile).join('')}</div>`;
                }
                return `<div class="prev-banner-row prev-banner-single">${tile(imgs[0])}</div>`;
            }

            // Reference section for an existing banner spot not overlapped by the new banner
            function renderExistingRefSection(sp) {
                if (!sp.records.length) return '';
                const units = groupRecordsToUnits(sp.records);
                if (!units.length) return '';
                const front = renderExistingUnit(units[0]);
                // Banners sitting behind the front unit → drive stack badge count
                const frontImgs = units[0].items.filter(r => r.image).length;
                const behind = sp.images.length - frontImgs;
                const inner = behind > 0 ? wrapStack(front, behind, sp.records) : front;
                return `<div class="prev-section-wrap" data-existing-spot="${sp.key}"><div class="prev-section">${inner}</div></div>`;
            }

            // Section order: includes new "banner_new" entry; default placed at index 1
            let placementOrder = [];
            let placementBuiltFor = '';

            function applyPlacementCols() {
                const dragCol = document.getElementById('placement-col-drag');
                const previewCol = document.getElementById('placement-col-preview');
                if (!dragCol || !previewCol) return;
                const isApp = selections.platform === 'app';
                const dragClass = isApp ? 'col-lg-8' : 'col-lg-4';
                const previewClass = isApp ? 'col-lg-4' : 'col-lg-8';
                dragCol.classList.remove('col-lg-4', 'col-lg-5', 'col-lg-6', 'col-lg-7', 'col-lg-8');
                previewCol.classList.remove('col-lg-4', 'col-lg-5', 'col-lg-6', 'col-lg-7', 'col-lg-8');
                dragCol.classList.add(dragClass);
                previewCol.classList.add(previewClass);
            }

            function buildPlacement() {
                applyPlacementCols();
                const key = (selections.page || '') + '|' + (selections.platform || '') + '|' + (selections.layout || '');
                if (placementBuiltFor === key && placementOrder.length) {
                    renderPlacement();
                    return;
                }
                placementBuiltFor = key;
                if (selections.page === 'detail') {
                    placementOrder = buildDetailPlacement();
                } else if (selections.page === 'listing') {
                    placementOrder = buildListingPlacement();
                } else {
                    const base = HOMEPAGE_SECTIONS_WEB
                        .filter(s => !(selections.platform === 'app' && s.id === 'all_advertisement'))
                        .map(s => ({ ...s }));
                    base.splice(1, 0, { id: 'banner_new', label: '{{ __('Banner Ad (New)') }}', type: 'banner_new', isNew: true });
                    placementOrder = base;
                }
                renderPlacement();
            }

            function buildDetailPlacement() {
                const layout = selections.layout;
                const isSide = layout === 'single_side' || layout === 'dual_side';
                const isApp = selections.platform === 'app';

                if (isApp) {
                    return [
                        { id: 'banner_new', label: '{{ __('Banner Ad (New)') }}', type: 'banner_new', isNew: true },
                        { id: 'image', label: '{{ __('Image') }}', type: 'app_detail_image' },
                        { id: 'ad_info', label: '{{ __('Ad Info') }}', type: 'app_detail_info' },
                        { id: 'custom_fields', label: '{{ __('Custom Fields') }}', type: 'app_detail_custom' },
                        { id: 'about_advertisement', label: '{{ __('About Advertisement') }}', type: 'app_detail_about' },
                        { id: 'location', label: '{{ __('Location') }}', type: 'app_detail_location' },
                        { id: 'related_ads', label: '{{ __('Related Ads') }}', type: 'app_detail_related' },
                    ];
                }

                const adsDetail = { id: 'ads_detail', label: '{{ __('Ads Details') }}', type: 'ads_detail' };
                const similar = { id: 'similar_product', label: '{{ __('Similar Products') }}', type: 'similar_product' };

                if (isSide) {
                    // Side layout: all items locked; side banners listed between sections (top-down)
                    const items = [adsDetail];
                    const count = layout === 'dual_side' ? 2 : 1;
                    for (let i = 1; i <= count; i++) {
                        items.push({
                            id: 'side_banner_' + i,
                            label: '{{ __('Side Banner') }} ' + i + ' ({{ __('New') }})',
                            type: 'side_banner',
                            sideIndex: i,
                            isNew: true,
                            locked: true,
                        });
                    }
                    items.push(similar);
                    return items;
                }
                // Non-side: banner_new at top, draggable
                return [
                    { id: 'banner_new', label: '{{ __('Banner Ad (New)') }}', type: 'banner_new', isNew: true },
                    adsDetail,
                    similar,
                ];
            }

            function buildListingPlacement() {
                const layout = selections.layout;
                const isSide = layout === 'single_side' || layout === 'dual_side';
                const categories = { id: 'categories_list', label: '{{ __('Categories List') }}', type: 'categories_list' };
                const listing = { id: 'listing_data', label: '{{ __('Listing Data') }}', type: 'listing_data' };

                if (isSide) {
                    const items = [categories, listing];
                    const count = layout === 'dual_side' ? 2 : 1;
                    for (let i = 1; i <= count; i++) {
                        items.push({
                            id: 'side_banner_' + i,
                            label: '{{ __('Side Banner') }} ' + i + ' ({{ __('New') }})',
                            type: 'side_banner',
                            sideIndex: i,
                            isNew: true,
                            locked: true,
                        });
                    }
                    return items;
                }
                return [
                    { id: 'banner_new', label: '{{ __('Banner Ad (New)') }}', type: 'banner_new', isNew: true },
                    categories,
                    listing,
                ];
            }

            function renderPlacement() {
                const list = document.getElementById('section-list');
                list.innerHTML = placementOrder.map(s => {
                    const draggable = s.isNew && !s.locked;
                    const cls = draggable ? 'is-new' : (s.isNew ? 'is-new is-fixed' : 'is-fixed');
                    const icon = draggable
                        ? '<span class="section-item-handle"><i class="ph ph-hand-grabbing"></i></span>'
                        : '<span class="section-item-lock"><i class="ph ph-lock-simple"></i></span>';
                    return `
                    <li class="section-item ${cls}" data-id="${s.id}">
                        <span class="section-item-label">${s.label}</span>
                        ${icon}
                    </li>`;
                }).join('');

                if (window.jQuery && jQuery.fn.sortable) {
                    if (jQuery(list).hasClass('ui-sortable')) {
                        jQuery(list).sortable('destroy');
                    }
                    // Block drag init on fixed items at capture phase (mouse + touch + pointer)
                    if (!list.dataset.dragGuardBound) {
                        const blockOnFixed = (e) => {
                            const t = e.target;
                            if (t && t.closest && t.closest('.section-item.is-fixed')) {
                                e.stopPropagation();
                            }
                        };
                        list.addEventListener('mousedown', blockOnFixed, true);
                        list.addEventListener('touchstart', blockOnFixed, { capture: true, passive: true });
                        list.addEventListener('pointerdown', blockOnFixed, true);
                        list.dataset.dragGuardBound = '1';
                    }
                    const fixedOrder = placementOrder.filter(s => !s.isNew).map(s => s.id);
                    jQuery(list).sortable({
                        items: '.section-item',
                        cancel: '.section-item.is-fixed',
                        axis: 'y',
                        tolerance: 'pointer',
                        placeholder: 'section-item-placeholder',
                        forcePlaceholderSize: true,
                        helper: 'original',
                        update: function () {
                            // Read DOM, rebuild order — enforce fixed sequence, only banner moves
                            const ids = Array.from(list.children).map(li => li.dataset.id);
                            const bannerIdx = ids.indexOf('banner_new');
                            const newIds = [...fixedOrder];
                            newIds.splice(bannerIdx, 0, 'banner_new');
                            placementOrder = newIds.map(id => placementOrder.find(s => s.id === id));
                            syncPlacementHiddens();

                            const prev = capturePreviewScroll();
                            renderPreview();
                            restorePreviewScroll(prev);
                            scrollPreviewTo('banner_new');
                        }
                    }).disableSelection();
                }
                syncPlacementHiddens();
                renderPreview();
            }

            function renderPreview() {
                const wrap = document.getElementById('preview-scroll');
                stackRegistry = [];
                const isApp = selections.platform === 'app';
                wrap.classList.toggle('app-preview', isApp);

                buildExistingSpots();
                const bnSpot = bannerNewSpot();
                const bnKey = bnSpot ? bnSpot.anchorId + '|' + bnSpot.side : null;

                const parts = [];
                placementOrder
                    .filter(s => s.type !== 'side_banner')
                    .forEach(s => {
                        const isBanner = s.type === 'banner_new';
                        if (!isBanner) {
                            existingSpots
                                .filter(sp => sp.anchorId === s.id && sp.side === 'above' && sp.key !== bnKey)
                                .forEach(sp => parts.push(renderExistingRefSection(sp)));
                        }
                        parts.push(`<div class="prev-section-wrap" data-section-id="${s.id}">${renderPreviewSection(s)}</div>`);
                        if (!isBanner) {
                            existingSpots
                                .filter(sp => sp.anchorId === s.id && sp.side === 'below' && sp.key !== bnKey)
                                .forEach(sp => parts.push(renderExistingRefSection(sp)));
                        }
                    });
                const sectionsHtml = parts.join('');

                if (isApp) {
                    wrap.innerHTML = renderAppFrame(sectionsHtml);
                } else {
                    wrap.innerHTML = sectionsHtml;
                }
            }

            function renderAppFrame(inner) {
                const isDetail = selections.page === 'detail';
                const statusBar = `
                    <div class="prev-app-statusbar">
                        <span class="prev-app-time">6:15</span>
                        <span class="prev-app-status-icons">
                            <i class="ph ph-signal-high"></i>
                            <i class="ph ph-wifi-high"></i>
                            <i class="ph ph-battery-full"></i>
                        </span>
                    </div>`;

                const homeHeader = `
                    <div class="prev-app-location">
                        <i class="ph-fill ph-map-pin prev-app-pin"></i>
                        <div class="prev-app-location-text">
                            <div class="prev-app-location-title skel"></div>
                            <div class="prev-app-location-sub skel"></div>
                        </div>
                    </div>
                    <div class="prev-app-search">
                        <i class="ph ph-magnifying-glass"></i>
                        <span class="prev-app-search-line skel"></span>
                        <i class="ph ph-microphone"></i>
                    </div>`;

                const detailHeader = `
                    <div class="prev-app-detail-header">
                        <i class="ph ph-arrow-left"></i>
                        <i class="ph ph-share-network"></i>
                    </div>`;

                const homeTabbar = `
                    <div class="prev-app-tabbar">
                        <div class="prev-app-tab active"><i class="ph-fill ph-house"></i><span>{{ __('Home') }}</span></div>
                        <div class="prev-app-tab"><i class="ph ph-chat-circle"></i><span>{{ __('Chat') }}</span></div>
                        <div class="prev-app-fab"><i class="ph ph-plus"></i></div>
                        <div class="prev-app-tab"><i class="ph ph-megaphone"></i><span>{{ __('My Ads') }}</span></div>
                        <div class="prev-app-tab"><i class="ph ph-user"></i><span>{{ __('Profile') }}</span></div>
                    </div>`;

                const detailFooter = `
                    <div class="prev-app-detail-footer">
                        <button type="button" class="prev-app-detail-foot-btn outline">{{ __('Make an Offer') }}</button>
                        <button type="button" class="prev-app-detail-foot-btn solid">{{ __('Chat') }}</button>
                    </div>`;

                return `
                    <div class="prev-app-frame ${isDetail ? 'is-detail' : ''}">
                        ${statusBar}
                        ${isDetail ? detailHeader : homeHeader}
                        <div class="prev-app-scroll">
                            ${inner}
                        </div>
                        ${isDetail ? detailFooter : homeTabbar}
                    </div>`;
            }

            function capturePreviewScroll() {
                const scroller = document.getElementById('preview-scroll');
                if (!scroller) return null;
                const inner = scroller.querySelector('.prev-app-scroll');
                return {
                    outer: scroller.scrollTop,
                    inner: inner ? inner.scrollTop : 0,
                };
            }

            function restorePreviewScroll(prev) {
                if (!prev) return;
                const scroller = document.getElementById('preview-scroll');
                if (!scroller) return;
                scroller.scrollTop = prev.outer;
                const inner = scroller.querySelector('.prev-app-scroll');
                if (inner) inner.scrollTop = prev.inner;
            }

            function scrollPreviewTo(sectionId) {
                const scroller = document.getElementById('preview-scroll');
                if (!scroller) return;
                const target = scroller.querySelector(`[data-section-id="${sectionId}"]`);
                if (!target) return;
                const innerScroll = scroller.querySelector('.prev-app-scroll') || scroller;
                const top = target.offsetTop - innerScroll.offsetTop - 8;
                innerScroll.scrollTo({ top, behavior: 'smooth' });
            }

            function renderPreviewSection(s) {
                if (s.type === 'cat_nav') {
                    return `
                        <div class="prev-section prev-section-nav">
                            <div class="prev-cat-nav">
                                ${Array(9).fill(0).map(() => `<span class="prev-cat-nav-item skel"></span>`).join('')}
                            </div>
                        </div>`;
                }
                if (s.type === 'slider') {
                    return `
                        <div class="prev-section">
                            <div class="prev-slider-row">
                                <div class="prev-slider-main skel"></div>
                                <div class="prev-slider-side skel"></div>
                            </div>
                        </div>`;
                }
                if (s.type === 'categories') {
                    return `
                        <div class="prev-section">
                            <div class="prev-section-head">
                                <div class="prev-section-title">${s.label || '{{ __('Popular Categories') }}'}</div>
                                <div class="prev-arrows">
                                    <span class="prev-arrow"><i class="ph ph-caret-left"></i></span>
                                    <span class="prev-arrow active"><i class="ph ph-caret-right"></i></span>
                                </div>
                            </div>
                            <div class="prev-cat-row">
                                ${Array(8).fill(0).map(() => `
                                    <div class="prev-cat-item">
                                        <div class="prev-cat-circle skel"></div>
                                        <div class="prev-cat-label skel"></div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>`;
                }
                if (s.type === 'banner_new') {
                    return renderBannerPreview();
                }
                if (s.type === 'ads_detail') {
                    return renderAdsDetailPreview();
                }
                if (s.type === 'similar_product') {
                    return renderSimilarProductsPreview();
                }
                if (s.type === 'categories_list') {
                    return renderCategoriesListPreview();
                }
                if (s.type === 'listing_data') {
                    return renderListingDataPreview();
                }
                if (s.type === 'app_detail_image') return renderAppDetailImage();
                if (s.type === 'app_detail_info') return renderAppDetailInfo();
                if (s.type === 'app_detail_custom') return renderAppDetailCustom();
                if (s.type === 'app_detail_about') return renderAppDetailAbout();
                if (s.type === 'app_detail_location') return renderAppDetailLocation();
                if (s.type === 'app_detail_related') return renderAppDetailRelated();
                if (s.type === 'all_advertisement') {
                    return renderCardGrid(s.label, { withViewAll: false });
                }
                const isFeature = typeof s.id === 'string' && s.id.startsWith('feature_');
                return renderCardGrid(s.label, { withViewAll: true, isFeature });
            }

            function renderCardGrid(label, opts) {
                const withViewAll = !!(opts && opts.withViewAll);
                const isFeature = !!(opts && opts.isFeature);
                const isApp = selections.platform === 'app';
                const rowClass = (isApp && isFeature) ? 'prev-card-row prev-card-row-scroll' : 'prev-card-row';
                return `
                    <div class="prev-section">
                        <div class="prev-section-head">
                            <div class="prev-section-title">${label}</div>
                            ${withViewAll ? `<div class="prev-view-all">{{ __('View All') }}</div>` : ''}
                        </div>
                        <div class="${rowClass}">
                            ${Array(5).fill(0).map(() => {
                                const isFeatured = Math.random() < 0.4;
                                const isLiked = Math.random() < 0.5;
                                return `
                                <div class="prev-card">
                                    <div class="prev-card-img skel">
                                        ${isFeatured ? `<span class="prev-featured-badge"><i class="ph-fill ph-check-circle"></i> {{ __('Featured') }}</span>` : ''}
                                        <span class="prev-heart ${isLiked ? 'liked' : ''}"><i class="${isLiked ? 'ph-fill' : 'ph'} ph-heart"></i></span>
                                    </div>
                                    <div class="prev-card-price-row">
                                        <span class="skel prev-card-price"></span>
                                        <span class="skel prev-card-day"></span>
                                    </div>
                                    <div class="skel prev-card-title"></div>
                                    <div class="skel prev-card-sub"></div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>`;
            }

            function renderAdsDetailPreview() {
                const layout = selections.layout;
                const isSideSingle = layout === 'single_side';
                const isSideDual = layout === 'dual_side';
                const previews = bannerState.map(b => b.file ? URL.createObjectURL(b.file) : null);
                const spec = bannerSizeSpec();
                const aspectStyle = spec ? `style="--bnr-ratio:${(spec.h / spec.w * 100).toFixed(3)}%;"` : '';

                const sideTile = (idx) => `
                    <div class="prev-banner-tile prev-side-tile ${previews[idx] ? 'has-image' : ''}" ${aspectStyle}>
                        <span class="prev-banner-badge">{{ __('New Banner') }}</span>
                        ${previews[idx]
                            ? `<img src="${previews[idx]}" alt="">`
                            : `<div class="prev-banner-icon"><i class="ph ph-image"></i></div>`}
                    </div>`;

                let sideRailHtml = isSideSingle
                    ? sideTile(0)
                    : isSideDual ? (sideTile(0) + sideTile(1)) : '';
                if (sideRailHtml) {
                    const exSide = existingSpots.find(sp => sp.isSide);
                    if (exSide && exSide.images.length) {
                        sideRailHtml = wrapStack(sideRailHtml, exSide.images.length, exSide.records);
                    }
                }

                return `
                    <div class="prev-section prev-ads-detail ${isSideSingle || isSideDual ? 'has-side-rail' : ''}">
                        <div class="prev-ads-body">
                            <div class="prev-ads-main">
                                <div class="prev-ads-gallery skel"></div>
                                <div class="prev-ads-thumbs">
                                    ${Array(6).fill(0).map(() => `<span class="prev-ads-thumb skel"></span>`).join('')}
                                </div>

                                <div class="prev-ads-block">
                                    <div class="prev-ads-block-heading">{{ __('Highlights') }}</div>
                                    ${Array(8).fill(0).map(() => `
                                        <div class="prev-ads-hl-row">
                                            <span class="prev-ads-hl-key skel"></span>
                                            <span class="prev-ads-hl-val skel"></span>
                                        </div>`).join('')}
                                </div>

                                <div class="prev-ads-block">
                                    <div class="prev-ads-block-heading">{{ __('Description') }}</div>
                                    <div class="prev-ads-desc-line skel"></div>
                                    <div class="prev-ads-desc-line skel"></div>
                                    <div class="prev-ads-desc-line skel short"></div>
                                </div>

                                <div class="prev-ads-block">
                                    <div class="prev-ads-block-heading">{{ __('Posted in') }}</div>
                                    <div class="prev-ads-addr-line skel"></div>
                                    <div class="prev-ads-map skel"></div>
                                    <div class="prev-ads-map-btn-skel skel"></div>
                                </div>

                                <div class="prev-ads-block">
                                    <div class="prev-ads-report-line skel"></div>
                                    <div class="prev-ads-report-foot-skel">
                                        <span class="skel"></span>
                                        <span class="skel"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="prev-ads-aside">
                                <div class="prev-ads-seller">
                                    <div class="prev-ads-seller-line skel"></div>
                                    <div class="prev-ads-price skel"></div>
                                    <div class="prev-ads-meta skel"></div>
                                    <div class="prev-ads-seller-divider"></div>
                                    <div class="prev-ads-seller-info">
                                        <div class="prev-ads-avatar skel"></div>
                                        <div class="prev-ads-seller-meta">
                                            <div class="prev-ads-seller-name skel"></div>
                                            <div class="prev-ads-seller-rating skel"></div>
                                            <div class="prev-ads-seller-email skel"></div>
                                        </div>
                                    </div>
                                    <div class="prev-ads-seller-actions">
                                        <div class="prev-ads-action-btn-skel skel"></div>
                                        <div class="prev-ads-action-btn-skel skel"></div>
                                    </div>
                                    <div class="prev-ads-offer-btn-skel skel"></div>
                                </div>
                                ${sideRailHtml ? `<div class="prev-ads-side-rail">${sideRailHtml}</div>` : ''}
                            </div>
                        </div>
                    </div>`;
            }

            function renderSimilarProductsPreview() {
                return `
                    <div class="prev-section">
                        <div class="prev-section-head">
                            <div class="prev-section-title">{{ __('Similar Products') }}</div>
                        </div>
                        <div class="prev-card-row">
                            ${Array(5).fill(0).map(() => `
                                <div class="prev-card">
                                    <div class="prev-card-img skel"></div>
                                    <div class="prev-card-price-row">
                                        <span class="skel prev-card-price"></span>
                                        <span class="skel prev-card-day"></span>
                                    </div>
                                    <div class="skel prev-card-title"></div>
                                    <div class="skel prev-card-sub"></div>
                                </div>`).join('')}
                        </div>
                    </div>`;
            }

            function renderCategoriesListPreview() {
                return '';
            }

            function renderListingCategoriesBlock() {
                return `
                    <div class="prev-listing-head-inline">
                        <div class="prev-listing-title-row">
                            <div class="prev-listing-title-text">{{ __('Categories List') }}</div>
                            <div class="prev-listing-arrows">
                                <span class="prev-arrow"><i class="ph ph-caret-left"></i></span>
                                <span class="prev-arrow active"><i class="ph ph-caret-right"></i></span>
                            </div>
                        </div>
                        <div class="prev-listing-catnav">
                            ${Array(8).fill(0).map(() => `
                                <div class="prev-listing-catnav-item">
                                    <div class="prev-listing-catnav-icon skel"></div>
                                    <div class="prev-listing-catnav-label skel"></div>
                                </div>`).join('')}
                        </div>
                        <div class="prev-listing-sort-row">
                            <div class="prev-listing-sort skel"></div>
                            <div class="prev-listing-chips">
                                ${Array(3).fill(0).map(() => `<span class="prev-listing-chip skel"></span>`).join('')}
                            </div>
                            <div class="prev-listing-view skel"></div>
                        </div>
                    </div>`;
            }

            function renderListingDataPreview() {
                const layout = selections.layout;
                const isSideSingle = layout === 'single_side';
                const isSideDual = layout === 'dual_side';
                const previews = bannerState.map(b => b.file ? URL.createObjectURL(b.file) : null);
                const spec = bannerSizeSpec();
                const aspectStyle = spec ? `style="--bnr-ratio:${(spec.h / spec.w * 100).toFixed(3)}%;"` : '';

                const sideTile = (idx) => `
                    <div class="prev-banner-tile prev-side-tile ${previews[idx] ? 'has-image' : ''}" ${aspectStyle}>
                        <span class="prev-banner-badge">{{ __('New Banner') }}</span>
                        ${previews[idx]
                            ? `<img src="${previews[idx]}" alt="">`
                            : `<div class="prev-banner-icon"><i class="ph ph-image"></i></div>`}
                    </div>`;

                let sideRailHtml = isSideSingle
                    ? sideTile(0)
                    : isSideDual ? (sideTile(0) + sideTile(1)) : '';
                if (sideRailHtml) {
                    const exSide = existingSpots.find(sp => sp.isSide);
                    if (exSide && exSide.images.length) {
                        sideRailHtml = wrapStack(sideRailHtml, exSide.images.length, exSide.records);
                    }
                }

                return `
                    <div class="prev-section prev-listing-body ${isSideSingle || isSideDual ? 'has-side-rail' : ''}">
                        <aside class="prev-listing-filters">
                            <div class="prev-listing-filter-heading">{{ __('Filters') }}</div>
                            ${Array(7).fill(0).map(() => `<div class="prev-listing-filter-row skel"></div>`).join('')}
                            <div class="prev-listing-filter-heading">{{ __('Colors') }}</div>
                            ${Array(6).fill(0).map(() => `
                                <div class="prev-listing-filter-check">
                                    <span class="prev-listing-check skel"></span>
                                    <span class="prev-listing-check-label skel"></span>
                                </div>`).join('')}
                            ${sideRailHtml ? `<div class="prev-listing-side-rail">${sideRailHtml}</div>` : ''}
                        </aside>
                        <div class="prev-listing-grid-wrap">
                            ${renderListingCategoriesBlock()}
                            <div class="prev-listing-grid">
                                ${Array(20).fill(0).map(() => `
                                    <div class="prev-card">
                                        <div class="prev-card-img skel"></div>
                                        <div class="prev-card-price-row">
                                            <span class="skel prev-card-price"></span>
                                            <span class="skel prev-card-day"></span>
                                        </div>
                                        <div class="skel prev-card-title"></div>
                                        <div class="skel prev-card-sub"></div>
                                    </div>`).join('')}
                            </div>
                            <div class="prev-listing-load-more skel"></div>
                            <div class="prev-listing-bottom-banner skel"></div>
                        </div>
                    </div>`;
            }

            function renderAppDetailImage() {
                return `
                    <div class="prev-section prev-app-detail-image">
                        <div class="prev-app-detail-image-box">
                            <i class="ph ph-image"></i>
                            <span class="prev-app-detail-heart"><i class="ph ph-heart"></i></span>
                            <div class="prev-app-detail-dots">
                                ${Array(5).fill(0).map((_, i) => `<span class="${i === 0 ? 'active' : ''}"></span>`).join('')}
                            </div>
                        </div>
                    </div>`;
            }

            function renderAppDetailInfo() {
                return `
                    <div class="prev-section prev-app-detail-info">
                        <div class="prev-app-info-title skel"></div>
                        <div class="prev-app-info-price skel"></div>
                        <div class="prev-app-info-row">
                            <div class="prev-app-info-loc skel"></div>
                            <div class="prev-app-info-date skel"></div>
                        </div>
                    </div>`;
            }

            function renderAppDetailCustom() {
                return `
                    <div class="prev-section prev-app-detail-custom">
                        <div class="prev-app-block-heading">{{ __('Custom Fields') }}</div>
                        <div class="prev-app-custom-grid">
                            ${Array(12).fill(0).map(() => `
                                <div class="prev-app-custom-cell">
                                    <div class="prev-app-custom-key skel"></div>
                                    <div class="prev-app-custom-val skel"></div>
                                </div>`).join('')}
                        </div>
                    </div>`;
            }

            function renderAppDetailAbout() {
                return `
                    <div class="prev-section prev-app-detail-about">
                        <div class="prev-app-block-heading">{{ __('About Advertisement') }}</div>
                        <div class="prev-app-about-sub skel"></div>
                        <div class="prev-app-about-line skel"></div>
                        <div class="prev-app-about-line skel"></div>
                        <div class="prev-app-about-line skel short"></div>
                        <div class="prev-app-seller">
                            <div class="prev-app-seller-avatar skel"></div>
                            <div class="prev-app-seller-meta">
                                <span class="prev-app-seller-verified skel"></span>
                                <div class="prev-app-seller-name skel"></div>
                                <div class="prev-app-seller-rating skel"></div>
                            </div>
                            <div class="prev-app-seller-actions">
                                <span class="prev-app-seller-icon skel"></span>
                                <span class="prev-app-seller-icon skel"></span>
                            </div>
                        </div>
                    </div>`;
            }

            function renderAppDetailLocation() {
                return `
                    <div class="prev-section prev-app-detail-location">
                        <div class="prev-app-block-heading">{{ __('Location') }}</div>
                        <div class="prev-app-map skel"></div>
                        <div class="prev-app-adid-row">
                            <div class="prev-app-adid skel"></div>
                            <div class="prev-app-report skel"></div>
                        </div>
                    </div>`;
            }

            function renderAppDetailRelated() {
                return `
                    <div class="prev-section prev-app-detail-related">
                        <div class="prev-app-block-heading">{{ __('Related Ads') }}</div>
                        <div class="prev-app-related-row">
                            ${Array(5).fill(0).map(() => `
                                <div class="prev-app-related-card">
                                    <div class="prev-app-related-img skel"></div>
                                    <div class="prev-app-related-price skel"></div>
                                    <div class="prev-app-related-name skel"></div>
                                    <div class="prev-app-related-loc skel"></div>
                                </div>`).join('')}
                        </div>
                        <div class="prev-app-detail-actions">
                            <div class="prev-app-action-btn skel"></div>
                            <div class="prev-app-action-btn skel"></div>
                        </div>
                    </div>`;
            }

            function renderBannerPreview() {
                const layout = selections.layout;
                const previews = bannerState.map(b => b.file ? URL.createObjectURL(b.file) : null);
                const spec = bannerSizeSpec();
                const aspectStyle = spec ? `style="--bnr-ratio:${(spec.h / spec.w * 100).toFixed(3)}%;"` : '';
                const tile = (idx) => `
                    <div class="prev-banner-tile ${previews[idx] ? 'has-image' : ''}" ${aspectStyle}>
                        <span class="prev-banner-badge">{{ __('New Banner') }}</span>
                        ${previews[idx]
                            ? `<img src="${previews[idx]}" alt="">`
                            : `<div class="prev-banner-icon"><i class="ph ph-image"></i></div>`}
                    </div>`;
                let rowHtml = '';
                if (layout === 'single' || layout === 'large') {
                    rowHtml = `<div class="prev-banner-row prev-banner-single">${tile(0)}</div>`;
                } else if (layout === 'dual') {
                    rowHtml = `<div class="prev-banner-row prev-banner-dual">${tile(0)}${tile(1)}</div>`;
                } else if (layout === 'single_side') {
                    rowHtml = `<div class="prev-banner-row prev-banner-side-single"><div class="prev-side-body skel"></div>${tile(0)}</div>`;
                } else if (layout === 'dual_side') {
                    rowHtml = `<div class="prev-banner-row prev-banner-side-dual"><div class="prev-side-body skel"></div><div class="prev-side-stack">${tile(0)}${tile(1)}</div></div>`;
                } else {
                    return '';
                }

                // If the new banner lands on a spot that already has saved banners, show a stack
                const spot = bannerNewSpot();
                const ex = spot ? existingSpots.find(sp => sp.key === spot.anchorId + '|' + spot.side) : null;
                if (ex && ex.images.length) {
                    return `<div class="prev-section">${wrapStack(rowHtml, ex.images.length, ex.records)}</div>`;
                }
                return `<div class="prev-section">${rowHtml}</div>`;
            }

            // Map placement section id → detail_page_sections enum
            const DETAIL_SECTION_ENUM = {
                ads_detail: 'ad_info',
                similar_product: 'similar_ads',
                image: 'image',
                ad_info: 'ad_info',
                custom_fields: 'custom_fields',
                about_advertisement: 'about_ad',
                location: 'location',
                related_ads: 'similar_ads',
            };
            // Map placement section id → listing_page_sections enum
            const LISTING_SECTION_ENUM = {
                categories_list: 'category_list',
                listing_data: 'listing_data',
            };

            function syncPlacementHiddens() {
                let hsId = '';
                let featureId = '';
                let detailSection = '';
                let listingSection = '';
                let placement = '';
                const bannerIdx = placementOrder.findIndex(s => s.id === 'banner_new');
                if (bannerIdx !== -1) {
                    let refSection;
                    if (bannerIdx === 0) {
                        refSection = placementOrder[1];
                        placement = 'above';
                    } else {
                        refSection = placementOrder[bannerIdx - 1];
                        placement = 'below';
                    }
                    if (refSection) {
                        hsId = refSection.hs_id ?? '';
                        featureId = refSection.feature_id ?? '';
                        if (selections.page === 'detail') {
                            detailSection = DETAIL_SECTION_ENUM[refSection.id] ?? '';
                        } else if (selections.page === 'listing') {
                            listingSection = LISTING_SECTION_ENUM[refSection.id] ?? '';
                        }
                    }
                } else if (selections.layout === 'single_side' || selections.layout === 'dual_side') {
                    // Side layouts: banner anchored to fixed section (ads_detail / listing_data)
                    if (selections.page === 'detail') {
                        detailSection = DETAIL_SECTION_ENUM['ads_detail'] ?? '';
                    } else if (selections.page === 'listing') {
                        listingSection = LISTING_SECTION_ENUM['listing_data'] ?? '';
                    }
                }
                const hsEl = document.getElementById('input-hs-id');
                const fEl = document.getElementById('input-feature-id');
                const dEl = document.getElementById('input-detail-section');
                const lEl = document.getElementById('input-listing-section');
                const pEl = document.getElementById('input-placement');
                if (hsEl) hsEl.value = hsId;
                if (fEl) fEl.value = featureId;
                if (dEl) dEl.value = detailSection;
                if (lEl) lEl.value = listingSection;
                if (pEl) pEl.value = placement;
            }

            document.getElementById('create-form').addEventListener('submit', function () {
                // Disable hidden-wrapper inputs so they don't submit stale/irrelevant values
                this.querySelectorAll('.banner-accordion .d-none select, .banner-accordion .d-none input').forEach(el => {
                    el.disabled = true;
                });
                syncPlacementHiddens();
            });

            document.querySelectorAll('.banner-wizard .btn-previous').forEach(btn => {
                btn.addEventListener('click', function () {
                    goToStep(parseInt(this.dataset.prevTo));
                });
            });

            function applyLayoutVisibility() {
                // home → primary only (single, dual)
                // list/detail + web → primary + side
                // app → primary + large (all app pages)
                const showSide = selections.page !== 'home' && selections.platform === 'web';
                const showLarge = selections.platform === 'app';
                document.querySelectorAll('[data-layout-group="side"]').forEach(el => {
                    el.classList.toggle('d-none', !showSide);
                });
                document.querySelectorAll('[data-layout-group="large"]').forEach(el => {
                    el.classList.toggle('d-none', !showLarge);
                });

                // App: single = full row; dual + large share a row (6/6).
                // Web: single + dual share a row (6/6); large hidden.
                const singleCol = document.querySelector('.layout-card[data-value="single"]').parentElement;
                const largeCol = document.querySelector('.layout-card[data-value="large"]').parentElement;
                const isApp = selections.platform === 'app';
                singleCol.classList.toggle('col-12', isApp);
                singleCol.classList.toggle('col-md-6', !isApp);
                largeCol.classList.toggle('offset-md-3', !isApp);

                const invalid = (!showSide && (selections.layout === 'single_side' || selections.layout === 'dual_side'))
                    || (!showLarge && selections.layout === 'large');
                if (invalid) {
                    document.querySelectorAll('.layout-card').forEach(c => c.classList.remove('selected'));
                    selections.layout = null;
                    document.getElementById('input-layout').value = '';
                    document.getElementById('btn-step-2-continue').disabled = true;
                }
            }

            function goToStep(n) {
                document.querySelectorAll('.banner-wizard .step').forEach(s => {
                    const sn = parseInt(s.dataset.step);
                    s.classList.toggle('completed', sn < n);
                    s.classList.toggle('active', sn === n);
                });
                document.querySelectorAll('.wizard-step').forEach(c => {
                    c.classList.toggle('d-none', parseInt(c.dataset.stepContent) !== n);
                });
                const recap = document.getElementById('wizard-recap');
                if (n === 1) {
                    recap.classList.add('d-none');
                } else {
                    recap.classList.remove('d-none');
                    renderRecap();
                }
            }

            // Stack count badge → modal listing every banner at that spot.
            // Each banner shows in its own highlighted block labelled with its layout;
            // dual banners (shared group_id) share one block with both tiles side by side.
            function openStackModal(records) {
                const modal = document.getElementById('stack-banner-modal');
                const body = modal.querySelector('.stack-modal-body');

                const layoutLabel = (layout) => {
                    switch (layout) {
                        case 'dual': return '{{ __('Dual Banner') }}';
                        case 'single_side': return '{{ __('Side Single Banner') }}';
                        case 'dual_side': return '{{ __('Side Dual Banner') }}';
                        case 'large': return '{{ __('Large Banner') }}';
                        default: return '{{ __('Single Banner') }}';
                    }
                };

                const groups = [];
                const byGroup = {};
                (records || []).forEach(r => {
                    if (r.group_id) {
                        if (!byGroup[r.group_id]) {
                            byGroup[r.group_id] = { items: [] };
                            groups.push(byGroup[r.group_id]);
                        }
                        byGroup[r.group_id].items.push(r);
                    } else {
                        groups.push({ items: [r] });
                    }
                });
                groups.forEach(g => g.items.sort((a, b) => (a.position || 0) - (b.position || 0)));

                let n = 0;
                body.innerHTML = groups.length
                    ? groups.map(g => {
                        const layout = g.items[0] ? g.items[0].layout : 'single';
                        const tiles = g.items.map(r => {
                            n++;
                            return `
                                <div class="stack-modal-tile">
                                    <span class="stack-modal-index">${n}</span>
                                    <img src="${r.image}" alt="">
                                </div>`;
                        }).join('');
                        const spec = bannerSizeSpec(layout);
                        const sizeText = spec ? `${spec.w} x ${spec.h} px` : '';
                        return `
                            <div class="stack-modal-group">
                                <div class="stack-modal-group-head">
                                    <span class="stack-modal-group-label">${layoutLabel(layout)}</span>
                                    ${sizeText ? `<span class="stack-modal-group-size"><i class="ph ph-frame-corners"></i> {{ __('Recommended Size') }} : ${sizeText}</span>` : ''}
                                </div>
                                <div class="stack-modal-group-tiles">${tiles}</div>
                            </div>`;
                    }).join('')
                    : `<div class="stack-modal-empty">{{ __('No banners') }}</div>`;

                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('stack-modal-lock');
            }

            function closeStackModal() {
                const modal = document.getElementById('stack-banner-modal');
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('stack-modal-lock');
            }

            const previewScrollEl = document.getElementById('preview-scroll');
            previewScrollEl.addEventListener('click', function (e) {
                const c = e.target.closest('.prev-stack-count');
                if (!c) return;
                openStackModal(stackRegistry[parseInt(c.dataset.stackId, 10)] || []);
            });
            previewScrollEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                const c = e.target.closest('.prev-stack-count');
                if (!c) return;
                e.preventDefault();
                openStackModal(stackRegistry[parseInt(c.dataset.stackId, 10)] || []);
            });

            const stackModalEl = document.getElementById('stack-banner-modal');
            stackModalEl.addEventListener('click', function (e) {
                if (e.target === stackModalEl || e.target.closest('.stack-modal-close')) {
                    closeStackModal();
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && stackModalEl.classList.contains('open')) {
                    closeStackModal();
                }
            });
        })();

        function successFunction(response) {
            setTimeout(() => {
                window.location.reload();
            }, 500);   
        }
    </script>
@endsection
