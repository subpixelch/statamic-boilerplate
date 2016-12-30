@extends('outside')

@section('content')

    <login inline-template :show-email-login="!{{ bool_str($oauth) }}" v-cloak>

        @if ($oauth)
            <div class="login-oauth-providers">
                @foreach (Statamic\API\OAuth::providers() as $provider => $data)
                    <div class="provider">
                        <a href="{{ Statamic\API\OAuth::route($provider) }}?redirect={{ parse_url(route('cp'))['path'] }}" class="btn btn-block btn-outside">
                            {{ t('login_with', ['provider' => array_get($data, 'label', \Statamic\API\Str::title($provider))]) }}
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="login-or">or</div>

            <div class="login-with-email" v-if="! showEmailLogin">
                <a class="btn btn-block" @click.prevent="showEmailLogin = true">
                    {{ t('login_with', ['provider' => t(\Statamic\API\Config::get('users.login_type'))]) }}
                </a>
            </div>
        @endif

        <form method="POST" v-show="showEmailLogin" class="email-login">
            {!! csrf_field() !!}

            <div class="form-group">
                <label>
                @if (\Statamic\API\Config::get('users.login_type') === 'email')
                    {{ trans_choice('cp.emails', 1) }}
                @else
                    {{ trans('cp.username') }}
                @endif
                </label>
                <input type="text" class="form-control" name="username" value="{{ old('username') }}" autofocus>
            </div>

            <div class="form-group">
                <label>{{ trans_choice('cp.passwords', 1) }}</label>
                <input type="password" class="form-control" name="password" id="password">
            </div>

            <div class="form-group">
                <input type="checkbox" class="form-control" name="remember" id="checkbox-0">
                <label for="checkbox-0" class="normal">{{ trans('cp.remember_me') }}</label>
            </div>

            <div>
                <button type="submit" class="btn btn-outside btn-block">{{ trans('cp.login') }}</button>
            </div>
        </form>
    </login>

@endsection
