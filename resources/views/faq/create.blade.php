@extends('layouts.main')
@section('title')
    {{__("FAQ")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        @can('faq-create')
            <div class="row">
                <form class="create-form" action="{{ route('faq.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">{{__("Add FAQ")}}</div>

                            <div class="card-body mt-3">
                                    <ul class="nav nav-tabs" role="tablist">
                                                    @foreach($languages as $index => $language)
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link @if($index === 0) active @endif" data-bs-toggle="tab"
                                                                    data-bs-target="#lang-{{ $language->id }}" type="button" role="tab">
                                                                {{ $language->name }}
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                <div class="tab-content mt-3">
                                                    @foreach($languages as $index => $language)
                                                        <div class="tab-pane fade @if($index === 0) show active @endif"
                                                            id="lang-{{ $language->id }}" role="tabpanel">
                                                            <div class="form-group">
                                                                <label>{{ __('Question') }} ({{ $language->name }}) <span class="text-danger">*</span></label>
                                                                <input type="text" name="question[{{ $language->id }}]" class="form-control"
                                                                    {{ $language->code === 'en' ? 'required' : '' }}>
                                                            </div>
                                                            <div class="form-group mt-2">
                                                                <label>{{ __('Answer') }} ({{ $language->name }}) <span class="text-danger">*</span></label>
                                                                <textarea name="answer[{{ $language->id }}]" class="form-control" rows="4"
                                                                        {{ $language->code === 'en' ? 'required' : '' }}></textarea>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                    <div class="col-md-12 m-2 text-end">
                                        <input type="submit" class="btn btn-primary" value="{{__("Create")}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @endcan
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                @php
                                    $cols = [
                                        ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                        ['field'=>'question','title'=>__('Questions'),'sortable'=>false],
                                        ['field'=>'answer','title'=>__('Answers'),'sortable'=>false,'formatter'=>'descriptionFormatter'],
                                        ['field'=>'operate','title'=>__('Action'),'sortable'=>false,'escape'=>false,'events'=>'faqEvents'],
                                    ];
                                @endphp
                                <x-data-table
                                    id="table_list"
                                    :url="route('faq.show',1)"
                                    click-to-select
                                    fixed-columns
                                    :extra="['data-query-params'=>'queryParams']"
                                    :columns="$cols"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('faq-update')
        <!-- EDIT MODEL MODEL -->
            <div id="editModal" class="modal fade modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="" class="form-horizontal edit-form" enctype="multipart/form-data" method="POST" novalidate>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="faq_id" id="edit_faq_id">

                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Edit FAQ') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    {{-- Language Tabs --}}
                                    <ul class="nav nav-tabs" role="tablist">
                                        @foreach($languages as $index => $language)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link @if($index === 0) active @endif" data-bs-toggle="tab"
                                                        data-bs-target="#edit-lang-{{ $language->id }}" type="button" role="tab">
                                                    {{ $language->name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- Language Tab Panes --}}
                                    <div class="tab-content mt-3">
                                        @foreach($languages as $index => $language)
                                            <div class="tab-pane fade @if($index === 0) show active @endif"
                                                id="edit-lang-{{ $language->id }}" role="tabpanel">
                                                <div class="form-group">
                                                    <label>{{ __('Question') }} ({{ $language->name }})</label>
                                                    <input type="text"
                                                        name="question[{{ $language->id }}]"
                                                        id="edit_question_{{ $language->id }}"
                                                        class="form-control"
                                                        {{ $language->code === 'en' ? 'required' : '' }}>
                                                </div>
                                                <div class="form-group mt-2">
                                                    <label>{{ __('Answer') }} ({{ $language->name }})</label>
                                                    <textarea name="answer[{{ $language->id }}]"
                                                            id="edit_answer_{{ $language->id }}"
                                                            class="form-control"
                                                            rows="4"
                                                            {{ $language->code === 'en' ? 'required' : '' }}></textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">{{ __('Close') }}</button>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('Save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

        @endcan
    </section>
@endsection
