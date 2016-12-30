<?php

namespace Statamic\Addons\GetContent;

use Statamic\API\Helper;
use Statamic\API\Content;
use Statamic\Addons\Collection\CollectionTags;

class GetContentTags extends CollectionTags
{
    /**
     * The {{ get_content:[foo] }} tag
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        $from = array_get($this->context, $this->tag_method);

        return $this->index($from);
    }

    /**
     * The {{ get_content }} tag
     *
     * @param string|null $locations  Optional requested location(s) to retrieve content from.
     * @return string
     */
    public function index($locations = null)
    {
        if (! $locations) {
            $locations = $this->get(['from', 'id']);
        }

        $this->collection = collect_content(
            Helper::explodeOptions($locations)
        )->map(function ($from) {
            return $this->getContent($from);
        })->filter();

        $this->filter();

        return $this->output();
    }

    /**
     * Get content from somewhere
     *
     * @param string $from  Either an ID or URI
     * @return \Statamic\Contracts\Data\Content\Content
     */
    protected function getContent($from)
    {
        if (Content::uriExists($from)) {
            return Content::whereUri($from);
        }

        return Content::find($from);
    }

    protected function getSortOrder()
    {
        return $this->get('sort');
    }
}
