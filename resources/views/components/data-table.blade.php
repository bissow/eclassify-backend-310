@props([
    'id' => 'table_list',
    'url' => null,
    'columns' => [],
    'toolbarId' => null,
    'toolbarSlot' => null,
    'pageSize' => 10,
    'pageList' => '[5, 10, 20, 50, 100, 200]',
    'search' => true,
    'showColumns' => true,
    'showRefresh' => true,
    'showExport' => false,
    'exportFileName' => 'export',
    'exportIgnore' => '["operate"]',
    'exportTypes' => "['pdf','json','xml','csv','txt','sql','doc','excel']",
    'sortName' => 'id',
    'sortOrder' => 'desc',
    'sidePagination' => 'server',
    'filterControl' => false,
    'fixedColumns' => false,
    'fixedNumber' => 1,
    'fixedRightNumber' => 1,
    'statusColumn' => null,
    'tableName' => null,
    'clickToSelect' => false,
    'responsive' => true,
    'mobileResponsive' => true,
    'escape' => true,
    'extra' => [],
    'caption' => null,
    'captionId' => null,
    'pagination' => true,
    'class' => '',
])

@if($toolbarSlot)
    <div id="{{ $toolbarId ?? $id.'-toolbar' }}">{!! $toolbarSlot !!}</div>
@endif

<div class="row">
    <div class="col-12">
        <table
            class="table-borderless table {{ $class }}"
            aria-describedby="dataTable"
            id="{{ $id }}"
            data-toggle="table"
            @if($url) data-url="{{ $url }}" @endif
            data-side-pagination="{{ $sidePagination }}"
            data-pagination="{{ $pagination ? 'true' : 'false' }}"
            data-page-size="{{ $pageSize }}"
            data-page-list="{{ $pageList }}"
            data-search="{{ $search ? 'true' : 'false' }}"
            data-show-columns="{{ $showColumns ? 'true' : 'false' }}"
            data-show-refresh="{{ $showRefresh ? 'true' : 'false' }}"
            data-sort-name="{{ $sortName }}"
            data-sort-order="{{ $sortOrder }}"
            data-pagination-successively-size="3"
            data-trim-on-search="false"
            data-escape="{{ $escape ? 'true' : 'false' }}"
            @if($clickToSelect) data-click-to-select="true" @endif
            @if($responsive) data-responsive="true" @endif
            @if($mobileResponsive) data-mobile-responsive="true" @endif
            @if($tableName) data-table="{{ $tableName }}" @endif
            @if($statusColumn) data-status-column="{{ $statusColumn }}" @endif
            @if($fixedColumns)
                data-fixed-columns="true"
                data-fixed-number="{{ $fixedNumber }}"
                data-fixed-right-number="{{ $fixedRightNumber }}"
            @endif
            @if($filterControl)
                data-filter-control="true"
                @if($toolbarId) data-filter-control-container="#{{ $toolbarId }}" @endif
            @endif
            @if($toolbarId) data-toolbar="#{{ $toolbarId }}" @endif
            @if($showExport)
                data-show-export="true"
                data-export-options='{"fileName": "{{ $exportFileName }}", "ignoreColumn": {{ $exportIgnore }}}'
                data-export-types="{{ $exportTypes }}"
            @endif
            @foreach($extra as $k => $v)
                {{ $k }}="{{ $v }}"
            @endforeach
        >
            @if($caption)
                <caption @if($captionId) id="{{ $captionId }}" @endif class="visually-hidden">{{ $caption }}</caption>
            @endif
            <thead class="thead-dark">
                <tr>
                    @foreach($columns as $c)
                        <th scope="col"
                            @isset($c['field']) data-field="{{ $c['field'] }}" @endisset
                            @isset($c['sortable']) data-sortable="{{ $c['sortable'] ? 'true' : 'false' }}" @endisset
                            @isset($c['formatter']) data-formatter="{{ $c['formatter'] }}" @endisset
                            @isset($c['events']) data-events="{{ $c['events'] }}" @endisset
                            @isset($c['escape']) data-escape="{{ $c['escape'] ? 'true' : 'false' }}" @endisset
                            @isset($c['width']) data-width="{{ $c['width'] }}" @endisset
                            @isset($c['align']) data-align="{{ $c['align'] }}" @endisset
                            @isset($c['visible'])
                                data-visible="{{ $c['visible'] ? 'true' : 'false' }}"
                            @else
                                @if(($c['field'] ?? '') === 'id')
                                    data-visible="false"
                                @endif
                            @endisset
                            @isset($c['switchable']) data-switchable="{{ $c['switchable'] ? 'true' : 'false' }}" @endisset
                            @isset($c['checkbox']) data-checkbox="{{ $c['checkbox'] ? 'true' : 'false' }}" @endisset
                            @isset($c['filterControl']) data-filter-control="{{ $c['filterControl'] }}" @endisset
                            @isset($c['filterName']) data-filter-name="{{ $c['filterName'] }}" @endisset
                            @isset($c['filterData']) data-filter-data="{{ $c['filterData'] }}" @endisset
                            @isset($c['attrs'])
                                @foreach($c['attrs'] as $ak => $av)
                                    {{ $ak }}="{{ $av }}"
                                @endforeach
                            @endisset
                        >{{ $c['title'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            {{ $slot ?? '' }}
        </table>
    </div>
</div>

@if($search)
    <script>
        window.addEventListener('load', function () {
            var q = new URLSearchParams(window.location.search).get('search');
            if (!q || !window.jQuery || !jQuery.fn.bootstrapTable) return;
            jQuery('#{{ $id }}').bootstrapTable('resetSearch', q);
        });
    </script>
@endif
