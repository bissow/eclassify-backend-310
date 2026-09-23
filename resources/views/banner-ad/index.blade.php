@extends('layouts.main')

@section('title')
    {{ __('Banner Ads') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                @can('banner-ad-create')
                    <a href="{{ route('banner-ad.create') }}" class="btn btn-primary float-end">
                        {{ __('Create Banner Ad') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div id="bannerAdFilters">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_platform">{{ __('Platform') }}</label>
                            <select id="filter_platform" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                <option value="web">{{ __('Web') }}</option>
                                <option value="app">{{ __('App') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_page">{{ __('Page') }}</label>
                            <select id="filter_page" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                <option value="home">{{ __('Homepage') }}</option>
                                <option value="detail">{{ __('Ads Details Page') }}</option>
                                <option value="listing">{{ __('Listing Page') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_layout">{{ __('Layout') }}</label>
                            <select id="filter_layout" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                <option value="single">{{ __('Single') }}</option>
                                <option value="dual">{{ __('Dual') }}</option>
                                <option value="single_side">{{ __('Single Side') }}</option>
                                <option value="dual_side">{{ __('Dual Side') }}</option>
                                <option value="large">{{ __('Large') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filter_ad_type">{{ __('Ad Type') }}</label>
                            <select id="filter_ad_type" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                <option value="only_banner">{{ __('Only Banner') }}</option>
                                <option value="category">{{ __('Category') }}</option>
                                <option value="advertisement">{{ __('Advertisement') }}</option>
                                <option value="external_link">{{ __('External Link') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                @php
                    $cols = [
                        ['field' => 'id', 'title' => __('ID'), 'visible' => false],
                        ['field' => 'images', 'title' => __('Banner Image'), 'escape' => false, 'formatter' => 'bannerImagesFormatter'],
                        ['field' => 'platform', 'title' => __('Platform')],
                        ['field' => 'page', 'title' => __('Page')],
                        ['field' => 'layout', 'title' => __('Layout')],
                        ['field' => 'ad_type', 'title' => __('Ad Type')],
                    ];
                    if (auth()->user()->canany(['banner-ad-update', 'banner-ad-delete'])) {
                        $cols[] = ['field' => 'action', 'title' => __('Action'), 'escape' => false];
                    }
                @endphp
                <x-data-table
                    id="table_list"
                    :url="route('banner-ad.show', 'list')"
                    sort-name="id"
                    sort-order="desc"
                    :extra="['data-query-params' => 'bannerAdQueryParams']"
                    :columns="$cols"
                />
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        $(function () {
            $('#filter_platform, #filter_page, #filter_layout, #filter_ad_type').on('change', function () {
                $('#table_list').bootstrapTable('refresh');
            });
        });

        function bannerAdQueryParams(params) {
            params.platform = $('#filter_platform').val() || '';
            params.page_filter = $('#filter_page').val() || '';
            params.layout = $('#filter_layout').val() || '';
            params.ad_type = $('#filter_ad_type').val() || '';
            return params;
        }
    </script>
@endsection
