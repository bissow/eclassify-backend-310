@extends('layouts.main')

@section('title')
    {{ __('Plugin Manager') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12">
                <h4 class="mb-1">@yield('title')</h4>
                <p class="text-muted small mb-0">{{ __('Activate the plugin to enable its features and start using it.') }}</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section plugin-manager-dashboard">
        <div class="card border-0 shadow-sm plugin-list-card">
            <div class="card-body p-0">
                <!-- Tabs & Search Header -->
                <div class="d-flex flex-wrap justify-content-between align-items-center px-4 pt-4 pb-3 plugin-list-header">
                    <ul class="nav underline-tabs" id="pluginTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="installed-tab" data-bs-toggle="tab" data-bs-target="#installed" type="button" role="tab" aria-controls="installed" aria-selected="true">
                                {{ __('Installed Plugin') }} <span class="tab-count">({{ count($modules) }})</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="marketplace-tab" data-bs-toggle="tab" data-bs-target="#marketplace" type="button" role="tab" aria-controls="marketplace" aria-selected="false">
                                {{ __('Available Plugin') }} <span class="tab-count">({{ count($notInstalled) }})</span>
                            </button>
                        </li>
                    </ul>
                    <div class="input-group search-group mt-3 mt-md-0">
                        <span class="input-group-text bg-light border-0 text-muted"><i class="ph-bold ph-magnifying-glass"></i></span>
                        <input type="text" id="pluginSearch" class="form-control bg-light border-0" placeholder="{{ __('Search something...') }}">
                    </div>
                </div>

                <div class="tab-content" id="pluginTabContent">
                    <!-- Installed Plugins Tab -->
                    <div class="tab-pane fade show active" id="installed" role="tabpanel" aria-labelledby="installed-tab">
                        <div id="installed-plugins-list">
                            @forelse ($modules as $module)
                                @php
                                    $license = $module['license'];
                                    $isActive = $license && $license->is_enabled && !$license->revoked;
                                    $statusLabel = !$license ? __('Not Activated') : ($license->revoked ? __('Revoked') : ($license->is_enabled ? __('Active') : __('Disabled')));
                                    $statusClass = !$license ? 'not-activated' : ($license->revoked ? 'revoked' : ($license->is_enabled ? 'active' : 'disabled'));
                                @endphp
                                <div class="plugin-row plugin-row-col" data-slug="{{ strtolower($module['slug']) }}">
                                    <div class="plugin-row-icon">
                                        <i class="ph-bold ph-puzzle-piece fs-5"></i>
                                    </div>
                                    <div class="plugin-row-body">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="plugin-row-name">{{ $module['name'] }}</span>
                                            <span class="plugin-row-status status-{{ $statusClass }}" data-status-for="{{ $module['slug'] }}">{{ $statusLabel }}</span>
                                        </div>
                                        <span class="plugin-row-sub">
                                            {{ $module['slug'] }}@if ($license && $license->version) &middot; v{{ $license->version }}@endif
                                        </span>
                                    </div>
                                    <div class="plugin-row-action">
                                        @if ($license)
                                            <form class="plugin-toggle-form" action="{{ route('plugin-manager.toggle', $module['slug']) }}" method="POST">
                                                {{ csrf_field() }}
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input plugin-toggle-input" type="checkbox" role="switch"
                                                        {{ $license->is_enabled ? 'checked' : '' }}
                                                        data-slug="{{ $module['slug'] }}"
                                                        aria-label="{{ __('Toggle') }} {{ $module['slug'] }}">
                                                </div>
                                            </form>
                                        @else
                                            <a href="{{ route('plugin-manager.install-form', $module['slug']) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                                {{ __('Activate') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <div class="empty-state-icon bg-light text-muted mx-auto rounded-circle mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="ph-bold ph-puzzle-piece fs-1"></i>
                                    </div>
                                    <h5 class="fw-bold">{{ __('No Installed Plugins Found') }}</h5>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Marketplace Tab -->
                    <div class="tab-pane fade" id="marketplace" role="tabpanel" aria-labelledby="marketplace-tab">
                        <div class="marketplace-available-plugin-card">
                            <div>
                                <span id="marketplace-count-label" class="fw-semibold d-block">{{ count($notInstalled) }} {{ __('Plugin Available') }}</span>
                                <span id="marketplace-synced-label" class="text-muted small d-block" @if (!$catalogLastFetched) style="display:none" @endif>
                                    <i class="ph ph-arrow-counter-clockwise me-1"></i>{{ __('Synced') }} <span id="marketplace-synced-when">{{ $catalogLastFetched ? \Illuminate\Support\Carbon::parse($catalogLastFetched)->diffForHumans() : '' }}</span>
                                </span>
                            </div>
                            <div>    
                                <form class="create-form mt-3 mt-sm-0" action="{{ route('plugin-manager.refresh-catalog') }}" method="POST" data-success-function="successFunction">
                                    {{ csrf_field() }}
                                    <button type="submit" class="marketplace-plugin-refresh">
                                        <i class="ph-bold ph-arrows-counter-clockwise me-2"></i>{{ __('Sync Plugin') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="marketplace-list">
                            @forelse ($notInstalled as $item)
                                <div class="plugin-row catalog-row-col" data-slug="{{ strtolower($item->slug) }}" data-name="{{ strtolower($item->name) }}" data-desc="{{ strtolower($item->description) }}">
                                    <div class="plugin-row-icon plugin-row-icon-catalog">
                                        <i class="ph-bold ph-storefront fs-5"></i>
                                    </div>
                                    <div class="plugin-row-body">
                                        <span class="plugin-row-name">{{ $item->name }}</span>
                                        <span class="plugin-row-sub">
                                            {{ $item->slug }}@if ($item->latest_version) &middot; v{{ $item->latest_version }}@endif
                                        </span>
                                    </div>
                                    <div class="plugin-row-action">
                                        <a href="{{ route('plugin-manager.install-form', $item->slug) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                            {{ __('Install Plugin') }}
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <div class="empty-state-icon bg-light text-muted mx-auto rounded-circle mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="ph-bold ph-storefront fs-1"></i>
                                    </div>
                                    <h5 class="fw-bold">{{ __('Marketplace Empty') }}</h5>
                                    <p class="text-muted mb-0">{{ __('No entries found in catalog or catalog has never been refreshed.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <template id="marketplace-empty-template">
                            <div class="text-center py-5">
                                <div class="empty-state-icon bg-light text-muted mx-auto rounded-circle mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="ph-bold ph-storefront fs-1"></i>
                                </div>
                                <h5 class="fw-bold">{{ __('Marketplace Empty') }}</h5>
                                <p class="text-muted mb-0">{{ __('No entries found in catalog or catalog has never been refreshed.') }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        const marketplaceInstallFormBaseUrl = "{{ route('plugin-manager.install-form') }}";

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function renderMarketplaceList(notInstalled) {
            const $list = $('#marketplace-list');

            if (!notInstalled || !notInstalled.length) {
                $list.html($('#marketplace-empty-template').html());
                $('#marketplace-count-label').text("0 {{ __('Plugin Available') }}");
                return;
            }

            const rows = notInstalled.map(function (item) {
                const version = item.latest_version ? ' &middot; v' + escapeHtml(item.latest_version) : '';

                return '<div class="plugin-row catalog-row-col" data-slug="' + escapeHtml(item.slug.toLowerCase()) + '" data-name="' + escapeHtml(item.name.toLowerCase()) + '" data-desc="' + escapeHtml((item.description || '').toLowerCase()) + '">'
                    + '<div class="plugin-row-icon plugin-row-icon-catalog"><i class="ph-bold ph-storefront fs-5"></i></div>'
                    + '<div class="plugin-row-body">'
                    + '<span class="plugin-row-name">' + escapeHtml(item.name) + '</span>'
                    + '<span class="plugin-row-sub">' + escapeHtml(item.slug) + version + '</span>'
                    + '</div>'
                    + '<div class="plugin-row-action">'
                    + '<a href="' + marketplaceInstallFormBaseUrl + '/' + encodeURIComponent(item.slug) + '" class="btn btn-primary btn-sm rounded-pill px-3">{{ __('Install Plugin') }}</a>'
                    + '</div>'
                    + '</div>';
            });

            $list.html(rows.join(''));
            $('#marketplace-count-label').text(notInstalled.length + " {{ __('Plugin Available') }}");
        }

        function successFunction(response) {
            renderMarketplaceList(response.data?.notInstalled);

            if (response.data?.lastFetched) {
                $('#marketplace-synced-when').text('just now');
                $('#marketplace-synced-label').show();
            }
        }

        // Toggle plugin enable/disable — confirm first, then update in place (no page reload).
        $(document).on('click', '.plugin-toggle-input', function (e) {
            e.preventDefault();
            const checkbox = this;
            const slug = checkbox.getAttribute('data-slug');
            const willEnable = checkbox.checked;
            const form = $(checkbox).closest('form.plugin-toggle-form');

            Swal.fire({
                title: "{{ __('Are you sure?') }}",
                text: willEnable
                    ? "{{ __('This will enable the plugin.') }}"
                    : "{{ __('This will disable the plugin.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: willEnable ? "{{ __('Yes, enable it!') }}" : "{{ __('Yes, disable it!') }}",
            }).then((result) => {
                if (!result.isConfirmed) {
                    checkbox.checked = !willEnable;
                    return;
                }

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        const isEnabled = !!response?.data?.is_enabled;
                        checkbox.checked = isEnabled;

                        const statusEl = document.querySelector(`[data-status-for="${slug}"]`);
                        if (statusEl) {
                            statusEl.textContent = isEnabled ? "{{ __('Active') }}" : "{{ __('Disabled') }}";
                            statusEl.classList.remove('status-active', 'status-disabled');
                            statusEl.classList.add(isEnabled ? 'status-active' : 'status-disabled');
                        }

                        showSuccessToast(response.message || "{{ __('Updated successfully') }}");
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.message || "{{ __('Something went wrong') }}");
                    },
                });
            });
        });

        // Real-time Search Filter
        const searchInput = document.getElementById('pluginSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();

                document.querySelectorAll('.plugin-row-col').forEach(row => {
                    const slug = row.getAttribute('data-slug') || '';
                    row.style.display = slug.includes(query) ? '' : 'none';
                });

                document.querySelectorAll('.catalog-row-col').forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const slug = row.getAttribute('data-slug') || '';
                    const desc = row.getAttribute('data-desc') || '';
                    const text = `${name} ${slug} ${desc}`;
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    </script>
@endsection
