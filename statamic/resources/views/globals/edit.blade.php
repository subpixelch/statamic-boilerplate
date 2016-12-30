@extends('layout')

@section('content')

    <form method="post" action="{{ route('globals.update', $global->slug()) }}">
        {!! csrf_field() !!}

        <div class="publish-form card">
            <div class="head">

                <h1>
                    <i class="icon icon-cog"></i>
                    {{ $global->title() }}
                </h1>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">{{ translate('cp.save') }}</button>
                </div>
            </div>

            <hr>

            <div class="publish-fields">

                <div class="form-group">
                    <label class="block">{{ t('title') }}</label>
                    <small class="help-block">{{ t('globals_title_instructions') }}</small>
                    <input type="text" name="title" class="form-control" value="{{ $global->title() }}" />
                </div>

                <div class="form-group">
                    <label class="block">{{ t('fieldset') }}</label>
                    <fieldset-fieldtype name="fieldset" data="{{ $global->fieldset()->name() }}"></fieldset-fieldtype>
                </div>

            </div>
        </div>
    </form>

@endsection
