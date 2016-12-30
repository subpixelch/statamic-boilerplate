@extends('layout')

@section('content')

    <configure-asset-container-listing inline-template v-cloak>
        <div>

            <div class="card flush sticky flat-bottom">

                <div class="head">
                    <h1>{{ translate('cp.nav_assets') }}</h1>

                    <a href="{{ route('assets.container.create') }}" class="btn btn-primary">{{ translate('cp.new_asset_container') }}</a>
                </div>

                <template v-if="noItems">
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.asset_containers_empty_heading') }}</h2>
                        <h3>{{ trans('cp.asset_containers_empty') }}</h3>
                        <a href="{{ route('assets.container.create') }}" class="btn btn-default btn-lg">{{ trans('cp.new_asset_container') }}</a>
                    </div>
                </template>

            </div>

            <div class="card flush flat-top">
                <dossier-table v-if="hasItems" :options="tableOptions"></dossier-table>
            </div>

        </div>
    </configure-asset-container-listing>

@endsection
