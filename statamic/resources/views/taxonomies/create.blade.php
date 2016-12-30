@extends('layout')

@section('content')

    <form method="post" action="{{ route('taxonomy.store') }}">
        {!! csrf_field() !!}

        <div class="publish-form card">
            <div class="head">
                <h1>{{ translate('cp.create_taxonomy') }}</h1>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">{{ translate('cp.save') }}</button>
                </div>
            </div>

            <hr>

            <div class="publish-fields">

                <div class="form-group">
                    <label class="block">{{ t('title') }}</label>
                    <small class="help-block">{{ t('taxonomies_title_instructions') }}</small>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" autofocus="autofocus" />
                </div>

                <div class="form-group">
                    <label class="block">{{ t('slug') }}</label>
                    <small class="help-block">{{ t('taxonomies_slug_instructions') }}</small>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" />
                </div>

                <div class="form-group">
                    <label class="block">{{ t('fieldset') }}</label>
                    <small class="help-block">{{ t('taxonomies_fieldset_instructions') }}</small>
                    <fieldset-fieldtype name="fieldset" data="{{ old('fieldset') }}"></fieldset-fieldtype>
                </div>

                <div class="form-group">
                    <label class="block">{{ t('route') }}</label>
                    <small class="help-block">{{ t('taxonomies_route_instructions') }}</small>
                    <input type="text" name="route" class="form-control" value="{{ old('route') }}" />
                </div>
            </div>

        </div>
    </form>

@endsection
