@extends('layout')
@section('content-class', 'publishing')

@section('content')

    <entry-listing inline-template v-cloak
        get="{{ route('entries.get', $collection->path()) }}"
        delete="{{ route('entries.delete') }}"
        reorder="{{ route('entries.reorder') }}"
        sort="{{ $sort }}"
        sort-order="{{ $sort_order }}"
        :reorderable="{{ bool_str($collection->order() === 'number') }}"
        :can-delete="{{ bool_str(\Statamic\API\User::getCurrent()->can('collections:'.$collection->path().':delete')) }}">

        <div class="listing entry-listing">

            <div id="publish-controls" class="head sticky">
                <h1 id="publish-title">{{ $collection->title() }}</h1>
                <div class="controls">
                    @can("collections:{$collection->path()}:create")
                        <template v-if="! reordering">
                            <search v-model="keyword"></search>
                            <div class="btn-group">
                                <button type="button" @click="enableReorder" class="btn btn-secondary" v-if="reorderable">
                                    {{ translate('cp.reorder') }}
                                </button>
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('entry.create', $collection->path()) }}" class="btn btn-primary">{{ translate('cp.create_entry_button') }}</a>
                            </div>
                        </template>
                        <template v-else>
                            <div class="btn-group">
                                <button type="button" @click="cancelOrder" class="btn btn-secondary">
                                    {{ translate('cp.cancel') }}
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" @click="saveOrder" class="btn btn-primary">
                                    {{ translate('cp.save_order') }}
                                </button>
                            </div>
                        </template>
                    @endcan
                </div>
            </div>
            <div class="card flush">
                <template v-if="noItems">
                    <div class="info-block">
                        <span class="icon icon-documents"></span>
                        <h2>{{ trans('cp.entries_empty_heading', ['type' => $collection->title()]) }}</h2>
                        <h3>{{ trans('cp.entries_empty') }}</h3>
                        @can("collections:{$collection->path()}:create")
                            <a href="{{ route('entry.create', $collection->path()) }}" class="btn btn-default btn-lg">{{ trans('cp.create_entry_button') }}</a>
                        @endcan
                    </div>
                </template>
                <dossier-table v-if="hasItems" :keyword.sync="keyword" :options="tableOptions"></dossier-table>
            </div>
        </div>

    </entry-listing>
@endsection
