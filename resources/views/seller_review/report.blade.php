@extends('layouts.main')

@section('title')
    {{ __('Seller Review Reports') }}
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
                            $srCols = [
                                ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                ['field'=>'seller','title'=>__('Seller Name'),'sortable'=>false,'formatter'=> 'sellerProfileFormatter'],
                                ['field'=>'buyer','title'=>__('Buyer Name'),'align'=>'center','sortable'=>false,'formatter'=> 'buyerProfileFormatter'],
                                ['field'=>'item_name','title'=>__('Advertisement'),'sortable'=>false],
                                ['field'=>'ratings','title'=>__('Ratings'),'visible'=>true,'formatter'=>'ratingFormatter'],
                                ['field'=>'review','title'=>__('Review'),'sortable'=>false,'formatter'=>'descriptionFormatter'],
                                ['field'=>'report_status','title'=>__('Report Status'),'sortable'=>true,'filterControl'=>'select','filterData'=>'','formatter'=>'reportStatusFormatter'],
                                ['field'=>'report_reason','title'=>__('Report Reason'),'sortable'=>false],
                                ['field'=>'report_rejected_reason','title'=>__('Report Rejection Reason'),'sortable'=>false],
                            ];
                            if(auth()->user()->canany(['item-update','item-delete'])) {
                                $srCols[] = ['field'=>'operate','title'=>__('Action'),'align'=>'center','sortable'=>false,'events'=>'reviewReportEvents','escape'=>false];
                            }
                            $exportOptions = ['pdf','json','xml','csv','txt','sql','doc','excel'];
                        @endphp
                        <x-data-table
                            id="table_list"
                            :url="route('review-report.show',1)"
                            toolbar-id="filters"
                            click-to-select
                            filter-control
                            fixed-columns
                            table-name="seller_ratings"
                            status-column="deleted_at"
                            :extra="['data-show-export'=>'true','data-export-options'=>'sellerReviewReportExportOptions','data-export-types'=> json_encode($exportOptions)]"
                            :columns="$srCols"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div id="editStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
        aria-hidden="true">
       <div class="modal-dialog">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title" id="myModalLabel1">{{ __('Status') }}</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                   <form class="edit-form" action="" method="POST" data-success-function="updateApprovalSuccess">
                       @csrf
                       <div class="row">
                           <div class="col-md-12">
                               <select name="report_status" class="form-select" id="report_status" aria-label="status">
                                   <option value="" selected>{{__("Select Status")}}</option>
                                   <option value="approved">{{__("Approve")}}</option>
                                   <option value="rejected">{{__("Reject")}}</option>
                               </select>
                           </div>
                       </div>
                       <div id="report_rejected_reason_container" class="col-md-12" style="display: none;">
                           <label for="rejected_reason" class="mandatory form-label">{{ __('Reason') }}</label>
                           <textarea name="report_rejected_reason" id="report_rejected_reason" class="form-control" placeholder={{ __('Reason') }}></textarea>
                       </div>
                       <input type="submit" value="{{__("Save")}}" class="btn btn-primary mt-3">
                   </form>
               </div>
           </div>
       </div>
       <!-- /.modal-content -->
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

        function updateApprovalSuccess() {
            $('#editStatusModal').modal('hide');
        }
    </script>
@endsection
