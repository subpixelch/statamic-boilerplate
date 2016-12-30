@extends('layout')

@section('content')

    <importer inline-template importer="{{ $importer->name() }}">

        <div class="card">
            <div class="head">
                <h1>Import from {{ $importer->title() }}</h1>
            </div>
        </div>

        <div class="card" v-if="loading">
            <div class="loading">
                <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
            </div>
        </div>

        <template v-if="!loading && !exporting && !exported && !exportFailed">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('export') }}</h1>
                </div>
            </div>
            <div class="card flat-top flat-bottom">
                @{{{ instructions }}}
                <br>
                <div class="form-group">
                    <label>{{ t('site_url') }}</label>
                    <small class="help-block">{{ t('import_site_url_instructions') }}</small>
                    <input type="text" class="form-control" v-model="siteUrl" />
                </div>
                <button class="btn btn-primary" @click.prevent="export">{{ t('export') }}</button>
            </div>
        </template>

        <template v-if="exporting">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('exporting') }}</h1>
                </div>
            </div>
            <div class="card flat-top">
                <div class="loading loading-basic">
                    <span class="icon icon-circular-graph animation-spin"></span> {{ t('please_wait') }}
                </div>
            </div>
        </template>

        <template v-if="exportFailed">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('exporting_failed') }}</h1>
                </div>
            </div>
            <div class="card flat-top text-danger">
                <p>{{ t('export_failed_with_error') }}</p>
                <p>@{{ exportError }}</p>
            </div>
        </template>

        <template v-if="exported && !importing && !imported">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('import_summary') }}</h1>
                </div>
            </div>
            <div class="card flat-top flat-bottom">
                <h2>{{ trans_choice('cp.pages', 2) }}</h2>
                <p>
                    @{{ totalPages }} pages.
                    <a href="#" @click.prevent="showAllPages = true" v-if="!showAllPages">{{ t('show') }}</a>
                    <a href="#" @click.prevent="showAllPages = false" v-else>{{ t('hide') }}</a>
                </p>

                <table v-if="showAllPages">
                    <thead>
                        <th></th>
                        <th>URL</th>
                    </thead>
                    <tbody>
                        <tr v-for="(i, page) in summary.pages">
                            <td class="checkbox-col">
                                <input type="checkbox" v-model="page._checked" id="page-@{{ i }}" />
                                <label for="page-@{{ i }}"></label>
                            </td>
                            <td>@{{ page.url }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-for="(collectionName, collection) in summary.collections" class="card flat-top flat-bottom">
                <h2>Collection: @{{ collectionName }}</h2>
                <div class="form-group">
                    <label>{{ t('route') }}</label>
                    <input type="text" v-model="collection.route" class="form-control" />
                </div>
                <div class="form-group">
                    <label>{{ trans_choice('entries', 2) }}</label>
                    <p>
                        @{{ size(collection.entries) }} entries.
                        <a href="#" @click.prevent="showCollection(collectionName)" v-if="!shouldShowCollection(collectionName)">{{ t('show') }}</a>
                        <a href="#" @click.prevent="hideCollection(collectionName)" v-else>{{ t('hide') }}</a>
                    </p>
                </div>
                <table v-if="shouldShowCollection(collectionName)">
                    <thead>
                        <th></th>
                        <th>{{ t('slug') }}</th>
                    </thead>
                    <tbody>
                        <tr v-for="(slug, entry) in collection.entries">
                            <td class="checkbox-col">
                                <input type="checkbox" v-model="entry._checked" id="c-@{{ collectionName }}-@{{ slug }}" />
                                <label for="c-@{{ collectionName }}-@{{ slug }}"></label>
                            </td>
                            <td>@{{ entry.slug }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-for="(taxonomyName, taxonomy) in summary.taxonomies" class="card flat-top flat-bottom">
                <h2>Taxonomy: @{{ taxonomyName }}</h2>
                <div class="form-group">
                    <label>{{ t('route') }}</label>
                    <input type="text" v-model="taxonomy.route" class="form-control" />
                </div>
                <div class="form-group">
                    <label>{{ trans_choice('cp.terms', 2) }}</label>
                    <p>
                        @{{ size(taxonomy.terms) }} terms.
                        <a href="#" @click.prevent="showTaxonomy(taxonomyName)" v-if="!shouldShowTaxonomy(taxonomyName)">{{ t('show') }}</a>
                        <a href="#" @click.prevent="hideTaxonomy(taxonomyName)" v-else>{{ t('hide') }}</a>
                    </p>
                </div>
                <table v-if="shouldShowTaxonomy(taxonomyName)">
                    <thead>
                        <th></th>
                        <th>{{ t('slug') }}</th>
                    </thead>
                    <tbody>
                        <tr v-for="(slug, term) in taxonomy.terms">
                            <td class="checkbox-col">
                                <input type="checkbox" v-model="term._checked" id="t-@{{ taxonomyName }}-@{{ slug }}" />
                                <label for="t-@{{ taxonomyName }}-@{{ slug }}"></label>
                            </td>
                            <td>@{{ term.slug }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-for="(setName, globalSet) in summary.globals" class="card flat-top flat-bottom">
                <h2>Global Set: @{{ setName }}</h2>
                <div class="form-group">
                    <label>{{ trans_choice('variables', 2) }}</label>
                    <p>
                        @{{ size(globalSet.variables) }} variables.
                        <a href="#" @click.prevent="showGlobal(setName)" v-if="!shouldShowGlobal(setName)">{{ t('show') }}</a>
                        <a href="#" @click.prevent="hideGlobal(setName)" v-else>{{ t('hide') }}</a>
                    </p>
                </div>
                <table v-if="shouldShowGlobal(setName)">
                    <thead>
                    <th></th>
                    <th>{{ trans_choice('variables', 1) }}</th>
                    </thead>
                    <tbody>
                    <tr v-for="(key, var) in globalSet.variables">
                        <td class="checkbox-col">
                            <input type="checkbox" v-model="var._checked" id="g-@{{ setName }}-@{{ key }}" />
                            <label for="g-@{{ setName }}-@{{ key }}"></label>
                        </td>
                        <td>@{{ key }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="card flat-top">
                <button class="btn btn-primary" @click.prevent="import">{{ t('import') }}</button>
            </div>
        </template>

        <template v-if="importing">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('importing') }}</h1>
                </div>
            </div>
            <div class="card flat-top">
                <div class="loading loading-basic">
                    <span class="icon icon-circular-graph animation-spin"></span> {{ t('please_wait') }}
                </div>
            </div>
        </template>

        <template v-if="imported">
            <div class="card flat-bottom">
                <div class="head">
                    <h1>{{ t('import_complete') }}</h1>
                </div>
            </div>
            <div class="card flat-top">
                <p>{{ t('import_has_completed') }}</p>
            </div>
        </template>

    </importer>

@endsection
