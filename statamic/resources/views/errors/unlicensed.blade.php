@extends('outside')

@section('content')
    <form method="POST" action="{{ route('license-key') }}">
        {!! csrf_field() !!}

        <p>
            <b>Developer Mode</b><br>
            Please enter your license key to access to the control panel on a public domain.
        </p>
        <p><a href="https://docs.statamic.com/knowledge-base/developer-mode">Learn more about developer mode.</a></p>

        <hr>

        <div class="form-group">
            <label>License Key</label>
            <input type="text" class="form-control" name="key" value="{{ \Statamic\API\Config::getLicenseKey() }}" autofocus>
        </div>
        <div>
            <button type="submit" class="btn btn-outside btn-block">{{ trans('cp.submit') }}</button>
        </div>
    </form>
@endsection
