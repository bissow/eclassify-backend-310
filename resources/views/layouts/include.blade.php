@php
    // $lang = Session::get('language');
    // dd($lang);
    
@endphp

@if (empty($lang) || !$lang->rtl)
    {{-- NON RTL CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/main/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/otherpages.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}" />
@else
    {{-- RTL CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/main/rtl.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/otherpages_rtl.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}" />
@endif
{{-- Bootstrap Switch --}}
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-switch-button.min.css') }}">

{{-- Phosphor Icons (local) --}}
<link rel="stylesheet" href="{{ asset('assets/libs/phosphor-icons/regular/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/libs/phosphor-icons/bold/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/libs/phosphor-icons/fill/style.css') }}">

@php
    use App\Services\CachingService;
    $adminColorDB = CachingService::getSystemSettings('admin_primary_color');
    $adminPrimaryColor =  !empty($adminColorDB) ? $adminColorDB : '#00B2CA';
    
    // Convert hex to RGB for rgba usage
    $hex = str_replace('#', '', $adminPrimaryColor);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $rgba = "$r, $g, $b, 0.15";
@endphp

{{-- Dynamic Primary Color Override --}}
<style>
    :root {
        --bs-primary: {{ $adminPrimaryColor }} !important;
        --bs-primary-rgb: {{ $r }}, {{ $g }}, {{ $b }} !important;
        --bs-primary-rgba: {{ $rgba }} !important;
        --bs-blue: {{ $adminPrimaryColor }} !important;
    }
</style>

{{-- Toastify --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/toastify-js/toastify.css') }}">

{{-- Bootstrap Table --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/bootstrap-table/bootstrap-table.min.css') }}"
    type="text/css" />
<link rel="stylesheet"
    href="{{ asset('assets/extensions/bootstrap-table/fixed-columns/bootstrap-table-fixed-columns.min.css') }}"
    type="text/css" />
<link rel="stylesheet" href="{{ asset('assets/extensions/bootstrap-table/bootstrap-table-reorder-rows.css') }}">


{{-- Font Awesome --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/@fortawesome/fontawesome-free/css/all.min.css') }}"
    type="text/css" />

{{-- Magnific Popup --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/magnific-popup/magnific-popup.css') }}">

{{-- Select2 --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/select2/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/extensions/select2/select2-bootstrap-5-theme.min.css') }}" />

{{-- Tagify --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/tagify/tagify.css') }}" type="text/css" />

{{-- Sweet Alert --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/sweetalert2/sweetalert2.min.css') }}" />

{{-- Filepond --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/filepond/filepond.min.css') }}" type="text/css" />
<link rel="stylesheet" href="{{ asset('assets/extensions/filepond/filepond-plugin-image-preview.min.css') }}"
    type="text/css" />
<link rel="stylesheet" href="{{ asset('assets/extensions/filepond/filepond-plugin-pdf-preview.min.css') }}"
    type="text/css" />

{{-- Jquery Vectormap --}}
<link rel="stylesheet" href="{{ asset('assets/css/pages/jquery-jvectormap-2.0.5.css') }}" type="text/css" />

{{-- JS Tree --}}
<link rel="stylesheet" href="{{ asset('assets/extensions/jstree/jstree.min.css') }}" />

{{-- <link href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css" rel="stylesheet"/> --}}
{{-- <link rel="stylesheet" href="{{ url('assets/extensions/chosen.css') }}"/> --}}

<link rel="stylesheet" href="{{ asset('assets/css/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/map.css') }}">
@yield('css')

<script>
    // Function to handle image errors
    function handleImageError(image) {
        image.classList.contains('custom-default-image')
        if (image.getAttribute('data-custom-image') != null) {
            image.src = image.getAttribute('data-custom-image');
        } else if ("{{ $favicon }}") {
            image.src = "{{ $favicon }}";
        } else {
            image.src = "{{ asset('/assets/images/no_image_available.png') }}";
        }
        // console.log('Image failed to load: ' + image.src);
    }

    // Create a MutationObserver to watch for DOM changes
    const observer = new MutationObserver((mutationsList) => {
        mutationsList.forEach((mutation) => {
            if (mutation.addedNodes) {
                mutation.addedNodes.forEach((node) => {
                    // Check if the added node is an image element
                    if (node instanceof HTMLImageElement && !node.hasAttribute('data-no-auto-error')) {
                        node.addEventListener('error', () => {
                            handleImageError(node);
                        });
                    }
                });
            }
        });
    });

    // Start observing changes in the DOM
    observer.observe(document, {
        childList: true,
        subtree: true
    });

    window.defaultProfileImage = "{{ asset('assets/images/default-profile-icon.svg') }}";

    const onErrorImage = (e) => {
        e.target.src = "{{ $favicon }}";
    };

    const onErrorImageSidebarHorizontalLogo = (e) => {
        e.target.src = "{{$company_logo}}";
    };
</script>
