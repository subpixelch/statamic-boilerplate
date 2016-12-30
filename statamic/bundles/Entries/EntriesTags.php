<?php

namespace Statamic\Addons\Entries;

use Statamic\API\URL;
use Statamic\API\Str;
use Statamic\API\Page;
use Statamic\Addons\Collection\CollectionTags;

class EntriesTags extends CollectionTags
{
    /**
     * Catch-all for any child tags
     *
     * @param string $method
     * @param array  $args
     * @return string
     **/
    public function __call($method, $arguments)
    {
        return $this->index();
    }

    /**
     * Maps to `{{ entries }}`
     *
     * @return string
     */
    public function index()
    {
        $locale = $this->get('locale', site_locale());

        $from = $this->get(['from', 'folder', 'url'], URL::getCurrent());
        $from = Str::ensureLeft($from, '/');
        $from = URL::getDefaultUri($locale, $from);

        $this->collection = Page::whereUri($from)->in($locale)->entries()->localize($locale);

        $this->filter();

        if ($this->collection->isEmpty()) {
            return $this->parseNoResults();
        }

        return $this->output();
    }
}
