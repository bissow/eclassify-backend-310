@extends('layouts.main')

@section('title')
    {{ __('Video Ads Settings') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>{{ __('Video Ads Settings') }}</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form method="POST" action="{{ route('settings.reel-settings.store') }}" data-parsley-validate class="create-form" data-success-function="formSuccessFunction">
            @csrf
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">{{ __('Video Ads Upload Limits') }}</div>
                        <div class="card-body row">
                            <div class="form-group mt-2 col-lg-6">
                                <label class="form-label" for="reel_max_file_size_mb">
                                    {{ __('Max File Size (MB)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="reel_max_file_size_mb" id="reel_max_file_size_mb"
                                    class="form-control" min="1" max="500" data-parsley-required="true"
                                    value="{{ $settings['reel_max_file_size_mb'] ?? 50 }}">
                                <small class="text-muted">{{ __('Maximum allowed video file size for Video Ad uploads. (1–500 MB)') }}</small>
                            </div>

                            <div class="form-group mt-2 col-lg-6">
                                <label class="form-label" for="reel_max_duration_sec">
                                    {{ __('Max Duration (Seconds)') }} <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="reel_max_duration_sec" id="reel_max_duration_sec"
                                    class="form-control" min="1" max="600" data-parsley-required="true"
                                    value="{{ $settings['reel_max_duration_sec'] ?? 60 }}">
                                <small class="text-muted">{{ __('Maximum allowed Video Ad duration in seconds. (1–600 sec)') }}</small>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        const formSuccessFunction = () => {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    </script>
@endsection
