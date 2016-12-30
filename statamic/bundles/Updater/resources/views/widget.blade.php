<div class="card widget-updater">
    @if ($update_available)
        <h1>{{ t('updates') }}</h1>
        <p>{{ translate_choice('cp.updates_available', $updates, ['updates' => $updates]) }}!</p>
        @can('updater:update')
            <a href="{{ route('updater') }}" class="btn btn-small btn-primary">{{ t('upgrade_to_latest')}}</a>
        @endcan
    @else
        <h1>{{ t('on_latest') }}</h1>
    @endif
</div>
