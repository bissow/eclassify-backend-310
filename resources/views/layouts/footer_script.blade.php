<script type="text/javascript" src="{{ asset('assets/js/apexcharts.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/popper.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/bootstrap.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/js/app.js') }}"></script>

@if($firebaseWebConfigured ?? false)
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"></script>
@endif


{{--Sweet Alert --}}
<script type="text/javascript" src="{{ asset('assets/extensions/sweetalert2/sweetalert2.min.js') }}"></script>

{{--Tiny MCE--}}
<script type="text/javascript" src="{{ asset('assets/extensions/tinymce/tinymce.min.js') }}"></script>

{{--Jquery Vector Map--}}
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-vector-map/jquery-jvectormap-2.0.5.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-vector-map/jquery-jvectormap-asia-merc.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-vector-map/jquery-jvectormap-world-mill-en.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-vector-map/jquery-jvectormap-world-mill.js') }}"></script>

{{--Toastify--}}
<script type="text/javascript" src="{{ asset('assets/extensions/toastify-js/toastify.js') }}"></script>

{{--Parsley--}}
<script type="text/javascript" src="{{ asset('assets/js/parsley.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/pages/parsley.js') }}"></script>

{{--Language Translation--}}
<script src="{{route('common.language.read')}}"></script>


{{--Magnific Popup--}}
<script type="text/javascript" src="{{ asset('assets/extensions/magnific-popup/jquery.magnific-popup.min.js') }}"></script>

{{--Select2--}}
<script type="text/javascript" src="{{ asset('assets/extensions/select2/select2.min.js') }}"></script>

{{--Tagify--}}
<script type="text/javascript" src="{{ asset('assets/extensions/tagify/tagify.js') }}"></script>

{{--Jquery UI--}}
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-ui/jquery-ui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/jquery-ui/jquery.ui.touch-punch.min.js') }}"></script>

{{--Clipboard JS--}}
<script type="text/javascript" src="{{ asset('assets/js/clipboard.min.js') }}"></script>

{{--Filepond--}}
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond.jquery.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond-plugin-image-preview.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond-plugin-pdf-preview.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond-plugin-file-validate-size.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond-plugin-file-validate-type.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/filepond/filepond-plugin-image-validate-size.min.js') }}"></script>

{{--JS Tree--}}
<script src="{{asset("assets/extensions/jstree/jstree.min.js")}}"></script>


{{-- Custom JS --}}
<script type="text/javascript" src="{{ asset('assets/js/custom/common.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom/custom.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom/function.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom/bootstrap-table/formatter.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom/bootstrap-table/queryParams.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/custom/bootstrap-table/actionEvents.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/sidebar-responsive.js') }}"></script>


{{--Bootstrap Table--}}
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/fixed-columns/bootstrap-table-fixed-columns.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/mobile/bootstrap-table-mobile.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/jquery.tablednd.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/bootstrap-table.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('assets/extensions/bootstrap-table/bootstrap-table-reorder-rows.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/extensions/bootstrap-table/export/bootstrap-table-export.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/extensions/bootstrap-table/export/tableExport.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/extensions/bootstrap-table/export/jspdf.umd.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/extensions/bootstrap-table/mobile/bootstrap-table-mobile.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/extensions/bootstrap-table/filter/bootstrap-table-filter-control.min.js')}}"></script>

<script src="{{ asset('assets/js/leaflet.js') }}"></script>
<script src="{{ asset('assets/js/map.js') }}"></script>
<script src="{{ asset('assets/js/bundle.min.js') }}"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script> --}}
{{--<script src="https://harvesthq.github.io/chosen/chosen.jquery.js"></script>--}}
{{--<script src="https://bevacqua.github.io/dragula/dist/dragula.js"></script>--}}
<script type="text/javascript">
    window.baseurl = "{{ URL::to('/') }}/";
    window.ActionIcons = {
        edit: @json(\App\Services\BootstrapTableService::icon('edit')),
        delete: @json(\App\Services\BootstrapTableService::icon('delete')),
        view: @json(\App\Services\BootstrapTableService::icon('view')),
    };
    @if (Session::has('success'))
    showSuccessToast("{{ Session::get('success') }}")
    @endif

    {{--    @if (Session::has('errors'))--}}
    {{--    @if(is_array(Session::get('errors')))--}}
    {{--    @foreach ($errors->all() as $error)--}}

    {{--    showErrorToast("{{ $error }}")--}}
    {{--    @endforeach--}}
    {{--    @else--}}
    {{--    @dd(Session::get('errors'))--}}
    {{--    console.log("{{ Session::get('errors') }}")--}}
    {{--    showErrorToast("{{ Session::get('errors')->message }}")--}}
    {{--    @endif--}}
    {{--    @endif--}}

    @if ($errors->any())
    @foreach ($errors->all() as $error)
    showErrorToast("{!! $error !!}");
    @endforeach
    @endif
    @if (Session::has('error'))
    showErrorToast('{!!  Session::get('error') !!}')
    @endif

</script>
<script>
    // Dynamic translation loading function
    function loadTableTranslations() {
        @php
            $tableKeys = [
                "Search...",
                "Refresh",
                "Toggle",
                "Columns",
                "Detail",
                "Detail Formatter",
                "Previous",
                "Next",
                "First",
                "Last",
                "Showing {ctx.start} to {ctx.end} of {ctx.total} entries",
                "Export Data",
                "Toggle Columns",
                "No description",
                "No image",
                "No date",
                "No price",
                "Active",
                "Inactive",
                "Pending",
                "Approved",
                "Rejected",
                "Published",
                "Draft",
                "No matching records found",
                "rows per page" // Add this new key
            ];

            // Get current language from session or default
            $currentLang = session('locale', config('app.locale', 'en'));

            // Load translations for current language
            $translations = [];
            foreach ($tableKeys as $key) {
                $translations[$key] = __($key);
            }
        @endphp

        window.tableTranslations = @json($translations);

        // Force update all translatable tables
        $('.translatable-table').each(function() {
            const $table = $(this);
            const tableId = $table.attr('id');

            // Update table attributes with new translations
            $table.attr({
                'data-search-placeholder': window.trans('Search...'),
                'data-refresh-text': window.trans('Refresh'),
                'data-toggle-text': window.trans('Toggle'),
                'data-columns-text': window.trans('Columns'),
                'data-detail-view-text': window.trans('Detail'),
                'data-detail-formatter-text': window.trans('Detail Formatter'),
                'data-pagination-pre-text': window.trans('Previous'),
                'data-pagination-next-text': window.trans('Next'),
                'data-pagination-first-text': window.trans('First'),
                'data-pagination-last-text': window.trans('Last'),
                'data-pagination-info-text': window.trans('Showing {ctx.start} to {ctx.end} of {ctx.total} entries'),
                'data-pagination-info-formatted': window.trans('Showing {ctx.start} to {ctx.end} of {ctx.total} entries')
            });

            // Force complete table refresh
            if ($table.hasClass('bootstrap-table')) {
                // Get current table options
                const tableOptions = $table.bootstrapTable('getOptions');

                // Destroy the table
                $table.bootstrapTable('destroy');

                // Re-initialize with new translations
                $table.bootstrapTable(tableOptions);
            }
        });

        // Manually update search placeholder, pagination text, and no records message
        setTimeout(function() {
            updateSearchAndPagination();
            updateNoRecordsMessage();
            updateRowsPerPageText(); // Add this new function call
        }, 500);
    }

    // Function to manually update search and pagination
    function updateSearchAndPagination() {
        // Update search placeholder
        $('.search-input').attr('placeholder', window.trans('Search...'));

        // Update pagination info text
        $('.pagination-info').each(function() {
            const $info = $(this);
            const currentText = $info.text();

            // Extract numbers from current text (e.g., "Showing 1 to 4 of 4 rows")
            const match = currentText.match(/Showing (\d+) to (\d+) of (\d+) rows/);
            if (match) {
                const start = match[1];
                const end = match[2];
                const total = match[3];

                // Replace with translated text
                const translatedText = window.trans('Showing {ctx.start} to {ctx.end} of {ctx.total} entries')
                    .replace('{ctx.start}', start)
                    .replace('{ctx.end}', end)
                    .replace('{ctx.total}', total);

                $info.text(translatedText);
            }
        });

        // Update refresh button title
        $('button[name="refresh"]').attr('title', window.trans('Refresh'));

        // Update columns button title
        $('button[aria-label="Columns"]').attr('title', window.trans('Columns'));

        // Update export button title
        $('button[aria-label="Export data"]').attr('title', window.trans('Export Data'));
    }

    // Function to update "No matching records found" message
    function updateNoRecordsMessage() {
        $('.no-records-found td').each(function() {
            const $cell = $(this);
            const currentText = $cell.text();

            if (currentText === 'No matching records found') {
                $cell.text(window.trans('No matching records found'));
            }
        });
    }

    // Add this new function to handle "rows per page" text
    // function updateRowsPerPageText() {
    //     $('.page-list').each(function() {
    //         const $pageList = $(this);
    //         const currentText = $pageList.text();

    //         if (currentText.includes('rows per page')) {
    //             const translatedText = currentText.replace('rows per page', window.trans('rows per page'));
    //             $pageList.text(translatedText);
    //         }
    //     });
    // }
      function updateRowsPerPageText() {
        $('.page-list').each(function() {
            const $pageList = $(this);

            // Use a more specific selector to find the text after the dropdown
            const $dropdown = $pageList.find('.btn-group');
            if ($dropdown.length) {
                // Get all text nodes after the dropdown
                let found = false;
                $pageList.contents().each(function() {
                    if (this.nodeType === 3 && !found) { // Text node
                        const text = this.textContent.trim();
                        if (text === 'rows per page') {
                            this.textContent = window.trans('rows per page');
                            found = true;
                        }
                    }
                });
            }
        });
    }
    // Translation helper
    window.trans = function(key) {
        return window.tableTranslations && window.tableTranslations[key] ? window.tableTranslations[key] : key;
    };

    // Load translations on page load
    loadTableTranslations();

    // Also update when table is refreshed
    $(document).on('post-body.bs.table', function() {
        setTimeout(function() {
            updateSearchAndPagination();
            updateNoRecordsMessage();
            updateRowsPerPageText(); // Add this new function call
        }, 100);
    });

    // Also update when table data is loaded
    $(document).on('load-success.bs.table', function() {
        setTimeout(function() {
            updateSearchAndPagination();
            updateNoRecordsMessage();
            updateRowsPerPageText(); // Add this new function call
        }, 100);
    });

    // ── Global: keep action-column buttons in a single row across ALL tables ──
    // Bootstrap Table does NOT copy data-field to <td>, so CSS td[data-field] won't
    // work. Instead, find the column index from the <th> and style the matching <td>.
    $(document).on('post-body.bs.table', function(e) {
        var $table = $(e.target);
        $table.find('thead tr:first th').each(function(colIdx) {
            if ($(this).data('field') === 'operate') {
                $table.find('tbody tr').each(function() {
                    $(this).find('td').eq(colIdx).css('white-space', 'nowrap')
                });
                return false; // found the column — stop iterating
            }
        });
    });
</script>
<script>
    // Global Bootstrap tooltip init — opt-in via [data-bs-toggle="tooltip"]
    $(function () {
        const initTooltips = function (root) {
            $(root || document).find('[data-bs-toggle="tooltip"]').each(function () {
                if (!bootstrap.Tooltip.getInstance(this)) {
                    new bootstrap.Tooltip(this, {
                        container: 'body',
                        boundary: 'viewport',
                        trigger: 'hover focus'
                    });
                }
            });
        };
        window.initTooltips = initTooltips;
        initTooltips(document);

        // Re-init after dynamic content render
        $(document).on('shown.bs.modal', function (e) { initTooltips(e.target); });
        $(document).on('post-body.bs.table', function (e) { initTooltips(e.target); });

        // Hide stuck tooltips before DOM mutations (table re-render, modal close)
        $(document).on('pre-body.bs.table hide.bs.modal', function () {
            $('.tooltip').remove();
        });
    });
</script>
<script src="{{ asset('assets/js/custom/table-translations.js') }}"></script>

@if($firebaseWebConfigured ?? false)
<script>
    // Site-wide FCM init so chat notifications show on every admin screen,
    // not only while the admin-chat page's own script is loaded.
    // Pages that care about a message (e.g. admin-chat) listen for the
    // 'fcm:message' / 'fcm:sw-broadcast' window events instead of talking to
    // firebase.messaging() directly, so nothing here is chat-page-specific.
    (function() {
        'use strict';

        if (typeof firebase === 'undefined') return;

        const fcmWebConfig = @json($firebaseWebConfig ?? []);
        let messaging = null;
        let fcmTokenErrorShown = false;

        function registerFcmToken(token) {
            if (!token) return;
            $.ajax({
                url: '{{ route("admin-chat.register-fcm-token") }}',
                method: 'POST',
                data: { fcm_token: token, platform_type: 'web' },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                error: function(xhr) {
                    console.error('Failed to register FCM token:', xhr);
                }
            });
        }

        function getAndRegisterToken(serviceWorkerRegistration) {
            const tokenOptions = { serviceWorkerRegistration: serviceWorkerRegistration };
            if (fcmWebConfig.vapidKey && fcmWebConfig.vapidKey.trim() !== '') {
                tokenOptions.vapidKey = fcmWebConfig.vapidKey;
            }
            messaging.getToken(tokenOptions).then(function(currentToken) {
                if (currentToken) {
                    const storedToken = localStorage.getItem('fcm_token');
                    if (storedToken !== currentToken) {
                        localStorage.setItem('fcm_token', currentToken);
                        registerFcmToken(currentToken);
                    }
                }
            }).catch(function(err) {
                if (err.message && err.message.includes('PERMISSION_DENIED')) {
                    fcmTokenErrorShown = true;
                } else {
                    console.error('FCM getToken error:', err);
                }
            });
        }

        function initFcm() {
            try {
                const firebaseConfig = {
                    apiKey: fcmWebConfig.apiKey,
                    authDomain: window.location.hostname,
                    projectId: fcmWebConfig.projectId,
                    storageBucket: fcmWebConfig.storageBucket,
                    messagingSenderId: fcmWebConfig.messagingSenderId,
                    appId: fcmWebConfig.appId
                };

                if (firebase.apps.length === 0) {
                    firebase.initializeApp(firebaseConfig);
                }

                if (!('serviceWorker' in navigator)) return;

                navigator.serviceWorker.register('/firebase-messaging-sw.js').then(function() {
                    return navigator.serviceWorker.ready;
                }).then(function(serviceWorkerRegistration) {
                    messaging = firebase.messaging();

                    // Foreground messages: broadcast for any page to react to.
                    // admin-chat listens on 'fcm:message' to decide whether to
                    // suppress the popup (chat already open) and refresh its lists;
                    // everywhere else, always show the browser notification.
                    messaging.onMessage(function(payload) {
                        window.dispatchEvent(new CustomEvent('fcm:message', { detail: payload }));

                        const listenerHandled = window.__fcmChatPageActive === true;
                        if (!listenerHandled && 'Notification' in window && Notification.permission === 'granted') {
                            const data = payload.data || (payload.notification && payload.notification.data) || {};
                            const notificationData = payload.notification || {};
                            const title = data.title || notificationData.title || 'New Message';
                            const body = data.body || notificationData.body || 'You have a new chat message';
                            const icon = data.icon || notificationData.icon || '';
                            const notification = new Notification(title, { body: body, icon: icon });
                            notification.onclick = function() {
                                window.focus();
                                notification.close();
                                if (data.click_action) window.location.href = data.click_action;
                            };
                            setTimeout(function() { notification.close(); }, 5000);
                        }
                    });

                    if ('Notification' in window) {
                        const requestOnGesture = function() {
                            Notification.requestPermission().then(function(permission) {
                                if (permission === 'granted' || permission === 'default') {
                                    setTimeout(function() { getAndRegisterToken(serviceWorkerRegistration); }, 500);
                                }
                            });
                            document.removeEventListener('click', requestOnGesture);
                            document.removeEventListener('keydown', requestOnGesture);
                        };
                        if (Notification.permission === 'granted') {
                            setTimeout(function() { getAndRegisterToken(serviceWorkerRegistration); }, 500);
                        } else if (Notification.permission !== 'denied') {
                            document.addEventListener('click', requestOnGesture, { once: true });
                            document.addEventListener('keydown', requestOnGesture, { once: true });
                        }
                    }
                }).catch(function(error) {
                    console.error('Service Worker registration failed:', error);
                });

                // Relay messages the service worker forwards while it handled
                // the push itself (tab backgrounded when the push arrived).
                const broadcastChannel = new BroadcastChannel('firebase-messaging-channel');
                broadcastChannel.addEventListener('message', function(event) {
                    if (event.data && event.data.type === 'chat-message') {
                        window.dispatchEvent(new CustomEvent('fcm:sw-broadcast', { detail: event.data }));
                    }
                });
            } catch (error) {
                console.error('Firebase initialization error:', error);
            }
        }

        initFcm();
    })();
</script>
@endif

