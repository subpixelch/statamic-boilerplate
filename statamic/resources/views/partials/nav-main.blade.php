<nav class="nav-mobile">
	<a href="{{ route('cp') }}" class="logo">
		{!! svg('statamic-mark') !!}
	</a>

	<a href="" @click.prevent="toggleNav" class="toggle">
		<span class="icon icon-menu"></span>
	</a>
</nav>
<nav class="nav-main">
	<div class="head">
		<a href="{{ route('cp') }}" class="logo">
			{!! svg('statamic-mark') !!}
		</a>

		@if ($update_available)
			@can('updater:update')
				<a href="{{ route('updater') }}" class="update">{{ translate('cp.update_available')}}</a>
			@else
				<span class="update">{{ translate('cp.update_available')}}</span>
			@endcan
		@endif
	</div>

	<ul>

		<li class="nav-dashboard {{ request()->is('cp') ? 'visible active' : '' }}">
			<a href="{{ route('dashboard') }}" title="{{ translate('cp.nav_dashboard') }}">
				<span class="title">{{ translate('cp.nav_dashboard') }}
			</a>
		</li>

		@foreach ($nav->children() as $item)
			<li class="section">{{ $item->title() }}</li>
			@include('partials.nav-main-items', ['items' => $item->children()])
		@endforeach


	</ul>

	<div class="foot">
		<a href="{{ route('account') }}" class="account">
			<img src="{{ gravatar(\Statamic\API\User::getCurrent()->email(), 64) }}" alt="">
			{{ translate('cp.my_account') }}
		</a>
		<a href="{{ route('logout') }}" class="logout">{{ translate('cp.logout') }}</a>

		<div class="version" v-cloak>Statamic @{{ version }}</div>

		@if ($is_trial)
			<div class="trial">DEV MODE</div>
		@endif
	</div>
</nav>
