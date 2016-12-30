@extends('layout')

@section('content')

    <div class="tabs">
        @foreach ($settings as $setting)
            <a href="{{ route('settings.edit', $setting) }}" class="{{ $setting !== $slug ?: 'active' }}">
                {{ translate('cp.settings_'.$setting) }}
            </a>
        @endforeach
    </div>

    <publish title="{{ $title }}"
             extra="{{ json_encode($extra) }}"
             :is-new="false"
             slug="{{ $slug }}"
             content-type="{{ $content_type }}"
             content-data="{{ json_encode($content_data) }}"
             fieldset-name="{{ $fieldset }}"
    ></publish>

@endsection
