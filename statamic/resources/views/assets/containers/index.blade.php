@extends('layout')

@section('content')

    <asset-container-listing inline-template v-cloak>
        <div>

            <div class="card flush sticky flat-bottom">

                <div class="head">
                    <h1>{{ translate('cp.nav_assets') }}</h1>

                    @can('super')
                        <a href="{{ route('assets.containers.manage') }}" class="btn">{{ translate('cp.manage_asset_containers') }}</a>
                    @endcan
                </div>

                <template v-if="noItems" v-cloak>
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.asset_containers_empty_heading') }}</h2>
                        <h3>{{ trans('cp.asset_containers_empty') }}</h3>
                        @can('super')
                            <a href="{{ route('assets.container.create') }}" class="btn btn-default btn-lg">{{ trans('cp.new_asset_container') }}</a>
                        @endcan
                    </div>
                </template>

            </div>

            <div class="card flush flat-top">
                <dossier-table v-if="hasItems" :options="tableOptions"></dossier-table>
            </div>

        </div>
    </asset-container-listing>

@endsection
