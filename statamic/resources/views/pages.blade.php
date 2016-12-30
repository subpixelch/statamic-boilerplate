@extends('layout')

@section('content')

    <page-tree inline-template v-cloak>
        <div id="pages">

            <div class="card sticky">
                <div class="head clearfix">
                    <h1>{{ translate('cp.nav_pages') }}</h1>

                    <div class="controls">
                        <div class="btn-group" v-if="arePages">
                            <button type="button" class="btn btn-default" v-on:click="expandAll">
                                {{ translate('cp.expand') }}
                            </button>
                            <button type="button" class="btn btn-default" v-on:click="collapseAll">
                                {{ translate('cp.collapse') }}
                            </button>
                            <button type="button" class="btn btn-default" v-on:click="toggleUrls" v-text="translate('cp.show_'+show)">
                            </button>
                        </div>
                        @can('pages:create')
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" @click="createPage('/')">
                                    {{ translate('cp.create_page_button') }}
                                </button>
                            </div>
                        @endcan
                        @can('pages:reorder')
                            <div class="btn-group" v-if="arePages && changed">
                                <button type="button" class="btn btn-secondary" v-if="! saving" @click="save">
                                    {{ translate('cp.save_changes') }}
                                </button>
                                <span class="btn btn-primary btn-has-icon-right disabled" v-if="saving">
                                    {{ translate('cp.saving') }} <i class="icon icon-circular-graph animation-spin"></i>
                                </span>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>

            <div :class="{'page-tree': true, 'show-urls': showUrls}" v-if="arePages">
                <div class="loading" v-if="loading">
                    <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
                </div>

                <div class="saving" v-if="saving">
                    <div class="inner">
                        <i class="icon icon-circular-graph animation-spin"></i> {{ translate('cp.saving') }}
                    </div>
                </div>

                <ul class="tree-home list-unstyled" v-if="!loading">
                    <branch url="/"
                            :home="true"
                            title="{{ array_get($home, 'title') }}"
                            edit-url="{{ route('page.edit') }}"
                            :has-entries="{{ bool_str(array_get($home, 'has_entries')) }}"
                            entries-url="{{ array_get($home, 'entries_url') }}"
                            create-entry-url="{{ array_get($home, 'create_entry_url') }}">
                    </branch>
                </ul>

                <branches :pages="pages" :depth="1"></branches>
            </div>

            <div class="card" v-if="! arePages" v-cloak>
                <div class="no-results">
                    <span class="icon icon-documents"></span>
                    <h2>{{ trans('cp.pages_empty_heading') }}</h2>
                    <h3>{{ trans('cp.pages_empty') }}</h3>
                    @can('pages:create')
                        <a href="{{ route('page.create') }}" class="btn btn-default btn-lg">{{ trans('cp.create_page_button') }}</a>
                        <a href="{{ route('page.edit') }}" class="btn btn-default btn-lg">{{ trans('cp.edit_homepage_button') }}</a>
                    @endcan
                </div>
            </div>

            <create-page></create-page>
            <mount-collection></mount-collection>
        </div>
    </page-tree>

@endsection
