@extends('layout')

@section('content')

    <fieldset-listing inline-template v-cloak>
        <div>

            <div class="card flush sticky flat-bottom">

                <div class="head">
                    <h1>{{ translate('cp.nav_fieldsets') }}</h1>

                    <a href="{{ route('fieldset.create') }}" class="btn btn-primary">{{ translate('cp.create_fieldset_button') }}</a>
                </div>

                <template v-if="noItems">
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans_choice('cp.fieldsets', 2) }}</h2>
                        <h3>{{ trans('cp.fieldsets_empty') }}</h3>
                        <a href="{{ route('fieldset.create') }}" class="btn btn-default btn-lg">{{ trans('cp.create_fieldset_button') }}</a>
                    </div>
                </template>

            </div>

            <div class="card flush flat-top">
                <dossier-table v-if="hasItems" :options="tableOptions"></dossier-table>
            </div>

        </div>
    </fieldset-listing>

@endsection
