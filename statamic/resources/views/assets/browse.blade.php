@extends('layout')

@section('content')

    <asset-browser container="{{ $container->title() }}"
                   uuid="{{ $container->uuid() }}"
                   path="{{ $folder }}">
    </asset-browser>

@endsection