@extends('layouts.main')

@section('title')
    {{ __('Change Profile') }}
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="divider">
                    <h4>{{ __('Change Profile') }}</h4>
                </div>
            </div>

            <form method="POST" action="{{ route('change-profile.update') }}" class="create-form-without-reset" enctype="multipart/form-data" data-success-function="successFunction">
            <div class="row mt-1">
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-alert text-center">{{ __('Profile') }}</label>
                        <div class="col-sm-4 cs_field_img ">
                            <input type="file" name="profile" class="image" style="display: none" accept=" .jpg, .jpeg, .png, .svg">
                            @if(!empty(Auth::user()->getRawOriginal('profile')))
                                <img src="{{ Auth::user()->profile }}" alt="" class="img preview-image">
                            @elseif(!empty(Auth::user()->name))
                                <div id="initial-avatar-wrapper">
                                    <x-avatar-initial :name="Auth::user()->name" :size="100" />
                                </div>
                                <img src="" alt="" class="img preview-image" style="display: none;">
                            @else
                                <x-avatar-initial :name="''" :size="100" />
                            @endif
                            <div class='img_input'>{{__("Browse File")}}</div>
                        </div>
                        <div class="img_error" style="color:#DC3545;"></div>
                    </div>

                    <div class="form-group row">
                        <label for="name" class="col-sm-4 col-form-label text-alert text-center">{{ __('Name') }}</label>
                        <div class="col-sm-4">
                            <input type="text" name="name" id="name" class="form-control form-control-lg form-control-solid mb-2" placeholder={{ __('Name') }} value="{{ Auth::user()->name }}" required/>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="{{ __('email') }}" class="col-sm-4 col-form-label text-alert text-center">{{ __('Email') }}</label>
                        <div class="col-sm-4">
                            <input type="email" name="{{ __('email') }}" id="{{ __('email') }}" class="form-control form-control-lg form-control-solid mb-2" placeholder="{{__("Email")}}" value="{{ Auth::user()->email }}" required/>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label text-alert">&nbsp;</label>
                        <div class="col-sm-12 text-end">
                            <button type="submit" name="btnadd" value="btnadd" class="btn btn-primary float-right">{{ __('Change') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let successFunction = () => {
            window.location.reload()
        }
    </script>
@endsection