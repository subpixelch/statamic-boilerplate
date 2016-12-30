@extends('layout')

@section('content')

    <form method="post" action="{{ route('collection.update', $collection->path()) }}">
        {!! csrf_field() !!}

        <div class="publish-form card">
            <div class="head">

                <h1>
                    <i class="icon icon-cog"></i>
                    {{ $collection->title() }}
                </h1>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">{{ translate('cp.save') }}</button>
                </div>
            </div>

            <hr>

            <div class="publish-fields">

                <div class="form-group">
                    <label class="block">{{ t('title') }}</label>
                    <small class="help-block">{{ t('collection_title_instructions') }}</small>
                    <input type="text" name="fields[title]" class="form-control" value="{{ $collection->title() }}" />
                </div>

                <div class="form-group">
                    <label class="block">{{ t('fieldset') }}</label>
                    <fieldset-fieldtype name="fields[fieldset]" data="{{ $collection->get('fieldset') }}"></fieldset-fieldtype>
                </div>

                <div class="form-group">
                    <label class="block">{{ t('route') }}</label>
                    <small class="help-block">{{ t('collection_route_instructions') }}</small>
                    <input type="text" name="fields[route]" class="form-control" value="{{ $collection->route() }}" />
                </div>

            </div>
        </div>
    </form>

@endsection
