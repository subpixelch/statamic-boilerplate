<!doctype html>
<html lang="en">
	<head>
		@include('partials.head')
	</head>

	<body id="statamic" :class="{ 'nav-visible': navVisible }">

		@include('partials.nav-main')
		@include('partials.shortcuts')

		<div class="sneak-peek-viewport">
			<i class="icon icon-circular-graph animation-spin"></i>
			<div class="sneak-peek-iframe-wrap" id="sneak-peek"></div>
		</div>

		<div class="content @yield('content-class')">

			<div class="cp-head">
			    <typeahead v-ref:search src="/cp/search/perform" :limit="10"></typeahead>

		        <a href="{{ route('site') }}" target="_blank" class="view" v-cloak>
		    	    <span class="icon icon-eye"></span> {{ translate('cp.view_site') }}
		        </a>
			</div>

			@include('partials.alerts')

			<div class="page-wrapper" v-show="showPage">

				<div class="sneak-peek-header">
					<h2 class="pull-left">{{ trans('cp.sneak_peeking') }}</h2>
					<button class="pull-right btn btn-primary" @click="stopPreviewing">{{ trans('cp.done') }}</button>
				</div>

				@yield('content')

			</div>
		</div>

		<script>
			Statamic.translations = {!! $translations !!};
			Statamic.permissions = '{!! $permissions !!}';
            Statamic.version = '{!! STATAMIC_VERSION !!}';
		</script>
		@include('partials.scripts')
		@yield('scripts')
	</body>
</html>
