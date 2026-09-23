@extends('layouts.main')
@section('title')
    {{ __('Change Popular Categories Order') }}
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
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('home-screen-section.index') }}">< {{ __('Back to Home Screen Sections') }}</a>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form class="pt-3" id="update-team-member-rank-form"
                            action="{{ route('home-screen-section.popular-categories.change-order') }}"
                            novalidate="novalidate">
                            <ul class="sortable row col-12 d-flex justify-content-center">
                                <div class="row bg-light pt-2 rounded mb-2 col-12 d-flex justify-content-center">
                                    @foreach ($popularCategories as $pc)
                                        <li id="{{ $pc->id }}"
                                            class="ui-state-default draggable col-md-12 col-lg-5 mr-2 col-xl-3"
                                            style="cursor:grab">
                                            <div
                                                class="bg-light pt-2 rounded mb-2 col-12 d-flex justify-content-center">
                                                <div class="row">
                                                    <div class="col-6"
                                                        style="padding-left: 15px; padding-right:5px;">
                                                        @if ($pc->category && $pc->category->image)
                                                            <img src="{{ $pc->category->image }}" alt="image"
                                                                class="order-change" />
                                                        @endif
                                                    </div>
                                                    <div class="col-6 d-flex flex-column justify-content-center align-items-center"
                                                        style="padding-left: 5px; padding-right:5px;">
                                                        <strong>{{ $pc->category->name ?? __('Deleted') }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </div>
                            </ul>
                            <input class="btn btn-primary" type="submit" value="{{ __('Update') }}" />
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
