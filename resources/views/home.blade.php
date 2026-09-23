@extends('layouts.main')
@section('title')
    {{ __('Home') }}
@endsection
@section('content')

<div class="dash-wrap">
    <div class="dash-welcome">{{ __('Welcome back') }}, {{ Auth::user()->name ?? 'Admin' }}!</div>

    {{-- Top stat cards --}}
    <div class="row g-3 mb-3 top-cards-row">
        @php
            $topCards = [
                ['label' => __('Total Ads'), 'value' => $total_advertisement, 'icon' => 'ph-squares-four',            'cls' => 'ic-bg-1', 'link' => route('advertisement.index')],
                ['label' => __('Featured Ads'),    'value' => $featured_listing,    'icon' => 'ph-rocket-launch',           'cls' => 'ic-bg-2', 'link' => route('advertisement.index', ['featured' => 1])],
                ['label' => __('Active Ads'),      'value' => $active_listing,      'icon' => 'ph-seal-check',              'cls' => 'ic-bg-3', 'link' => route('advertisement.index', ['status' => 'approved'])],
                ['label' => __('Expired Ads'),     'value' => $expired_listing,     'icon' => 'ph-hourglass-high',          'cls' => 'ic-bg-4', 'link' => route('advertisement.index', ['status' => 'expired'])],
                ['label' => __('Sold Ads'),                'value' => $sold_listing,        'icon' => 'ph-handshake',               'cls' => 'ic-bg-5', 'link' => route('advertisement.index', ['status' => 'sold out'])],
            ];
        @endphp
        @foreach($topCards as $c)
            <div class="col-lg col-md-4 col-sm-6">
                <a href="{{ $c['link'] }}" class="stat-card stat-card-link">
                    <div class="stat-icon {{ $c['cls'] }}">
                        <i class="ph {{ $c['icon'] }}"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">{{ $c['label'] }}</div>
                        <div class="stat-value">{{ number_format($c['value']) }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Side stats + Revenue --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            @php
                $sideCards = [
                    ['label' => __('All Users'),        'value' => $user_count,         'icon' => 'ph-users-three',     'cls' => 'ic-bg-side-1', 'link' => route('customer.index')],
                    ['label' => __('Subscriptions'),    'value' => $subscription_count, 'icon' => 'ph-sketch-logo',     'cls' => 'ic-bg-side-2', 'link' => route('package.users.index', ['status' => 'active'])],
                    ['label' => __('Favorite Ads'),     'value' => $favorite_count,     'icon' => 'ph-bookmark-simple', 'cls' => 'ic-bg-side-3', 'link' => route('advertisement.index', ['filter' => 'favorite'])],
                    ['label' => __('Total Reviews'),    'value' => $reviews_count,      'icon' => 'ph-user-gear',       'cls' => 'ic-bg-side-4', 'link' => route('seller-review.index')],
                ];
            @endphp
            <div class="side-grid">
                @foreach($sideCards as $c)
                    <a href="{{ $c['link'] }}" class="stat-card side stat-card-link">
                        <div class="stat-icon {{ $c['cls'] }}">
                            <i class="ph {{ $c['icon'] }}"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-side-label">{{ $c['label'] }}</div>
                            <div class="stat-value">{{ number_format($c['value']) }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-lg-8">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title mb-2" style="font-size:18px;">{{ __('Revenue') }}</div>
                        <div class="revenue-legend">
                            <span><span class="legend-dot" style="background:#1cb8c4;"></span>{{ __('Subscription') }}</span>
                            <span><span class="legend-dot" style="background:#f97362;"></span>{{ __('Feature Ads') }}</span>
                        </div>
                    </div>
                    <select class="revenue-select" id="revenue_range">
                        <option value="week">{{ __('This Week') }}</option>
                        <option value="month">{{ __('This Month') }}</option>
                        <option value="year" selected>{{ __('This Year') }}</option>
                        <option value="all">{{ __('All Time') }}</option>
                    </select>
                </div>
                <div id="revenue_chart" style="min-height:340px;"></div>
            </div>
        </div>
    </div>

    {{-- Recent Ads --}}
    <div class="dash-card mb-3">
        <div class="dash-card-head">
            <div class="dash-card-title mb-0">{{ __('Recent Ads') }}</div>
            <a href="{{ url('advertisement') }}" class="view-all-btn">{{ __('View All') }}</a>
        </div>
        <hr class="dash-title-bottom-line">
        <div class="table-responsive">
            <x-data-table
                id="dash_recent_ads_table"
                :url="route('home.recent-ads')"
                :pagination="false"
                :page-size="5"
                page-list="[5,10,20,50]"
                :search="false"
                :show-columns="false"
                :show-refresh="false"
                :mobile-responsive="false"
                :escape="false"
                sort-name="created_at"
                :caption="__('Recent Ads')"
                caption-id="dash-recent-ads-desc"
                :extra="['data-card-view'=>'false','aria-describedby'=>'dash-recent-ads-desc']"
                :columns="[
                    ['field'=>'image','title'=>__('Image'),'sortable'=>false,'formatter'=>'dashAdImageFormatter'],
                    ['field'=>'name','title'=>__('Name'),'sortable'=>true,'formatter'=>'dashAdNameFormatter'],
                    ['field'=>'user','title'=>__('User'),'sortable'=>false,'formatter'=>'dashAdUserFormatter'],
                    ['field'=>'price','title'=>__('Price'),'sortable'=>true,'formatter'=>'dashAdPriceFormatter'],
                    ['field'=>'category','title'=>__('Category'),'sortable'=>false,'formatter'=>'dashAdCategoryFormatter'],
                    ['field'=>'address','title'=>__('Address'),'sortable'=>true,'formatter'=>'dashAdAddressFormatter'],
                    ['field'=>'created_at','title'=>__('Created'),'sortable'=>true],
                    ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'align'=>'center'],
                ]"
            />
        </div>
    </div>

    {{-- Recent Reports + New Message --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title mb-0">{{ __('Recent Reports') }}</div>
                    <a href="{{ route('report-reasons.user-reports.index') }}" class="view-all-btn">{{ __('View All') }}</a>
                </div>
                <hr class="dash-title-bottom-line">
                <div class="table-responsive">
                    <x-data-table
                        id="dash_recent_reports_table"
                        :url="route('home.recent-reports')"
                        :pagination="false"
                        :page-size="5"
                        page-list="[5,10,20,50]"
                        :search="false"
                        :show-columns="false"
                        :show-refresh="false"
                        :mobile-responsive="false"
                        :escape="false"
                        :caption="__('Recent Reports')"
                        caption-id="dash-recent-reports-desc"
                        :extra="['data-card-view'=>'false','aria-describedby'=>'dash-recent-reports-desc','data-row-style'=>'dashReportRowStyle']"
                        :columns="[
                            ['field'=>'image','title'=>__('Image'),'sortable'=>false,'formatter'=>'dashReportImageFormatter'],
                            ['field'=>'advertisement','title'=>__('Advertisement'),'sortable'=> false,'formatter'=>'dashReportAdNameFormatter'],
                            ['field'=>'advertisement_owner','title'=>__('Item Owner'),'sortable'=>false,'formatter'=>'dashboardReportItemUserProfileFormatter'],
                            ['field'=>'user','title'=>__('User'),'sortable'=>false,'formatter'=>'dashboardReportUserFormatter'],
                            ['field'=>'reason_text','title'=>__('Reason'),'sortable'=>false,'formatter'=>'dashReportReasonFormatter'],
                            ['field'=>'item_active_status','title'=>__('Ad Status'),'sortable'=>false,'formatter'=>'dashReportItemSwitchFormatter'],
                            ['field'=>'action','title'=>__('Action'),'sortable'=>false,'align'=>'center'],
                        ]"
                    />
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title mb-0">{{ __('Recent Chats') }}</div>
                    <a href="{{ route('admin-chat.index') }}" class="view-all-btn">{{ __('View All') }}</a>
                </div>
                <hr class="dash-title-bottom-line">
                @forelse($new_messages as $m)
                    @php
                        $item = optional($m->itemOffer)->item;
                        $chatUser = optional($m->itemOffer)->buyer ?? $m->sender;
                        $chatUrl = route('admin-chat.index');
                        if ($item && $m->item_offer_id) {
                            $chatUrl .= '?product_id=' . $item->id . '&chat_id=' . $m->item_offer_id;
                        }
                    @endphp
                    <a href="{{ $chatUrl }}" class="msg-row text-decoration-none text-reset" style="cursor:pointer;">
                        <div class="msg-avatar-stack">
                            @if(!empty($item) && !empty($item->image))
                                <img src="{{ $item->image }}" class="item-img" alt="" onerror="onErrorImage(event)">
                            @else
                                <span class="item-img"></span>
                            @endif
                            @if(!empty($chatUser) && !empty($chatUser->profile))
                                <img src="{{ $chatUser->profile }}" class="user-img" alt="" onerror="onErrorUserAvatar(event)" data-no-auto-error data-name="{{ $chatUser->name }}">
                            @else
                                <img src="#" class="user-img" alt="" onerror="onErrorUserAvatar(event)" data-no-auto-error data-name="{{ $chatUser->name }}">
                            @endif
                        </div>
                        <div class="flex-grow-1 msg-info">
                            <div class="msg-name">{{ optional($item)->name ?? __('Item') }}</div>
                            <div class="msg-text">{{ optional($chatUser)->name ?? __('User') }}</div>
                        </div>
                        <div class="text-end msg-time-info">
                            @if(!empty($m->unread_count))
                                <div class="unread-msg-time">{{ $m->created_at?->diffForHumans(null, true) }}</div>
                                <span class="msg-badge mt-1">{{ $m->unread_count }}</span>
                            @else
                                <div class="msg-time">{{ $m->created_at?->diffForHumans(null, true) }}</div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="text-center text-muted py-3">{{ __('No messages') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Donut charts --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6 col-xl-3">
            <div class="dash-card">
                <div class="dash-card-title">{{ __('Ads Statistics') }}</div>
                <hr class="dash-title-bottom-line">
                <div id="ads_stats_chart" style="min-height:380px;"></div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="dash-card">
                <div class="dash-card-title">{{ __('Top 5 Categories by Ads') }}</div>
                <hr class="dash-title-bottom-line">
                <div id="ads_per_cat_chart" style="min-height:380px;"></div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="dash-card">
                <div class="dash-card-title">{{ __('Top Sold Items by Category') }}</div>
                <hr class="dash-title-bottom-line">
                <div id="sold_per_cat_chart" style="min-height:380px;"></div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-3">
            <div class="dash-card d-flex flex-column">
                <div>
                    <div class="dash-card-title">{{ __('Customer Statistics') }}</div>
                    <hr class="dash-title-bottom-line">
                </div>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                    <div id="customer_stats_chart" class="w-100" style="min-height:380px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Stats + Top Customer --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title mb-0">{{ __('Top Customer') }}</div>
                    <a href="{{ url('customer') }}" class="view-all-btn">{{ __('View All') }}</a>
                </div>
                <hr class="dash-title-bottom-line">
                <div class="table-responsive">
                    <x-data-table
                        id="dash_top_customers_table"
                        :url="route('home.top-customers')"
                        :pagination="false"
                        :page-size="5"
                        page-list="[5,10,20,50]"
                        :search="false"
                        :show-columns="false"
                        :show-refresh="false"
                        :mobile-responsive="false"
                        :escape="false"
                        sort-name="listing_count"
                        :caption="__('Top Customer')"
                        caption-id="dash-top-customers-desc"
                        :extra="['data-card-view'=>'false','aria-describedby'=>'dash-top-customers-desc']"
                        :columns="[
                            ['field'=>'profile','title'=>__('Profile'),'sortable'=>false,'align'=>'center','formatter'=>'dashTopCustomerProfileFormatter'],
                            ['field'=>'name','title'=>__('Name'),'sortable'=>true],
                            ['field'=>'listing_count','title'=>__('Ads'),'sortable'=>true,'align'=>'center'],
                            ['field'=>'sold_count','title'=>__('Sold Item'),'sortable'=>true,'align'=>'center'],
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Ad Preview Modal --}}
    <div id="recentAdPreviewModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h4 class="modal-title fw-bold">{{ __('Advertisement Details') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="ad-preview-main-image mb-3">
                                <img id="rapMainImg" src="" alt="Advertisement" class="w-100 rounded-3" onerror="onErrorImage(event)">
                            </div>
                            <div class="ad-preview-gallery position-relative mb-4 d-none" id="rapGallery">
                                <div class="ad-gallery-track d-flex gap-2 overflow-auto" id="rapGalleryTrack"></div>
                            </div>
                            <div id="rapDescriptionWrap" class="mb-4 d-none">
                                <h5 class="fw-bold mb-3">{{ __('Description') }}</h5>
                                <p class="text-muted mb-0" id="rapDescription"></p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card shadow-sm border mb-3">
                                <div class="card-body">
                                    <h5 class="fw-bold mb-1" id="rapName"></h5>
                                    <h4 class="text-primary fw-bold mb-2" id="rapPrice"></h4>
                                    <div class="d-flex justify-content-end mb-2">
                                        <small class="text-muted" id="rapAdId"></small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 text-muted small mb-3" id="rapMeta"></div>
                                    <div class="d-flex gap-2" id="rapActions"></div>
                                </div>
                            </div>
                            @can('advertisement-update')
                            <div class="card shadow-sm border mb-3 d-none" id="rapStatusCard">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">{{ __('Change Status') }}</h6>
                                    <form id="rapStatusForm">
                                        @csrf
                                        <input type="hidden" name="id" id="rapStatusId">
                                        <select name="status" class="form-select mb-2" id="rapStatusSelect">
                                            <option value="review">{{ __('Under Review') }}</option>
                                            <option value="approved">{{ __('Approve') }}</option>
                                            <option value="soft rejected">{{ __('Soft Rejected') }}</option>
                                            <option value="permanent rejected">{{ __('Permanent Rejected') }}</option>
                                        </select>
                                        <div id="rapRejectReasonWrap" class="mb-2 d-none">
                                            <label class="form-label mandatory">{{ __('Reason') }}</label>
                                            <textarea name="rejected_reason" id="rapRejectReason" class="form-control" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">{{ __('Save') }}</button>
                                    </form>
                                </div>
                            </div>
                            @endcan
                            <div class="card shadow-sm border mb-3 d-none" id="rapLocationCard">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">{{ __('Location') }}</h6>
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-muted mt-1"></i>
                                        <span id="rapAddress" class="small"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card shadow-sm border mb-3 d-none" id="rapSellerCard">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="rapSellerImg" src="" class="rounded-circle" width="48" height="48" onerror="onErrorImage(event)">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 fw-bold" id="rapSellerName"></h6>
                                            <small class="text-muted" id="rapSellerEmail"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Most Viewed --}}
    <div class="dash-card mb-3">
        <div class="dash-card-title">{{ __('Most Viewed') }}</div>
        <hr class="dash-title-bottom-line">
        <div class="table-responsive">
            <x-data-table
                id="dash_most_viewed_table"
                :url="route('home.most-viewed')"
                :pagination="false"
                :page-size="5"
                page-list="[5,10,20,50]"
                :search="false"
                :show-columns="false"
                :show-refresh="false"
                :mobile-responsive="false"
                :escape="false"
                sort-name="clicks"
                :caption="__('Most Viewed')"
                caption-id="dash-most-viewed-desc"
                :extra="['data-card-view'=>'false','aria-describedby'=>'dash-most-viewed-desc']"
                :columns="[
                    ['field'=>'image','title'=>__('Image'),'sortable'=>false,'formatter'=>'dashAdImageFormatter'],
                    ['field'=>'name','title'=>__('Ad Title'),'sortable'=>true,'formatter'=>'dashMvNameFormatter'],
                    ['field'=>'user','title'=>__('User'),'sortable'=>false,'formatter'=>'dashAdUserFormatter'],
                    ['field'=>'category','title'=>__('Category'),'sortable'=>false,'formatter'=>'dashMvCategoryFormatter'],
                    ['field'=>'clicks','title'=>__('View'),'sortable'=>true,'align'=>'center'],
                    ['field'=>'location','title'=>__('Location'),'sortable'=>false,'formatter'=>'dashMvLocationFormatter'],
                    ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'align'=>'center'],
                ]"
            />
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
// ---- Dashboard bootstrap-table formatters ----
function dashAdImageFormatter(value, row) {
    let src = row.image || '';
    return src
        ? '<img src="' + src + '" class="thumb" alt="" onerror="onErrorImage(event)">'
        : '<span class="thumb"></span>';
}
function dashEscapeHtml(str) {
    return $('<div>').text(str == null ? '' : String(str)).html();
}
function dashAdNameCell(name) {
    name = name || '-';
    return '<div class="ad-name-cell">'
        + '<span class="ad-name-text">' + dashEscapeHtml(name) + '</span>'
        + '<a href="javascript:void(0)" class="ad-name-toggle d-none">{{ __('View more') }}</a>'
        + '</div>';
}
function dashAdNameFormatter(value, row) {
    return dashAdNameCell(row.translated_name || row.name || '-');
}
function dashAdUserFormatter(value, row) {
    let u = row.user || {};
    let img = u.profile
        ? '<img src="' + u.profile + '" class="rounded-circle" width="28" height="28" style="object-fit:cover;" onerror="onErrorUserAvatar(event)" data-no-auto-error data-name="' + u.name + '">'
        : generateInitialAvatar(u.name || '', 28);
    return '<div class="d-flex align-items-center gap-2">' + img + '<span>' + (u.name || '-') + '</span></div>';
}
function dashAdPriceFormatter(value) {
    let n = parseFloat(value || 0);
    return isNaN(n) ? '-' : n.toLocaleString(undefined, { maximumFractionDigits: 0 });
}
function dashAdCategoryFormatter(value, row) {
    let c = row.category || {};
    return c.translated_name || c.name || '-';
}
function dashAdAddressFormatter(value, row) {
    let parts = [row.address, row.city, row.country].filter(Boolean);
    let s = parts.join(', ') || '-';
    return s.length > 30 ? s.substring(0, 30) + '…' : s;
}

function dashboardReportItemUserProfileFormatter(value, row, index) {
    if (!row.item) return '-';
    const profile = row.item.user?.profile ?? '';
    const name    = row.item.user?.name    ?? '-';
    const isDeleted = row.item.is_user_deleted ?? false;
    const deletedBadge = isDeleted
        ? '<span class="badge bg-danger ms-1" style="font-size: 0.65em; vertical-align: middle; letter-spacing: 0.5px;">DELETED</span>'
        : '';
    const avatarHtml = profile
        ? `<img src="${profile}" onerror="onErrorUserAvatar(event,28)" height=28 width=28 data-no-auto-error data-name="${name}" class="rounded-circle flex-shrink-0" style="object-fit:cover;">`
        : generateInitialAvatar(name, 28);
    return `<div class="d-flex align-items-center gap-2">
                ${avatarHtml}
                <div style="white-space:normal; line-height: 1.4;">
                    <span${isDeleted ? ' style="text-decoration: line-through; color: #999;"' : ''}>${name}</span>
                    ${deletedBadge}
                </div>
            </div>`;
}

function dashboardReportUserFormatter(value, row, index) {
    if (!row.user) return '-';
    const profile = row.user?.profile ?? '';
    const name    = row.user?.name    ?? '-';
    const isDeleted = row.user.is_user_deleted ?? false;
    const deletedBadge = isDeleted
        ? '<span class="badge bg-danger ms-1" style="font-size: 0.65em; vertical-align: middle; letter-spacing: 0.5px;">DELETED</span>'
        : '';
    const avatarHtml = profile
        ? `<img src="${profile}" onerror="onErrorUserAvatar(event,28)" data-no-auto-error data-name="${name}" class="rounded-circle flex-shrink-0" width="40" height="40" style="object-fit:cover;">`
        : generateInitialAvatar(name, 28);
    return `<div class="d-flex align-items-center gap-2">
                ${avatarHtml}
                <div style="white-space:normal; line-height: 1.4;">
                    <span${isDeleted ? ' style="text-decoration: line-through; color: #999;"' : ''}>${name}</span>
                    ${deletedBadge}
                </div>
            </div>`;
}



function dashReportImageFormatter(value, row) {
    let src = (row.item && row.item.image) || '';
    return src ? '<img src="' + src + '" class="thumb" alt="" onerror="onErrorImage(event)">' : '<span class="thumb"></span>';
}
function dashReportAdNameFormatter(value, row) {
    let it = row.item || {};
    let name = it.translated_name || it.name || '-';
    let badge = it.is_item_deleted
        ? ' <span class="badge bg-danger ms-1" style="font-size:0.65em;letter-spacing:0.5px;">DELETED</span>'
        : '';
    let cls = it.is_item_deleted ? ' ad-name-deleted' : '';
    return '<div class="ad-name-cell' + cls + '">'
        + '<span class="ad-name-text">' + dashEscapeHtml(name) + badge + '</span>'
        + '<a href="javascript:void(0)" class="ad-name-toggle d-none">{{ __('View more') }}</a>'
        + '</div>';
}
function dashReportReasonFormatter(value, row) {
    return dashAdNameCell(value || row.reason_text || '-');
}
function dashReportUserFormatter(value, row) {
    return (row.user && row.user.name) || '-';
}
function dashReportItemSwitchFormatter(value, row) {
    if (!row.item || !row.item.id) return '-';
    let hidden = row.item.is_item_deleted || row.item.is_user_deleted;
    let disabled = hidden ? ' disabled' : '';
    let checked = (value && !hidden) ? ' checked' : '';
    let title = hidden ? ' title="{{ __('Ad not visible in frontend') }}"' : '';
    return '<div class="form-check form-switch switch-form-check"' + title + '>'
        + '<input class="form-check-input switch1 update-item-status" id="' + row.item.id + '" type="checkbox" role="switch"' + checked + disabled + '>'
        + '</div>';
}
function dashReportUserSwitchFormatter(value, row) {
    let uid = row.item && (row.item.user_id || (row.item.user && row.item.user.id));
    if (!uid) return '-';
    let disabled = (row.item && row.item.is_user_deleted) ? ' disabled' : '';
    return '<div class="form-check form-switch switch-form-check">'
        + '<input class="form-check-input switch1 update-user-status" id="' + uid + '" type="checkbox" role="switch"' + (value ? ' checked' : '') + disabled + '>'
        + '</div>';
}
function dashReportRowStyle(row, index) {
    if (row.item && (row.item.is_user_deleted || row.item.is_item_deleted)) {
        return { classes: 'deleted-user-row' };
    }
    return {};
}

function dashTopCustomerProfileFormatter(value, row) {
    return row.profile
        ? '<img src="' + row.profile + '" class="thumb rounded-circle flex-shrink-0"  alt="" data-no-auto-error onerror="onErrorUserAvatar(event,28)" data-name="'+row.name+'">'
        : generateInitialAvatar(row.name || '', 28);
}

function dashMvNameFormatter(value, row) {
    return row.translated_name || row.name || '-';
}
function dashMvCategoryFormatter(value, row) {
    let c = row.category || {};
    return c.translated_name || c.name || '-';
}
function dashMvLocationFormatter(value, row) {
    let parts = [row.city, row.state].filter(Boolean);
    return parts.join(', ') || '-';
}

(function () {
    const fmtCurrency = (v) => '$' + (v || 0).toLocaleString();

    // Revenue bar chart with range switch
    const revenueData = @json($revenue);
    const subLabel  = '{{ __('Subscription') }}';
    const featLabel = '{{ __('Feature ads') }}';
    const revenueChart = new ApexCharts(document.querySelector('#revenue_chart'), {
        chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'inherit', stacked: false },
        series: [
            { name: subLabel,  data: revenueData.year.subscription },
            { name: featLabel, data: revenueData.year.feature }
        ],
        colors: ['#1cb8c4', '#f97362'],
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 5,
                borderRadiusApplication: 'end',
                horizontal: false,
                colors: {
                    backgroundBarColors: ['rgba(0,0,0,0.001)'],
                    backgroundBarOpacity: 1
                }
            }
        },
        dataLabels: { enabled: false },
        stroke: { show: false, width: 0 },
        xaxis: {
            categories: revenueData.year.labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#9ca3af', fontSize: '12px' } },
            crosshairs: { show: true, width: 1, stroke: { color: '#e5e7eb', width: 1, dashArray: 4 } },
            tooltip: { enabled: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#9ca3af', fontSize: '12px' },
                formatter: (v) => '$' + Math.round(v)
            }
        },
        grid: { borderColor: '#eef0f3', strokeDashArray: 4, padding: { left: 4, right: 4 } },
        legend: { show: false },
        tooltip: {
            shared: true,
            intersect: false,
            followCursor: true,
            fixed: { enabled: false },
            x: { show: false },
            custom: function ({ series, dataPointIndex }) {
                const s = series[0][dataPointIndex] ?? 0;
                const f = series[1][dataPointIndex] ?? 0;
                return '<div style="background:#1f2937;color:#fff;padding:10px 14px;border-radius:8px;font-size:12px;line-height:1.6;min-width:170px;">'
                    + '<div style="display:flex;justify-content:space-between;gap:18px;"><span>' + subLabel + '</span><span style="font-weight:600;">$' + s.toLocaleString() + '</span></div>'
                    + '<div style="display:flex;justify-content:space-between;gap:18px;"><span>' + featLabel + '</span><span style="font-weight:600;">$' + f.toLocaleString() + '</span></div>'
                    + '</div>';
            }
        }
    });
    revenueChart.render();

    const rangeBarOpts = {
        week:  { columnWidth: '40%', borderRadius: 6 },
        month: { columnWidth: '60%', borderRadius: 4 },
        year:  { columnWidth: '35%', borderRadius: 5 },
        all:   { columnWidth: '60%', borderRadius: 4 }
    };

    document.getElementById('revenue_range').addEventListener('change', function (e) {
        const range = e.target.value;
        const r = revenueData[range];
        const opts = rangeBarOpts[range];
        revenueChart.updateOptions({
            xaxis: { categories: r.labels },
            plotOptions: { bar: { columnWidth: opts.columnWidth, borderRadius: opts.borderRadius, borderRadiusApplication: 'end', horizontal: false } },
            series: [
                { name: subLabel,  data: r.subscription },
                { name: featLabel, data: r.feature }
            ]
        });
    });

    const donutCommon = (labels, data, colors, fullLegend = false) => {
        return {
        chart: { type: 'donut', height: 380 },
        series: data,
        labels: labels,
        colors: colors,
        dataLabels: { enabled: false },
        legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '14px', height: 96,
            markers: { width: 10, height: 10, radius: 12 },
            formatter: (seriesName) => {
                const s = String(seriesName || '');
                if (fullLegend) return s;
                return s.length > 16 ? s.substring(0, 15) + '…' : s;
            }
        },
        plotOptions: { pie: { donut: { size: '72%', labels: {
            show: true,
            name: {
                show: true,
                fontSize: '20px',
                fontWeight: 600,
                offsetY: -6,
                formatter: (val) => {
                    const s = String(val || '');
                    return s.length > 14 ? s.substring(0, 13) + '…' : s;
                }
            },
            value: { show: true, fontSize: '20px', fontWeight: 500, offsetY: 4 },
            total: { show: true, label: 'Total', fontSize: '12px', formatter: (w) => {
                const total = w.globals.seriesTotals.reduce((a,b) => a+b, 0);
                return total >= 1000 ? (total/1000).toFixed(2) + 'k' : total;
            }}
        }}}},
        stroke: { width: 2 }
    };
    };

    // Sync donut center label/value when hovering legend items (ApexCharts only does this on slice hover by default)
    const bindLegendHover = (el, chart, labels, data) => {
        const series = el.querySelectorAll('.apexcharts-legend-series');
        const labelEl = () => el.querySelector('.apexcharts-datalabel-label');
        const valueEl = () => el.querySelector('.apexcharts-datalabel-value');
        const total = data.reduce((a, b) => a + b, 0);
        series.forEach((node, i) => {
            node.addEventListener('mouseenter', () => {
                const l = labelEl(), v = valueEl();
                if (l) l.textContent = String(labels[i]).length > 14 ? String(labels[i]).substring(0, 13) + '…' : labels[i];
                if (v) v.textContent = data[i];
            });
            node.addEventListener('mouseleave', () => {
                const l = labelEl(), v = valueEl();
                if (l) l.textContent = 'Total';
                if (v) v.textContent = total >= 1000 ? (total / 1000).toFixed(2) + 'k' : total;
            });
        });
    };

    const EMPTY_IMG = "{{ asset('assets/images/chats/chat-section-emtpy.png') }}";
    const chartEmptyHtml = (title, subtitle) =>
        '<div class="chart-empty-state">'
        + '<img src="' + EMPTY_IMG + '" alt="' + title + '" class="chart-empty-img" onerror="onErrorImage(event)">'
        + '<div class="chart-empty-title">' + title + '</div>'
        + '<div class="chart-empty-subtitle">' + subtitle + '</div>'
        + '</div>';

    const isEmptyData = (data) =>
        !Array.isArray(data) || data.length === 0 || data.every((v) => !v || Number(v) === 0);

    const mountDonut = (sel, labels, data, colors, fullLegend = false, empty = null) => {
        const el = document.querySelector(sel);
        if (isEmptyData(data) && empty) {
            el.innerHTML = chartEmptyHtml(empty.title, empty.subtitle);
            return null;
        }
        const chart = new ApexCharts(el, donutCommon(labels, data, colors, fullLegend));
        chart.render().then(() => bindLegendHover(el, chart, labels, data));
        return chart;
    };

    mountDonut('#ads_stats_chart', @json($ads_stats_labels), @json($ads_stats_data),
        ['#4f86f7', '#22c55e', '#3b82f6', '#fb923c', '#ef4444', '#9ca3af', '#f59e0b'], false, {
            title: "{{ __('No Ads Data Available') }}",
            subtitle: "{{ __('Advertisement statistics will appear here once listings become active on the platform.') }}"
        });

    mountDonut('#ads_per_cat_chart', @json($ads_per_cat_labels), @json($ads_per_cat_data),
        ['#4f86f7', '#22c55e', '#22c55e', '#fb923c', '#ec4899', '#9ca3af', '#a78bfa'], false, {
            title: "{{ __('No Category Data Found') }}",
            subtitle: "{{ __('Category-wise advertisement insights will appear once ads are posted across categories.') }}"
        });

    mountDonut('#sold_per_cat_chart', @json($sold_per_cat_labels), @json($sold_per_cat_data),
        ['#22c55e', '#4f86f7', '#3b82f6', '#fb923c', '#22c55e', '#ec4899', '#9ca3af'], false, {
            title: "{{ __('No Sold Item Analytics Available') }}",
            subtitle: "{{ __('Sold item statistics will be displayed after completed transactions are recorded.') }}"
        });

    mountDonut('#customer_stats_chart', @json($customer_stats_labels), @json($customer_stats_data),
        ['#22c55e', '#4f86f7'], true, {
            title: "{{ __('No Customer Statistics Available') }}",
            subtitle: "{{ __('Customer insights and engagement data will appear once platform activity is available.') }}"
        });
})();

// Recent Ads — view modal
$(document).on('click', '.recent-ad-view', function (e) {
    e.preventDefault();
    let row;
    try { row = JSON.parse($(this).attr('data-row')); } catch (err) { return; }
    if (!row) return;

    let mainImg = row.image || '';
    $('#rapMainImg').attr('src', mainImg);

    let imgs = [];
    if (mainImg) imgs.push(mainImg);
    if (row.gallery_images && row.gallery_images.length) {
        row.gallery_images.forEach(function (g) {
            if (g.image && g.image !== mainImg) imgs.push(g.image);
        });
    }
    let thumbHtml = '';
    imgs.forEach(function (src, i) {
        thumbHtml += `<img src="${src}" class="ad-gallery-thumb ${i === 0 ? 'active' : ''}" onerror="onErrorImage(event)" style="width:64px;height:64px;object-fit:cover;border-radius:6px;cursor:pointer;">`;
    });
    $('#rapGalleryTrack').html(thumbHtml);
    $('#rapGallery').toggleClass('d-none', imgs.length <= 1);

    $('#rapName').text(row.name || '');
    let sym = row.currency && row.currency.symbol ? row.currency.symbol : '$';
    let priceDisplay = row.price ? sym + parseFloat(row.price).toFixed(2) : '';
    if (row.min_salary && row.max_salary) {
        priceDisplay = sym + parseFloat(row.min_salary).toFixed(2) + ' - ' + sym + parseFloat(row.max_salary).toFixed(2);
    }
    $('#rapPrice').text(priceDisplay);
    $('#rapAdId').text('Ad id #' + row.id);

    let meta = '';
    if (row.created_at) meta += `<span><i class="far fa-calendar-alt me-1"></i> {{ __('Listed on') }}: ${String(row.created_at).substring(0,10)}</span>`;
    if (row.expiry_date) meta += `<span><i class="far fa-clock me-1"></i> {{ __('Expiry') }}: ${String(row.expiry_date).substring(0,10)}</span>`;
    if (row.clicks !== undefined) meta += `<span><i class="far fa-eye me-1"></i> {{ __('Views') }}: ${row.clicks || 0}</span>`;
    if (row.likes !== undefined) meta += `<span><i class="far fa-heart me-1"></i> {{ __('Favorites') }}: ${row.likes || 0}</span>`;
    if (row.status) meta += `<span><i class="fas fa-info-circle me-1"></i> {{ __('Status') }}: ${row.status}</span>`;
    $('#rapMeta').html(meta);

    let actions = '';
    @can('advertisement-update')
        actions += `<a href="{{ url('advertisement') }}/${row.id}/edit" class="btn btn-primary flex-fill">{{ __('Edit') }}</a>`;
    @endcan
    @can('advertisement-delete')
        actions += `<a href="{{ url('advertisement') }}/${row.id}" class="btn btn-danger flex-fill delete-form-reload">{{ __('Delete') }}</a>`;
    @endcan
    $('#rapActions').html(actions);

    @can('advertisement-update')
    if (!row.deleted_at && row.status !== 'sold out' && row.status !== 'expired') {
        $('#rapStatusId').val(row.id);
        $('#rapStatusSelect').val(row.status === 'approved' ? 'approved' : row.status).trigger('change');
        $('#rapRejectReason').val(row.rejected_reason || '');
        rapToggleRejectReason();
        $('#rapStatusCard').removeClass('d-none');
    } else {
        $('#rapStatusCard').addClass('d-none');
    }
    @endcan

    if (row.description) {
        $('#rapDescription').text(row.description);
        $('#rapDescriptionWrap').removeClass('d-none');
    } else {
        $('#rapDescriptionWrap').addClass('d-none');
    }

    let addrParts = [row.address, row.city, row.state, row.country].filter(Boolean);
    if (addrParts.length) {
        $('#rapAddress').text(addrParts.join(', '));
        $('#rapLocationCard').removeClass('d-none');
    } else {
        $('#rapLocationCard').addClass('d-none');
    }

    if (row.user) {
        $('#rapSellerImg').attr('src', row.user.profile || '');
        $('#rapSellerName').text(row.user.name || '');
        $('#rapSellerEmail').text(row.user.email || '');
        $('#rapSellerCard').removeClass('d-none');
    } else {
        $('#rapSellerCard').addClass('d-none');
    }

    $('#recentAdPreviewModal').modal('show');
});

$(document).on('click', '#rapGalleryTrack .ad-gallery-thumb', function () {
    $('#rapMainImg').attr('src', $(this).attr('src'));
    $('#rapGalleryTrack .ad-gallery-thumb').removeClass('active');
    $(this).addClass('active');
});

// Close modal when delete confirmed (delete-form-reload handles reload)
$(document).on('click', '#rapActions .delete-form-reload', function () {
    $('#recentAdPreviewModal').modal('hide');
});

function rapToggleRejectReason() {
    let s = $('#rapStatusSelect').val();
    $('#rapRejectReasonWrap').toggleClass('d-none', !(s === 'soft rejected' || s === 'permanent rejected'));
}
$(document).on('change', '#rapStatusSelect', rapToggleRejectReason);

$(document).on('submit', '#rapStatusForm', function (e) {
    e.preventDefault();
    let $form = $(this);
    if ($form.data('submitting')) return false;
    $form.data('submitting', true);
    let $btn = $form.find('button[type="submit"]');
    let originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving...') }}');
    $.ajax({
        url: "{{ route('advertisement.approval') }}",
        method: 'POST',
        data: $form.serialize(),
        success: function (response) {
            $('#recentAdPreviewModal').modal('hide');
            showSuccessToast(response.message || "{{ __('Status updated successfully') }}");
            setTimeout(() => window.location.reload(), 800);
        },
        error: function (xhr) {
            showErrorToast(xhr.responseJSON?.message || "{{ __('Something went wrong') }}");
        },
        complete: function () {
            $btn.prop('disabled', false).html(originalHtml);
            $form.data('submitting', false);
        }
    });
});

// Clamp ad titles to 2 lines, reveal "View more" only when text overflows
function dashWireAdNameClamps(scope) {
    $(scope || document).find('.ad-name-text').each(function () {
        var $text = $(this);
        var $toggle = $text.siblings('.ad-name-toggle');
        if ($text.hasClass('expanded')) return;
        if (this.scrollHeight > this.clientHeight + 1) {
            $toggle.removeClass('d-none');
        } else {
            $toggle.addClass('d-none');
        }
    });
}
$(document).on('click', '.ad-name-toggle', function () {
    var $text = $(this).siblings('.ad-name-text').toggleClass('expanded');
    $(this).text($text.hasClass('expanded') ? "{{ __('View less') }}" : "{{ __('View more') }}");
});
$(document).on('post-body.bs.table', '#dash_recent_ads_table, #dash_recent_reports_table', function () {
    dashWireAdNameClamps(this);
});
</script>
@endsection
