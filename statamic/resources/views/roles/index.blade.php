@extends('layout')

@section('content')

    <user-role-listing inline-template v-cloak>

        <div class="listing user-roles-listing">
            <div id="publish-controls" class="head sticky">
                <h1 id="publish-title">{{ translate('cp.nav_user-roles') }}</h1>
                <div class="controls">
                    @can('users:create')
                        <search v-model="keyword"></search>
                        <div class="btn-group">
                            <a href="{{ route('user.role.create') }}" class="btn btn-primary">{{ translate('cp.create_role_button') }}</a>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card flush">
                <template v-if="noItems" v-cloak>
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ translate('cp.roles_empty_heading') }}</h2>
                        <h3>{{ translate('cp.roles_empty') }}</h3>
                        <a href="{{ route('user.role.create') }}" class="btn btn-default btn-lg">{{ translate('cp.create_role_button') }}</a>
                    </div>
                </template>
                <dossier-table v-if="hasItems" :keyword.sync="keyword" :options="tableOptions"></dossier-table>
            </div>
        </div>

    </user-role-listing>

@endsection
