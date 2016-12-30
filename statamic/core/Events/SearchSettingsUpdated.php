<?php

namespace Statamic\Events;

class SearchSettingsUpdated extends Event
{
    /**
     * The updated settings.
     *
     * @var array
     */
    public $settings;

    /**
     * Create a new event instance.
     *
     * @param  array  $
     * @return SearchSettingsUpdated
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }
}
