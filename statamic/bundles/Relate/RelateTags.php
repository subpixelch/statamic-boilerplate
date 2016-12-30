<?php

namespace Statamic\Addons\Relate;

use Statamic\API\Data;
use Statamic\API\Str;
use Statamic\API\User;
use Statamic\API\Helper;
use Statamic\API\Content;
use Statamic\API\Pattern;
use Statamic\Data\ContentCollection;
use Statamic\Addons\Collection\CollectionTags;

class RelateTags extends CollectionTags
{
    public function __call($method, $args)
    {
        $var = explode(':', $this->tag, 2)[1];

        $this->collection = collect_content();

        $values = Helper::ensureArray(array_get($this->context, $var, []));

        foreach ($values as $value) {
            $content = (Pattern::isUUID($value)) ? $this->getRelation($value) : Content::find($value);

            if (! $content) {
                continue;
            }

            $this->collection->push($content);
        }

        // Swap to the appropriate locale. By default it's the site locale.
        $this->collection = $this->collection->localize($this->get('locale', site_locale()));

        $this->filter();

        if ($this->collection->isEmpty()) {
            return $this->parseNoResults();
        }

        return $this->output();
    }

    private function getRelation($id)
    {
        return Data::find($id);
    }

    protected function getSortOrder()
    {
        return $this->get('sort');
    }
}
