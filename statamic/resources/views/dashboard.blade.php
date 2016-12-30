@extends('layout')
@section('content-class', 'dashboard')

@section('content')
    <div id="publish-controls" class="head">
        <h1 id="publish-title">{{ t('dashboard') }}</h1>
        <div class="controls">
            <div class="btn-group">
                <a href="{{ route('settings.edit', 'cp')}}" class="btn btn-white">{{ t('manage_widgets') }}</a>
            </div>
        </div>
    </div>
    @if (empty($widgets))
        <div class="card flat-bottom">
            <div class="head">
                <h1>{{ translate('cp.dashboard') }}</h1>
            </div>
        </div>
        <div class="card flat-top">
            <a href="{{ route('settings.edit', 'cp') }}" class="btn btn-primary">{{ translate('cp.configure')}}</a>
        </div>
    @else
        <div class="widgets">
            @foreach($widgets as $widget)
                <div class="widget">
                    {!! $widget['html'] !!}
                </div>
            @endforeach
        </div>
    @endif
@stop
