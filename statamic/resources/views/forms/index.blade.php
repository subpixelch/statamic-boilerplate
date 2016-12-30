@extends('layout')

@section('content')

    <div class="card flush sticky flat-bottom">

        <div class="head">
            <h1>{{ translate('cp.nav_forms') }}</h1>
            @can('super')
                <a href="{{ route('form.create') }}" class="btn btn-primary">{{ t('create_form') }}</a>
            @endcan
        </div>
        @if(count($forms) == 0)
            <div class="no-results">
                <span class="icon icon-download"></span>
                <h2>{{ trans_choice('cp.forms', 2) }}</h2>
                <h3>{{ trans('cp.forms_empty') }}</h3>
                @can('super')
                    <a href="{{ route('form.create') }}" class="btn btn-default btn-lg">{{ trans('cp.create_form') }}</a>
                @endcan
            </div>
        @endif
    </div>

    @if(count($forms) > 0)
    <div class="stacks">
        @foreach($forms as $form)
            <a href="{{ $form['show_url'] }}" class="stack">
                <h3>{{ $form['title'] }}</h3>
                <div class="callout">
                    <span class="major">{{ $form['count'] }}</span>
                    <span class="minor">{{ trans_choice('cp.response', $form['count']) }}</span>
                </div>
            </a>
        @endforeach
    </div>
    @endif

@endsection
