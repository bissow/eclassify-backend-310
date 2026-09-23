<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $store->name }} - {{ __('Digital Store & Catalog') }}</title>
    <meta name="description" content="{{ Str::limit($store->description, 160) }}">

    <!-- Open Graph for Social Sharing -->
    <meta property="og:title" content="{{ $store->name }} - {{ __('Digital Store & Catalog') }}">
    <meta property="og:description" content="{{ Str::limit($store->description, 160) }}">
    @if($store->logo)
        <meta property="og:image" content="{{ $store->logo }}">
    @endif
    <meta property="og:type" content="business.business">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <style>
        :root {
            --primary: {{ $qrCode?->primary_color ?: '#00B2CA' }};
            --secondary: {{ $qrCode?->secondary_color ?: '#0F172A' }};
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-card: 16px;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.08);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #090d16;
                --surface: #131b2e;
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --border: #1e293b;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            padding-bottom: 60px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Smart App Banner */
        .app-banner {
            background: linear-gradient(135deg, var(--secondary), #1e293b);
            color: #ffffff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }
        .app-banner-btn {
            background: var(--primary);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Store Header / Hero */
        .store-hero {
            background: var(--surface);
            border-radius: 0 0 var(--radius-card) var(--radius-card);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-top: none;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .store-banner {
            height: 130px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .store-profile-bar {
            padding: 0 20px 20px 20px;
            position: relative;
        }
        .store-logo-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: var(--surface);
            border: 4px solid var(--surface);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-top: -40px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .store-logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .store-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .store-title {
            font-size: 20px;
            font-weight: 800;
        }
        .verified-badge {
            background: #e0f2fe;
            color: #0284c7;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .store-meta {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .action-btn {
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .action-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Location Mismatch Warning Notice */
        .location-alert {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            color: #92400e;
            border-radius: var(--radius-card);
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }
        @media (prefers-color-scheme: dark) {
            .location-alert {
                background: #451a03;
                border-color: #78350f;
                color: #fef3c7;
            }
        }
        .location-alert i {
            font-size: 22px;
            color: #d97706;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .location-alert-content h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .location-alert-content p {
            font-size: 12px;
            line-height: 1.4;
        }

        /* Search & Filter Bar */
        .catalog-controls {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            padding: 14px 16px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        .search-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        .search-input-wrapper {
            flex: 1;
            position: relative;
        }
        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }
        .search-input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text-main);
            font-size: 14px;
            outline: none;
        }
        .search-input:focus {
            border-color: var(--primary);
        }
        .sort-select {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            outline: none;
        }
        .categories-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }
        .categories-scroll::-webkit-scrollbar {
            display: none;
        }
        .cat-pill {
            padding: 6px 14px;
            border-radius: 20px;
            background: var(--bg);
            border: 1px solid var(--border);
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
            transition: all 0.2s;
        }
        .cat-pill.active, .cat-pill:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }

        /* Items Grid */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        @media (min-width: 640px) {
            .items-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (min-width: 860px) {
            .items-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .item-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .item-img-box {
            width: 100%;
            height: 140px;
            background: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .item-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-badge-featured {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(15, 23, 42, 0.85);
            color: #fbbf24;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .item-body {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .item-price {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 6px;
            line-height: 1.35;
        }
        .item-footer {
            margin-top: auto;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Empty State */
        .empty-box {
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius-card);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-box i {
            font-size: 40px;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        /* Footer */
        .catalog-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <!-- App Deep Link Header Banner -->
    <div class="app-banner">
        <div style="display: flex; align-items: center; gap: 8px;">
            @if(!empty($appLogo))
                <img src="{{ $appLogo }}" alt="{{ $appName }}" style="height: 20px; width: auto; object-fit: contain; vertical-align: middle;">
            @else
                <i class="ph ph-device-mobile fs-5"></i>
            @endif
            <span>{{ __('Browse this store catalog on :app App', ['app' => $appName]) }}</span>
        </div>
        <button class="app-banner-btn" onclick="openNativeApp()">
            <i class="ph ph-arrow-square-out"></i> {{ __('Open App') }}
        </button>
    </div>

    <div class="container">
        <!-- Store Hero Card -->
        <div class="store-hero">
            <div class="store-banner" style="{{ $store->banner ? 'background-image: url(' . $store->banner . ');' : '' }}"></div>
            <div class="store-profile-bar">
                <div class="store-logo-wrapper">
                    <img src="{{ $store->logo ?: asset('assets/images/logo/placeholder.png') }}" alt="{{ $store->name }}">
                </div>

                <div class="store-title-row">
                    <h1 class="store-title">{{ $store->name }}</h1>
                    @if($store->is_verified)
                        <span class="verified-badge"><i class="ph-fill ph-check-circle"></i> {{ __('Verified Store') }}</span>
                    @endif
                </div>

                <div class="store-meta">
                    @if($store->city)
                        <span><i class="ph ph-map-pin"></i> {{ $store->city }}{{ $store->state ? ', ' . $store->state : '' }}</span>
                    @endif
                    @if($storeStats['total_reviews'] > 0)
                        <span><i class="ph-fill ph-star" style="color: #eab308;"></i> {{ $storeStats['average_rating'] }} ({{ $storeStats['total_reviews'] }})</span>
                    @endif
                    <span><i class="ph ph-shopping-bag"></i> {{ $storeStats['active_items_count'] }} {{ __('Active Ads') }}</span>
                </div>

                @if($store->description)
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">{{ $store->description }}</p>
                @endif

                <!-- Contact / Actions Row -->
                <div class="action-buttons">
                    @if($store->contact)
                        <a href="tel:{{ $store->contact }}" class="action-btn">
                            <i class="ph ph-phone-call text-primary"></i> {{ __('Call') }}
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', ($store->country_code ?: '') . $store->contact) }}" target="_blank" class="action-btn">
                            <i class="ph-fill ph-whatsapp" style="color: #22c55e;"></i> {{ __('WhatsApp') }}
                        </a>
                    @endif
                    @if($store->latitude && $store->longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $store->latitude }},{{ $store->longitude }}" target="_blank" class="action-btn">
                            <i class="ph ph-navigation-arrow text-info"></i> {{ __('Directions') }}
                        </a>
                    @endif
                    <button type="button" class="action-btn" onclick="shareStore()">
                        <i class="ph ph-share-network"></i> {{ __('Share') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Location Discrepancy Warning Notice -->
        @if($locationWarning['warning'])
            <div class="location-alert" id="locationAlert">
                <i class="ph-fill ph-warning-circle"></i>
                <div class="location-alert-content">
                    <h4>{{ __('Location Notice') }}</h4>
                    <p>{{ $locationWarning['message'] }}</p>
                </div>
            </div>
        @endif

        <!-- Controls / Search & Category Filters -->
        <div class="catalog-controls">
            <form action="" method="GET" id="catalogFilterForm">
                <div class="search-row">
                    <div class="search-input-wrapper">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" name="search" class="search-input" placeholder="{{ __('Search in this store...') }}" value="{{ $search }}">
                    </div>
                    <select name="sort_by" class="sort-select" onchange="document.getElementById('catalogFilterForm').submit()">
                        <option value="newest" {{ $sortBy == 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                        <option value="price_asc" {{ $sortBy == 'price_asc' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                        <option value="price_desc" {{ $sortBy == 'price_desc' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                        <option value="popular" {{ $sortBy == 'popular' ? 'selected' : '' }}>{{ __('Most Popular') }}</option>
                    </select>
                </div>

                @if(count($categories) > 0)
                    <div class="categories-scroll">
                        <a href="{{ request()->fullUrlWithQuery(['category_id' => null]) }}" class="cat-pill {{ empty($categoryId) ? 'active' : '' }}">
                            {{ __('All Items') }}
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ request()->fullUrlWithQuery(['category_id' => $cat->id]) }}" class="cat-pill {{ $categoryId == $cat->id ? 'active' : '' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>

        <!-- Items Grid -->
        @if(count($items) > 0)
            <div class="items-grid">
                @foreach($items as $item)
                    @php
                        $coverImg = $item->image ?: ($item->gallery_images->first()?->image ?? asset('assets/images/logo/placeholder.png'));
                    @endphp
                    <a href="{{ url('/item/' . ($item->slug ?: $item->id)) }}" class="item-card">
                        <div class="item-img-box">
                            <img src="{{ $coverImg }}" alt="{{ $item->name }}" loading="lazy">
                            @if($item->is_feature)
                                <div class="item-badge-featured">
                                    <i class="ph-fill ph-lightning"></i> {{ __('Featured') }}
                                </div>
                            @endif
                        </div>
                        <div class="item-body">
                            <div class="item-price">
                                {{ $currencySymbol }}{{ number_format($item->price, 0) }}
                            </div>
                            <div class="item-title" title="{{ $item->name }}">{{ $item->name }}</div>
                            <div class="item-footer">
                                <span>{{ $item->category?->name ?? __('Ad') }}</span>
                                <span>{{ $item->created_at?->diffForHumans(null, true) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div style="margin-top: 24px; display: flex; justify-content: center;">
                {{ $items->links() }}
            </div>
        @else
            <div class="empty-box">
                <i class="ph ph-package"></i>
                <h3>{{ __('No Items Listed') }}</h3>
                <p>{{ __('This seller has no active ads matching your filters.') }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="catalog-footer">
            @if(!empty($settings['footer_logo_url']))
                <div style="margin-bottom: 8px;">
                    <img src="{{ $settings['footer_logo_url'] }}" alt="{{ $appName }}" style="max-height: 26px; object-fit: contain;">
                </div>
            @endif
            <p>{{ $settings['default_footer_text'] ?: ('Powered by ' . $appName) }}</p>
        </div>
    </div>

    <script>
        function openNativeApp() {
            var deepLink = "{{ $deepLinkUrl }}";
            window.location.href = deepLink;
        }

        function shareStore() {
            if (navigator.share) {
                navigator.share({
                    title: "{{ $store->name }}",
                    text: "{{ __('Check out items from :store on :app', ['store' => $store->name, 'app' => $appName]) }}",
                    url: window.location.href
                }).catch(function () {});
            } else {
                navigator.clipboard.writeText(window.location.href).then(function () {
                    alert("{{ __('Store link copied to clipboard!') }}");
                });
            }
        }

        // Try getting user location silently to check distance if not provided in URL
        @if(!request()->has('lat') && !request()->has('lng'))
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                var url = new URL(window.location.href);
                url.searchParams.set('lat', position.coords.latitude);
                url.searchParams.set('lng', position.coords.longitude);
                window.location.replace(url.toString());
            }, function () {}, { timeout: 4000, maximumAge: 60000 });
        }
        @endif
    </script>
</body>
</html>
