<?php

namespace Statamic\Addons\Search;

use Statamic\API\Str;
use Statamic\API\Search;
use Statamic\API\Content;
use Statamic\Addons\Collection\CollectionTags;

class SearchTags extends CollectionTags
{
    /**
     * The search query
     *
     * @var string
     */
    private $query;

    /**
     * Fields to search within
     *
     * @var null|array
     */
    private $fields;

    /**
     * Hash to identify the search collection in the Blink cache
     *
     * @var string
     */
    private $blink_hash;

    /**
     * The {{ search }} tag. An alias of search:results
     *
     * @return string
     */
    public function index()
    {
        return $this->results();
    }

    /**
     * The {{ search:results }} tag
     *
     * @return string
     */
    public function results()
    {
        $param = $this->get('param', 'q');

        $this->query = request()->query($param);

        $this->fields = $this->getList('fields');

        $this->blink_hash = md5(serialize($this->fields));

        $this->collection = ($this->blink->exists($this->blink_hash))
            ? $this->blink->get($this->blink_hash)
            : $this->buildSearchCollection();

        $this->filter();

        if ($this->collection->isEmpty()) {
            return $this->parseNoResults();
        }

        return $this->output();
    }

    protected function getSortOrder()
    {
        return $this->get('sort', 'search_score:desc');
    }

    /**
     * Perform a search and generate a collection
     *
     * @return \Statamic\Data\Content\ContentCollection
     */
    private function buildSearchCollection()
    {
        $results = Search::get($this->query, $this->fields);

        $collection = collect_content();

        foreach ($results as $key => $result) {
            // Only add to the results if the item exists. This happens when content
            // gets deleted but the search index hasn't yet been re-indexed.
            if ($content = Content::find($result['id'])) {
                $content->set('search_score', $result['_score']);
                $collection->push($content);
            }
        }


        $this->blink->put($this->blink_hash, $collection);

        return $collection;
    }
}
