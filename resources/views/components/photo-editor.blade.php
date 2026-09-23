{{-- Reusable Standalone Photo Editor Component for Eclassify --}}
@once
    <link rel="stylesheet" href="{{ asset('assets/css/custom/photo-editor.css') }}">
    <script src="{{ asset('assets/js/custom/photo-editor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.PhotoEditor && typeof window.PhotoEditor.init === 'function') {
                window.PhotoEditor.init();
            }
        });
    </script>
@endonce
