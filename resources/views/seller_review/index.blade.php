@extends('layouts.main')

@section('title')
    {{ __('Seller Reviews') }}
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
                <div class="row">
                    <div class="col-12">
                        @php
                            $cols = [
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'seller','title'=>__('Seller Name'),'sortable'=>false,'formatter' => 'sellerProfileFormatter'],
                                ['field'=>'buyer','title'=>__('Buyer Name'),'align'=>'center','sortable'=>false,'formatter' => 'buyerProfileFormatter'],
                                ['field'=>'item_name','title'=>__('Advertisement'),'sortable'=>false],
                                ['field'=>'ratings','title'=>__('Ratings'),'visible'=>true,'formatter'=>'ratingFormatter'],
                                ['field'=>'review','title'=>__('Review'),'sortable'=>true,'formatter'=>'descriptionFormatter'],
                            ];
                            $exportOptions = ['pdf','json','xml','csv','txt','sql','doc','excel'];
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('seller-review.show',1)"
                            click-to-select
                            fixed-columns
                            filter-control
                            table-name="seller_ratings"
                            status-column="deleted_at"
                            :extra="['data-show-export'=>'true','data-export-options'=>'sellerReviewReportExportOptions','data-export-types'=> json_encode($exportOptions)]"
                            :columns="$cols"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        window.sellerReviewReportExportOptions = {
            fileName: 'seller-review-report-list',
            ignoreColumn: ['operate'],
            onCellHtmlData: function (cell, row, col, htmlData) {
                var $temp = $('<div>').html(htmlData);

                // Ratings column: convert star icons to numeric value
                if ($temp.find('i.fa-star, i.fa-star-half').length) {
                    var full  = $temp.find('i.fa-star.text-warning').length;
                    var half  = $temp.find('i.fa-star-half').length;
                    return full + (half ? '.5' : '') + ' / 5';
                }

                // Review column: return full text without the "View Less" link
                var $fullDesc = $temp.find('.full-description');
                if ($fullDesc.length) {
                    $fullDesc.find('a').remove();
                    return $fullDesc.text().trim();
                }

                return htmlData;
            }
        };
    </script>
@endsection


