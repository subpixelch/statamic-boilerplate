@extends('layout')
@section('content-class', 'publishing')

@section('content')

    <publish title="{{ $title }}"
             extra="{{ json_encode($extra) }}"
             :is-new="{{ bool_str($is_new) }}"
             content-type="{{ $content_type }}"
             uuid="{{ $uuid }}"
             content-data="{{ json_encode($content_data) }}"
             fieldset-name="{{ $fieldset }}"
             slug="{{ $slug }}"
             uri="{{ $uri }}"
             url="{{ $url }}"
             :status="{{ bool_str($status) }}"
             locale="{{ $locale }}"
             locales="{{ json_encode($locales) }}"
             :is-default-locale="{{ bool_str($is_default_locale) }}"
             title-display-name="{{ isset($title_display_name) ? $title_display_name : t('title') }}"
             :remove-title="true"
    ></publish>

@endsection
