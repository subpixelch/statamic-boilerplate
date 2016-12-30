@extends('layout')

@section('content')

    <div class="card flush flat-bottom">
        <div class="head">
            <h1>{{ translate('cp.nav_import') }}</h1>
        </div>
    </div>

    <div class="stacks">
        <a href="{{ route('importer', 'statamic') }}" class="stack">
            <h3>Statamic v1</h3>
            <div class="callout">
                <span class="minor">{{ t('import_from_version', ['version' => 'v1']) }}</span>
            </div>
        </a>
    </div>

@endsection
