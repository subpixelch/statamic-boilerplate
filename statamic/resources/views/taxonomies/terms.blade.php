@extends('layout')

@section('content')

    <term-listing inline-template v-cloak
        get="{{ route('terms.get', $group) }}"
        delete="{{ route('terms.delete') }}"
        :can-delete="{{ bool_str(\Statamic\API\User::getCurrent()->can('taxonomies:'.$group.':delete')) }}">

        <div class="listing term-listing">
            <div id="publish-controls" class="head sticky">
                <h1 id="publish-title">{{ $group_title }}</h1>
                <div class="controls">
                    <search v-model="keyword"></search>
                    @can("taxonomies:{$group}:create")
                        <div class="btn-group">
                            <a href="{{ route('term.create', $group) }}" class="btn btn-primary">
                                {{ trans('cp.create_taxonomy_term_button', ['term' => str_singular($group_title)]) }}
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card flush">
                <template v-if="noItems">
                    <div class="no-results">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.taxonomy_terms_empty_heading', ['term' => $group_title]) }}</h2>
                        <h3>{{ trans('cp.taxonomy_terms_empty') }}</h3>
                        @can("taxonomies:{$group}:manage")
                            <a href="{{ route('term.create', $group) }}" class="btn btn-default btn-lg">{{ trans('cp.create_taxonomy_term_button', ['term' => str_singular($group_title)]) }}</a>
                        @endcan
                    </div>
                </template>
                <dossier-table v-if="hasItems" :keyword.sync="keyword" :options="tableOptions"></dossier-table>
            </div>
        </div>

    </term-listing>

@endsection
