@extends('layout')

@section('content')

    <publish title="{{ $title }}"
             extra="{{ json_encode($extra) }}"
             :is-new="false"
             slug="{{ $slug }}"
             content-type="{{ $content_type }}"
             content-data="{{ json_encode($content_data) }}"
             fieldset-name="{{ $fieldset }}"
    ></publish>

@endsection
