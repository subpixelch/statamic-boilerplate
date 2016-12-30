@extends('layout')

@section('content')

    <form method="post" action="{{ route('user.role', $role->uuid()) }}">
        {!! csrf_field() !!}

        <div class="card sticky flat-bottom">
            <div class="head">
                <h1>{{ translate('cp.editing_role') }}</h1>

                <button type="submit" class="btn btn-primary">{{ translate('cp.save') }}</button>
            </div>

        </div>

        @include('roles.partials.form', [
            'role_title' => $role->title(),
            'role_slug' => $role->slug()
        ])

    </form>
@endsection
