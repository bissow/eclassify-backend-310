@extends('layouts.main')

@section('title')
    {{ __('User Reports') }}
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
    <div class="row">
        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div id="filters">
                                        <div class="row">
                                            <div class="col-12 col-md-6">
                                                <label for="user_filter">{{__("User")}}</label>
                                                <select class="form-control bootstrap-table-filter-control-user_id" id="user_filter">
                                                    <option value="">{{__("All")}}</option>
                                                    @foreach($users as $user)
                                                        <option value="{{$user->id}}">{{$user->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label for="item_filter">{{__("Item")}}</label>
                                                <select class="form-control bootstrap-table-filter-control-item_id" id="item_filter">
                                                    <option value="">{{__("All")}}</option>
                                                    @foreach($items as $item)
                                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <x-data-table
                                        id="table_list"
                                        :url="route('report-reasons.user-reports.show')"
                                        toolbar-id="filters"
                                        click-to-select
                                        filter-control
                                        fixed-columns
                                        show-export
                                        export-file-name="advertisement-package-list"
                                        :extra="['data-query-params'=>'queryParams']"
                                        :columns="[
                                            ['field'=>'id','title'=>__('ID'),'align'=>'center','sortable'=>true],
                                            ['field'=>'reason','title'=>__('Reason'),'align'=>'center','formatter'=>'descriptionFormatter'],
                                            ['field'=>'user.name','title'=>__('User'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'user_name']],
                                            ['field'=>'item.name','title'=>__('Advertisement'),'align'=>'center','sortable'=>true,'attrs'=>['data-sort-name'=>'item_name']],
                                            ['field'=>'item.image','title'=>__('Advertisement Image'),'align'=>'center','sortable'=>false,'formatter'=>'imageFormatter'],
                                            ['field'=>'item_id','title'=>__('Advertisement ID'),'align'=>'center','sortable'=>true,'visible'=>false,'filterControl'=>'select','filterData'=>''],
                                            ['field'=>'user_id','title'=>__('User ID'),'align'=>'center','sortable'=>true,'visible'=>false,'filterControl'=>'select','filterData'=>''],
                                            ['field'=>'item_status','title'=>__('Advertisement Status'),'visible'=>true,'formatter'=>'itemStatusSwitchFormatter'],
                                            ['field'=>'view_ad','title'=>__('View Ad'),'align'=>'center','sortable'=>false,'escape'=>false],
                                        ]"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Ad Preview Modal --}}
    <div id="userReportAdPreviewModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
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
                                <img id="urapMainImg" src="" alt="Advertisement" class="w-100 rounded-3" onerror="onErrorImage(event)">
                            </div>
                            <div class="ad-preview-gallery position-relative mb-4 d-none" id="urapGallery">
                                <div class="ad-gallery-track d-flex gap-2 overflow-auto" id="urapGalleryTrack"></div>
                            </div>
                            <div id="urapDescriptionWrap" class="mb-4 d-none">
                                <h5 class="fw-bold mb-3">{{ __('Description') }}</h5>
                                <p class="text-muted mb-0" id="urapDescription"></p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card shadow-sm border mb-3">
                                <div class="card-body">
                                    <h5 class="fw-bold mb-1" id="urapName"></h5>
                                    <h4 class="text-primary fw-bold mb-2" id="urapPrice"></h4>
                                    <div class="d-flex justify-content-end mb-2">
                                        <small class="text-muted" id="urapAdId"></small>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 text-muted small mb-3" id="urapMeta"></div>
                                    <div class="d-flex gap-2" id="urapActions"></div>
                                </div>
                            </div>
                            <div class="card shadow-sm border mb-3 d-none" id="urapLocationCard">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">{{ __('Location') }}</h6>
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-muted mt-1"></i>
                                        <span id="urapAddress" class="small"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card shadow-sm border mb-3 d-none" id="urapSellerCard">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-3">
                                        <img id="urapSellerImg" src="" class="rounded-circle" width="48" height="48" onerror="onErrorImage(event)">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 fw-bold" id="urapSellerName"></h6>
                                            <small class="text-muted" id="urapSellerEmail"></small>
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
@endsection

@section('script')
<script>
$(document).on('click', '.user-report-ad-view', function (e) {
    e.preventDefault();
    let row;
    try { row = JSON.parse($(this).attr('data-row')); } catch (err) { return; }
    if (!row) return;

    let mainImg = row.image || '';
    $('#urapMainImg').attr('src', mainImg);

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
    $('#urapGalleryTrack').html(thumbHtml);
    $('#urapGallery').toggleClass('d-none', imgs.length <= 1);

    $('#urapName').text(row.name || '');
    let sym = row.currency && row.currency.symbol ? row.currency.symbol : '$';
    let priceDisplay = row.price ? sym + parseFloat(row.price).toFixed(2) : '';
    if (row.min_salary && row.max_salary) {
        priceDisplay = sym + parseFloat(row.min_salary).toFixed(2) + ' - ' + sym + parseFloat(row.max_salary).toFixed(2);
    }
    $('#urapPrice').text(priceDisplay);
    $('#urapAdId').text('Ad id #' + row.id);

    let meta = '';
    if (row.created_at) meta += `<span><i class="far fa-calendar-alt me-1"></i> {{ __('Listed on') }}: ${String(row.created_at).substring(0,10)}</span>`;
    if (row.expiry_date) meta += `<span><i class="far fa-clock me-1"></i> {{ __('Expiry') }}: ${String(row.expiry_date).substring(0,10)}</span>`;
    if (row.clicks !== undefined) meta += `<span><i class="far fa-eye me-1"></i> {{ __('Views') }}: ${row.clicks || 0}</span>`;
    if (row.likes !== undefined) meta += `<span><i class="far fa-heart me-1"></i> {{ __('Favorites') }}: ${row.likes || 0}</span>`;
    if (row.status) meta += `<span><i class="fas fa-info-circle me-1"></i> {{ __('Status') }}: ${row.status}</span>`;
    $('#urapMeta').html(meta);

    let actions = '';
    @can('advertisement-update')
        actions += `<a href="{{ url('advertisement') }}/${row.id}/edit" class="btn btn-primary flex-fill">{{ __('Edit') }}</a>`;
    @endcan
    $('#urapActions').html(actions);

    if (row.description) {
        $('#urapDescription').text(row.description);
        $('#urapDescriptionWrap').removeClass('d-none');
    } else {
        $('#urapDescriptionWrap').addClass('d-none');
    }

    let addrParts = [row.address, row.city, row.state, row.country].filter(Boolean);
    if (addrParts.length) {
        $('#urapAddress').text(addrParts.join(', '));
        $('#urapLocationCard').removeClass('d-none');
    } else {
        $('#urapLocationCard').addClass('d-none');
    }

    if (row.user) {
        $('#urapSellerImg').attr('src', row.user.profile || '');
        $('#urapSellerName').text(row.user.name || '');
        $('#urapSellerEmail').text(row.user.email || '');
        $('#urapSellerCard').removeClass('d-none');
    } else {
        $('#urapSellerCard').addClass('d-none');
    }

    $('#userReportAdPreviewModal').modal('show');
});

$(document).on('click', '#urapGalleryTrack .ad-gallery-thumb', function () {
    $('#urapMainImg').attr('src', $(this).attr('src'));
    $('#urapGalleryTrack .ad-gallery-thumb').removeClass('active');
    $(this).addClass('active');
});
</script>
@endsection
