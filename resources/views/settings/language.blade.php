@extends('layouts.main')

@section('title')
    {{ __('Languages') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first d-flex justify-content-end">
                <button type="button" class="btn btn-primary" id="btn-add-language">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('Add New Language') }}
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">
            {{-- Add Language --}}
            <div class="col-12" id="add-language-section" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ __('Add Language') }}</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12 form-group">
                                    {{-- Form --}}
                                    <form method="POST" action="{{ route('language.store') }}" enctype="multipart/form-data" data-parsley-validate class="create-form" data-success-function="successFunction">
                                    @csrf
                                    <div class="row">
                                        {{-- Language Name --}}
                                        <div class="col-md-12 col-lg-6 col-xl-3 form-group mandatory ">
                                            <label class="form-label text-center">{{ __('Language Name') }}</label>
                                            <input type="text" name="name" value="{{ old('name', '') }}" class="form-control" placeholder="{{ __('Language Name') }}" data-parsley-required="true">
                                        </div>

                                        {{-- English Language Name --}}
                                        <div class="col-md-12 col-lg-6 col-xl-3 form-group mandatory ">
                                            <label class="form-label text-center">{{ __('Language Name') . ' (' . __('in English') . ')' }}</label>
                                            <input type="text" name="name_in_english" value="{{ old('name_in_english', '') }}" class="form-control" placeholder="{{ __('Language Name') . ' (' . __('in English') . ')' }}" data-parsley-required="true">
                                        </div>

                                        {{-- Language Code --}}
                                        <div class="col-md-12 col-lg-6 col-xl-3 form-group mandatory ">
                                            <label class="form-label text-center">{{ __('Language Code') }}</label>
                                            <input type="text" name="code" value="{{ old('code', '') }}" class="form-control" placeholder="{{ __('Language Code') }}" data-parsley-required="true">
                                        </div>

                                        {{-- Country ISO Code --}}
                                        <div class="col-md-12 col-lg-6 col-xl-3 form-group mandatory">
                                            <label for="country_code" class="form-label text-center">{{ __('Country ISO Code') }}</label>
                                            <input type="text" name="country_code" id="country_code" value="{{ old('country_code', '') }}" class="form-control" placeholder="{{ __('Country ISO Code') }}" data-parsley-required="true">
                                            <small class="form-text text-muted">
                                                {{ __('Provide the two-letter ISO country code for a country. Reference:') }}
                                                <a href="https://countrycode.org" target="_blank">CountryCode.org</a>.
                                            </small>
                                        </div>

                                        {{-- Image --}}
                                        <div class="col-sm-12 col-md-12 form-group mandatory">
                                            <label class="form-label ">{{ __('Image') }}</label>
                                            <div class="">
                                                <input class="filepond" type="file" name="image" id="favicon_icon">
                                            </div>
                                        </div>

                                        {{-- Download Template Files --}}
                                        @php
                                            $enLanguage = $languages->where('code', 'en')->first();
                                        @endphp
                                        @if($enLanguage)
                                            <div class="row mt-3 mb-4">
                                                <div class="col-12">
                                                    <div class="divider">
                                                        <div class="divider-text">
                                                            <h6 class="mb-0">{{ __('Download Current Language Files') }}</h6>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 col-md-4 text-center mb-2">
                                                    <a class="btn btn-outline-primary btn-sm w-100" href="{{ route('language.download.json', [$enLanguage->id, 'panel']) }}">
                                                        <i class="bi bi-download me-1"></i> {{ __('Admin Panel') }}
                                                    </a>
                                                </div>
                                                <div class="col-sm-4 col-md-4 text-center mb-2">
                                                    <a class="btn btn-outline-primary btn-sm w-100" href="{{ route('language.download.json', [$enLanguage->id, 'app']) }}">
                                                        <i class="bi bi-download me-1"></i> {{ __('App') }}
                                                    </a>
                                                </div>
                                                <div class="col-sm-4 col-md-4 text-center mb-2">
                                                    <a class="btn btn-outline-primary btn-sm w-100" href="{{ route('language.download.json', [$enLanguage->id, 'web']) }}">
                                                        <i class="bi bi-download me-1"></i> {{ __('Web') }}
                                                    </a>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Admin Panel File --}}
                                        <div class="col-md-12 col-lg-4 form-group">
                                            <label for="panel_file" class="form-label text-center">{{ __('Translated File For Admin Panel') }}</label>
                                            <input type="file" name="panel_file" id="panel_file" class="form-control" accept=".json">
                                        </div>

                                        {{-- App File --}}
                                        <div class="col-md-12 col-lg-4 form-group">
                                            <label for="app_file" class="form-label text-center">{{ __('Translated File For App') }}</label>
                                            <input type="file" name="app_file" id="app_file" class="form-control" accept=".json">
                                        </div>

                                        {{-- Web File --}}
                                        <div class="col-md-12 col-lg-4 form-group">
                                            <label for="web_file" class="form-label text-center">{{ __('Translated File For Web') }}</label>
                                            <input type="file" name="web_file" id="web_file" class="form-control" accept=".json">
                                        </div>
                                    </div>

                                    {{-- RTL toggle --}}
                                    <div class="col-sm-1 col-md-12">
                                        <label class="col-form-label text-center">{{ __('RTL') }}</label>
                                        <div class="form-check form-switch col-12" style='padding-right:12.5rem;'>
                                            <input type="checkbox" name="rtl" value="1" class="form-check-input" id="rtl">
                                        </div>
                                    </div>

                                    <div class="col-sm-12 d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary me-1 mb-1">{{ __('Save') }}</button>
                                    </div>

                                </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                @php
                                    $cols = [
                                        ['field'=>'id','title'=>__('ID'),'sortable'=>true],
                                        ['field'=>'name','title'=>__('Name'),'sortable'=>false],
                                        ['field'=>'name_in_english','title'=>__('Name') . ' (' . __('in English') . ')','sortable'=>false],
                                        ['field'=>'code','title'=>__('Language Code'),'sortable'=>true],
                                        ['field'=>'country_code','title'=>__('Country Code'),'sortable'=>true],
                                        ['field'=>'image','title'=>__('Image'),'sortable'=>false,'formatter'=>'imageFormatter'],
                                        ['field'=>'status','title'=>__('Active'),'sortable'=>true,'formatter'=>'statusSwitchFormatter'],
                                        ['field'=>'operate','title'=>__('Action'),'escape'=>false,'sortable'=>false,'events'=>'languageEvents'],
                                    ];
                                @endphp
                                <x-data-table
                                    id="table_list"
                                    :url="route('language.show', 1)"
                                    table-name="languages"
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
    </section>

    <!-- EDIT MODEL MODEL -->
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="#" class="form-horizontal" id="edit-form" enctype="multipart/form-data" method="POST"
                data-parsley-validate>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1">{{ __('Edit Language') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_is_default_lang" value="0">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_name"
                                            class="form-label col-12">{{ __('Language Name') }}</label>
                                        <input type="text" id="edit_name" class="form-control col-12"
                                            placeholder="{{ __('Name') }}" name="name"
                                            data-parsley-required="true">
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_name_in_english"
                                            class="form-label col-12">{{ __('Language Name') }}({{ __('in English') }})</label>
                                        <input type="text" id="edit_name_in_english" class="form-control col-12"
                                            placeholder="{{ __('Name') }}" name="name_in_english"
                                            data-parsley-required="true">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_code"
                                            class="form-label col-12">{{ __('Language Code') }}</label>
                                        <input type="text" id="edit_code" class="form-control col-12"
                                            placeholder="{{ __('Language Code') }}" name="code"
                                            data-parsley-required="true">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group mandatory">
                                        <label for="edit_code" class="form-label col-12">{{ __('Country Code') }}</label>
                                        <input type="text" id="edit_country_code" class="form-control col-12"
                                            placeholder="{{ __('Country Code') }}" name="country_code"
                                            data-parsley-required="true">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12 form-group">
                                <label class="col-form-label ">{{ __('Image') }}</label>
                                <div class="">
                                    <input class="filepond" type="file" name="image" id="edit_image">
                                </div>
                            </div>
                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="edit_panel_file"
                                            class="form-label col-12">{{ __('File For Admin Panel') }}</label>
                                        <input type="file" id="edit_panel_file" class="form-control col-12"
                                            name="panel_file" accept=".json">
                                        <a id="download_panel_file" href="#" target="_blank"
                                            class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="bi bi-download"></i> {{ __('Download Current') }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="edit_app_file"
                                            class="form-label col-12">{{ __('File For App') }}</label>
                                        <input type="file" id="edit_app_file" class="form-control col-12"
                                            name="app_file" accept=".json">
                                        <a id="download_app_file" href="#" target="_blank"
                                            class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="bi bi-download"></i> {{ __('Download Current') }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label for="edit_web_file"
                                            class="form-label col-12">{{ __('File For Web') }}</label>
                                        <input type="file" id="edit_web_file" class="form-control col-12"
                                            name="web_file" accept=".json">
                                        <a id="download_web_file" href="#" target="_blank"
                                            class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="bi bi-download"></i> {{ __('Download Current') }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 non-default-field">
                                <div class="col-md-12 col-12">
                                    <div class="form-group form-check form-switch">
                                        <label for="edit_rtl" class="form-label col-12">{{ __('RTL') }}</label>
                                        <input type="hidden" value="0" name="rtl" id="edit_rtl">
                                        <input type="checkbox" class="form-check-input status-switch"
                                            id="edit_rtl_switch" aria-label="edit_rtl">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit"
                            class="btn btn-primary waves-effect waves-light">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('btn-add-language');
            var section = document.getElementById('add-language-section');
            if (btn && section) {
                btn.addEventListener('click', function () {
                    if (section.style.display === 'none') {
                        section.style.display = '';
                        btn.innerHTML = '<i class="bi bi-x-lg me-1"></i> {{ __('Cancel') }}';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-danger');
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        section.style.display = 'none';
                        btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> {{ __('Add New Language') }}';
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn-primary');
                    }
                });
            }

            document.addEventListener('change', function (e) {
                if (e.target.matches('#table_list .update-status')) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                }
            });
        });
        function successFunction(response) {
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    </script>
@endsection
