<?php

namespace Statamic\Listeners;

use Statamic\API\Search;
use Statamic\Events\SearchSettingsUpdated;

class UpdateSearchIndex
{
    /**
     * Handle the event.
     *
     * @param  SearchSettingsUpdated  $event
     * @return void
     */
    public function handle(SearchSettingsUpdated $event)
    {
        $settings = $event->settings;

        list($index, $attributes) = [
            $settings['default_index'],
            $settings['algolia_searchable_attributes'],
        ];

        Search::in($index)->searchableAttributes($attributes);
    }
}
