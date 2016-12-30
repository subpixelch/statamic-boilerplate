@extends('layout')

@section('content')

    <configure-globals-listing inline-template v-cloak>
        <div>

            <div class="card flush sticky flat-bottom">

                <div class="head">
                    <h1>{{ translate('cp.nav_globals') }}</h1>

                    <a href="{{ route('globals.create') }}" class="btn btn-primary pull-right">{{ translate('cp.create_global_set_button') }}</a>
                </div>

                <template v-if="noItems">
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.globals_empty_heading') }}</h2>
                        <h3>{{ trans('cp.globals_empty') }}</h3>
                        <a href="{{ route('globals.create') }}" class="btn btn-default btn-lg">{{ trans('cp.create_global_set_button') }}</a>
                    </div>
                </template>

            </div>

            <div class="card flush flat-top">
                <dossier-table v-if="hasItems" :options="tableOptions"></dossier-table>
            </div>

        </div>
    </configure-globals-listing>

@endsection
